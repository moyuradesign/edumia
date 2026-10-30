<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for like_dislike_reviews.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('like_dislike_reviews', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('review_id')->constrained('reviews')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_like');
            $table->timestamps();
            $table->unique(['review_id', 'user_id'], 'ed_like_dislike_reviews_review_id_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('like_dislike_reviews');
    }
};
