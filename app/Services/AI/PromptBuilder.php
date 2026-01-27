<?php

namespace App\Services\AI;

use App\Enums\ActionType;
use App\Models\Business;
use App\Models\Client;
use App\Models\Conversation;
use App\Models\GptConfiguration;

class PromptBuilder
{
    /**
     * Build system messages array for GPT.
     * Returns array of system messages to prepend to conversation.
     */
    public function buildSystemMessages(
        Business $business,
        GptConfiguration $config,
        Conversation $conversation,
        ?Client $client = null
    ): array {
        $messages = [];

        // 1. Base system prompt with role and action instructions
        $messages[] = [
            'role' => 'system',
            'content' => $this->buildBasePrompt($business, $config),
        ];

        // 2. Conversation state (intent, stage, awaiting)
        $messages[] = [
            'role' => 'system',
            'content' => $this->buildStateMessage($conversation),
        ];

        // 3. Context (client info, recent actions)
        $messages[] = [
            'role' => 'system',
            'content' => $this->buildContextMessage($conversation, $client),
        ];

        // 4. Summary of conversation (if exists)
        if (!empty($conversation->summary)) {
            $messages[] = [
                'role' => 'system',
                'content' => "КРАТКОЕ РЕЗЮМЕ ДИАЛОГА:\n" . $conversation->summary,
            ];
        }

        // 5. State update instructions
        $messages[] = [
            'role' => 'system',
            'content' => $this->buildStateUpdateInstructions(),
        ];

        return $messages;
    }

    /**
     * Legacy method for backwards compatibility.
     */
    public function buildSystemPrompt(
        Business $business,
        GptConfiguration $config,
        ?Client $client = null
    ): string {
        $prompt = $config->system_prompt ?? $this->getDefaultSystemPrompt($business);
        $prompt .= "\n\n" . $this->buildActionInstructions($config);

        if ($client) {
            $prompt .= "\n\n" . $this->buildClientContext($client);
        }

        return $prompt;
    }

    private function buildBasePrompt(Business $business, GptConfiguration $config): string
    {
        $basePrompt = $config->system_prompt ?? $this->getDefaultSystemPrompt($business);
        $actionInstructions = $this->buildActionInstructions($config);

        return $basePrompt . "\n\n" . $actionInstructions;
    }

    private function buildStateMessage(Conversation $conversation): string
    {
        $state = $conversation->getState();

        $lines = ["СОСТОЯНИЕ ДИАЛОГА:"];
        $lines[] = "intent=" . ($state['intent'] ?? 'unknown');
        $lines[] = "stage=" . ($state['stage'] ?? 'initial');

        if (!empty($state['awaiting'])) {
            $lines[] = "awaiting=" . $state['awaiting'];
        }

        if (!empty($state['flags'])) {
            $lines[] = "flags=" . implode(',', $state['flags']);
        }

        return implode("\n", $lines);
    }

    private function buildContextMessage(Conversation $conversation, ?Client $client): string
    {
        $lines = ["КОНТЕКСТ:"];

        // Client info
        if ($client) {
            $lines[] = "Имя: " . ($client->name ?? 'неизвестно');
            $lines[] = "Телефон: " . ($client->phone ?? 'неизвестен');

            if ($client->first_contact_at) {
                $lines[] = "Клиент с: " . $client->first_contact_at->format('d.m.Y');
            }
        }

        // Context from conversation
        $context = $conversation->context ?? [];
        if (!empty($context)) {
            foreach ($context as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                $lines[] = ucfirst(str_replace('_', ' ', $key)) . ": " . $value;
            }
        }

        // Recent actions from this conversation
        $recentActions = $conversation->actions()
            ->latest()
            ->take(3)
            ->get();

        if ($recentActions->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "Последние действия:";
            foreach ($recentActions as $action) {
                $status = $action->status->value ?? $action->status;
                $type = $action->action->value ?? $action->action;
                $lines[] = "- {$type} ({$status}): " . json_encode($action->details, JSON_UNESCAPED_UNICODE);
            }
        }

        return implode("\n", $lines);
    }

