<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for reviews.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('title', 190)->nullable();
            $table->text('body')->nullable();
            $table->string('status', 24)->default('pending');
            $table->timestamps();
            $table->unique(['course_id', 'user_id'], 'ed_reviews_course_id_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
