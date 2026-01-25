<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('manager')->after('email');
            $table->foreignId('business_id')->nullable()->after('role')
                ->constrained('businesses')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('business_id');

            $table->index('role');
            $table->index('business_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropColumn(['role', 'business_id', 'is_active']);
        });
    }
};
