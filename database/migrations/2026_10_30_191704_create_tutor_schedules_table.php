<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for tutor_schedules.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_schedules', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('timezone', 64)->default('Asia/Phnom_Penh');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index('tutor_id', 'ed_tutor_schedules_tutor_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_schedules');
    }
};
