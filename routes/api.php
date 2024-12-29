<?php

use App\Http\Controllers\Api\Admin\UpahController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\KaryawanController;
use Illuminate\Support\Facades\Route;


Route::post('/login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/users/upload-photo', [UserController::class, 'uploadPhoto']);
    Route::post('/users/change-password', [UserController::class, 'changePassword']);
    Route::get('/upah', [UpahController::class, 'index']);
    Route::get('/upah/{id}', [UpahController::class, 'show']);
});


// Routes (Admin)
Route::middleware(['auth:sanctum', 'admin'])->group(function () {

    // User Management
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('users.index');
        Route::post('/', [UserController::class, 'store'])->name('users.store');
        Route::get('/{id}', [UserController::class, 'show'])->name('users.show');
        Route::put('/{id}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Karyawan Management
    Route::prefix('karyawan')->group(function () {
        Route::get('/', [KaryawanController::class, 'index'])->name('karyawan.index');
        Route::post('/', [KaryawanController::class, 'store'])->name('karyawan.store');
        Route::get('/{id}', [KaryawanController::class, 'show'])->name('karyawan.show');
        Route::put('/{id}', [KaryawanController::class, 'update'])->name('karyawan.update');
        Route::delete('/{id}', [KaryawanController::class, 'destroy'])->name('karyawan.destroy');
        Route::get('/search', [KaryawanController::class, 'search'])->name('karyawan.search');
    });

    //Upah Managemet
    Route::prefix('upah')->group(function () {
        Route::post('/', [UpahController::class, 'store'])->name('upah.store');
        Route::put('/{id}', [UpahController::class, 'update'])->name('upah.update');
        Route::delete('/{id}', [UpahController::class, 'destroy'])->name('upah.destroy');
    });

});
