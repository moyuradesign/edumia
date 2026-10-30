<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for bootcamp_resources.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bootcamp_resources', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('module_id')->constrained('bootcamp_modules')->cascadeOnDelete();
            $table->string('title', 190);
            $table->string('file_path', 255)->nullable();
            $table->string('external_url', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index('module_id', 'ed_bootcamp_resources_module_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bootcamp_resources');
    }
};
