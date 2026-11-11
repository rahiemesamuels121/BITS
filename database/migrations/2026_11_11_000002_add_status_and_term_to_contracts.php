<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_indefinite')->default(true);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn(['is_active', 'is_indefinite', 'starts_at', 'ends_at']);
        });
    }
};
