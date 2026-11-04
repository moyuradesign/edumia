<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_provider_checks', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('provider', 30);
            $table->string('mode', 16);
            $table->string('result', 24);
            $table->string('country', 3)->nullable();
            $table->boolean('charges_enabled')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();
            $table->index(['provider', 'checked_at'], 'ed_payment_provider_check_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_provider_checks');
    }
};
