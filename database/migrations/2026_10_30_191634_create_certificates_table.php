<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for certificates.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('certificate_number', 100);
            $table->string('verification_token', 100);
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('pdf_path', 255)->nullable();
            $table->timestamps();
            $table->unique('certificate_number', 'ed_certificates_certificate_number_unique');
            $table->unique('verification_token', 'ed_certificates_verification_token_unique');
            $table->unique(['course_id', 'user_id'], 'ed_certificates_course_id_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
