<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_training_packages', static function (Blueprint $table): void {
            $table->foreignId('course_id')->nullable()->after('id')
                ->constrained('courses')->nullOnDelete();
        });
        Schema::table('team_package_members', static function (Blueprint $table): void {
            $table->string('status', 24)->default('pending')->after('user_id');
            $table->timestamp('accepted_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('team_package_members', static function (Blueprint $table): void {
            $table->dropColumn(['status', 'accepted_at']);
        });
        Schema::table('team_training_packages', static function (Blueprint $table): void {
            $table->dropConstrainedForeignId('course_id');
        });
    }
};
