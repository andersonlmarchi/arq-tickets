# sales-service (Laravel 13 / PHP 8.4)

API publica de vendas. Orquestra compras e chama o catalogo via HTTP.

## Endpoints (`/api`)

| Metodo | Path | Descricao |
|--------|------|-----------|
| GET | `/health` | Liveness |
| GET | `/eventos` | Proxy do catalogo |
| POST | `/compras/iniciar` | Reserva + sessao pagamento |
| POST | `/compras/confirmar` | Confirma chave + grava venda |
| POST | `/compras/cancelar` | Devolve estoque |

Headers: `X-Correlation-Id` (opcional).

## Compose

Subir a partir da raiz do monorepo (`docker compose up --build`). Servicos: `postgres-sales`, `sales-service` (**8080** no host), alem do catalogo.

Teste rapido:

```bash
curl -s http://localhost:8080/api/health
curl -s http://localhost:8080/api/eventos
curl -s -X POST http://localhost:8080/api/compras/iniciar \
  -H 'Content-Type: application/json' \
  -d '{"evento_id":1,"quantidade":1}'
```

Variaveis: arquivo **`.env` na raiz do monorepo** (ver `.env.example`). O Compose injeta com `env_file`.
