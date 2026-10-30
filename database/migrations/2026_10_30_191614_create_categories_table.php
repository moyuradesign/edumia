<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for categories.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name', 160);
            $table->string('slug', 190);
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable();
            $table->string('status', 24)->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique('slug', 'ed_categories_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
