<?php

/*
 * Project:  Intake (intake-webreact-io, Laravel)
 * Origineel: tests/Feature/GenerateDesignSpecBrandGuideTest.php
 * Feature-test (PHPUnit) met Http::fake en Storage::fake rond een andere Claude-aanroep: de design spec met brand guide.
 */

namespace Tests\Feature;

use App\Actions\GenerateDesignSpec;
use App\Models\Briefing;
use App\Models\Intake;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class GenerateDesignSpecBrandGuideTest extends TestCase
{
    use RefreshDatabase;

    private const PDF_BYTES = '%PDF-1.4 fake brand guide';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'filesystems.default' => 's3',
            'services.anthropic.key' => 'test-key',
        ]);

        Storage::fake('s3');

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Gegenereerde design spec']],
            ]),
        ]);
    }

    private function createBriefing(?string $brandGuidePath = null): Briefing
    {
        $intake = Intake::create([
            'uuid' => (string) Str::uuid(),
            'client_name' => 'Test Bedrijf',
            'client_email' => 'test@example.com',
            'status' => 'submitted',
            'brand_guide_path' => $brandGuidePath,
        ]);

        return Briefing::create([
            'intake_id' => $intake->id,
            'content_markdown' => '# Test briefing',
            'generated_by' => 'tester@example.com',
        ]);
    }

    /**
     * Guards the object storage migration: the brand guide is read off the
     * configured disk, so a PDF that only exists in object storage still
     * reaches the API.
     */
    public function test_brand_guide_is_read_from_the_configured_disk(): void
    {
        $briefing = $this->createBriefing('brand-guides/abc/brand_guide.pdf');

        Storage::disk('s3')->put('brand-guides/abc/brand_guide.pdf', self::PDF_BYTES);

        (new GenerateDesignSpec)->execute($briefing);

        Http::assertSent(function ($request) {
            $document = $request['messages'][0]['content'][0] ?? [];

            return ($document['type'] ?? null) === 'document'
                && base64_decode($document['source']['data']) === self::PDF_BYTES;
        });
    }

    public function test_brand_guide_is_skipped_when_the_file_is_missing_from_the_disk(): void
    {
        // Path recorded on the intake, but nothing was ever written to the disk.
        $briefing = $this->createBriefing('brand-guides/ghost/brand_guide.pdf');

        (new GenerateDesignSpec)->execute($briefing);

        Http::assertNotSent(fn ($request) => ($request['messages'][0]['content'][0]['type'] ?? null) === 'document');

        $briefing->refresh();
        $this->assertNotNull($briefing->design_prompt);
    }

    public function test_design_spec_is_generated_without_a_brand_guide(): void
    {
        $briefing = $this->createBriefing();

        (new GenerateDesignSpec)->execute($briefing);

        $briefing->refresh();

        $this->assertStringContainsString('Gegenereerde design spec', $briefing->design_prompt);
        $this->assertEquals('briefed', $briefing->intake->status);
    }
}
