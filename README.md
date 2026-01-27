# Chat Manager Bot - AI Assistant Platform

## Project Overview

ServiceBot is a multi-tenant WhatsApp/Telegram AI assistant platform that acts as middleware between clients and ChatGPT. It replaces business managers for customer interactions, supporting table reservations, orders, inquiries, and other configurable actions.

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: React admin panel with Refine.dev (in `/admin-panel`)
- **Database**: PostgreSQL
- **Queue/Cache**: Redis
- **AI**: OpenAI GPT-4

## Project Structure

```
/
├── app/
│   ├── Actions/Chat/          # Message processing actions
│   ├── Contracts/             # Interfaces
│   │   ├── AI/
│   │   ├── ActionHandlers/
│   │   └── Messaging/
│   ├── DataTransferObjects/   # DTOs for type safety
│   ├── Enums/                 # ActionType, ActionStatus, Platform
│   ├── Events/                # Domain events
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/Admin/     # Admin API controllers
│   │   │   └── Webhook/       # WhatsApp/Telegram webhooks
│   │   ├── Middleware/        # Signature verification
│   │   └── Resources/         # API resources
│   ├── Jobs/                  # Async processing jobs
│   ├── Listeners/             # Event listeners
│   ├── Models/                # Eloquent models
│   ├── Observers/             # Model observers (BusinessObserver)
│   ├── Policies/              # Authorization policies
│   └── Services/
│       ├── AI/                # ChatGPT integration
│       ├── ActionHandlers/    # Action processing
│       ├── Messaging/         # WhatsApp/Telegram services
│       │   ├── Telegram/
│       │   └── WhatsApp/
│       └── Notification/      # Group notifications
├── admin-panel/               # React admin (Refine.dev)
│   └── src/
│       ├── pages/
│       │   ├── businesses/    # Business CRUD
│       │   ├── users/         # User management
│       │   └── ...
│       ├── providers/
│       ├── components/
│       └── types/             # TypeScript interfaces
├── config/
│   ├── openai.php
│   ├── telegram.php
│   └── whatsapp.php
├── docker/
│   ├── php/                   # PHP-FPM with auto-migration
│   ├── php-cli/
│   └── nginx/
└── database/migrations/
```

## Key Concepts

### Multi-Tenancy

Each `Business` is a tenant with:
- WhatsApp phone ID and access token
- Telegram bot token
- Custom GPT configurations
- Notification channels

### Message Flow

1. Webhook receives message → `ProcessIncomingMessageJob`
2. Parse message → Find/create Client → Find/create Conversation
3. Build prompt → Call ChatGPT → Parse response
4. Extract actions from `[ACTION:type]{json}[/ACTION]` tags
5. Create `ClientAction` records → Dispatch notifications
6. Send response back to client

### Action System

GPT embeds actions in responses:
```
[ACTION:reservation]{"date":"2024-01-15","time":"19:00","party_size":4}[/ACTION]
```

Action types: `reservation`, `order`, `inquiry`, `complaint`, `callback`, `other`

### Notifications

When actions are created, managers receive notifications via:
- WhatsApp group chats
- Telegram group chats
- In-app notifications

### Telegram Auto Webhook Setup

When a business adds/updates their Telegram bot token via admin panel:

1. **BusinessObserver** detects the change
2. Generates unique `telegram_webhook_id` and `telegram_webhook_secret`
3. Dispatches `SetupTelegramWebhookJob`
4. Job calls Telegram API `setWebhook` with:
    - URL: `https://yourdomain.com/api/webhook/telegram/{webhook_id}`
    - Secret token for verification

**Webhook lookup is efficient**: Single indexed query on `telegram_webhook_id` instead of iterating all businesses.

### Conversation State Management

GPT maintains conversation context through state tracking:

**State Fields** (stored in `conversations.state` JSONB):
```json
{
  "intent": "reservation",      // greeting, menu, reservation, order, inquiry, complaint, callback, other
  "stage": "gathering_info",    // initial, gathering_info, confirming, completed, transferred
  "awaiting": "date",           // What we're waiting for: name, phone, date, time, confirmation, etc.
  "flags": []                   // Additional flags: needs_human, urgent, vip
}
```

**Summary** (stored in `conversations.summary` TEXT):
Brief description of what happened in the conversation.

**GPT Prompt Structure** (multiple system messages):
```
[SYSTEM] Base prompt + action rules
[SYSTEM] СОСТОЯНИЕ ДИАЛОГА: intent=reservation, stage=gathering_info, awaiting=date
[SYSTEM] КОНТЕКСТ: Имя: Иван, Телефон: +7..., Последние действия: ...
[SYSTEM] КРАТКОЕ РЕЗЮМЕ: Клиент хочет забронировать стол, уточняем дату
[SYSTEM] State update instructions
[USER] Last message
```

