<?php

namespace App\Http\Controllers;

use App\Models\ImageUser;
use App\Models\ImagePortadaUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageUserController extends Controller
{
    /* -------------------------------------------------------------
     * Subir / reemplazar imagen de perfil o portada de usuario
     * ------------------------------------------------------------ */
    public function updateImage(Request $request, $correo, $isProfile = true)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $isProfile     = $isProfile !== 'false';
        $imageType     = $isProfile ? 'profile'  : 'portada';
        $imageField    = $isProfile ? 'profilePicture' : 'portadaPicture';
        $imageRelation = $isProfile ? 'imagenProfile'   : 'imagenPortada';
        $folder        = $isProfile ? 'profile_image'   : 'portada_image';
        $model         = $isProfile ? ImageUser::class  : ImagePortadaUser::class;
        $imageFile     = $request->file($imageField);
        $imageUsuario  = $user->$imageRelation;

        if (!$imageFile) {
            return response()->json(['message' => 'Archivo no recibido'], 400);
        }

        try {
            /* 🔄 Borrar imagen previa si existe */
            if ($imageUsuario && Storage::disk('public')->exists($imageUsuario->url)) {
                Storage::disk('public')->delete($imageUsuario->url);
            }

            /* 📤 Guardar nueva imagen */
            $filename     = "image_{$imageType}_user_" . time() . '.' . $imageFile->getClientOriginalExtension();
            $relativePath = $imageFile->storeAs("images_user/{$folder}", $filename, 'public');

            $data = [
                'url'       => $relativePath,
                'tamaño'    => $imageFile->getSize(),
                'nombre'    => $imageFile->getClientOriginalName(),
                'extension' => $imageFile->getClientOriginalExtension(),
            ];

            $imageUsuario
                ? $imageUsuario->update($data)
                : $model::create($data + ['id_usuario' => $user->id]);

            return response()->json(['message' => 'Imagen actualizada con éxito'], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar la imagen', 'error' => $e->getMessage()], 500);
        }
    }

    /* -------------------------------------------------------------
     * Obtener imagen de perfil o portada por ID de usuario
     * ------------------------------------------------------------ */
    public function getImages($id, $portada = false)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $image = $portada ? $user->imagenPortada : $user->imagenProfile;
        if (!$image) {
            return response()->json(['message' => 'Sin imagen'], 200);
        }

        $url = asset(Storage::disk('public')->url($image->url));
        return response()->json(['mensaje' => 'Imagen encontrada', 'imageUrl' => $url]);
    }

    /* -------------------------------------------------------------
     * Obtener imagen de perfil por ID de usuario (única)
     * ------------------------------------------------------------ */
    public function getUserImageById($user_id)
    {
        $user = User::find($user_id);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $image = ImageUser::where('id_usuario', $user_id)->first();
        if (!$image) {
            return response()->json(['message' => 'Sin imagen'], 200);
        }

        $url = asset(Storage::disk('public')->url($image->url));
        return response()->json(['mensaje' => 'Imagen encontrada', 'imageUrl' => $url]);
    }

    /* -------------------------------------------------------------
     * Transformar array de usuarios añadiendo URL absolutas
     * ------------------------------------------------------------ */
    public function getImagesById(Request $request)
    {
        $users = collect($request->users);

        $transformados = $users->map(function ($u) {
            $u['imagen_profile'] = isset($u['imagen_profile']['url'])
                ? asset(Storage::disk('public')->url($u['imagen_profile']['url']))
                : null;
            return $u;
        });

        return response()->json([
            'mensaje' => 'Imágenes procesadas',
            'users'   => $transformados,
        ]);
    }
}
