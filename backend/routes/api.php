<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
    
Route::get('/login', function () {
    return response()->json([
        'message' => 'Unauthenticated.'
    ], 401);
})->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users/{username}', [AuthController::class, 'showUser']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/users/post', [AuthController::class, 'posts']);   
    Route::post('/users/follow', [AuthController::class, 'follows']);
    Route::post('/users/private', [AuthController::class, 'private']);
    Route::post('/users/accept', [AuthController::class, 'accept']);
    Route::get('/auth/hp', [AuthController::class, 'hp']);
    Route::post('/auth/delete/{id}', [AuthController::class, 'delete']);
});
