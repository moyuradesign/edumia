<?php

declare(strict_types=1);

use App\Http\Controllers\CertificateController;
use App\Http\Controllers\Frontend\CourseController;
use App\Http\Controllers\Frontend\BlogController;
use App\Http\Controllers\Frontend\ContactController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\KnowledgeBaseController;
use App\Http\Controllers\Frontend\EbookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/en', fn () => redirect()->route('home'));

Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('/courses/{slug}', [CourseController::class, 'show'])->name('courses.show');
Route::get('/ebooks', [EbookController::class, 'index'])->name('ebooks.index');
Route::get('/ebooks/{slug}', [EbookController::class, 'show'])->name('ebooks.show');
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/help', [KnowledgeBaseController::class, 'index'])->name('help.index');
Route::get('/help/{slug}', [KnowledgeBaseController::class, 'show'])->name('help.show');
Route::get('/help/{slug}/{topicSlug}', [KnowledgeBaseController::class, 'topic'])->name('help.topic');
Route::get('/contact', [ContactController::class, 'create'])->name('contact.create');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:3,1')->name('contact.store');

Route::prefix('{locale}')
    ->where(['locale' => 'zh|fr|de|ja|km|ko|vi'])
    ->group(function (): void {
        Route::get('/', [HomeController::class, 'index'])->name('localized.home');
        Route::get('/courses', [CourseController::class, 'index'])->name('localized.courses.index');
        Route::get('/courses/{slug}', [CourseController::class, 'localizedShow'])->name('localized.courses.show');
        Route::get('/blog', [BlogController::class, 'index'])->name('localized.blog.index');
        Route::get('/blog/{slug}', [BlogController::class, 'localizedShow'])->name('localized.blog.show');
    });

Route::get('/certificates/verify/{token}', [CertificateController::class, 'verify'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->name('certificate.verify');

// Each audience owns its HTTP routes. Keep public routes above these imports.
require __DIR__.'/auth.php';
require __DIR__.'/student.php';
require __DIR__.'/instructor.php';
require __DIR__.'/admin.php';
