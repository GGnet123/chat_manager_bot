<?php

namespace App\Services\AI;

use App\DataTransferObjects\ParsedActionDTO;
use Illuminate\Support\Facades\Log;

class ResponseParser
{
    private const ACTION_PATTERN = '/\[ACTION:(\w+)\](.*?)\[\/ACTION\]/s';

    public function parse(string $content): array
    {
        $actions = [];
        $cleanContent = $content;

        // Find all action tags
        if (preg_match_all(self::ACTION_PATTERN, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $actionType = $match[1];
                $actionData = $match[2];

                try {
                    $details = json_decode(trim($actionData), true);

                    if (json_last_error() === JSON_ERROR_NONE && is_array($details)) {
                        $actions[] = ParsedActionDTO::fromGptResponse($actionType, $details);
                    } else {
                        Log::warning('Invalid action JSON in GPT response', [
                            'action_type' => $actionType,
                            'data' => $actionData,
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::error('Failed to parse action from GPT response', [
                        'error' => $e->getMessage(),
                        'action_type' => $actionType,
                    ]);
                }

                // Remove the action tag from the clean content
                $cleanContent = str_replace($match[0], '', $cleanContent);
            }
        }

        // Clean up any extra whitespace
        $cleanContent = preg_replace('/\s+/', ' ', trim($cleanContent));

        return [
            'actions' => $actions,
            'cleanContent' => $cleanContent,
        ];
    }

    public function hasActions(string $content): bool
    {
        return preg_match(self::ACTION_PATTERN, $content) === 1;
    }

    public function extractActionTypes(string $content): array
    {
        $types = [];

        if (preg_match_all(self::ACTION_PATTERN, $content, $matches)) {
            $types = array_unique($matches[1]);
        }

        return $types;
    }
}
