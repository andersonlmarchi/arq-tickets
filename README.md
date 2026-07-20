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
| Catálogo              | Python + FastAPI     | 8000 (rede interna) |
| PostgreSQL (vendas)   | PostgreSQL           | 5433                |
| PostgreSQL (catálogo) | PostgreSQL           | 5434                |


Fluxo de estudo: **Usuário -> Frontend -> Vendas -> Catálogo (reserva) -> pagamento simulado (chave 4 dígitos, 30s) -> Vendas confirma venda -> Frontend**.

## Docker Compose (etapa atual: catálogo)

```bash
cp .env.example .env
docker compose up --build
```

| Servico | Host | Rede interna |
|---------|------|----------------|
| PostgreSQL catálogo | `localhost:5434` | `postgres-catalog:5432` |
| catalog-service | *(nao publicado no host)* | `catalog-service:8000` |

Teste rapido (catálogo so na rede Docker):

```bash
docker compose exec catalog-service python -c "
import urllib.request
req = urllib.request.Request(
    'http://127.0.0.1:8000/api/catalogo/eventos',
    headers={'X-API-Key': 'dev-shared-key-change-me'},
)
print(urllib.request.urlopen(req).read().decode())
"
```

Detalhes: [catalog-service/README.md](./catalog-service/README.md).