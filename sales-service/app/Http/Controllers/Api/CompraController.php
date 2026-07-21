<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CatalogConflictException;
use App\Exceptions\CatalogUnavailableException;
use App\Http\Controllers\Controller;
use App\Models\PagamentoPendente;
use App\Models\Venda;
use App\Services\CatalogClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CompraController extends Controller
{
    public function __construct(private readonly CatalogClient $catalogClient) {}

    public function iniciar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'evento_id' => ['required', 'integer', 'min:1'],
            'quantidade' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $correlationId = (string) $request->attributes->get('correlation_id');

        try {
            $this->catalogClient->reservar(
                (int) $validated['evento_id'],
                (int) $validated['quantidade'],
                $correlationId,
            );
        } catch (CatalogConflictException $e) {
            return $this->error('ESTOQUE_INSUFICIENTE', $e->getMessage(), 409, $correlationId);
        } catch (CatalogUnavailableException $e) {
            return $this->error('CATALOGO_INDISPONIVEL', $e->getMessage(), 503, $correlationId);
        }

        $prazoSegundos = (int) config('services.pagamento.prazo_segundos', 30);
        $token = (string) Str::uuid();
        $chave = $this->gerarChavePagamento();

        PagamentoPendente::query()->create([
            'id' => $token,
            'evento_id' => $validated['evento_id'],
            'quantidade' => $validated['quantidade'],
            'chave_pagamento' => $chave,
            'tentativas_chave_errada' => 0,
            'status' => PagamentoPendente::STATUS_AGUARDANDO,
            'correlation_id' => $correlationId,
            'expires_at' => Carbon::now()->addSeconds($prazoSegundos),
        ]);

        return response()->json([
            'token' => $token,
            'chave_exibicao' => $chave,
            'expira_em_segundos' => $prazoSegundos,
            'evento_id' => (int) $validated['evento_id'],
            'quantidade' => (int) $validated['quantidade'],
            'correlation_id' => $correlationId,
        ]);
    }

    public function confirmar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'uuid'],
            'chave_digitada' => ['required', 'string', 'size:4'],
        ]);

        $correlationId = (string) $request->attributes->get('correlation_id');
        $pendente = PagamentoPendente::query()->find($validated['token']);

        if (! $pendente || $pendente->status !== PagamentoPendente::STATUS_AGUARDANDO) {
            return $this->error('PAGAMENTO_NAO_ENCONTRADO', 'Pagamento nao encontrado.', 404, $correlationId);
        }

        if ($pendente->expires_at->isPast()) {
            $this->finalizarComDevolucao($pendente, PagamentoPendente::STATUS_EXPIRADO, $correlationId);

            return $this->error('PAGAMENTO_EXPIRADO', 'Tempo para pagamento esgotado.', 410, $correlationId);
        }

        if ($validated['chave_digitada'] !== $pendente->chave_pagamento) {
            return $this->tratarChaveIncorreta($pendente, $correlationId);
        }

        $venda = Venda::query()->create([
            'evento_id' => $pendente->evento_id,
            'quantidade' => $pendente->quantidade,
            'status' => 'confirmada',
            'correlation_id' => $correlationId,
        ]);

        $pendente->update([
            'status' => PagamentoPendente::STATUS_CONFIRMADO,
            'venda_id' => $venda->id,
        ]);

        return response()->json([
            'id' => $venda->id,
            'evento_id' => $venda->evento_id,
            'quantidade' => $venda->quantidade,
            'status' => $venda->status,
            'created_at' => $venda->created_at?->toIso8601String(),
            'correlation_id' => $correlationId,
        ], 201);
    }

    public function cancelar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'uuid'],
        ]);

        $correlationId = (string) $request->attributes->get('correlation_id');
        $pendente = PagamentoPendente::query()->find($validated['token']);

        if (! $pendente || $pendente->status !== PagamentoPendente::STATUS_AGUARDANDO) {
            return $this->error('PAGAMENTO_NAO_ENCONTRADO', 'Pagamento nao encontrado.', 404, $correlationId);
        }

        $this->finalizarComDevolucao($pendente, PagamentoPendente::STATUS_CANCELADO, $correlationId);

        return response()->json([
            'token' => $pendente->id,
            'status' => PagamentoPendente::STATUS_CANCELADO,
            'correlation_id' => $correlationId,
        ]);
    }

    private function tratarChaveIncorreta(PagamentoPendente $pendente, string $correlationId): JsonResponse
    {
        $maxErros = (int) config('services.pagamento.max_erros_chave', 3);
        $novasTentativas = $pendente->tentativas_chave_errada + 1;

        if ($novasTentativas >= $maxErros) {
            $this->finalizarComDevolucao($pendente, PagamentoPendente::STATUS_CHAVE_ESGOTADA, $correlationId);

            return $this->error(
                'CHAVE_INVALIDA',
                'Numero maximo de tentativas de pagamento excedido.',
                400,
                $correlationId,
            );
        }

        $novaChave = $this->gerarChavePagamento();
        $pendente->update([
            'tentativas_chave_errada' => $novasTentativas,
            'chave_pagamento' => $novaChave,
        ]);

        return response()->json([
            'code' => 'CHAVE_INCORRETA',
            'message' => 'Chave de pagamento incorreta. Nova chave gerada.',
            'chave_exibicao' => $novaChave,
            'tentativas_restantes' => $maxErros - $novasTentativas,
            'correlation_id' => $correlationId,
        ], 422);
    }

    private function finalizarComDevolucao(
        PagamentoPendente $pendente,
        string $status,
        string $correlationId,
    ): void {
        try {
            $this->catalogClient->devolver(
                (int) $pendente->evento_id,
                (int) $pendente->quantidade,
                $correlationId,
            );
        } catch (CatalogUnavailableException $e) {
            Log::error('devolver_falhou', [
                'pagamento_id' => $pendente->id,
                'message' => $e->getMessage(),
            ]);
        }

        $pendente->update(['status' => $status]);
    }

    private function gerarChavePagamento(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
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
