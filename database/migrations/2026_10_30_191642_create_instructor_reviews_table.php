<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for instructor_reviews.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_reviews', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('review')->nullable();
            $table->string('status', 24)->default('pending');
            $table->timestamps();
            $table->unique(['instructor_id', 'reviewer_id'], 'ed_instructor_reviews_instructor_id_reviewer_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_reviews');
    }
};
