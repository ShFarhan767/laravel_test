<?php

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserDetailsController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('dataTable', function (Request $request) {
    return Inertia::render('DataTable', [
        'search' => $request->query('search', ''),
        'page' => (int) $request->query('page', 1),
    ]);
})->middleware(['auth', 'verified'])->name('dataTable');

Route::get('/users', [UserController::class, 'index']);
Route::get('/user-details', [UserDetailsController::class, 'index']);
Route::post('/user-details/{id}', [UserDetailsController::class, 'update']);
Route::delete('/user-details/{id}', [UserDetailsController::class, 'destroy']);

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
