<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for lessons.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();
            $table->string('title', 190);
            $table->string('slug', 190);
            $table->longText('content')->nullable();
            $table->string('video_url', 255)->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->string('content_type', 30)->default('text');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_preview')->default(false);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->unique(['course_id', 'slug'], 'ed_lessons_course_id_slug_unique');
            $table->index('section_id', 'ed_lessons_section_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
