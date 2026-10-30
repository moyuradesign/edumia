<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for knowledge_base_topics.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_base_topics', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('knowledge_base_id')->constrained('knowledge_bases')->cascadeOnDelete();
            $table->string('title', 190);
            $table->string('slug', 190);
            $table->longText('body')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['knowledge_base_id', 'slug'], 'ed_knowledge_base_topics_knowledge_base_id_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_base_topics');
    }
};
