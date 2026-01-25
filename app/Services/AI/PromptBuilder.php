<?php

namespace App\Services\AI;

use App\Enums\ActionType;
use App\Models\Business;
use App\Models\Client;
use App\Models\GptConfiguration;

class PromptBuilder
{
    public function buildSystemPrompt(
        Business $business,
        GptConfiguration $config,
        ?Client $client = null
    ): string {
        $prompt = $config->system_prompt ?? $this->getDefaultSystemPrompt($business);

        // Append action instructions
        $prompt .= "\n\n" . $this->buildActionInstructions($config);

        // Add client context if available
        if ($client) {
            $prompt .= "\n\n" . $this->buildClientContext($client);
        }

        return $prompt;
    }

    private function getDefaultSystemPrompt(Business $business): string
    {
        return <<<PROMPT
You are a helpful AI assistant for {$business->name}. Your role is to assist customers with their inquiries,
help them make reservations, place orders, and handle any complaints professionally.

Always be polite, helpful, and professional. If you don't know something, admit it and offer to connect the customer
with a human representative.

When a customer wants to take an action (like making a reservation or placing an order), collect all necessary
information before confirming.
PROMPT;
    }

    private function buildActionInstructions(GptConfiguration $config): string
    {
        $availableActions = $config->available_actions ?? array_column(ActionType::cases(), 'value');

        $instructions = "When a customer requests an action, embed it in your response using this format:\n";
        $instructions .= "[ACTION:action_type]{\"key\": \"value\"}[/ACTION]\n\n";
        $instructions .= "Available actions:\n";

        foreach ($availableActions as $action) {
            $actionType = ActionType::tryFrom($action);
            if ($actionType) {
                $instructions .= "- {$action}: " . $this->getActionDescription($actionType) . "\n";
            }
        }

        $instructions .= "\nExample for a reservation:\n";
        $instructions .= "[ACTION:reservation]{\"date\": \"2024-01-15\", \"time\": \"19:00\", \"party_size\": 4, \"name\": \"John\", \"phone\": \"+1234567890\"}[/ACTION]\n";
        $instructions .= "\nAlways include the action tag in the same message as your natural response to the customer.";

        return $instructions;
    }

    private function getActionDescription(ActionType $type): string
    {
        return match ($type) {
            ActionType::Reservation => 'For table bookings. Include: date, time, party_size, name, phone, special_requests (optional)',
            ActionType::Order => 'For product/service orders. Include: items (array), name, phone, delivery_address (if applicable)',
            ActionType::Inquiry => 'For general questions requiring follow-up. Include: topic, details',
            ActionType::Complaint => 'For customer complaints. Include: issue, details, urgency (low/medium/high)',
            ActionType::Callback => 'When customer requests a callback. Include: name, phone, preferred_time (optional), reason',
            ActionType::Other => 'For any other actions. Include: type, details',
        };
    }

    private function buildClientContext(Client $client): string
    {
        $context = "Current customer information:\n";
        $context .= "- Name: " . ($client->name ?? 'Unknown') . "\n";

        if ($client->phone) {
            $context .= "- Phone: {$client->phone}\n";
        }

        if ($client->first_contact_at) {
            $context .= "- Customer since: " . $client->first_contact_at->format('F Y') . "\n";
        }

        if (!empty($client->metadata)) {
            $context .= "- Additional info: " . json_encode($client->metadata) . "\n";
        }

        return $context;
    }
}
