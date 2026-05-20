<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FavoriteResource;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $favorites = Favorite::with('professional')->where('client_id', $user->id)->get();

        return response()->json(['success' => true, 'data' => FavoriteResource::collection($favorites)]);
    }

    public function store(Request $request, $professionalId)
    {
        $user = $request->user();
        // ensure professional exists and role
        $professional = User::where('id', $professionalId)->where('role', 'professional')->firstOrFail();

        $favorite = Favorite::firstOrCreate([
            'client_id' => $user->id,
            'professional_id' => $professional->id,
        ]);

        $favorite->load('professional');

        return response()->json(['success'=>true,'message'=>'Agregado a favoritos','data'=>new FavoriteResource($favorite)],201);
    }

    public function destroy(Request $request, $professionalId)
    {
        $user = $request->user();
        $deleted = Favorite::where('client_id', $user->id)->where('professional_id', $professionalId)->delete();

        return response()->json(['success'=>true,'message'=>'Eliminado de favoritos']);
    }
}

