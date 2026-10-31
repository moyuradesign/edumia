<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('payment_gateway', 32)->nullable();
            $table->string('gateway_session_id', 190)->nullable()->unique();
            $table->text('gateway_checkout_url')->nullable();
            $table->string('gateway_payment_reference', 190)->nullable()->unique();
        });

        Schema::create('payment_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('gateway', 32);
            $table->string('event_id', 190);
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('event_type', 100);
            $table->timestamp('processed_at');
            $table->timestamps();
            $table->unique(['gateway', 'event_id'], 'payment_webhook_events_unique');
            $table->index(['order_id', 'event_type'], 'payment_webhook_events_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique('orders_gateway_session_id_unique');
            $table->dropUnique('orders_gateway_payment_reference_unique');
            $table->dropColumn([
                'payment_gateway', 'gateway_session_id',
                'gateway_checkout_url', 'gateway_payment_reference',
            ]);
        });
    }
};
