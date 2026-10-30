<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for permissions.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', static function (Blueprint $table): void {
            $table->id();
            $table->string('name', 190);
            $table->string('guard_name', 60)->default('web');
            $table->string('description', 255)->nullable();
            $table->timestamps();
            $table->unique(['name', 'guard_name'], 'ed_permissions_name_guard_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
