<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for password_reset_tokens.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_tokens', static function (Blueprint $table): void {
            $table->string('email', 190)->primary();
            $table->string('token', 255);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
