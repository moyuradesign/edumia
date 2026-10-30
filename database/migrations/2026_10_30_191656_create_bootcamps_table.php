<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for bootcamps.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bootcamps', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('bootcamp_categories')->nullOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 190);
            $table->string('slug', 190);
            $table->longText('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('status', 24)->default('draft');
            $table->timestamps();
            $table->unique('slug', 'ed_bootcamps_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bootcamps');
    }
};
