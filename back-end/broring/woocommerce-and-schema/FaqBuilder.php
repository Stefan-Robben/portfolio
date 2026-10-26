<?php

/*
 * Project:  Broring (broring-radicle)
 * Origineel: app/Services/Schema/Builders/FaqBuilder.php
 * Builder voor een deel van het schema.org FAQPage-object.
 */

namespace App\Services\Schema\Builders;

use App\Contracts\Builders\SchemaBuilderInterface;

class FaqBuilder implements SchemaBuilderInterface
{
    protected array $data = [];

    public function setQuestion(string $question): self
    {
        $this->data['@type'] = 'Question';
        $this->data['name'] = $question;

        return $this;
    }

    public function setAnswer(AnswerBuilder $answer): FaqBuilder
    {
        $this->data['acceptedAnswer'] = $answer->build();
        return $this;
    }

    public function build(): array
    {
        return $this->data;
    }
}
