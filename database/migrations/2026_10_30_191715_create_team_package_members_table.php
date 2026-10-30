<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for team_package_members.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_package_members', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_id')->constrained('team_package_purchases')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['purchase_id', 'user_id'], 'ed_team_package_members_purchase_id_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_package_members');
    }
};
