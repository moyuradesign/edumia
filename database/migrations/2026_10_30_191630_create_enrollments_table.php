<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for enrollments.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->string('status', 24)->default('active');
            $table->timestamps();
            $table->unique(['course_id', 'user_id'], 'ed_enrollments_course_id_user_id_unique');
            $table->index('user_id', 'ed_enrollments_user_id_index');
            $table->index('status', 'ed_enrollments_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
