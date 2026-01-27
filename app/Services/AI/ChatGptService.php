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

    /**
     * Complete a chat with GPT.
     *
     * @param array $messages Conversation messages
     * @param GptConfiguration $config GPT configuration
     * @param string|array|null $systemPrompt Single system prompt string OR array of system messages
     */
    public function complete(
        array $messages,
        GptConfiguration $config,
        string|array|null $systemPrompt = null
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

            Log::debug("ChatGPT Request", ['messages' => $preparedMessages]);

            if (!$response->successful()) {
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return new ChatResponseDTO(
                    content: 'Извините, возникла проблема при обработке вашего запроса. Пожалуйста, попробуйте позже.',
                );
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';
            $usage = $data['usage'] ?? [];

            // Parse the response to extract actions and state
            $parsed = $this->responseParser->parse($content);

            Log::debug("ChatGPT Response", [
                'content' => $content,
                'actions' => count($parsed['actions']),
                'state' => $parsed['state'] ?? null,
            ]);

            return new ChatResponseDTO(
                content: $content,
                actions: $parsed['actions'],
                cleanContent: $parsed['cleanContent'],
                state: $parsed['state'] ?? null,
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
                content: 'Извините, возникли технические неполадки. Пожалуйста, попробуйте позже.',
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

    /**
     * Prepare messages for the API call.
     *
     * @param array $messages Conversation messages
     * @param string|array|null $systemPrompt Single string OR array of system messages
     */
    private function prepareMessages(array $messages, string|array|null $systemPrompt): array
    {
        $prepared = [];

        // Handle system prompt(s)
        if ($systemPrompt) {
            if (is_array($systemPrompt)) {
                // Multiple system messages (new format)
                foreach ($systemPrompt as $sysMessage) {
                    if (is_array($sysMessage) && isset($sysMessage['content'])) {
                        $prepared[] = [
                            'role' => $sysMessage['role'] ?? 'system',
                            'content' => $sysMessage['content'],
                        ];
                    } elseif (is_string($sysMessage)) {
                        $prepared[] = [
                            'role' => 'system',
                            'content' => $sysMessage,
                        ];
                    }
                }
            } else {
                // Single system prompt string (legacy format)
                $prepared[] = [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ];
            }
        }

        // Add conversation messages
        foreach ($messages as $message) {
            $prepared[] = [
                'role' => $message['role'],
                'content' => $message['content'],
            ];
        }

        return $prepared;
    }
}
