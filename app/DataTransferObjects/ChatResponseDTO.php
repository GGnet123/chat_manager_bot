<?php

namespace App\DataTransferObjects;

readonly class ChatResponseDTO
{
    public function __construct(
        public string $content,
        public array $actions = [],
        public ?string $cleanContent = null,
        public ?array $state = null,
        public ?int $promptTokens = null,
        public ?int $completionTokens = null,
        public ?string $model = null,
    ) {}

    public function hasActions(): bool
    {
        return count($this->actions) > 0;
    }

    public function hasState(): bool
    {
        return $this->state !== null;
    }

    public function getDisplayContent(): string
    {
        return $this->cleanContent ?? $this->content;
    }

    public function getTotalTokens(): int
    {
        return ($this->promptTokens ?? 0) + ($this->completionTokens ?? 0);
    }
}
