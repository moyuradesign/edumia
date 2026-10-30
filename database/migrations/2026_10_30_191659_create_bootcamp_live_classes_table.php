<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for bootcamp_live_classes.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bootcamp_live_classes', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bootcamp_id')->constrained('bootcamps')->cascadeOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 190);
            $table->string('meeting_url', 255)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index('bootcamp_id', 'ed_bootcamp_live_classes_bootcamp_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bootcamp_live_classes');
    }
};
