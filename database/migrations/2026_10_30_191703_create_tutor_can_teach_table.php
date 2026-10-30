<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for tutor_can_teach.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_can_teach', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('tutor_subjects')->cascadeOnDelete();
            $table->decimal('rate_per_hour', 12, 2)->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->timestamps();
            $table->unique(['tutor_id', 'subject_id'], 'ed_tutor_can_teach_tutor_id_subject_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_can_teach');
    }
};
