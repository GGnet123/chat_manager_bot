<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // Unique identifier for webhook URL lookup (more efficient than hashing)
            $table->string('telegram_webhook_id', 32)->nullable()->unique()->after('telegram_bot_token');
            // Per-business webhook secret for signature verification
            $table->string('telegram_webhook_secret', 64)->nullable()->after('telegram_webhook_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['telegram_webhook_id', 'telegram_webhook_secret']);
        });
    }
};