**GPT Response includes state update**:
```
Отлично! На какую дату вы хотите забронировать?
[STATE]{"intent":"reservation","stage":"gathering_info","awaiting":"date","summary":"Клиент хочет стол, уточняем дату"}[/STATE]
```

This reduces token usage while maintaining full context awareness.

## Development Commands

```bash
# Start Docker environment (includes auto-migration and queue worker)
docker-compose up -d

# Rebuild after Dockerfile changes
docker-compose build php-fpm
docker-compose up -d

# Run migrations manually (auto-runs on php-fpm start)
docker-compose exec php-fpm php artisan migrate

# View queue worker logs
docker-compose logs -f queue

# Run tests
docker-compose exec php-cli php artisan test

# Admin panel development
cd admin-panel && npm run dev
```

### Docker Services

| Service | Purpose |
|---------|---------|
| `nginx` | Web server |
| `php-fpm` | PHP application (runs migrations on startup) |
| `php-cli` | For manual artisan commands |
| `queue` | Queue worker (always running, auto-restart) |
| `postgres` | Database |
| `redis` | Queue/cache |
| `admin-panel` | React frontend dev server |

## API Endpoints

### Webhooks (No Auth)
- `GET /api/webhook/whatsapp` - WhatsApp verification
- `POST /api/webhook/whatsapp` - WhatsApp messages
- `POST /api/webhook/telegram/{token}` - Telegram updates

### Admin API (Sanctum Auth)
- `POST /api/v1/auth/login` - Login
- `GET /api/v1/auth/user` - Current user
- `GET /api/v1/admin/dashboard/stats` - Dashboard statistics
- `GET/POST/PUT/DELETE /api/v1/admin/businesses` - Businesses CRUD (super_admin only for create/update/delete)
- `GET/POST/PUT/DELETE /api/v1/admin/businesses/{id}/users` - Business users CRUD (super_admin or admin_manager)
- `GET/POST/PUT/DELETE /api/v1/admin/users` - Global user management (super_admin or admin_manager)
- `GET/POST /api/v1/admin/actions` - Client actions CRUD
- `GET /api/v1/admin/conversations` - Conversations list
- `GET/POST /api/v1/admin/gpt-configs` - GPT configuration
- `GET/POST /api/v1/admin/prompts` - Prompt management
- `GET/POST /api/v1/admin/notification-channels` - Notification channels

### Role-Based Access Control

| Role | Permissions |
|------|-------------|
| `super_admin` | Full access to all businesses, users, and settings |
| `admin_manager` | Manage users within assigned businesses (cannot create/modify super_admin or other admin_managers) |
| `manager` | View and manage data within assigned businesses |

The `business_user` pivot table stores the role per business, allowing a user to have different roles in different businesses.

## Environment Variables

```env
# WhatsApp Cloud API
WHATSAPP_API_URL=https://graph.facebook.com
WHATSAPP_API_VERSION=v18.0
WHATSAPP_VERIFY_TOKEN=your-verify-token
WHATSAPP_APP_SECRET=your-app-secret

# OpenAI
OPENAI_API_KEY=your-api-key
OPENAI_MODEL=gpt-4-turbo-preview
OPENAI_MAX_TOKENS=1000
OPENAI_TEMPERATURE=0.7

# Telegram
TELEGRAM_BOT_TOKEN=your-bot-token
TELEGRAM_WEBHOOK_SECRET=your-webhook-secret
```

## Database Schema

### Core Tables
- `businesses` - Tenant organizations
    - `telegram_webhook_id` - Unique identifier for webhook URL (indexed)
    - `telegram_webhook_secret` - Per-business webhook secret
- `users` - Admin users (managers)
- `business_user` - Many-to-many pivot with per-business role
- `clients` - WhatsApp/Telegram contacts
- `conversations` - Chat sessions
    - `state` (JSONB) - Intent, stage, awaiting, flags
    - `summary` (TEXT) - Brief conversation summary
    - `context` (JSONB) - Additional context data
- `conversation_messages` - Individual messages
- `client_actions` - Extracted actions from conversations
- `gpt_configurations` - GPT settings per business
- `prompts` - Reusable prompt templates
- `notification_channels` - Group chat configs
- `manager_notification_preferences` - Per-user notification settings

## Code Conventions

### DTOs
Use DTOs for data transfer between layers:
- `IncomingMessageDTO` - Parsed incoming messages
- `OutgoingMessageDTO` - Messages to send
- `ParsedActionDTO` - Extracted actions
- `ChatResponseDTO` - GPT responses

### Services
Services implement contracts (interfaces):
- `MessengerInterface` - WhatsApp/Telegram services
- `ChatCompletionInterface` - ChatGPT service
- `ActionHandlerInterface` - Action handlers

