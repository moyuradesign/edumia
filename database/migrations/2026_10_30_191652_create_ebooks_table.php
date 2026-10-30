<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for ebooks.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ebooks', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('ebook_categories')->nullOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 190);
            $table->string('slug', 190);
            $table->longText('description')->nullable();
            $table->string('file_path', 255)->nullable();
            $table->string('cover_path', 255)->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('status', 24)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique('slug', 'ed_ebooks_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ebooks');
    }
};
