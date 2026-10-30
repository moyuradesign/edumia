<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for users.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', static function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('email', 190);
            $table->string('google_id', 190)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 255);
            $table->string('role', 24)->default('student');
            $table->string('status', 24)->default('active');
            $table->string('locale', 8)->default('en');
            $table->string('avatar_path', 255)->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
            $table->unique('email', 'ed_users_email_unique');
            $table->unique('google_id', 'ed_users_google_id_unique');
            $table->index('status', 'ed_users_status_index');
            $table->index('role', 'ed_users_role_index');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
