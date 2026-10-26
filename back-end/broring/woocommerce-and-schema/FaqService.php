<?php

/*
 * Project:  Broring (broring-radicle)
 * Origineel: app/Services/Schema/FaqService.php
 * Zet accordion-items om naar schema.org Question/Answer-objecten.
 */

namespace App\Services\Schema;

use App\Helpers\ContentHelper;
use App\Services\Schema\Builders\AnswerBuilder;
use App\Services\Schema\Builders\FaqBuilder;

class FaqService
{
    public function __construct(
        private readonly FaqBuilder $faqBuilder,
        private readonly AnswerBuilder $answerBuilder
    ) {
    }

    public function buildFaqEntries(array $questions): array
    {
        return collect($questions)
            ->map(fn ($entry) => $this->buildSingleFaq($entry))
            ->all();
    }

    protected function buildSingleFaq($entry): array
    {
        $title = data_get($entry, 'title');
        $answerText = ContentHelper::sanitizeContent(data_get($entry, 'answer'));
        ;

        return $this->faqBuilder
            ->setQuestion($title)
            ->setAnswer($this->answerBuilder->setText($answerText))
            ->build();
    }
}
