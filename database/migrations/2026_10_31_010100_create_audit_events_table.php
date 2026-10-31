<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_type', 190);
            $table->unsignedBigInteger('subject_id');
            $table->string('action', 100);
            $table->json('previous_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id'], 'audit_subject_index');
            $table->index(['action', 'created_at'], 'audit_action_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
