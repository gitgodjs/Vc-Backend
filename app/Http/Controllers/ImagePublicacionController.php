<?php

namespace App\Http\Controllers;

use App\Models\Publicacion;
use App\Models\ImagePublicacion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImagePublicacionController extends Controller
{
    /* -------------------------------------------------------------
     * Subida y reemplazo de imágenes de una publicación
     * ------------------------------------------------------------ */
    public function updateImage(Request $request, $publicacion_id)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }
    
        $publicacion = Publicacion::find($publicacion_id);
        if (!$publicacion) {
            return response()->json(['message' => 'Publicación no encontrada'], 404);
        }
    
        if (!$request->hasFile('publicacionPicture')) {
            return response()->json(['message' => 'No se encontraron imágenes nuevas, se mantiene las existentes'], 200);
        }
    
        try {
            // 🔁 Borrar solo si hay nuevas
            ImagePublicacion::where('id_publicacion', $publicacion_id)->each(function ($img) {
                Storage::disk('public')->delete($img->url);
                $img->delete();
            });
    
            foreach ($request->file('publicacionPicture') as $imageFile) {
                $filename = 'image_publicacion_' . now()->timestamp . '_' . uniqid() . '.' . $imageFile->getClientOriginalExtension();
                $relativePath = $imageFile->storeAs('images_publicaciones', $filename, 'public');
    
                ImagePublicacion::create([
                    'id_usuario'     => $publicacion->id_user,
                    'id_publicacion' => $publicacion_id,
                    'url'            => $relativePath,
                    'tamaño'         => $imageFile->getSize(),
                    'nombre'         => $imageFile->getClientOriginalName(),
                    'extension'      => $imageFile->getClientOriginalExtension(),
                ]);
            }
    
            return response()->json(['message' => 'Imágenes actualizadas con éxito'], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al actualizar las imágenes',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    

    /* -------------------------------------------------------------
     * Obtener imagen de perfil o portada de un usuario
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

        return response()->json([
            'mensaje'  => 'Imagen encontrada',
            'imageUrl' => $url,
        ]);
    }

    /* -------------------------------------------------------------
     * Obtener la primera imagen de una publicación por ID
     * ------------------------------------------------------------ */
    public function getPubImageById($publicacion_id)
    {
        $publicacion = Publicacion::find($publicacion_id);
        if (!$publicacion) {
            return response()->json(['message' => 'Publicación no encontrada'], 404);
        }

        $image = ImagePublicacion::where('id_publicacion', $publicacion_id)->first();
        if (!$image) {
            return response()->json(['message' => 'Sin imagen'], 200);
        }

        $url = asset(Storage::disk('public')->url($image->url));

        return response()->json([
            'mensaje'  => 'Imagen encontrada',
            'imageUrl' => $url,
        ]);
    }

    /* -------------------------------------------------------------
     * Transformar array de publicaciones añadiendo URL absolutas
     * ------------------------------------------------------------ */
    public function getImagesById(Request $request)
    {
        $publicaciones = collect($request->publicaciones);

        $publicacionesTransformadas = $publicaciones->map(function ($pub) {
            $pub['imagenUrl'] = isset($pub['imagenUrl']['url'])
                ? asset(Storage::disk('public')->url($pub['imagenUrl']['url']))
                : null;
            return $pub;
        });

        return response()->json([
            'mensaje'       => 'Imágenes procesadas',
            'publicaciones' => $publicacionesTransformadas,
        ]);
    }

    /* -------------------------------------------------------------
     * Devolver binario de la imagen (descarga/preview directa)
     * ------------------------------------------------------------ */
    public function getFileImageById($image_id)
    {
        $imagen = ImagePublicacion::find($image_id);
        if (!$imagen) {
            return response()->json(['message' => 'Imagen no encontrada'], 404);
        }

        $path = storage_path('app/public/' . $imagen->url);
        if (!file_exists($path)) {
            return response()->json(['message' => 'Imagen no encontrada'], 404);
        }

        return response()->file($path, [
            'Content-Type'  => mime_content_type($path),
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
