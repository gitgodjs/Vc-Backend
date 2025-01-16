<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;


Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('get_credentials_from_token', [AuthController::class, 'get_credentials_from_token']);
});

Route::get('obtenerUserCorreo/{correo}', [UserController::class, 'obtenerUserCorreo']);
