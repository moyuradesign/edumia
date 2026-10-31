<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->foreignId('payment_order_id')->nullable()->constrained('orders')->nullOnDelete();
        });

        Schema::create('commerce_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->restrictOnDelete();
            $table->string('number', 80)->unique();
            $table->string('customer_name', 190);
            $table->string('customer_email', 190);
            $table->string('currency_code', 3);
            $table->unsignedBigInteger('total_minor');
            $table->unsignedBigInteger('refunded_minor')->default(0);
            $table->string('tax_treatment', 32)->default('not_assessed');
            $table->string('status', 32)->default('paid');
            $table->timestamp('issued_at');
            $table->timestamps();
        });

        Schema::create('instructor_earning_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->restrictOnDelete();
            $table->string('source_key', 190)->unique();
            $table->string('entry_type', 32);
            $table->string('currency_code', 3);
            $table->bigInteger('gross_minor');
            $table->bigInteger('platform_minor');
            $table->bigInteger('instructor_minor');
            $table->string('status', 24)->default('held');
            $table->timestamps();
            $table->index(['instructor_id', 'status'], 'earning_instructor_status_index');
        });

        Schema::create('commerce_refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->restrictOnDelete();
            $table->string('gateway_charge_id', 190)->unique();
            $table->unsignedBigInteger('refunded_minor');
            $table->string('currency_code', 3);
            $table->timestamps();
        });

        Schema::create('commerce_disputes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->string('gateway_dispute_id', 190)->unique();
            $table->string('status', 40);
            $table->unsignedBigInteger('amount_minor');
            $table->timestamps();
        });

        Schema::create('instructor_payout_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('instructor_id')->constrained('users')->restrictOnDelete();
            $table->string('currency_code', 3);
            $table->unsignedBigInteger('amount_minor');
            $table->string('status', 24)->default('pending');
            $table->string('provider_reference', 190)->nullable()->unique();
            $table->timestamps();
            $table->index(['instructor_id', 'status']);
        });

        Schema::create('commerce_reconciliation_issues', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('issue_key', 190)->unique();
            $table->string('kind', 70);
            $table->string('status', 24)->default('open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_reconciliation_issues');
        Schema::dropIfExists('instructor_payout_records');
        Schema::dropIfExists('commerce_disputes');
        Schema::dropIfExists('commerce_refunds');
        Schema::dropIfExists('instructor_earning_entries');
        Schema::dropIfExists('commerce_invoices');
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('payment_order_id');
        });
    }
};
