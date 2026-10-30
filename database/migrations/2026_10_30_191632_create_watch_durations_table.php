<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for watch_durations.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watch_durations', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('seconds_watched')->default(0);
            $table->unsignedInteger('last_position_seconds')->default(0);
            $table->timestamps();
            $table->unique(['lesson_id', 'user_id'], 'ed_watch_durations_lesson_id_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watch_durations');
    }
};
