<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ImageUserController;
use App\Http\Controllers\PublicacionesController;
use App\Http\Controllers\ImagePublicacionController;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('get_credentials_from_token', [AuthController::class, 'get_credentials_from_token']);
});


//cambiar todas la de user que arranquen con user
Route::get('user/crearCodigoVerificacion/{correo}', [UserController::class, 'crearCodigoVerficacion']);
Route::post('user/verificarCodigo/{correo}/{codigo}', [UserController::class, 'verficarCodigo']);
Route::get('user/obtenerUserCorreo/{correo}', [UserController::class, 'obtenerUserCorreo']);
Route::post('user/actualizarPerfil/{correo}', [UserController::class, 'completarPerfil']);
Route::post('user/actualizarTallas/{correo}', [UserController::class, 'actualizarTallasUser']);
Route::get('user/obtenerTallas/{correo}', [UserController::class, 'obtenerTallasUser']);

Route::post('userImage/updateImage/{correo}/{isProfile?}', [ImageUserController::class, 'updateImage']);
Route::get('userImage/getImages/{id}/{portada}', [ImageUserController::class, 'getImages'] );


Route::post('publicaciones/crear/{user_id}', [PublicacionesController::class, 'crearPublicacion']);
Route::post('publicacionImage/updateImage/{user_id}/{publicacion_id}', [ImagePublicacionController::class, 'updateImage']);
