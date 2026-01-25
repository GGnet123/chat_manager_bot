<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->string('chat_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('business_id');
            $table->index('platform');
            $table->index('is_active');
            $table->unique(['business_id', 'platform', 'chat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_channels');
    }
};
