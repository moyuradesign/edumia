<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for coupons.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', static function (Blueprint $table): void {
            $table->id();
            $table->string('code', 60);
            $table->string('discount_type', 20)->default('percent');
            $table->decimal('discount_value', 12, 2);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->unsignedInteger('redemption_count')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique('code', 'ed_coupons_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
