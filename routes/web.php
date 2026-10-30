<?php

use App\Http\Controllers\frontend\HomeController;
use App\Http\Controllers\frontend\CourseController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/en', fn () => redirect()->route('home'));
Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('/courses/{slug}', [CourseController::class, 'show'])->name('courses.show');

Route::prefix('{locale}')
    ->where(['locale' => 'zh|fr|de|ja|km|ko|vi'])
    ->group(function (): void {
        Route::get('/', [HomeController::class, 'index'])->name('localized.home');
        Route::get('/courses', [CourseController::class, 'index'])->name('localized.courses.index');
        Route::get('/courses/{slug}', [CourseController::class, 'localizedShow'])->name('localized.courses.show');
    });
