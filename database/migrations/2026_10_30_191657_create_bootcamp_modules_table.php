<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for bootcamp_modules.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bootcamp_modules', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bootcamp_id')->constrained('bootcamps')->cascadeOnDelete();
            $table->string('title', 190);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index('bootcamp_id', 'ed_bootcamp_modules_bootcamp_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bootcamp_modules');
    }
};
