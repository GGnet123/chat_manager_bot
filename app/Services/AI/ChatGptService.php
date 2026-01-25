<?php

namespace App\Services\AI;

use App\Contracts\AI\ChatCompletionInterface;
use App\DataTransferObjects\ChatResponseDTO;
use App\Models\GptConfiguration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatGptService implements ChatCompletionInterface
{
    private const API_URL = 'https://api.openai.com/v1/chat/completions';

    public function __construct(
        private ResponseParser $responseParser,
    ) {}

    public function complete(
        array $messages,
        GptConfiguration $config,
        ?string $systemPrompt = null
    ): ChatResponseDTO {
        $preparedMessages = $this->prepareMessages($messages, $systemPrompt ?? $config->system_prompt);

        try {
            $response = Http::withToken(config('openai.api_key'))
                ->timeout(60)
                ->post(self::API_URL, [
                    'model' => $config->model,
                    'messages' => $preparedMessages,
                    'max_tokens' => $config->max_tokens,
                    'temperature' => $config->temperature,
                ]);

            Log::info("Message TO ChatGPT", ['messages' => $preparedMessages]);

            if (!$response->successful()) {
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return new ChatResponseDTO(
                    content: 'I apologize, but I\'m having trouble processing your request right now. Please try again later.',
                );
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';
            $usage = $data['usage'] ?? [];

            // Parse the response to extract any actions
            $parsed = $this->responseParser->parse($content);

            return new ChatResponseDTO(
                content: $content,
                actions: $parsed['actions'],
                cleanContent: $parsed['cleanContent'],
                promptTokens: $usage['prompt_tokens'] ?? null,
                completionTokens: $usage['completion_tokens'] ?? null,
                model: $data['model'] ?? $config->model,
            );
        } catch (\Throwable $e) {
            Log::error('ChatGPT service error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new ChatResponseDTO(
                content: 'I apologize, but I\'m experiencing technical difficulties. Please try again later.',
            );
        }
    }

    public function testConnection(GptConfiguration $config): bool
    {
        try {
            $response = Http::withToken(config('openai.api_key'))
                ->timeout(10)
                ->post(self::API_URL, [
                    'model' => $config->model,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Hello'],
                    ],
                    'max_tokens' => 10,
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('ChatGPT connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function prepareMessages(array $messages, ?string $systemPrompt): array
    {
        $prepared = [];

        if ($systemPrompt) {
            $prepared[] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
        }

        foreach ($messages as $message) {
            $prepared[] = [
                'role' => $message['role'],
                'content' => $message['content'],
            ];
        }

        return $prepared;
    }
}
