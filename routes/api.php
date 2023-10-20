<?php

use App\Http\Controllers\UsersController;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function() {
    Route::apiResource('users', UsersController::class);
    Route::get('users', function() {
        return ApiResponse::fail(
            'Validation error',
            JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
            ['The ID is required']
        );
    });
    Route::get('users/regenerate-verification-token/{id}', [UsersController::class, 'regenerateVerificationToken']);
    Route::patch('users/change-password/{id}', [UsersController::class, 'changePassword']);
});
