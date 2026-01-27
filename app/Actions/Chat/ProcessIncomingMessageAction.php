<?php

namespace App\Actions\Chat;

use App\DataTransferObjects\IncomingMessageDTO;
use App\DataTransferObjects\OutgoingMessageDTO;
use App\Enums\Platform;
use App\Events\MessageReceived;
use App\Models\Business;
use App\Models\Client;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Services\ActionHandlers\ActionHandlerFactory;
use App\Services\AI\ChatGptService;
use App\Services\AI\PromptBuilder;
use Illuminate\Support\Facades\Log;

class ProcessIncomingMessageAction
{
    public function __construct(
        private ChatGptService $chatGptService,
        private PromptBuilder $promptBuilder,
        private ActionHandlerFactory $actionHandlerFactory,
    ) {}

    public function execute(IncomingMessageDTO $message, Business $business): ?OutgoingMessageDTO
    {
        // Find or create client
        $client = $this->findOrCreateClient($message, $business);

        // Find or create conversation
        $conversation = $this->findOrCreateConversation($client, $message->platform);

        // Store incoming message
        $this->storeMessage($conversation, 'user', $message->content, $message->messageId);

        // Dispatch message received event
        event(new MessageReceived($conversation, $message));

        // Get GPT configuration
        $gptConfig = $business->activeGptConfiguration();

        if (!$gptConfig) {
            Log::warning('No active GPT configuration for business', ['business_id' => $business->id]);
            return $this->createResponse(
                $message,
                $client,
                'Спасибо за ваше сообщение. Наша команда свяжется с вами в ближайшее время.'
            );
        }

        // Build system messages (multiple system messages for state, context, etc.)
        $systemMessages = $this->promptBuilder->buildSystemMessages(
            $business,
            $gptConfig,
            $conversation,
            $client
        );

        // Get only last user message (state and summary provide context now)
        $messages = $conversation->getMessagesForGpt(5);

        // Get GPT response
        $response = $this->chatGptService->complete($messages, $gptConfig, $systemMessages);

        // Process any actions in the response
        if ($response->hasActions()) {
            foreach ($response->actions as $action) {
                $handler = $this->actionHandlerFactory->getHandler($action->type->value);

                if ($handler->validate($action)) {
                    $handler->handle($action, $conversation);
                } else {
                    Log::warning('Invalid action data', [
                        'action_type' => $action->type->value,
                        'details' => $action->details,
                    ]);
                }
            }
        }

        // Update conversation state if GPT provided it
        if ($response->hasState()) {
            $stateUpdate = $response->state;

            // Extract summary separately
            $summary = $stateUpdate['summary'] ?? null;
            unset($stateUpdate['summary']);

            // Update state fields
            if (!empty($stateUpdate)) {
                $conversation->updateState($stateUpdate);
            }

            // Update summary
            if ($summary) {
                $conversation->updateSummary($summary);
            }
        }

        // Store assistant response
        $displayContent = $response->getDisplayContent();
        $this->storeMessage($conversation, 'assistant', $displayContent);

        // Update conversation last message time
        $conversation->update(['last_message_at' => now()]);

        // Update client last contact
        $client->update(['last_contact_at' => now()]);

        return $this->createResponse($message, $client, $displayContent);
    }

    private function findOrCreateClient(IncomingMessageDTO $message, Business $business): Client
    {
        $query = Client::where('business_id', $business->id);

        if ($message->platform === Platform::WhatsApp) {
            $query->where('phone', $message->senderId);
        } else {
            $query->where('telegram_id', $message->senderId);
        }

        $client = $query->first();

        if (!$client) {
            $client = Client::create([
                'business_id' => $business->id,
                'phone' => $message->platform === Platform::WhatsApp ? $message->senderId : null,
                'telegram_id' => $message->platform === Platform::Telegram ? $message->senderId : null,
                'name' => $message->senderName,
                'platform' => $message->platform,
                'first_contact_at' => now(),
                'last_contact_at' => now(),
            ]);
        } elseif ($message->senderName && !$client->name) {
            $client->update(['name' => $message->senderName]);
        }

        return $client;
    }

    private function findOrCreateConversation(Client $client, Platform $platform): Conversation
    {
        $conversation = $client->activeConversation();

        if (!$conversation) {
            $conversation = Conversation::create([
                'business_id' => $client->business_id,
                'client_id' => $client->id,
                'platform' => $platform,
                'status' => 'active',
                'state' => Conversation::defaultState(),
                'last_message_at' => now(),
            ]);
        }

        return $conversation;
    }

    private function storeMessage(Conversation $conversation, string $role, string $content, ?string $messageId = null): ConversationMessage
    {
        return ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'role' => $role,
            'content' => $content,
            'message_id' => $messageId,
        ]);
    }

    private function createResponse(IncomingMessageDTO $incoming, Client $client, string $content): OutgoingMessageDTO
    {
        $recipientId = $incoming->platform === Platform::WhatsApp
            ? $client->phone
            : ($incoming->metadata['chat_id'] ?? $client->telegram_id);

        return new OutgoingMessageDTO(
            platform: $incoming->platform,
            recipientId: $recipientId,
            content: $content,
            replyToMessageId: $incoming->messageId,
        );
    }
}
