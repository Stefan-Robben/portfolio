<?php

/*
 * Project:  Vonders (vonders-radicle)
 * Origineel: app/Console/Commands/BullhornSyncRolesCommand.php
 * Artisan-command die Bullhorn-specialismen (rollen) per post type naar aparte taxonomieën migreert, met --dry-run.
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PostType;
use App\Enums\Taxonomy;
use App\Services\Bullhorn\JobOrderMapper;
use Illuminate\Console\Command;

/**
 * Command to migrate specialty (rol) terms from the old shared taxonomy
 * to the new per-post-type taxonomies.
 *
 * Reads the stored specialty JSON from each job post's ACF field and
 * assigns terms to the correct taxonomy (bullhorn_vacancy_specialty
 * or bullhorn_assignment_specialty).
 */
class BullhornSyncRolesCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'bullhorn:sync-roles
                            {--dry-run : Show what would be synced without making changes}';

    /**
     * @var string
     */
    protected $description = 'Sync specialty (rol) terms to the per-post-type taxonomies for all existing jobs';

    public function __construct(
        private readonly JobOrderMapper $mapper,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 Dry run mode — no changes will be made.');
        }

        $this->info('Syncing roles for vacancies...');
        $vacancyCount = $this->syncPostType(
            PostType::BullhornVacancy,
            Taxonomy::BullhornVacancySpecialty,
            $dryRun
        );

        $this->info('Syncing roles for assignments...');
        $assignmentCount = $this->syncPostType(
            PostType::BullhornAssignment,
            Taxonomy::BullhornAssignmentSpecialty,
            $dryRun
        );

        $this->newLine();
        $this->info("✅ Done. Synced roles for {$vacancyCount} vacancies and {$assignmentCount} assignments.");

        if (! $dryRun) {
            // Clear FacetWP's term cache to ensure correct indexing
            if (function_exists('FWP') && isset(FWP()->helper->term_cache)) {
                FWP()->helper->term_cache = [];
            }

            $this->info('💡 Run `wp rewrite flush` to update permalink rules.');
        }

        return self::SUCCESS;
    }

    private function syncPostType(PostType $postType, Taxonomy $taxonomy, bool $dryRun): int
    {
        $postIds = get_posts([
            'post_type'      => $postType->value,
            'post_status'    => ['publish', 'draft'],
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        $count = 0;
        $bar = $this->output->createProgressBar(count($postIds));
        $bar->start();

        foreach ($postIds as $postId) {
            $specialtyJson = get_field('bullhorn_specialty', $postId);

            if (empty($specialtyJson)) {
                $bar->advance();
                continue;
            }

            $specialties = is_string($specialtyJson)
                ? json_decode($specialtyJson, true)
                : $specialtyJson;

            if (empty($specialties) || ! is_array($specialties)) {
                $bar->advance();
                continue;
            }

            if ($dryRun) {
                $names = collect($specialties)->pluck('name')->implode(', ');
                $this->line(" Post #{$postId}: {$names} → {$taxonomy->value}");
            } else {
                $termIds = $this->syncTermsForTaxonomy($specialties, $taxonomy->value);
                wp_set_object_terms($postId, $termIds, $taxonomy->value);
            }

            $count++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return $count;
    }

    /**
     * Create/find terms for the given taxonomy using Bullhorn ID meta.
     *
     * @param array<int, array{id: int, name: string}> $items
     * @param string $taxonomy
     * @return array<int> Term IDs
     */
    private function syncTermsForTaxonomy(array $items, string $taxonomy): array
    {
        $termIds = [];

        foreach ($items as $item) {
            $bullhornId = $item['id'] ?? null;
            $name = $item['name'] ?? null;

            if ($bullhornId === null || $name === null) {
                continue;
            }

            // Find existing term by Bullhorn ID
            $existingTermId = $this->findTermByBullhornId((int) $bullhornId, $taxonomy);

            if ($existingTermId !== null) {
                wp_update_term($existingTermId, $taxonomy, ['name' => $name]);
                $termIds[] = $existingTermId;
                continue;
            }

            // Find by name
            $termByName = get_term_by('name', $name, $taxonomy);

            if ($termByName !== false) {
                update_term_meta($termByName->term_id, 'bullhorn_id', $bullhornId);
                $termIds[] = $termByName->term_id;
                continue;
            }

            // Create new term
            $result = wp_insert_term($name, $taxonomy, [
                'slug' => sanitize_title($name) . '-' . $bullhornId,
            ]);

            if (! is_wp_error($result)) {
                $newTermId = $result['term_id'];
                update_term_meta($newTermId, 'bullhorn_id', $bullhornId);
                $termIds[] = $newTermId;

                // Clear FacetWP's term hierarchy cache
                if (function_exists('FWP') && isset(FWP()->helper->term_cache[$taxonomy])) {
                    unset(FWP()->helper->term_cache[$taxonomy]);
                }
            }
        }

        return $termIds;
    }

    private function findTermByBullhornId(int $bullhornId, string $taxonomy): ?int
    {
        $terms = get_terms([
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'meta_key'   => 'bullhorn_id',
            'meta_value' => $bullhornId,
            'number'     => 1,
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            return null;
        }

        return $terms[0]->term_id;
    }
}
