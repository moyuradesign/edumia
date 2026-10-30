<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for ticket_categories.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_categories', static function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique('slug', 'ed_ticket_categories_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_categories');
    }
};
