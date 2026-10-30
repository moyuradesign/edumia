<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for tutor_bookings.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_bookings', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('learner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('tutor_subjects')->nullOnDelete();
            $table->timestamp('scheduled_at');
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->decimal('fee', 12, 2)->default(0);
            $table->string('status', 24)->default('pending');
            $table->string('meeting_url', 255)->nullable();
            $table->timestamps();
            $table->index('tutor_id', 'ed_tutor_bookings_tutor_id_index');
            $table->index('learner_id', 'ed_tutor_bookings_learner_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_bookings');
    }
};
