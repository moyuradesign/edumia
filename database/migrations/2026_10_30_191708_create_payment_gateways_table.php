<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for payment_gateways.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', static function (Blueprint $table): void {
            $table->id();
            $table->string('code', 70);
            $table->string('display_name', 150);
            $table->boolean('enabled')->default(false);
            $table->json('config')->nullable();
            $table->timestamps();
            $table->unique('code', 'ed_payment_gateways_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
