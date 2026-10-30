<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for payouts.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payment_histories')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency_code', 3)->default('USD');
            $table->string('status', 24)->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->string('reference', 190)->nullable();
            $table->timestamps();
            $table->index('instructor_id', 'ed_payouts_instructor_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
