<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', static function (Blueprint $table): void {
            $table->boolean('randomize_questions')->default(false);
        });

        Schema::create('quiz_attempt_sessions', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('status', 24)->default('active');
            $table->timestamp('started_at');
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['quiz_id', 'user_id', 'status'], 'ed_quiz_session_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempt_sessions');
        Schema::table('quizzes', static function (Blueprint $table): void {
            $table->dropColumn('randomize_questions');
        });
    }
};
