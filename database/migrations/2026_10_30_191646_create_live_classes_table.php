<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for live_classes.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_classes', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 190);
            $table->string('provider', 50)->nullable();
            $table->string('meeting_url', 255)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 24)->default('scheduled');
            $table->timestamps();
            $table->index('course_id', 'ed_live_classes_course_id_index');
            $table->index('starts_at', 'ed_live_classes_starts_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_classes');
    }
};
