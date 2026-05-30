<?php

namespace App\Http\Controllers;

use App\Models\Container;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class ContainerController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: "/api/v1/gateway/containers",
        summary: "Ambil seluruh data kontainer",
        security: [["bearerAuth" => []]],
        tags: ["Containers (Gateway)"],
        responses: [
            new OA\Response(response: 200, description: "Data berhasil ditarik"),
            new OA\Response(response: 401, description: "Akses ditolak")
        ]
    )]
    #[OA\Get(
        path: "/api/v1/gateway/containers/search",
        summary: "Pencarian kontainer dengan parameter filter",
        security: [["bearerAuth" => []]],
        tags: ["Containers (Gateway)"],
        parameters: [
            new OA\Parameter(name: "type", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "min_weight", in: "query", required: false, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Hasil pencarian berhasil ditarik")
        ]
    )]
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

    #[OA\Post(
        path: "/api/v1/gateway/containers",
        summary: "Registrasi kontainer baru (Admin Eksklusif)",
        security: [["bearerAuth" => []]],
        tags: ["Containers (Gateway)"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["container_id", "waste_type", "weight_kg"],
                properties: [
                    new OA\Property(property: "container_id", type: "string", example: "WH12345"),
                    new OA\Property(property: "waste_type", type: "string", example: "Plastic"),
                    new OA\Property(property: "weight_kg", type: "integer", example: 500)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Kontainer terdaftar"),
            new OA\Response(response: 403, description: "Akses Ditolak (Forbidden Role)"),
            new OA\Response(response: 422, description: "Validasi gagal")
        ]
    )]
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

    #[OA\Patch(
        path: "/api/v1/gateway/containers/{id}/archive",
        summary: "Ubah status kontainer menjadi Archived (Admin)",
        security: [["bearerAuth" => []]],
        tags: ["Containers (Gateway)"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Status berhasil diubah"),
            new OA\Response(response: 403, description: "Akses Ditolak (Forbidden Role)"),
            new OA\Response(response: 404, description: "Kontainer tidak ditemukan")
        ]
    )]
    public function archive($id)
    {
        $container = Container::find($id);
        if (!$container) return $this->errorResponse('Container not found', 404);
        $container->update(['status' => 'Archived']);
        return $this->successResponse($container, 'Container archived');
    }

    #[OA\Delete(
        path: "/api/v1/gateway/containers/{id}",
        summary: "Hapus data kontainer (Admin Eksklusif)",
        security: [["bearerAuth" => []]],
        tags: ["Containers (Gateway)"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Kontainer berhasil dihapus"),
            new OA\Response(response: 403, description: "Akses Ditolak (Forbidden Role)")
        ]
    )]
    public function destroy($id)
    {
        $container = Container::find($id);
        if (!$container) return $this->errorResponse('Container not found', 404);
        $container->delete();
        return $this->successResponse(null, 'Container deleted');
    }

    #[OA\Get(
        path: "/api/v1/gateway/containers/{id}/logs",
        summary: "Ambil log perjalanan spesifik kontainer",
        security: [["bearerAuth" => []]],
        tags: ["Containers (Gateway)"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Log berhasil ditarik")
        ]
    )]
    public function logs($id)
    {
        $container = Container::with('trackingLogs')->find($id);
        if (!$container) return $this->errorResponse('Container not found', 404);
        return $this->successResponse($container->trackingLogs);
    }
}