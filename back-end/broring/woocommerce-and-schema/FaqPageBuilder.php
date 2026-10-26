<?php

/*
 * Project:  Broring (broring-radicle)
 * Origineel: app/Services/Schema/Builders/FaqPageBuilder.php
 * Builder voor een deel van het schema.org FAQPage-object.
 */

namespace App\Services\Schema\Builders;

use App\Enums\Schema;

class FaqPageBuilder
{
    /**
     * https://schema.org/FAQPage
     */

    public static function fromEntries(array $faqEntries): array
    {
        return [
            '@type' => Schema::FAQPage->value,
            'mainEntity' => $faqEntries,
        ];
    }
}
