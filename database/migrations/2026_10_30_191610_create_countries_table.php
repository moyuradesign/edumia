<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for countries.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', static function (Blueprint $table): void {
            $table->id();
            $table->string('name', 140);
            $table->string('iso2', 2);
            $table->string('iso3', 3)->nullable();
            $table->string('dialing_code', 10)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique('iso2', 'ed_countries_iso2_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
