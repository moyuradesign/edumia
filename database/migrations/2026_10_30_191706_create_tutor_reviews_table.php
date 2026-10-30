<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for tutor_reviews.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_reviews', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tutor_booking_id')->constrained('tutor_bookings')->cascadeOnDelete();
            $table->foreignId('learner_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('review')->nullable();
            $table->timestamps();
            $table->unique('tutor_booking_id', 'ed_tutor_reviews_tutor_booking_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_reviews');
    }
};
