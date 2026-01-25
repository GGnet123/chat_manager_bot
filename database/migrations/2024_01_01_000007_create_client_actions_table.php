<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->json('details')->nullable();
            $table->string('client_name')->nullable();
            $table->string('client_phone')->nullable();
            $table->string('status')->default('pending');
            $table->string('priority')->default('normal');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('business_id');
            $table->index('client_id');
            $table->index('action');
            $table->index('status');
            $table->index('priority');
            $table->index('assigned_to');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_actions');
    }
};
