<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for payment_histories.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_histories', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('gateway_id')->nullable()->constrained('payment_gateways')->nullOnDelete();
            $table->string('reference', 190);
            $table->decimal('amount', 12, 2);
            $table->string('currency_code', 3)->default('USD');
            $table->string('status', 24)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique('reference', 'ed_payment_histories_reference_unique');
            $table->index('user_id', 'ed_payment_histories_user_id_index');
            $table->index('status', 'ed_payment_histories_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_histories');
    }
};
