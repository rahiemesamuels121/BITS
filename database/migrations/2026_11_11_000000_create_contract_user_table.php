<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_user', function (Blueprint $table) {
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->primary(['contract_id', 'user_id']);
        });

        DB::table('contracts')->whereNotNull('client_id')->orderBy('id')->chunkById(500, function ($contracts) {
            DB::table('contract_user')->insert($contracts->map(fn ($contract) => [
                'contract_id' => $contract->id,
                'user_id' => $contract->client_id,
            ])->all());
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->constrained('users')->restrictOnDelete();
        });

        DB::table('contract_user')->orderBy('contract_id')->get()->groupBy('contract_id')->each(function ($clients, $contractId) {
            DB::table('contracts')->where('id', $contractId)->update(['client_id' => $clients->first()->user_id]);
        });

        Schema::dropIfExists('contract_user');
    }
};