### Events
Domain events for loose coupling:
- `MessageReceived` - When a message comes in
- `ActionCreated` - When an action is extracted
- `ActionStatusChanged` - When action status updates

## Testing

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter=WhatsAppWebhookTest

# Run with coverage
php artisan test --coverage
```

## Common Tasks

### Add New Action Type

1. Add to `App\Enums\ActionType`
2. Create handler in `App\Services\ActionHandlers`
3. Register in `ActionHandlerFactory`
4. Update `PromptBuilder` with action description

### Add New Messenger Platform

1. Create service implementing `MessengerInterface`
2. Add to `App\Enums\Platform`
3. Create webhook controller
4. Register routes in `routes/api.php`

### Modify GPT Behavior

1. Update system prompt in `PromptBuilder`
2. Or use admin panel to create custom GPT configuration
3. Set configuration as active for the business

---

## Complete System Workflow

### Step 1: Business Onboarding (Admin Panel)

**Super Admin creates a new Business:**
```
Admin Panel → POST /api/v1/admin/businesses
```
- Enter business name (e.g., "Pizza Palace")
- Configure WhatsApp: `whatsapp_phone_id` + `whatsapp_access_token` (from Meta)
- Configure Telegram: `telegram_bot_token` (from BotFather)
- Business gets a unique `slug`

**Business record saved → `businesses` table**

---

### Step 2: Create Manager Users

**Add managers who will handle actions:**
```
Admin Panel → Create users with role='manager' and business_id
```
- Managers can log into admin panel
- They see only their business's data

**Pivot table `business_user` links managers to businesses**

---

### Step 3: Configure GPT Behavior

**Create GPT Configuration for the business:**
```
Admin Panel → POST /api/v1/admin/gpt-configs
```
- Set model (gpt-4-turbo)
- Set temperature, max_tokens
- Write system prompt explaining the business:
  ```
  "You are an assistant for Pizza Palace. Help customers
  order pizza, make reservations, and handle complaints..."
  ```
- Define available actions: `['reservation', 'order', 'inquiry']`
- **Activate** this configuration

**Saved → `gpt_configurations` table (is_active=true)**

---

### Step 4: Set Up Notification Channels

**Configure where managers receive alerts:**
```
Admin Panel → POST /api/v1/admin/notification-channels
```
- Add Telegram group: `chat_id="-100123456789"`, name="Managers Group"
- Add WhatsApp group (if supported)

**Each manager sets their preferences:**
```
Admin Panel → PUT /api/v1/admin/notification-preferences
```
- Which channels to notify them on
- Which action types they care about (reservations, complaints, etc.)

---

### Step 5: Configure Webhooks (One-time setup)

**WhatsApp:**
- In Meta Developer Console, set webhook URL:
  ```
  https://yourdomain.com/api/webhook/whatsapp
  ```
- Set verify token (matches `WHATSAPP_VERIFY_TOKEN` in .env)

**Telegram:**
- Webhook is **automatically configured** when you add a bot token to a business
- The system generates a unique webhook URL and secret per business
- Manual setup (if needed):
  ```
  https://api.telegram.org/bot{token}/setWebhook?url=https://yourdomain.com/api/webhook/telegram/{webhook_id}&secret_token={secret}
  ```

---

### Step 6: Customer Sends Message (Runtime)

```
Customer (WhatsApp) → "Hi, I'd like to book a table for 4 people tomorrow at 7pm"
```

**Flow:**
```
1. WhatsApp Cloud API → POST /api/webhook/whatsapp

2. WhatsAppWebhookController::handle()
   - Extract phone_number_id from payload
   - Find Business by whatsapp_phone_id
   - Dispatch ProcessIncomingMessageJob

3. ProcessIncomingMessageJob (async via Redis queue)
   - Parse payload → IncomingMessageDTO
   - Call ProcessIncomingMessageAction::execute()

4. ProcessIncomingMessageAction:
   a) Find or create Client (by phone number)
      → clients table

   b) Find or create Conversation
      → conversations table

   c) Store incoming message
      → conversation_messages (role='user')

   d) Get active GptConfiguration for business

   e) Build system prompt via PromptBuilder
      - Business context
      - Available actions with format instructions
      - Client history

   f) Call ChatGptService::complete()
      → OpenAI API

   g) GPT Response example:
      "I'd be happy to help you book a table! I've made a reservation
      for 4 people tomorrow at 7:00 PM. See you then!
      [ACTION:reservation]{"date":"2024-01-16","time":"19:00","party_size":4,"name":"John"}[/ACTION]"

   h) ResponseParser extracts action tags
      → ParsedActionDTO

   i) ActionHandlerFactory gets ReservationHandler
      → Creates ClientAction record
      → Fires ActionCreated event

   j) Store assistant response (clean, without tags)
      → conversation_messages (role='assistant')

   k) Return OutgoingMessageDTO

