<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for ticket_messages.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_messages', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->longText('body');
            $table->string('attachment_path', 255)->nullable();
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
            $table->index('ticket_id', 'ed_ticket_messages_ticket_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
    }
};
