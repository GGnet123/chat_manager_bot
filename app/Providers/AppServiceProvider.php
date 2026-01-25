<?php

namespace App\Providers;

use App\Contracts\AI\ChatCompletionInterface;
use App\Contracts\Messaging\MessengerInterface;
use App\Events\ActionCreated;
use App\Listeners\NotifyManagersOnActionCreated;
use App\Services\AI\ChatGptService;
use App\Services\Messaging\WhatsApp\WhatsAppService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use App\Models\Business;
use App\Models\User;
use App\Observers\BusinessObserver;
use App\Policies\BusinessPolicy;
use App\Policies\UserPolicy;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to implementations
        $this->app->bind(ChatCompletionInterface::class, ChatGptService::class);
        $this->app->bind(MessengerInterface::class, WhatsAppService::class);
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerEvents();
        $this->registerPolicies();
        $this->registerObservers();
    }

    protected function registerObservers(): void
    {
        Business::observe(BusinessObserver::class);
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Business::class, BusinessPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    protected function registerEvents(): void
    {
        Event::listen(ActionCreated::class, NotifyManagersOnActionCreated::class);
    }
}
