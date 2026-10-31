<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Users\MemberController;
use App\Http\Controllers\Admin\Users\InstructorApplicationController;
use App\Http\Controllers\Admin\Courses\CategoryController;
use App\Http\Controllers\Admin\Courses\ReviewModerationController;
use App\Http\Controllers\Admin\Content\MediaController;
use App\Http\Controllers\Admin\Content\BlogController;
use App\Http\Controllers\Admin\Content\KnowledgeBaseController;
use App\Http\Controllers\Admin\Content\EbookPublicationController;
use App\Http\Controllers\Admin\Support\ContactController;
use App\Http\Controllers\Admin\Support\TicketController;
use App\Http\Controllers\Admin\Support\ForumModerationController;
use App\Http\Controllers\Admin\System\GeneralSettingsController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\Instructor\CourseManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::put('/users/{user}', MemberController::class)->name('users.update');
        Route::get('/instructor-applications', [InstructorApplicationController::class, 'index'])->name('instructor-applications.index');
        Route::patch('/instructor-applications/{application}', [InstructorApplicationController::class, 'review'])->name('instructor-applications.review');
        Route::get('/reviews', [ReviewModerationController::class, 'index'])->name('reviews.index');
        Route::patch('/reviews/{review}', [ReviewModerationController::class, 'update'])->name('reviews.update');

        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/settings/general', [GeneralSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings/general', [GeneralSettingsController::class, 'update'])->name('settings.update');

        Route::get('/forums', [ForumModerationController::class, 'index'])->name('forums.index');
        Route::patch('/forums/{forum}', [ForumModerationController::class, 'topic'])->name('forums.update');
        Route::patch('/forum-replies/{reply}', [ForumModerationController::class, 'reply'])->name('forum-replies.update');

        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::patch('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');

        Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::get('/contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show');
        Route::patch('/contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');

        Route::get('/ebooks', [EbookPublicationController::class, 'index'])->name('ebooks.index');
        Route::patch('/ebooks/{ebook}/publication', [EbookPublicationController::class, 'update'])->name('ebooks.publication');

        Route::get('/knowledge-base', [KnowledgeBaseController::class, 'index'])->name('knowledge-base.index');
        Route::get('/knowledge-base/create', [KnowledgeBaseController::class, 'create'])->name('knowledge-base.create');
        Route::post('/knowledge-base', [KnowledgeBaseController::class, 'store'])->name('knowledge-base.store');
        Route::get('/knowledge-base/{knowledgeBase}/edit', [KnowledgeBaseController::class, 'edit'])->name('knowledge-base.edit');
        Route::put('/knowledge-base/{knowledgeBase}', [KnowledgeBaseController::class, 'update'])->name('knowledge-base.update');
        Route::delete('/knowledge-base/{knowledgeBase}', [KnowledgeBaseController::class, 'destroy'])->name('knowledge-base.destroy');
        Route::post('/knowledge-base/{knowledgeBase}/topics', [KnowledgeBaseController::class, 'storeTopic'])->name('knowledge-base.topics.store');
        Route::put('/knowledge-base/{knowledgeBase}/topics/{topic}', [KnowledgeBaseController::class, 'updateTopic'])->name('knowledge-base.topics.update');
        Route::delete('/knowledge-base/{knowledgeBase}/topics/{topic}', [KnowledgeBaseController::class, 'destroyTopic'])->name('knowledge-base.topics.destroy');

        Route::get('/blogs', [BlogController::class, 'index'])->name('blogs.index');
        Route::get('/blogs/create', [BlogController::class, 'create'])->name('blogs.create');
        Route::post('/blogs', [BlogController::class, 'store'])->name('blogs.store');
        Route::get('/blogs/{blog}/edit', [BlogController::class, 'edit'])->name('blogs.edit');
        Route::put('/blogs/{blog}', [BlogController::class, 'update'])->name('blogs.update');
        Route::delete('/blogs/{blog}', [BlogController::class, 'destroy'])->name('blogs.destroy');

        Route::get('/media', [MediaController::class, 'index'])->name('media.index');
        Route::post('/media', [MediaController::class, 'store'])->name('media.store');
        Route::get('/media/{media}/download', [MediaController::class, 'download'])->name('media.download');
        Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');

        Route::post('/courses/{course}/publish', [CourseManagementController::class, 'publish'])->name('courses.publish');
        Route::post('/courses/{course}/unpublish', [CourseManagementController::class, 'unpublish'])->name('courses.unpublish');
        Route::post('/certificates/{certificate}/revoke', [CertificateController::class, 'revoke'])->name('certificates.revoke');
    });
