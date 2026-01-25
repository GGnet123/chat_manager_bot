<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gpt_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('model')->default('gpt-4-turbo-preview');
            $table->integer('max_tokens')->default(1000);
            $table->decimal('temperature', 3, 2)->default(0.7);
            $table->text('system_prompt')->nullable();
            $table->json('available_actions')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('business_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gpt_configurations');
    }
};
