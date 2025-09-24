<?php

use App\Http\Controllers\AdminAuthController;
use Illuminate\Support\Facades\Route;

// Admin Authentication Routes (not protected)
Route::get('admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login.form');
Route::post('admin/login', [AdminAuthController::class, 'login'])->name('admin.login');
Route::post('admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
