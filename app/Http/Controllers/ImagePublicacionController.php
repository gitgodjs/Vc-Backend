<?php

namespace App\Http\Controllers;

use App\Models\Publicacion;
use App\Models\ImagePublicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ImagePublicacionController extends Controller
{
    public function updateImage(Request $request, $user_id, $publicacion_id)
    {
        $user = auth()->user();
    
        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado',
                'code' => 404
            ], 404);
        }
    
        // Verificar si la publicación existe
        $publicacion = Publicacion::find($publicacion_id);
        if (!$publicacion) {
            return response()->json([
                'message' => 'Publicación no encontrada',
                'code' => 404
            ], 404);
        }
    
        try {
            // Verificar si existen archivos en el request
            if ($request->hasFile('publicacionPicture')) {
                // Recoger todas las imágenes subidas
                $images = $request->file('publicacionPicture');
    
                foreach ($images as $imageFile) {
                    // Generar el nombre de archivo y la ruta de almacenamiento
                    $filename = 'image_publicacion_' . time() . '_' . uniqid() . '.' . $imageFile->getClientOriginalExtension();
                    $path = $imageFile->storeAs('images_publicaciones', $filename, 'public');
                    $relativePath = 'images_publicaciones/' . $filename;
    
                    // Crear el nuevo registro para cada imagen
                    ImagePublicacion::create([
                        'id_usuario' => $user_id,
                        'id_publicacion' => $publicacion_id,
                        'url' => $relativePath,
                        'tamaño' => $imageFile->getSize(),
                        'nombre' => $imageFile->getClientOriginalName(),
                        'extension' => $imageFile->getClientOriginalExtension(),
                    ]);
                }
    
                return response()->json(['message' => 'Imágenes de portada actualizadas con éxito'], 200);
            } else {
                return response()->json(['message' => 'No se encontraron imágenes'], 400);
            }
    
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar las imágenes de portada', 'error' => $e->getMessage()], 500);
        }
    }
    
    

    public function getImages($id, $portada = false) {
        $user = User::find($id);
        $image = $portada == false ? $user->imagenProfile : $user->imagenPortada;


        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado',
                'code' => 404
            ], 404);
        }

        $baseUrl = env('APP_URL');
    
        $fullImageUrl = $baseUrl . "/storage/" . $image->url; 

        return response()->json([
            'mensaje' => 'Imagen encontrada',
            'imageUrl' => $fullImageUrl,  
        ]);
    }

    public function getImageById($publicacion_id) {
        $publicacion = Publicacion::find($publicacion_id);
        
        if (!$publicacion) {
            return response()->json([
                'message' => 'Publicacion no encontrada',
                'code' => 404
            ], 404);
        }

        $image = ImagePublicacion::where("id_publicacion", $publicacion_id)->first();
        
        if (!$image) {
            return response()->json([
                'message' => 'No hay imagen',
            ], 200);
        }
        $baseUrl = env('APP_URL');
        $fullImageUrl = $baseUrl . "/storage/" . $image->url; 

        return response()->json([
            'mensaje' => 'Imagen encontrada',
            'imageUrl' => $fullImageUrl,  
        ]);
    }

}
