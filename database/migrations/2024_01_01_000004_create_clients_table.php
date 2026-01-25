<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('phone')->nullable();
            $table->string('telegram_id')->nullable();
            $table->string('name')->nullable();
            $table->string('platform');
            $table->json('metadata')->nullable();
            $table->timestamp('first_contact_at')->nullable();
            $table->timestamp('last_contact_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'phone']);
            $table->unique(['business_id', 'telegram_id']);
            $table->index('business_id');
            $table->index('platform');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
