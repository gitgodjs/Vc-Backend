<?php

namespace App\Http\Controllers;

use App\Models\Publicacion;
use Illuminate\Http\Request;
use App\Models\ImagePublicacion;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ImagePublicacionController extends Controller
{
    public function updateImage(Request $request, $publicacion_id)
    {
        $user = auth()->user();
    
        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado',
                'code' => 404
            ], 404);
        }
    
        $publicacion = Publicacion::find($publicacion_id);
        if (!$publicacion) {
            return response()->json([
                'message' => 'Publicación no encontrada',
                'code' => 404
            ], 404);
        }
    
        try {
            $previousImages = ImagePublicacion::where('id_publicacion', $publicacion_id)->get();
    
            foreach ($previousImages as $image) {
                if (Storage::disk('public')->exists($image->url)) {
                    Storage::disk('public')->delete($image->url); 
                }
    
                $image->delete();
            };
    
            if ($request->hasFile('publicacionPicture')) {
                $images = $request->file('publicacionPicture');
    
                foreach ($images as $imageFile) {
                    $filename = 'image_publicacion_' . time() . '_' . uniqid() . '.' . $imageFile->getClientOriginalExtension();
                    $path = $imageFile->storeAs('images_publicaciones', $filename, 'public');
                    $relativePath = 'images_publicaciones/' . $filename;
    
                    ImagePublicacion::create([
                        'id_usuario' => $publicacion->id_user,
                        'id_publicacion' => $publicacion_id,
                        'url' => $relativePath,
                        'tamaño' => $imageFile->getSize(),
                        'nombre' => $imageFile->getClientOriginalName(),
                        'extension' => $imageFile->getClientOriginalExtension(),
                    ]);
                };
    
                return response()->json(['message' => 'Imágenes de portada actualizadas con éxito'], 200);
            } else {
                return response()->json(['message' => 'No se encontraron imágenes'], 400);
            };
    
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar las imágenes de portada', 'error' => $e->getMessage()], 500);
        };
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

    public function getPubImageById($publicacion_id) {
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

    public function getImagesById(Request $request) {
        $publicaciones = collect($request->publicaciones); 
        $baseUrl = env('APP_URL');
        
        $publicacionesTransformadas = $publicaciones->map(function ($pub) use ($baseUrl) {
            $pub['imagenUrl'] = isset($pub['imagenUrl']['url']) 
                ? $baseUrl . "/storage/" . $pub['imagenUrl']['url']
                : null;
            
            return $pub;
        });
    
        return response()->json([
            'mensaje' => 'Imágenes procesadas',
            "publicaciones" => $publicacionesTransformadas,
        ]);
    }

    public function getFileImageById($image_id)
    {
        $imagen = ImagePublicacion::find($image_id);
    
        if(!$imagen){
            return response()->json(['message' => 'Imagen no encontrada'], 404);
        }
    
        $path = storage_path('app/public/' . $imagen->url);
    
        if (!file_exists($path)) {
            return response()->json(['message' => 'Imagen no encontrada', 'path' => $path], 404);
        }
    
        $file = file_get_contents($path);
        $type = mime_content_type($path);
    
        return response($file, 200)->header('Content-Type', $type);
    }

}