    private function buildStateUpdateInstructions(): string
    {
        return <<<INSTRUCTIONS
ВАЖНО: В конце КАЖДОГО ответа добавь обновление состояния в формате:
[STATE]{"intent":"текущий_интент","stage":"текущий_этап","awaiting":"что_ожидаем","summary":"краткое_резюме_диалога"}[/STATE]

Возможные значения:
- intent: greeting, menu, reservation, order, inquiry, complaint, callback, other
- stage: initial, gathering_info, confirming, completed, transferred
- awaiting: null, name, phone, date, time, party_size, confirmation, menu_choice, address, details
- summary: краткое описание что произошло в диалоге (1-2 предложения)

Пример:
[STATE]{"intent":"reservation","stage":"gathering_info","awaiting":"date","summary":"Клиент хочет забронировать стол, уточняем дату"}[/STATE]
INSTRUCTIONS;
    }

    private function getDefaultSystemPrompt(Business $business): string
    {
        return <<<PROMPT
Ты - AI-ассистент для {$business->name}. Твоя задача - помогать клиентам с вопросами,
бронированием столов, заказами и обработкой жалоб профессионально.

Всегда будь вежливым, полезным и профессиональным. Если чего-то не знаешь, честно признай это
и предложи связаться с живым представителем.

Когда клиент хочет выполнить действие (забронировать стол или сделать заказ), собери всю необходимую
информацию перед подтверждением.

Отвечай на том языке, на котором пишет клиент.
PROMPT;
    }

    private function buildActionInstructions(GptConfiguration $config): string
    {
        $availableActions = $config->available_actions ?? array_column(ActionType::cases(), 'value');

        $instructions = "Когда клиент запрашивает действие, добавь его в ответ в формате:\n";
        $instructions .= "[ACTION:тип_действия]{\"ключ\": \"значение\"}[/ACTION]\n\n";
        $instructions .= "Доступные действия:\n";

        foreach ($availableActions as $action) {
            $actionType = ActionType::tryFrom($action);
            if ($actionType) {
                $instructions .= "- {$action}: " . $this->getActionDescription($actionType) . "\n";
            }
        }

        $instructions .= "\nПример бронирования:\n";
        $instructions .= "[ACTION:reservation]{\"date\": \"2024-01-15\", \"time\": \"19:00\", \"party_size\": 4, \"name\": \"Иван\", \"phone\": \"+77001234567\"}[/ACTION]\n";
        $instructions .= "\nВсегда включай тег действия в том же сообщении, что и естественный ответ клиенту.";

        $instructions .= "\n\n⚠️ ВАЖНО О ПОДТВЕРЖДЕНИЯХ:\n";
        $instructions .= "НЕ подтверждай бронирования и заказы напрямую! Когда создаёшь действие:\n";
        $instructions .= "- Скажи клиенту, что его ЗАЯВКА ПРИНЯТА и передана менеджеру для подтверждения\n";
        $instructions .= "- Скажи что менеджер свяжется/ответит для подтверждения\n";
        $instructions .= "- НЕ говори 'забронировано', 'подтверждено', 'готово' - только 'заявка принята', 'запрос отправлен'\n";
        $instructions .= "\nПример правильного ответа:\n";
        $instructions .= "\"Отлично! Ваша заявка на бронирование столика на 4 персоны на 15 января в 19:00 принята. ";
        $instructions .= "Менеджер проверит доступность и свяжется с вами для подтверждения.\"\n";

        return $instructions;
    }

    private function getActionDescription(ActionType $type): string
    {
        return match ($type) {
            ActionType::Reservation => 'Для бронирования. Включи: date, time, party_size, name, phone, special_requests (опционально)',
            ActionType::Order => 'Для заказов. Включи: items (массив), name, phone, delivery_address (если применимо)',
            ActionType::Inquiry => 'Для вопросов требующих ответа. Включи: topic, details',
            ActionType::Complaint => 'Для жалоб. Включи: issue, details, urgency (low/medium/high)',
            ActionType::Callback => 'Когда клиент просит перезвонить. Включи: name, phone, preferred_time (опционально), reason',
            ActionType::Other => 'Для других действий. Включи: type, details',
        };
    }

    private function buildClientContext(Client $client): string
    {
        $context = "Информация о клиенте:\n";
        $context .= "- Имя: " . ($client->name ?? 'Неизвестно') . "\n";

        if ($client->phone) {
            $context .= "- Телефон: {$client->phone}\n";
        }

        if ($client->first_contact_at) {
            $context .= "- Клиент с: " . $client->first_contact_at->format('F Y') . "\n";
        }

        if (!empty($client->metadata)) {
            $context .= "- Дополнительно: " . json_encode($client->metadata, JSON_UNESCAPED_UNICODE) . "\n";
        }

        return $context;
    }
}
