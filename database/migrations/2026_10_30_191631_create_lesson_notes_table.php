<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for lesson_notes.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_notes', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->longText('note');
            $table->string('visibility', 20)->default('private');
            $table->timestamps();
            $table->index('user_id', 'ed_lesson_notes_user_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_notes');
    }
};
