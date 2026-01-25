<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manager_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->boolean('in_app')->default(true);
            $table->json('whatsapp_groups')->nullable();
            $table->json('telegram_groups')->nullable();
            $table->json('action_types')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'business_id']);
            $table->index('user_id');
            $table->index('business_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_notification_preferences');
    }
};
