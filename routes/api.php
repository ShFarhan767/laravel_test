<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/api/users', function () {
    return User::select('id', 'name as email', 'created_at as date') // Rename/alias for compatibility
        ->get()
        ->map(function ($user) {
            return [
                'id' => $user->id,
                'email' => $user->email,     // using email field
                'amount' => rand(100, 1000), // dummy amount
                'status' => collect(['success', 'pending', 'processing', 'failed'])->random(),
                'date' => $user->date->format('d/m/Y'),
            ];
        });
});
