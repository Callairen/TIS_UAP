<?php

namespace App\Http\Controllers;

use App\Models\Container;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class ContainerController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     * path="/api/v1/gateway/containers",
     * tags={"Containers"},
     * summary="Ambil Data Kontainer via Gateway",
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(name="type", in="query", required=false, @OA\Schema(type="string")),
     * @OA\Parameter(name="min_weight", in="query", required=false, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Success"),
     * @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request)
    {
        $query = Container::query();

        if ($request->has('type')) {
            $query->where('waste_type', $request->type);
        }

        if ($request->has('min_weight')) {
            $query->where('weight_kg', '>=', $request->min_weight);
        }

        return $this->successResponse($query->get());
    }

    /**
     * @OA\Post(
     * path="/api/v1/gateway/containers",
     * tags={"Containers"},
     * summary="Tambah Kontainer via Gateway (Admin Only)",
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"container_id","waste_type","weight_kg"},
     * @OA\Property(property="container_id", type="string", example="WH12345"),
     * @OA\Property(property="waste_type", type="string", example="Plastic"),
     * @OA\Property(property="weight_kg", type="integer", example=500)
     * )
     * ),
     * @OA\Response(response=201, description="Container created"),
     * @OA\Response(response=403, description="Forbidden"),
     * @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'container_id' => ['required', 'string', 'unique:containers', 'regex:/^[a-zA-Z]{2}[0-9]{5}$/'],
            'waste_type' => 'required|string',
            'weight_kg' => 'required|numeric|min:10|max:5000',
        ]);

        $validator->sometimes('weight_kg', 'max:1000', function ($input) {
            return strtolower($input->waste_type) === 'chemical';
        });

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $container = Container::create($request->only(['container_id', 'waste_type', 'weight_kg']));

        return $this->successResponse($container, 'Container created', 201);
    }

    public function archive($id)
    {
        $container = Container::find($id);

        if (!$container) {
            return $this->errorResponse('Container not found', 404);
        }

        $container->update(['status' => 'Archived']);

        return $this->successResponse($container, 'Container archived');
    }

    public function destroy($id)
    {
        $container = Container::find($id);

        if (!$container) {
            return $this->errorResponse('Container not found', 404);
        }

        $container->delete();

        return $this->successResponse(null, 'Container deleted');
    }

    public function logs($id)
    {
        $container = Container::with('trackingLogs')->find($id);

        if (!$container) {
            return $this->errorResponse('Container not found', 404);
        }

        return $this->successResponse($container->trackingLogs);
    }
}