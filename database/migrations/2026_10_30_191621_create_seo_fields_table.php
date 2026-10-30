<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for seo_fields.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_fields', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->nullable()->constrained('builder_pages')->nullOnDelete();
            $table->string('route_name', 190)->nullable();
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('canonical_url', 255)->nullable();
            $table->string('image_path', 255)->nullable();
            $table->timestamps();
            $table->index('page_id', 'ed_seo_fields_page_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_fields');
    }
};
