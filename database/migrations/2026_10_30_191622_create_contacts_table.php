<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for contacts.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', static function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('email', 190);
            $table->string('subject', 190)->nullable();
            $table->text('message');
            $table->string('status', 24)->default('new');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->index('status', 'ed_contacts_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
