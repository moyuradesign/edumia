<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for languages.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', static function (Blueprint $table): void {
            $table->id();
            $table->string('code', 8);
            $table->string('name', 100);
            $table->string('native_name', 100);
            $table->string('direction', 3)->default('ltr');
            $table->boolean('active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique('code', 'ed_languages_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
