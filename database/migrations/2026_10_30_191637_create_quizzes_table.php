<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for quizzes.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->string('title', 190);
            $table->text('instructions')->nullable();
            $table->unsignedTinyInteger('passing_score')->default(70);
            $table->unsignedInteger('time_limit_minutes')->nullable();
            $table->unsignedTinyInteger('attempts_allowed')->default(3);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->index('course_id', 'ed_quizzes_course_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
