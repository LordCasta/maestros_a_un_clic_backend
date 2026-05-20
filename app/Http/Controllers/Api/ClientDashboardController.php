<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Http\Resources\ProfessionalResource;
use App\Models\Booking;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Http\Request;

class ClientDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // reservas activas
        $active = Booking::where('client_id', $user->id)->whereIn('status', ['pending','confirmed','on_way'])->latest()->get();

        // favoritos
        $favorites = Favorite::with('professional')->where('client_id', $user->id)->get();

        // profesionales cerca: same commune
        $nearby = User::where('role','professional')->where('commune', $user->commune)->limit(10)->get();

        // categorías populares (top specialties)
        $popular = []; // placeholder: could be computed with queries

        return response()->json([
            'success' => true,
            'data' => [
                'active_bookings' => BookingResource::collection($active),
                'favorites' => ProfessionalResource::collection($favorites->pluck('professional')),
                'nearby_professionals' => ProfessionalResource::collection($nearby),
                'popular_categories' => $popular,
            ],
        ]);
    }
}

