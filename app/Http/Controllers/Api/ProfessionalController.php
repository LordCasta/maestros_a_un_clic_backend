<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfessionalResource;
use App\Repositories\ProfessionalRepository;
use Illuminate\Http\Request;

class ProfessionalController extends Controller
{
    protected ProfessionalRepository $repo;

    public function __construct(ProfessionalRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['commune','specialty_id','min_price','max_price']);
        $perPage = (int) $request->get('per_page', 15);

        $paginator = $this->repo->paginate($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => ProfessionalResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show($id)
    {
        $user = $this->repo->query()->where('id', $id)->firstOrFail();
        return response()->json(['success'=>true,'data'=>new ProfessionalResource($user)]);
    }
}

