<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for media_files.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_files', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk', 40)->default('public');
            $table->string('path', 255);
            $table->string('original_name', 255);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('alt_text', 255)->nullable();
            $table->string('visibility', 20)->default('private');
            $table->timestamps();
            $table->index('user_id', 'ed_media_files_user_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};
