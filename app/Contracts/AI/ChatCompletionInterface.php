<?php

namespace App\Contracts\AI;

use App\DataTransferObjects\ChatResponseDTO;
use App\Models\GptConfiguration;

interface ChatCompletionInterface
{
    public function complete(
        array $messages,
        GptConfiguration $config,
        ?string $systemPrompt = null
    ): ChatResponseDTO;

    public function testConnection(GptConfiguration $config): bool;
}
