<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_user', function (Blueprint $table) {
            $table->boolean('uses_contract_duration')->default(true);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('contract_user', function (Blueprint $table) {
            $table->dropColumn(['uses_contract_duration', 'starts_at', 'ends_at']);
        });
    }
};
