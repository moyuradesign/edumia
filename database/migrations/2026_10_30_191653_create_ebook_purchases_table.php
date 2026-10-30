<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for ebook_purchases.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ebook_purchases', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ebook_id')->constrained('ebooks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->string('payment_reference', 190)->nullable();
            $table->string('status', 24)->default('pending');
            $table->timestamp('purchased_at')->nullable();
            $table->timestamps();
            $table->index('user_id', 'ed_ebook_purchases_user_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ebook_purchases');
    }
};