5. SendResponseAction sends reply via WhatsAppMessageSender
   → Customer receives: "I'd be happy to help you book a table!..."
```

---

### Step 7: Manager Notification

```
ActionCreated event → NotifyManagersOnActionCreated listener

1. NotificationDispatcher::dispatchForAction()
   - Query ManagerNotificationPreference for this business
   - Check which managers want reservation notifications

2. For each configured channel:
   - Dispatch SendGroupNotificationJob

3. SendGroupNotificationJob:
   - Build notification message:
     "📅 New Reservation
      Client: John (+1234567890)
      Date: Jan 16, 2024 at 7:00 PM
      Party size: 4"

   - Send via TelegramGroupNotifier or WhatsAppGroupNotifier
```

**Manager receives notification in their Telegram group**

---

### Step 8: Manager Processes Action

**Manager opens Admin Panel:**
```
Dashboard shows: "5 Pending Actions"

Actions List → Filter by status='pending'
```

**Manager views the reservation:**
```
GET /api/v1/admin/actions/123
```
- Sees client details, reservation details
- Can view full conversation

**Manager confirms the reservation:**
```
POST /api/v1/admin/actions/123/status
{ "status": "completed", "notes": "Table 5 reserved" }
```

**ActionStatusChanged event fires** (could trigger customer notification if implemented)

---

### Visual Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        SETUP PHASE                               │
├─────────────────────────────────────────────────────────────────┤
│  Admin creates Business → Adds Managers → Configures GPT        │
│  → Sets up Notification Channels → Configures Webhooks          │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      RUNTIME PHASE                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Customer ──► Webhook ──► Job Queue ──► Process Message         │
│                                              │                   │
│                                              ▼                   │
│                                    ┌─────────────────┐          │
│                                    │  Find/Create    │          │
│                                    │  Client +       │          │
│                                    │  Conversation   │          │
│                                    └────────┬────────┘          │
│                                             │                    │
│                                             ▼                    │
│                                    ┌─────────────────┐          │
│                                    │  ChatGPT API    │          │
│                                    │  (with context) │          │
│                                    └────────┬────────┘          │
│                                             │                    │
│                                             ▼                    │
│                                    ┌─────────────────┐          │
│                                    │ Parse Response  │          │
│                                    │ Extract Actions │          │
│                                    └────────┬────────┘          │
│                                             │                    │
│                          ┌──────────────────┼──────────────────┐│
│                          ▼                  ▼                  ▼│
│                   ┌───────────┐     ┌─────────────┐    ┌──────┐│
│                   │  Create   │     │   Send      │    │Notify││
│                   │  Action   │     │   Reply     │    │Mgrs  ││
│                   └───────────┘     └─────────────┘    └──────┘│
│                          │                  │              │    │
│                          ▼                  ▼              ▼    │
│                   ┌───────────┐     ┌─────────────┐  ┌────────┐│
│                   │  Admin    │     │  Customer   │  │Telegram││
│                   │  Panel    │     │  WhatsApp   │  │ Group  ││
│                   └───────────┘     └─────────────┘  └────────┘│
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

### Key Files in the Flow

| Step | File | Purpose |
|------|------|---------|
| Webhook Entry | `app/Http/Controllers/Webhook/WhatsAppWebhookController.php` | Receives WhatsApp messages |
| Webhook Entry | `app/Http/Controllers/Webhook/TelegramWebhookController.php` | Receives Telegram messages |
| Job Dispatch | `app/Jobs/ProcessIncomingMessageJob.php` | Async message processing |
| Message Processing | `app/Actions/Chat/ProcessIncomingMessageAction.php` | Main orchestration logic |
| Client Management | `app/Models/Client.php` | Find/create customers |
| Conversation | `app/Models/Conversation.php` | Chat session + state management |
| Prompt Building | `app/Services/AI/PromptBuilder.php` | Constructs multi-message GPT prompts |
| GPT Integration | `app/Services/AI/ChatGptService.php` | Calls OpenAI API |
| Response Parsing | `app/Services/AI/ResponseParser.php` | Extracts action and state tags |
| Action Creation | `app/Services/ActionHandlers/ReservationHandler.php` | Creates ClientAction |
| Event Dispatch | `app/Events/ActionCreated.php` | Triggers notifications |
| Notification | `app/Services/Notification/NotificationDispatcher.php` | Sends to managers |
| Send Reply | `app/Services/Messaging/WhatsApp/WhatsAppMessageSender.php` | Sends to customer |
| Business Observer | `app/Observers/BusinessObserver.php` | Auto-setup Telegram webhook |
| Webhook Setup Job | `app/Jobs/SetupTelegramWebhookJob.php` | Calls Telegram setWebhook API |
| Authorization | `app/Policies/BusinessPolicy.php`, `UserPolicy.php` | Role-based access control |
