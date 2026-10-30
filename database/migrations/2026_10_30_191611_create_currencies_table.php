<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for currencies.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', static function (Blueprint $table): void {
            $table->id();
            $table->string('code', 3);
            $table->string('name', 80);
            $table->string('symbol', 10)->nullable();
            $table->unsignedTinyInteger('decimals')->default(2);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique('code', 'ed_currencies_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
