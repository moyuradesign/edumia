<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for frontend_settings.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frontend_settings', static function (Blueprint $table): void {
            $table->id();
            $table->string('key', 190);
            $table->longText('value')->nullable();
            $table->string('type', 30)->default('string');
            $table->timestamps();
            $table->unique('key', 'ed_frontend_settings_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frontend_settings');
    }
};
