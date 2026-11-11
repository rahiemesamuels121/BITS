<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('slug', 180)->unique();
            $table->string('status', 20)->default('draft');
            $table->json('draft_settings')->nullable();
            $table->unsignedBigInteger('current_published_version_id')->nullable();
            $table->unsignedInteger('revision')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status']);
        });

        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->string('field_key', 64);
            $table->string('label');
            $table->string('type', 30);
            $table->string('placeholder')->nullable();
            $table->text('help_text')->nullable();
            $table->boolean('required')->default(false);
            $table->json('options')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('conditional_rules')->nullable();
            $table->json('default_value')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['form_id', 'field_key']);
            $table->index(['form_id', 'sort_order']);
        });

        Schema::create('form_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->json('schema_json');
            $table->json('settings_json');
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at');
            $table->timestamp('created_at');
            $table->unique(['form_id', 'version_number']);
        });

        Schema::table('forms', function (Blueprint $table) {
            $table->foreign('current_published_version_id')->references('id')->on('form_versions')->nullOnDelete();
        });

        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->restrictOnDelete();
            $table->foreignId('form_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('data');
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->index(['form_id', 'submitted_at']);
            $table->index(['form_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
        Schema::table('forms', fn (Blueprint $table) => $table->dropForeign(['current_published_version_id']));
        Schema::dropIfExists('form_versions');
        Schema::dropIfExists('form_fields');
        Schema::dropIfExists('forms');
    }
};
