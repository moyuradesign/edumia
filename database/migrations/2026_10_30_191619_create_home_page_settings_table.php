<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for home_page_settings.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_page_settings', static function (Blueprint $table): void {
            $table->id();
            $table->string('section', 100);
            $table->string('key', 190);
            $table->longText('value')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['section', 'key'], 'ed_home_page_settings_section_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_page_settings');
    }
};
