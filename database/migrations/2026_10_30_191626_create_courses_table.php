<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for courses.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('title', 190);
            $table->string('slug', 190);
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->string('thumbnail_path', 255)->nullable();
            $table->string('language_code', 8)->default('en');
            $table->string('level', 30)->default('beginner');
            $table->decimal('price', 12, 2)->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->string('status', 24)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('visibility', 24)->default('public');
            $table->timestamps();
            $table->unique('slug', 'ed_courses_slug_unique');
            $table->index('status', 'ed_courses_status_index');
            $table->index('instructor_id', 'ed_courses_instructor_id_index');
            $table->index('category_id', 'ed_courses_category_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
