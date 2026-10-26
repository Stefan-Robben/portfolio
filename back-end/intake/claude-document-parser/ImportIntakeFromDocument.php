<?php

/*
 * Project:  Intake (intake-webreact-io, Laravel)
 * Origineel: app/Actions/ImportIntakeFromDocument.php
 * Action die tekst extraheert, de parser aanroept en een Intake met antwoorden opslaat.
 */

namespace App\Actions;

use App\Data\ImportIntakeData;
use App\Models\Intake;
use App\Repositories\Contracts\QuestionRepositoryInterface;
use App\Services\ClaudeDocumentParser;
use App\Services\DocumentTextExtractor;

class ImportIntakeFromDocument
{
    public function __construct(
        private DocumentTextExtractor $extractor,
        private ClaudeDocumentParser $parser,
        private QuestionRepositoryInterface $questionRepository,
    ) {}

    public function execute(ImportIntakeData $data): Intake
    {
        $text = $this->extractor->extract($data->file);

        $questions = $this->questionRepository->allActiveSections()
            ->flatMap(fn ($section) => $section->questions);

        $parsedAnswers = $this->parser->parse($text, $questions);

        $intake = Intake::create([
            'client_name' => $data->clientName,
            'client_email' => $data->clientEmail,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        foreach ($parsedAnswers as $questionKey => $value) {
            $question = $questions->firstWhere('key', $questionKey);

            if ($question) {
                $intake->answers()->create([
                    'question_id' => $question->id,
                    'value' => is_array($value) ? json_encode($value) : $value,
                ]);
            }
        }

        return $intake;
    }
}
