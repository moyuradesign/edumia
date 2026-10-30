<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for player_settings.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_settings', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('playback_speed', 3, 2)->default(1);
            $table->boolean('autoplay')->default(true);
            $table->string('subtitle_language', 8)->nullable();
            $table->string('video_quality', 30)->default('auto');
            $table->timestamps();
            $table->unique('user_id', 'ed_player_settings_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_settings');
    }
};
