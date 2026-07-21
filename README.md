# arq-tickets - Venda de Ingressos

Repositório de estudo: microsserviços simples, REST síncrono, Docker Compose. 

## Documentação


| Documento                                                             | Conteúdo                                                    |
| --------------------------------------------------------------------- | ----------------------------------------------------------- |
| [Planejamento arquitetural](./docs/PLANEJAMENTO-ARQUITETURA.md)       | Visão, APIs, dados, ADRs                                    |
| [Planejamento de implementação](./docs/PLANEJAMENTO-IMPLEMENTACAO.md) | Roteiro simples: ordem, arquivos, Docker, contratos, testes |
| [Diagramas (Mermaid)](./docs/diagrams/README.md)                      | Baseline + evolução para alta demanda                       |




## Stack (referência)


| Serviço               | Stack                | Porta (dev)         |
| --------------------- | -------------------- | ------------------- |
| Frontend              | React + Vite + Axios | 5173                |
| Vendas                | PHP 8.4 + Laravel    | 8080                |
| Catálogo              | Python + FastAPI     | 8000                |
| PostgreSQL (vendas)   | PostgreSQL           | 5433                |
| PostgreSQL (catálogo) | PostgreSQL           | 5434                |


Fluxo de estudo: **Usuário -> Frontend -> Vendas -> Catálogo (reserva) -> pagamento simulado (chave 4 dígitos, 30s) -> Vendas confirma venda -> Frontend**.

## Docker Compose (stack completa)

Um unico **`.env` na raiz** e carregado em todos os containers via `env_file` do Compose.

```bash
cp .env.example .env
# edite .env se precisar (senhas, etc.)
docker compose up --build
```

Chaves de lab (`APP_KEY`, `CATALOG_API_KEY` / `API_KEY`): valores fixos no `.env.example`, sem rotacao automatica.

| Servico | Host | Rede interna |
|---------|------|----------------|
| PostgreSQL catálogo | `localhost:5434` | `postgres-catalog:5432` |
| PostgreSQL vendas | `localhost:5433` | `postgres-sales:5432` |
| catalog-service | `http://localhost:8000` | `catalog-service:8000` |
| sales-service | `http://localhost:8080` | `sales-service:8080` |
| frontend | `http://localhost:5173` | *(dev server Vite)* |

UI: abra [http://localhost:5173](http://localhost:5173) apos o compose subir. Swagger das APIs: [http://localhost:5173/docs](http://localhost:5173/docs).

```bash
curl -s http://localhost:8080/api/health
curl -s http://localhost:8000/health
curl -s http://localhost:8080/api/eventos
```

Detalhes: [frontend/README.md](./frontend/README.md), [catalog-service/README.md](./catalog-service/README.md), [sales-service/README.md](./sales-service/README.md).