<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ImageUserController;
use App\Http\Controllers\PublicacionesController;
use App\Http\Controllers\ImagePublicacionController;

// Principal usuarios
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('get_credentials_from_token', [AuthController::class, 'get_credentials_from_token']);
});

// Especificos usuarios
Route::get('user/crearCodigoVerificacion/{correo}', [UserController::class, 'crearCodigoVerficacion']);
Route::post('user/verificarCodigo/{correo}/{codigo}', [UserController::class, 'verficarCodigo']);
Route::get('user/obtenerUserCorreo/{correo}', [UserController::class, 'obtenerUserCorreo']);
Route::post('user/actualizarPerfil/{correo}', [UserController::class, 'completarPerfil']);
Route::post('user/actualizarTallas/{correo}', [UserController::class, 'actualizarTallasUser']);
Route::get('user/obtenerTallas/{correo}', [UserController::class, 'obtenerTallasUser']);

// Imagenes de usuarios
Route::post('userImage/updateImage/{correo}/{isProfile?}', [ImageUserController::class, 'updateImage']);
Route::get('userImage/getImages/{id}/{portada}', [ImageUserController::class, 'getImages'] );



// Generales de publicacion 
Route::post('publicaciones/crear', [PublicacionesController::class, 'crearPublicacion']);
Route::post('publicaciones/eliminar/{publicacion_id}', [PublicacionesController::class, 'eliminarPublicacion']);
Route::post('publicaciones/editar/{publicacion_id}', [PublicacionesController::class, 'editarPublicacion']);
  // Especificas de publicacion

    // Para home
    Route::post('publicaciones/getPublicacionesRecomendadas/{user_id}/{page}', [PublicacionesController::class, 'getPublicacionesRecomendadas']);
    Route::post('publicaciones/getPublicacionesGuardadasHome/{user_id}/{page}', [PublicacionesController::class, 'getPublicacionesGuardadasHome']);
    Route::post('publicaciones/getPublicacionesExplorar/{user_id}/{page}', [PublicacionesController::class, 'getPublicacionesExplorar']);

    // Para perfil
    Route::get('publicaciones/getPublicacionesUser/{user_id}/{userProfile_id}/{page}', [PublicacionesController::class, 'getPublicacionesUser']);
    Route::get('publicaciones/getPublicacionesGuardadasProfile/{user_id}/{userProfile_id}/{page}', [PublicacionesController::class, 'getPublicacionesGuardadasProfile']);

    Route::get('publicaciones/getPublicacion/{user_id}/{publicacion_id}', [PublicacionesController::class, 'getPublicacion']);

    // Para cada publicacion
    Route::post('publicaciones/guardados/{publicacion_id}', [PublicacionesController::class, 'guardadosPublicacion']);


// Imagenes de publicacion
Route::get('publicaciones/getImageById/{publicacion_id}', [ImagePublicacionController::class, 'getImageById']);
Route::get('publicacionImage/getFileImageById/{image_id}', [ImagePublicacionController::class, 'getFileImageById'] );
Route::post('publicacionImage/updateImage/{publicacion_id}', [ImagePublicacionController::class, 'updateImage']);
