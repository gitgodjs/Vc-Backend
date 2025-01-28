<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ImageUserController;


Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('get_credentials_from_token', [AuthController::class, 'get_credentials_from_token']);
});


//cambiar todas la de user que arranquen con user
Route::get('crearCodigoVerificacion/{correo}', [UserController::class, 'crearCodigoVerficacion']);
Route::post('verificarCodigo/{correo}/{codigo}', [UserController::class, 'verficarCodigo']);
Route::get('obtenerUserCorreo/{correo}', [UserController::class, 'obtenerUserCorreo']);
Route::post('user/completarPerfil/{correo}', [UserController::class, 'completarPerfil']);
Route::post('userImage/updateImage/{correo}/{isProfile?}', [ImageUserController::class, 'updateImage']);