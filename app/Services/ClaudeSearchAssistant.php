<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeSearchAssistant
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const ANTHROPIC_VERSION = '2023-06-01';

    /** Extract the best search term (serial number, asset tag, or keyword) from a natural-language query. */
    public function extractSearchTerm(string $naturalLanguageQuery): ?string
    {
        $data = $this->call([
            'model' => config('services.anthropic.model'),
            'max_tokens' => 200,
            'system' => 'You help search an ICT equipment register. Given a user question in plain English, '
                . 'extract the single best search term to look up equipment: prefer an exact serial number or '
                . 'asset tag if one is mentioned, otherwise the most distinctive item name, brand, or person '
                . 'mentioned. Reply with only the search term.',
            'messages' => [
                ['role' => 'user', 'content' => $naturalLanguageQuery],
            ],
            'output_config' => [
                'format' => [
                    'type' => 'json_schema',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'search_term' => ['type' => 'string'],
                        ],
                        'required' => ['search_term'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
        ]);

        $text = $data ? $this->firstText($data) : null;
        if (!$text) {
            return null;
        }

        $term = json_decode($text, true)['search_term'] ?? null;
        return $term ? trim($term) : null;
    }

    /**
     * Summarize an equipment's assignment history into 2-3 plain-English sentences.
     * $events is a list of ['date' => ..., 'event_type' => ..., 'description' => ..., 'recipient' => ?].
     */
    public function summarizeHistory(string $itemLabel, array $events): ?string
    {
        if (empty($events)) {
            return null;
        }

        $lines = collect($events)->map(fn ($e) => sprintf(
            '- %s: %s (%s)%s',
            $e['date'] ?? '—',
            $e['event_type'] ?? '',
            $e['description'] ?? '',
            !empty($e['recipient']) ? " — assigned to {$e['recipient']}" : ''
        ))->implode("\n");

        $data = $this->call([
            'model' => config('services.anthropic.model'),
            'max_tokens' => 300,
            'system' => 'You summarize the assignment history of ICT equipment for helpdesk staff. Write 2-3 '
                . 'concise sentences in plain English covering when it was received, who has held it over time, '
                . 'and its current status. No headings, no bullet points, no markdown.',
            'messages' => [
                ['role' => 'user', 'content' => "Equipment: {$itemLabel}\n\nHistory events (most recent first):\n{$lines}"],
            ],
        ]);

        return $data ? $this->firstText($data) : null;
    }

    /** POST to the Anthropic Messages API; returns the decoded JSON body, or null on any failure. */
    private function call(array $payload): ?array
    {
        $key = config('services.anthropic.key');
        if (!$key) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $key,
                'anthropic-version' => self::ANTHROPIC_VERSION,
                'content-type' => 'application/json',
            ])->timeout(15)->post(self::API_URL, $payload);

            if (!$response->successful()) {
                Log::warning('Anthropic API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $json = $response->json();

            if (($json['stop_reason'] ?? null) === 'refusal') {
                Log::warning('Anthropic API declined the request');
                return null;
            }

            return $json;
        } catch (\Throwable $e) {
            Log::warning('Anthropic API call threw an exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    private function firstText(array $data): ?string
    {
        foreach ($data['content'] ?? [] as $block) {
            if (($block['type'] ?? null) === 'text') {
                return trim($block['text']);
            }
        }
        return null;
    }
}
