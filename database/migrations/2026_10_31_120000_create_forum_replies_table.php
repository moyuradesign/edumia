<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_replies', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('forum_id')->constrained('forums')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->longText('body');
            $table->string('status', 24)->default('visible');
            $table->timestamps();
            $table->index(['forum_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_replies');
    }
};
