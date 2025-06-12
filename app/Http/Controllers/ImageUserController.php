<?php

namespace App\Http\Controllers;

use App\Models\ImageUser;
use App\Models\ImagePortadaUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ImageUserController extends Controller
{
    public function updateImage(Request $request, $correo, $isProfile = true)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado',
                'code' => 404
            ], 404);
        }   

        $isProfile = ($isProfile === 'false') ? false : true;
    
        $imageType = $isProfile ? 'profile' : 'portada';
        $imageField = $isProfile ? 'profilePicture' : 'portadaPicture';
        $imageRelation = $isProfile ? 'imagenProfile' : 'imagenPortada';
        $folder = $isProfile ? 'profile_image' : 'portada_image';
        $model = $isProfile ? ImageUser::class : ImagePortadaUser::class;
        $imageFile = $request->file($imageField);
        $imageUsuario = $user->$imageRelation;
    
        try {
            if ($imageUsuario) {
                $imagePath = $imageUsuario->url;
    
                if (Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                }
            };
    
            $filename = 'image_' . $imageType . '_user_' . time() . '.' . $imageFile->getClientOriginalExtension();
            $path = $imageFile->storeAs("images_user/{$folder}", $filename, 'public');
            $relativePath = "images_user/{$folder}/" . $filename;
    
            if ($imageUsuario) {
                $imageUsuario->update([
                    'url' => $relativePath,
                    'tamaño' => $imageFile->getSize(),
                    'nombre' => $imageFile->getClientOriginalName(),
                    'extension' => $imageFile->getClientOriginalExtension(),
                ]);
            } else {
                $model::create([
                    'id_usuario' => $user->id,
                    'url' => $relativePath,
                    'tamaño' => $imageFile->getSize(),
                    'nombre' => $imageFile->getClientOriginalName(),
                    'extension' => $imageFile->getClientOriginalExtension(),
                ]);
            }
    
            return response()->json(['message' => 'Imagen actualizada con éxito', 'ispro' => $isProfile], 200);
    
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar la imagen', 'error' => $e->getMessage()], 500);
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

    public function getUserImageById($user_id) {
        $user = User::find($user_id);
        
        if (!$user) {
            return response()->json([
                'message' => 'Publicacion no encontrada',
                'code' => 404
            ], 404);
        }

        $image = ImageUser::where("id_usuario", $user_id)->first();
        
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
        $users = collect($request->users); 
        
        $baseUrl = env('APP_URL');
        
        $usersTransformados = $users->map(function ($user) use ($baseUrl) {
            $user['imagen_profile'] = isset($user['imagen_profile']['url']) 
                ? $baseUrl . "/storage/" . $user['imagen_profile']['url']
                : null;
            
            return $user;
        });
    
        return response()->json([
            'mensaje' => 'Imágenes procesadas',
            "users" => $usersTransformados,
        ]);
    }
}
