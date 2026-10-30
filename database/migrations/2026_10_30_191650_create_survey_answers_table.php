<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for survey_answers.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_answers', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('survey_response_id')->constrained('survey_responses')->cascadeOnDelete();
            $table->foreignId('survey_question_id')->constrained('survey_questions')->cascadeOnDelete();
            $table->text('answer')->nullable();
            $table->timestamps();
            $table->unique(['survey_response_id', 'survey_question_id'], 'ed_survey_answers_063cbc211341_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_answers');
    }
};
