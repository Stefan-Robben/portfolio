<?php

/*
 * Project:  Broring (broring-radicle)
 * Origineel: app/Services/Schema/Builders/AnswerBuilder.php
 * Builder voor een deel van het schema.org FAQPage-object.
 */

namespace App\Services\Schema\Builders;

use App\Contracts\Builders\SchemaBuilderInterface;

class AnswerBuilder implements SchemaBuilderInterface
{
    protected array $data = [
        '@type' => 'Answer',
    ];

    public function setText(string $text): self
    {
        $this->data['text'] = $text;
        return $this;
    }

    public function build(): array
    {
        return $this->data;
    }
}
