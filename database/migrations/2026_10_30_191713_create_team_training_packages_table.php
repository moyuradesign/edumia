<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for team_training_packages.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_training_packages', static function (Blueprint $table): void {
            $table->id();
            $table->string('name', 190);
            $table->text('description')->nullable();
            $table->unsignedInteger('seats')->default(1);
            $table->decimal('price', 12, 2)->default(0);
            $table->string('status', 24)->default('active');
            $table->timestamps();
            $table->index('status', 'ed_team_training_packages_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_training_packages');
    }
};
