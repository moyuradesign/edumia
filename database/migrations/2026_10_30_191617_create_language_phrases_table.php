<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for language_phrases.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('language_phrases', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->string('key', 190);
            $table->text('value');
            $table->timestamps();
            $table->unique(['language_id', 'key'], 'ed_language_phrases_language_id_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('language_phrases');
    }
};
