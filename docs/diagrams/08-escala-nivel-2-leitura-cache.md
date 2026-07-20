# Evolução Nível 2 - Cache e read replicas (leituras)

**Objetivo:** tirar pressão de listagens; **nunca** cachear decisão de compra.

```mermaid
flowchart TB
    U[Usuários]
    CDN[CDN - cache GET estáticos]

    LB[Load Balancer Vendas]

    subgraph Vendas["Vendas"]
        VS[Laravel]
        REDIS[(Redis<br/>cache GET /api/eventos<br/>TTL 30-60s)]
    end

    subgraph CatalogRead["Catálogo - leitura"]
        CS_R[FastAPI read path]
        REPLICA[(PostgreSQL<br/>read replica)]
    end

    subgraph CatalogWrite["Catálogo - escrita compra"]
        CS_W[FastAPI reservar]
        PRIMARY[(PostgreSQL<br/>primary)]
    end

    U --> CDN
    U --> LB --> VS
    VS -->|GET eventos cache miss| REDIS
    REDIS -->|miss| CS_R --> REPLICA
    VS -->|POST compra sempre live| CS_W --> PRIMARY

    CS_W -.->|replicação streaming| REPLICA
```

| Onde | Tecnologia | Regra |
|------|------------|-------|
| Frontend | **CDN** | Cache de assets; API continua no Vendas |
| Vendas | **Redis** (ou cache HTTP) | Só `GET /api/eventos`; TTL curto; invalidação por TTL |
| Catálogo | **Read replica** PostgreSQL | `GET /eventos` na réplica; **`POST /reservar` no primary** |
| Compra | Sem cache | Sempre hit no primary via reserva atômica |

**Consistência:** usuário pode ver estoque ligeiramente desatualizado na listagem; no clique em Comprar, a verdade é o **409/200** da reserva.
