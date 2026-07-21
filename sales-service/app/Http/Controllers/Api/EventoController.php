<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CatalogUnavailableException;
use App\Http\Controllers\Controller;
use App\Services\CatalogClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventoController extends Controller
{
    public function __construct(private readonly CatalogClient $catalogClient) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = (string) $request->attributes->get('correlation_id');

        try {
            $eventos = $this->catalogClient->listEventos($correlationId);
        } catch (CatalogUnavailableException $e) {
            return $this->error('CATALOGO_INDISPONIVEL', $e->getMessage(), 503, $correlationId);
        }

        $data = array_map(static function (array $evento): array {
            return [
                'id' => $evento['id'],
                'nome' => $evento['nome'],
                'estoque_disponivel' => $evento['estoque'],
            ];
        }, $eventos);

        return response()->json(['data' => $data]);
    }

    private function error(string $code, string $message, int $status, string $correlationId): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'correlation_id' => $correlationId,
        ], $status);
    }
}
