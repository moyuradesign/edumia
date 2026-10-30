<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for blog_comments.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_comments', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blog_id')->constrained('blogs')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_name', 150)->nullable();
            $table->text('body');
            $table->boolean('approved')->default(false);
            $table->timestamps();
            $table->index('blog_id', 'ed_blog_comments_blog_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_comments');
    }
};
