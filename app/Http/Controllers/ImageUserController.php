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
            }
    
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
}
