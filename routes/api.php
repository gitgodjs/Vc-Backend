<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ImageUserController;
use App\Http\Controllers\PublicacionesController;
use App\Http\Controllers\ImagePublicacionController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\PlanesController;

// Principal usuarios
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('get_credentials_from_token', [AuthController::class, 'get_credentials_from_token']);
});

// Data users sin token
Route::get('user/obtenerUserCorreo/{correo}', [UserController::class, 'obtenerUserCorreo']);
Route::get('user/obtenerTallas/{correo}', [UserController::class, 'obtenerTallasUser']);
Route::post('user/getUsers/{page}', [UserController::class, 'getUsers']);
Route::post('user/borrarCuenta/{user_id}', [UserController::class, 'borrarCuenta']);

// Data para uno mismo (users token)
Route::get('user/crearCodigoVerificacion/{correo}', [UserController::class, 'crearCodigoVerficacion']);
Route::post('user/verificarCodigo/{correo}/{codigo}', [UserController::class, 'verficarCodigo']);
Route::get('user/obtenerUserToken', [UserController::class, 'obtenerUserToken']);
Route::post('user/actualizarTallas', [UserController::class, 'actualizarTallasUser']);
Route::post('user/actualizarPerfil', [UserController::class, 'completarPerfil']);


// Imagenes de usuarios
Route::post('userImage/updateImage/{correo}/{isProfile?}', [ImageUserController::class, 'updateImage']);
Route::get('userImage/getImages/{id}/{portada}', [ImageUserController::class, 'getImages'] );
Route::get('userImage/getUserImageById/{user_id}', [ImageUserController::class, 'getUserImageById'] );
Route::post('userImage/getImagesById', [ImageUserController::class, 'getImagesById']);


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
    Route::post('publicaciones/getPublicacionesUser/{user_id}/{userProfile_id}/{page}', [PublicacionesController::class, 'getPublicacionesUser']);
    Route::get('publicaciones/getPublicacionesGuardadasProfile/{user_id}/{userProfile_id}/{page}', [PublicacionesController::class, 'getPublicacionesGuardadasProfile']);
    Route::get('publicaciones/getPublicacionesEnVenta/{page}', [PublicacionesController::class, 'getPublicacionesEnVenta']);

    // Para buscador
    Route::post('publicaciones/getPublicacionesFiltro/{page}', [PublicacionesController::class, 'getPublicacionesFiltro']);
    
    // Para cada publicacion
    Route::get('publicaciones/getPublicacion/{user_id}/{publicacion_id}', [PublicacionesController::class, 'getPublicacion']);
    Route::post('publicaciones/guardados/{publicacion_id}', [PublicacionesController::class, 'guardadosPublicacion']);
    Route::post('publicaciones/getPublicacionesCategoria/{page}', [PublicacionesController::class, 'getPublicacionesCategoria']);

// Imagenes de publicacion
Route::get('publicaciones/getImageById/{publicacion_id}', [ImagePublicacionController::class, 'getPubImageById']);
Route::get('publicacionImage/getFileImageById/{image_id}', [ImagePublicacionController::class, 'getFileImageById'] );
Route::post('publicacionImage/updateImage/{publicacion_id}', [ImagePublicacionController::class, 'updateImage']);
Route::post('publicacionesImage/getImagesById', [ImagePublicacionController::class, 'getImagesById']);


// Chat 
Route::post('chat/ofertar', [ChatController::class, 'ofertar']);
Route::get('chat/obtenerChats', [ChatController::class, 'obtenerChats']);
Route::get('chat/obtenerConversation/{conversation_id}', [ChatController::class, 'obtenerConversation']);

// Ofertas a publicaciones
Route::post('publicaciones/eliminarOferta', [PublicacionesController::class, 'eliminarOferta']);

// Obtener planes o plan
Route::get('planes/obtenerPlanes', [PlanesController::class, 'obtenerPlanes']);
Route::get('planes/obtenerPlanActual', [PlanesController::class, 'obtenerPlanActual']);

// Obtener estadisticas
Route::get('estadisticas/getEstadisticas/{periodo}', [PublicacionesController::class, 'getEstadisticas']);