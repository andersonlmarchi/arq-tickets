# catalog-service

API FastAPI do catalogo de eventos (estoque). Roda via Docker (sem venv local).

## Endpoints

| Metodo | Path | API Key |
|--------|------|---------|
| GET | `/health` | nao |
| GET | `/api/catalogo/eventos` | sim |
| POST | `/api/catalogo/reservar` | sim |
| POST | `/api/catalogo/devolver` | sim |

Headers: `X-API-Key`, `X-Correlation-Id` (opcional).

## Compose (raiz do monorepo)

Na raiz `arq-tickets/`:

```bash
cp .env.example .env
docker compose up --build
```

Servicos desta etapa: `postgres-catalog`, `catalog-service` (porta **8000** apenas na rede `ticket-net`).

Entrypoint: aguarda PG -> `alembic upgrade head` -> `python -m app.db.seed` -> `uvicorn`.

Seed: eventos 1 Show Rock (100), 2 Festival Jazz (50), 3 Teatro Clássico (5).

Variaveis: ver `catalog-service/.env.example` e `.env.example` na raiz.
