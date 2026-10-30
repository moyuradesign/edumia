<?php
use App\Http\Controllers\Auth\AuthenticatedSessionController as Login;
use App\Http\Controllers\Auth\RegisteredUserController as Register;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Instructor\CourseManagementController as CourseManager;
use App\Http\Controllers\Instructor\LessonManagementController as LessonManager;
use App\Http\Controllers\Instructor\QuizManagementController as QuizManager;
use App\Http\Controllers\Student\EnrollmentController;
use App\Http\Controllers\Student\LearningController;
use App\Http\Controllers\Student\QuizAttemptController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\frontend\CourseController;
use App\Http\Controllers\frontend\HomeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetController;

Route::get('/',[HomeController::class,'index'])->name('home');
Route::get('/en',fn()=>redirect()->route('home'));
Route::get('/courses',[CourseController::class,'index'])->name('courses.index');
Route::get('/courses/{slug}',[CourseController::class,'show'])->name('courses.show');
Route::prefix('{locale}')->where(['locale'=>'zh|fr|de|ja|km|ko|vi'])->group(function() {
 Route::get('/',[HomeController::class,'index'])->name('localized.home');
 Route::get('/courses',[CourseController::class,'index'])->name('localized.courses.index');
 Route::get('/courses/{slug}',[CourseController::class,'localizedShow'])->name('localized.courses.show');
});
Route::middleware('guest')->group(function() {
 Route::get('/login',[Login::class,'create'])->name('login');
 Route::post('/login',[Login::class,'store'])->middleware('throttle:8,1')->name('login.store');
 Route::get('/forgot-password',[PasswordResetController::class,'requestForm'])->name('password.request');
 Route::post('/forgot-password',[PasswordResetController::class,'sendLink'])->middleware('throttle:3,1')->name('password.email');
 Route::get('/reset-password/{token}',[PasswordResetController::class,'resetForm'])->name('password.reset');
 Route::post('/reset-password',[PasswordResetController::class,'reset'])->middleware('throttle:5,1')->name('password.store');
 Route::get('/register',[Register::class,'create'])->name('register');
 Route::post('/register',[Register::class,'store'])->middleware('throttle:5,1')->name('register.store');
});
Route::get('/certificates/verify/{token}',[CertificateController::class,'verify'])->where('token','[A-Za-z0-9]{48}')->name('certificate.verify');
Route::middleware(['auth','active'])->group(function() {
 Route::post('/logout',[Login::class,'destroy'])->name('logout');
 Route::get('/account/password',[PasswordController::class,'edit'])->name('password.edit');
 Route::put('/account/password',[PasswordController::class,'update'])->middleware('throttle:5,1')->name('password.update');
 Route::get('/dashboard',[DashboardController::class,'index'])->name('dashboard');
 Route::post('/courses/{course}/enroll',[EnrollmentController::class,'store'])->middleware('throttle:12,1')->name('enrollments.store');
 Route::get('/learn/{course}',[LearningController::class,'course'])->name('learning.course');
 Route::get('/learn/{course}/lessons/{lesson}',[LearningController::class,'lesson'])->name('learning.lesson');
 Route::post('/learn/{course}/lessons/{lesson}/complete',[LearningController::class,'complete'])->name('learning.complete');
 Route::get('/learn/quizzes/{quiz}',[QuizAttemptController::class,'show'])->name('learning.quiz');
 Route::post('/learn/quizzes/{quiz}',[QuizAttemptController::class,'submit'])->middleware('throttle:10,1')->name('learning.quiz.submit');
 Route::get('/certificates/{certificate}',[CertificateController::class,'show'])->name('certificate.show');
 Route::middleware('role:instructor,admin')->prefix('instructor')->name('instructor.')->group(function(){
   Route::get('/courses/create',[CourseManager::class,'create'])->name('courses.create');
   Route::post('/courses',[CourseManager::class,'store'])->name('courses.store');
   Route::get('/courses/{course}/edit',[CourseManager::class,'edit'])->name('courses.edit');
   Route::put('/courses/{course}',[CourseManager::class,'update'])->name('courses.update');
   Route::delete('/courses/{course}',[CourseManager::class,'destroy'])->name('courses.destroy');
   Route::post('/courses/{course}/submit',[CourseManager::class,'submit'])->name('courses.submit');
   Route::post('/courses/{course}/sections',[LessonManager::class,'section'])->name('sections.store');
   Route::post('/courses/{course}/sections/{section}/lessons',[LessonManager::class,'lesson'])->name('lessons.store');
   Route::put('/courses/{course}/lessons/{lesson}',[LessonManager::class,'updateLesson'])->name('lessons.update');
   Route::post('/courses/{course}/quizzes',[QuizManager::class,'store'])->name('quizzes.store');
   Route::post('/courses/{course}/quizzes/{quiz}/questions',[QuizManager::class,'question'])->name('questions.store');
   Route::post('/courses/{course}/quizzes/{quiz}/publish',[QuizManager::class,'publish'])->name('quizzes.publish');
 });
 Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function(){
   Route::put('/users/{user}',[DashboardController::class,'setRole'])->name('users.update');
   Route::post('/courses/{course}/publish',[CourseManager::class,'publish'])->name('courses.publish');
   Route::post('/courses/{course}/unpublish',[CourseManager::class,'unpublish'])->name('courses.unpublish');
   Route::post('/certificates/{certificate}/revoke',[CertificateController::class,'revoke'])->name('certificates.revoke');
 });
});
