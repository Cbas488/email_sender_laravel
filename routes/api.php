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

Route::prefix('v1') -> group(function() {
    Route::prefix('users') -> group(function(){
        Route::post('/', [UsersController::class, 'store']);
        Route::put('{id}', [UsersController::class, 'update']);
        Route::delete('{id}', [UsersController::class, 'destroy']);
        Route::patch('change-password/{id}', [UsersController::class, 'changePassword']);
        Route::get('regenerate-verification-token/{id}', [UsersController::class, 'regenerateVerificationToken']);
        Route::post('change-email', [UsersController::class, 'changeEmail']);
        Route::get('verify-account', [UsersController::class, 'verifyAccount']);
        Route::get('{id}', [UsersController::class, 'show']);
        Route::delete('disable-account/{id}', [UsersController::class, 'disableAccount']);
        Route::get('enable-account/{id}', [UsersController::class, 'enableAccount']);
    });
});
