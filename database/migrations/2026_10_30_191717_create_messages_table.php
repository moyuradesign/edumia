<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for messages.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('thread_id')->constrained('message_threads')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index('thread_id', 'ed_messages_thread_id_index');
            $table->index('sender_id', 'ed_messages_sender_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
