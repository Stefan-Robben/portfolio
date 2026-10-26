<?php

/*
 * Project:  Vonders (vonders-radicle)
 * Origineel: app/Services/Bullhorn/JobOrderMapper.php (uittreksel)
 * Gedeelde file: de mapper is grotendeels door een back-endcollega gebouwd. Hier staan alleen
 * de stukken van Stefan: de opleidingsniveau-taxonomie (VON-124) en de split van de specialismen per post type.
 */

declare(strict_types=1);

namespace App\Services\Bullhorn;

use App\Contracts\Bullhorn\JobOrderMapperContract;
use App\Contracts\Geocoding\GeocoderContract;
use App\DataTransferObjects\Bullhorn\JobOrderDto;
use App\DataTransferObjects\Geocoding\CoordinatesDto;
use App\Enums\PostType;
use App\Enums\Taxonomy;
use App\Exceptions\Geocoding\GeocodingException;
use Carbon\Carbon;

class JobOrderMapper implements JobOrderMapperContract
{
    // … (ingekort) constructor, post-type-bepaling, mapping naar post data en ACF-velden

    /**
     * Sync taxonomy terms for a job order.
     *
     * Creates terms if they don't exist (using Bullhorn ID as meta) and attaches them to the post.
     *
     * @param JobOrderDto $job
     * @param int $postId
     * @return void
     */
    public function syncTaxonomyTerms(JobOrderDto $job, int $postId): void
    {
        // Sync categories (vakgebied)
        $categoryTermIds = $this->syncTermsForTaxonomy(
            $job->categories,
            Taxonomy::BullhornCategory->value
        );
        wp_set_object_terms($postId, $categoryTermIds, Taxonomy::BullhornCategory->value);

        // Sync specialties (rol) - use post-type-specific taxonomy
        $specialtyTaxonomy = $job->isVacancy()
            ? Taxonomy::BullhornVacancySpecialty->value
            : Taxonomy::BullhornAssignmentSpecialty->value;

        $specialtyTermIds = $this->syncTermsForTaxonomy(
            $job->specialties,
            $specialtyTaxonomy
        );
        wp_set_object_terms($postId, $specialtyTermIds, $specialtyTaxonomy);

        // Sync education levels (opleidingsniveau)
        $educationTermIds = $this->syncEducationLevelTerms(
            $job->educationDegree,
            Taxonomy::BullhornEducationLevel->value
        );
        wp_set_object_terms($postId, $educationTermIds, Taxonomy::BullhornEducationLevel->value);
    }

    // … (ingekort) syncTermsForTaxonomy() en findTermByBullhornId(): zoeken/aanmaken van termen op Bullhorn-ID

    /**
     * Sync education level terms for a specific taxonomy.
     *
     * Education levels come as simple strings (e.g., "HBO", "WO") rather than
     * the {id, name} structure used by categories and specialties.
     *
     * @param array<int, string> $levels
     * @param string $taxonomy
     * @return array<int> Term IDs
     */
    protected function syncEducationLevelTerms(array $levels, string $taxonomy): array
    {
        $termIds = [];

        foreach ($levels as $level) {
            if (! is_string($level) || trim($level) === '') {
                continue;
            }

            $level = trim($level);

            // Try to find existing term by name
            $existingTerm = get_term_by('name', $level, $taxonomy);

            if ($existingTerm !== false) {
                $termIds[] = $existingTerm->term_id;

                continue;
            }

            // Try to find existing term by slug (in case name differs slightly)
            $slug = sanitize_title($level);
            $existingBySlug = get_term_by('slug', $slug, $taxonomy);

            if ($existingBySlug !== false) {
                $termIds[] = $existingBySlug->term_id;

                continue;
            }

            // Create new term
            $result = wp_insert_term($level, $taxonomy, [
                'slug' => $slug,
            ]);

            if (! is_wp_error($result)) {
                $termIds[] = $result['term_id'];
            } elseif (isset($result->error_data['term_exists'])) {
                // Term already exists (race condition or slug collision)
                $termIds[] = (int) $result->error_data['term_exists'];
            }
        }

        return $termIds;
    }

    // … (ingekort)
}
