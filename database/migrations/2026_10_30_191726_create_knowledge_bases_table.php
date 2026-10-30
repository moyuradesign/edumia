<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for knowledge_bases.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_bases', static function (Blueprint $table): void {
            $table->id();
            $table->string('title', 190);
            $table->string('slug', 190);
            $table->longText('content')->nullable();
            $table->string('category', 120)->nullable();
            $table->boolean('published')->default(false);
            $table->timestamps();
            $table->unique('slug', 'ed_knowledge_bases_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_bases');
    }
};
