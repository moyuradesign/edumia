<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blogs', static function (Blueprint $table): void {
            $table->foreignId('featured_media_id')->nullable()->after('category_id')
                ->constrained('media_files')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('blogs', static function (Blueprint $table): void {
            $table->dropConstrainedForeignId('featured_media_id');
        });
    }
};
