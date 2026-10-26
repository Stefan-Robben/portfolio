<?php

/*
 * Project:  Intake (intake-webreact-io, Laravel)
 * Origineel: app/Services/ClaudeDocumentParser.php
 * Haalt antwoorden op intakevragen uit een klantdocument via de Anthropic Messages API en geeft gestructureerde JSON terug.
 */

namespace App\Services;

use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ClaudeDocumentParser
{
    private const MODEL = 'claude-haiku-4-5-20251001';

    private const API_URL = 'https://api.anthropic.com/v1/messages';

    /**
     * @param  Collection<int, Question>  $questions
     * @return array<string, string|array<mixed>> question key => answer value
     */
    public function parse(string $text, Collection $questions): array
    {
        // Trim to ~20 000 chars to keep the prompt within Haiku's fast response window
        $text = mb_substr($text, 0, 20_000);

        $questionList = $questions->map(fn (Question $q) => [
            'key' => $q->key,
            'label' => $q->label,
            'type' => $q->type,
        ])->values()->all();

        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
        ])->timeout(90)->post(self::API_URL, [
            'model' => self::MODEL,
            'max_tokens' => 4096,
            'system' => $this->systemPrompt(),
            'messages' => [[
                'role' => 'user',
                'content' => $this->userPrompt($text, $questionList),
            ]],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Claude API-aanroep mislukt: '.$response->body()
            );
        }

        $content = $response->json('content.0.text', '');

        // Extract the JSON block from the response
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $decoded = json_decode($matches[0], true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        throw new RuntimeException(
            'Claude gaf geen geldig JSON-antwoord terug.'
        );
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Je bent een expert in het extraheren van gestructureerde informatie uit klantdocumenten voor een webbureau.
Je krijgt een document (bureaubriefing, projectomschrijving, of klantintake) en een lijst vragen.
Extraheer voor elke vraag het meest relevante antwoord uit het document.
Geef ALLEEN een geldig JSON-object terug zonder extra uitleg of markdown code blocks.
Laat een sleutel weg als het antwoord echt niet in het document staat.
Schrijf antwoorden in het Nederlands, beknopt maar volledig.
PROMPT;
    }

    /**
     * @param  array<int, array{key: string, label: string, type: string}>  $questions
     */
    private function userPrompt(string $text, array $questions): string
    {
        $questionsJson = json_encode($questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Hier is het document:

<document>
{$text}
</document>

Hier zijn de intake-vragen. Extraheer het antwoord op elke vraag uit het document.

<questions>
{$questionsJson}
</questions>

Geef een JSON-object terug waarbij de "key" van elke vraag de sleutel is en het antwoord de waarde:
{"vraag_key": "antwoord", "andere_key": "ander antwoord"}
PROMPT;
    }
}
