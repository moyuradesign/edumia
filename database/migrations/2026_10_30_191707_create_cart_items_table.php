<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edumia LMS — fresh-install schema for cart_items.
 * Generated as an original Laravel 13 migration (October 30, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->foreignId('ebook_id')->nullable()->constrained('ebooks')->nullOnDelete();
            $table->foreignId('bootcamp_id')->nullable()->constrained('bootcamps')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
            $table->index('user_id', 'ed_cart_items_user_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
