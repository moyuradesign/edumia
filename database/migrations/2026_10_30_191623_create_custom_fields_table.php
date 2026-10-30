<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for custom_fields.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_fields', static function (Blueprint $table): void {
            $table->id();
            $table->string('key', 190);
            $table->string('label', 190);
            $table->string('type', 30)->default('text');
            $table->string('target', 80)->default('course');
            $table->json('options')->nullable();
            $table->boolean('required')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['target', 'key'], 'ed_custom_fields_target_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
