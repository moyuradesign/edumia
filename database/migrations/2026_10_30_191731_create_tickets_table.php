<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for tickets.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('ticket_categories')->nullOnDelete();
            $table->foreignId('priority_id')->nullable()->constrained('ticket_priorities')->nullOnDelete();
            $table->foreignId('status_id')->nullable()->constrained('ticket_status')->nullOnDelete();
            $table->string('subject', 190);
            $table->longText('description')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index('requester_id', 'ed_tickets_requester_id_index');
            $table->index('assignee_id', 'ed_tickets_assignee_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
