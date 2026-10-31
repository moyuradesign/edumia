<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->foreignId('ebook_id')->nullable()->constrained('ebooks')->nullOnDelete();
            $table->foreignId('bootcamp_id')->nullable()->constrained('bootcamps')->nullOnDelete();
        });
        Schema::table('ebook_purchases', function (Blueprint $table): void {
            $table->unique(['user_id', 'ebook_id'], 'ebook_purchases_user_ebook_unique');
        });
        Schema::table('bootcamp_purchases', function (Blueprint $table): void {
            $table->unique(['user_id', 'bootcamp_id'], 'bootcamp_purchases_user_bootcamp_unique');
        });
    }

    public function down(): void
    {
        Schema::table('bootcamp_purchases', function (Blueprint $table): void {
            $table->dropUnique('bootcamp_purchases_user_bootcamp_unique');
        });
        Schema::table('ebook_purchases', function (Blueprint $table): void {
            $table->dropUnique('ebook_purchases_user_ebook_unique');
        });
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropForeign(['ebook_id']);
            $table->dropForeign(['bootcamp_id']);
            $table->dropColumn(['ebook_id', 'bootcamp_id']);
        });
    }
};
