<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{ 
    AuthController, UserController, ImageUserController, 
    PublicacionesController, ImagePublicacionController, 
    ChatController, PlanesController, MercadoPagoController, 
    CmsController, NotificacionesController 
};

// --------------------- AUTENTICACIÓN ---------------------
Route::post('/register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);

Route::get('auth/{provider}/redirect', [AuthController::class, 'redirect']);
Route::get('auth/{provider}/callback', [AuthController::class, 'callback']);
Route::get('auth/extract-jwt', [AuthController::class, 'extract_jwt']);

// --------------------- USUARIOS ---------------------

// Públicos
Route::get('user/obtenerUserCorreo/{correo}', [UserController::class, 'obtenerUserCorreo']);
Route::get('user/obtenerTallas/{correo}', [UserController::class, 'obtenerTallasUser']);
Route::post('user/getUsers/{page}', [UserController::class, 'getUsers']);
Route::post('user/borrarCuenta/{user_id}', [UserController::class, 'borrarCuenta']);

// Protegidos
Route::middleware('jwt.auth')->prefix('user')->group(function () {
    Route::get('crearCodigoVerificacion/{correo}', [UserController::class, 'crearCodigoVerficacion']);
    Route::post('verificarCodigo/{correo}/{codigo}', [UserController::class, 'verficarCodigo']);
    Route::get('obtenerUserToken', [UserController::class, 'obtenerUserToken']);
    Route::post('actualizarPerfil', [UserController::class, 'completarPerfil']);
    Route::post('actualizarTallas', [UserController::class, 'actualizarTallasUser']);
    Route::post('actualizarEstilos', [UserController::class, 'actualizarEstilosUser']);
    Route::post('reportarPublicacion', [UserController::class, 'reportarPublicacion']);
    Route::post('reportarUsuario', [UserController::class, 'reportarUsuario']);
    Route::post('solicitarVerificado', [UserController::class, 'solicitarVerificado']);
});

// Públicos (otros perfiles)
Route::get('user/obtenerReseñas/{correo_user}', [UserController::class, 'obtenerReseñas']);
Route::get('user/obtenerReseñasBasicas/{correo_user}', [UserController::class, 'obtenerReseñasBasicas']);
Route::get('user/obtenerInformacion/{correo_user}', [UserController::class, 'obtenerInformacion']);
Route::get('user/obtenerDescDeVc/{correo_user}', [UserController::class, 'obtenerDescDeVc']);

// --------------------- NOTIFICACIONES ---------------------
Route::middleware('jwt.auth')->get('notificaciones/obtener', [UserController::class, 'obtenerNotificaciones']);


// --------------------- IMÁGENES USUARIOS ---------------------
Route::prefix('userImage')->group(function () {
    Route::post('updateImage/{correo}/{isProfile?}', [ImageUserController::class, 'updateImage']);
    Route::get('getImages/{id}/{portada}', [ImageUserController::class, 'getImages']);
    Route::get('getUserImageById/{user_id}', [ImageUserController::class, 'getUserImageById']);
    Route::post('getImagesById', [ImageUserController::class, 'getImagesById']);
});


// --------------------- PUBLICACIONES ---------------------
Route::prefix('publicaciones')->group(function () {
    // CRUD
    Route::post('crear', [PublicacionesController::class, 'crearPublicacion']);
    Route::post('eliminar/{publicacion_id}', [PublicacionesController::class, 'eliminarPublicacion']);
    Route::post('editar/{publicacion_id}', [PublicacionesController::class, 'editarPublicacion']);

    // Home
    Route::post('getPublicacionesRecomendadas/{page}', [PublicacionesController::class, 'getPublicacionesRecomendadas']);
    Route::post('getPublicacionesGuardadas/{user_id}/{page}', [PublicacionesController::class, 'getPublicacionesGuardadas']);
    Route::post('getPublicacionesExplorar/{user_id}/{page}', [PublicacionesController::class, 'getPublicacionesExplorar']);

    // Perfil
    Route::post('getPublicacionesUser/{user_id}/{userProfile_id}/{page}', [PublicacionesController::class, 'getPublicacionesUser']);
    Route::post('getVentasUser/{user_id}/{userProfile_id}/{page}', [PublicacionesController::class, 'getVentasUser']);
    Route::get('getPublicacionesGuardadasProfile/{page}', [PublicacionesController::class, 'getPublicacionesGuardadasProfile']);
    Route::get('getPublicacionesEnVenta/{page}', [PublicacionesController::class, 'getPublicacionesEnVenta']);
    Route::get('getPublicacionesEnCompra/{page}', [PublicacionesController::class, 'getPublicacionesEnCompra']);
    Route::get('getPublicacionesCompradas/{page}', [PublicacionesController::class, 'getPublicacionesCompradas']);

    // Buscador
    Route::post('getPublicacionesFiltro', [PublicacionesController::class, 'getPublicacionesFiltro']);

    // Detalle y acciones
    Route::get('getPublicacion/{user_id}/{publicacion_id}', [PublicacionesController::class, 'getPublicacion']);
    Route::post('guardados/{publicacion_id}', [PublicacionesController::class, 'guardadosPublicacion']);
    Route::post('finalizarConCalificacion/{publicacion_id}/{comprador?}', [PublicacionesController::class, 'finalizarConCalificacion']);
    Route::post('eliminarOferta', [PublicacionesController::class, 'eliminarOferta']);
    Route::post('impulsar/{publicacion_id}', [PublicacionesController::class, 'impulsarPublicacion']);

});

// Estadísticas
Route::get('estadisticas/getEstadisticas/{periodo}', [PublicacionesController::class, 'getEstadisticas']);

// --------------------- IMÁGENES PUBLICACIÓN ---------------------
Route::prefix('publicacionImage')->group(function () {
    Route::get('getImageById/{publicacion_id}', [ImagePublicacionController::class, 'getPubImageById']);
    Route::get('getFileImageById/{image_id}', [ImagePublicacionController::class, 'getFileImageById']);
    Route::post('updateImage/{publicacion_id}', [ImagePublicacionController::class, 'updateImage']);
    Route::post('getImagesById', [ImagePublicacionController::class, 'getImagesById']);
});


// --------------------- CHAT ---------------------
Route::middleware('jwt.auth')->group(function () {
  Route::get('chat/obtenerChats', [ChatController::class, 'obtenerChats']);
  Route::get('chat/obtenerConversation/{conversation_id}', [ChatController::class, 'obtenerConversation']);
  Route::post('chat/ofertar', [ChatController::class, 'ofertar']);
  Route::post('chat/sendMessage', [ChatController::class, 'sendMessage']);
  Route::post('chat/markAsRead/{conversationId}', [ChatController::class, 'markAsRead']);
});

// --------------------- PLANES ---------------------
Route::middleware('jwt.auth')->prefix('planes')->group(function () {
    Route::get('obtenerPlanes', [PlanesController::class, 'obtenerPlanes']);
    Route::get('obtenerPlanActual', [PlanesController::class, 'obtenerPlanActual']);
    Route::post('cancelarPlan', [PlanesController::class, 'cancelarPlan']);
});


// --------------------- MERCADO PAGO ---------------------
Route::middleware('jwt.auth')->prefix('mercadopago')->group(function () {
    Route::post('create', [MercadoPagoController::class, 'createPreference']);
    Route::post('confirm', [MercadoPagoController::class, 'confirmTransaction']);
});


// --------------------- CMS ---------------------
Route::middleware('jwt.auth')->prefix('cms')->group(function () {
    Route::get('getGeneralData', [CmsController::class, 'getGeneralData']);
    Route::get('getNuevosUsuarios', [CmsController::class, 'getNuevosUsuarios']);
    Route::get('getGanancias', [CmsController::class, 'getGanancias']);
    Route::get('getInversiones', [CmsController::class, 'getInversiones']);
    Route::get('getPublicacionesData', [CmsController::class, 'getPublicacionesData']);
    Route::get('getVentasData', [CmsController::class, 'getVentasData']);
    Route::get('getUsersSolicitudes', [CmsController::class, 'getUsersSolicitudes']);
    Route::get('getReportes', [CmsController::class, 'getReportes']);

    Route::post('createInversion', [CmsController::class, 'createInversion']);
    Route::post('eliminarInversion', [CmsController::class, 'eliminarInversion']);
    Route::post('verificarUsuario', [CmsController::class, 'verificarUsuario']);
    Route::post('rechazarVerificacion', [CmsController::class, 'rechazarVerificacion']);
    Route::post('eliminarReportePub', [CmsController::class, 'eliminarReportePub']);
    Route::post('eliminarReporteUser', [CmsController::class, 'eliminarReporteUser']);
    Route::post('eliminarPub', [CmsController::class, 'eliminarPub']);
    Route::post('eliminarUser', [CmsController::class, 'eliminarUser']);
});

// --------------------- NOTIFICACIONES ACCIONES ---------------------
Route::middleware('jwt.auth')->prefix('notificacion')->group(function () {
    Route::post('recibiste-oferta', [NotificacionesController::class, 'recibisteOferta']);
    Route::post('aceptaste-oferta', [NotificacionesController::class, 'AceptasteOferta']);
    Route::post('oferta-aceptada', [NotificacionesController::class, 'OfertaAceptada']);
    Route::post('oferta-rechazada', [NotificacionesController::class, 'OfertaRechazada']);
});
