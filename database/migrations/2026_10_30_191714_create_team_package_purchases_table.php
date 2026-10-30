<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for team_package_purchases.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_package_purchases', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('package_id')->constrained('team_training_packages')->cascadeOnDelete();
            $table->string('organization_name', 190);
            $table->foreignId('purchaser_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('seats_purchased')->default(1);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status', 24)->default('pending');
            $table->timestamp('purchased_at')->nullable();
            $table->timestamps();
            $table->index('package_id', 'ed_team_package_purchases_package_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_package_purchases');
    }
};
