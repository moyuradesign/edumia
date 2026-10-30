<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for watch_histories.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watch_histories', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('watched_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->timestamps();
            $table->index('user_id', 'ed_watch_histories_user_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watch_histories');
    }
};
