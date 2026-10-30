<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for tutor_subjects.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_subjects', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('tutor_categories')->nullOnDelete();
            $table->string('name', 150);
            $table->string('slug', 190);
            $table->timestamps();
            $table->unique('slug', 'ed_tutor_subjects_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_subjects');
    }
};
