<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for newsletters.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletters', static function (Blueprint $table): void {
            $table->id();
            $table->string('title', 190);
            $table->string('subject', 190);
            $table->longText('content');
            $table->string('status', 24)->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('status', 'ed_newsletters_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletters');
    }
};
