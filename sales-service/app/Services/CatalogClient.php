<?php

namespace App\Services;

use App\Exceptions\CatalogConflictException;
use App\Exceptions\CatalogUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CatalogClient
{
    public function listEventos(string $correlationId): array
    {
        $response = $this->send('get', '/api/catalogo/eventos', [], $correlationId);

        return $response->json('data', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function reservar(int $eventoId, int $quantidade, string $correlationId): array
    {
        $response = $this->send('post', '/api/catalogo/reservar', [
            'evento_id' => $eventoId,
            'quantidade' => $quantidade,
        ], $correlationId);

        return $response->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function devolver(int $eventoId, int $quantidade, string $correlationId): array
    {
        $response = $this->send('post', '/api/catalogo/devolver', [
            'evento_id' => $eventoId,
            'quantidade' => $quantidade,
        ], $correlationId);

        return $response->json();
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function send(string $method, string $path, array $body, string $correlationId): Response
    {
        $config = config('services.catalog');
        $url = rtrim((string) $config['base_url'], '/').$path;
        $timeoutSeconds = ((int) $config['timeout_ms']) / 1000;
        $maxAttempts = 1 + (int) $config['retry_count'];

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $client = Http::withHeaders([
                    'X-API-Key' => $config['api_key'],
                    'X-Correlation-Id' => $correlationId,
                    'Accept' => 'application/json',
                ])->timeout($timeoutSeconds);

                $response = $method === 'get'
                    ? $client->get($url)
                    : $client->post($url, $body);

                if ($response->status() === 409) {
                    throw new CatalogConflictException(
                        (string) ($response->json('message') ?? 'Nao ha ingressos disponiveis para este evento.')
                    );
                }

                if ($response->successful()) {
                    Log::info('catalog_request_ok', [
                        'path' => $path,
                        'status' => $response->status(),
                    ]);

                    return $response;
                }

                if ($this->shouldRetry($response->status()) && $attempt < $maxAttempts) {
                    usleep(100_000);

                    continue;
                }

                if ($this->shouldRetry($response->status()) || $response->serverError()) {
                    throw new CatalogUnavailableException();
                }

                $response->throw();
            } catch (CatalogConflictException $e) {
                throw $e;
            } catch (ConnectionException $e) {
                if ($attempt < $maxAttempts) {
                    usleep(100_000);

                    continue;
                }

                throw CatalogUnavailableException::from($e);
            } catch (CatalogUnavailableException $e) {
                throw $e;
            }
        }

        throw new CatalogUnavailableException();
    }

    private function shouldRetry(int $status): bool
    {
        return in_array($status, [502, 503, 504], true);
    }
}
