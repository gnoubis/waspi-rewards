<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\RewardUserController;
use Illuminate\Support\Facades\Route;

// --- Demo UI auth (password-less "log in as", see AuthController) ---
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/demo-users', [AuthController::class, 'demoUsers']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/auth/user', function () {
    return request()->user();
})->middleware('auth:sanctum');

// --- Posts / comments / likes (WASPI REWARDS domain) ---
Route::get('/posts', [PostController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    Route::post('/comments/{comment}/like', [LikeController::class, 'store']);
    Route::delete('/comments/{comment}/like', [LikeController::class, 'destroy']);
});

// --- Graded endpoint: GET /api/rewards/users?access_token=...&type=...&points=... ---
Route::get('/rewards/users', RewardUserController::class);
