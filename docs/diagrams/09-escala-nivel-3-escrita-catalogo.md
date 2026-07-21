# Evolução Nível 3 - Proteção do gargalo de escrita (Catálogo)

**Objetivo:** maximizar reservas/segundo no primary e proteger o sistema de overload.

```mermaid
flowchart TB
    U[Usuários]
    LB[Load Balancer]

    subgraph Edge["Borda - Vendas"]
        VS1[Vendas]
        RL[Rate limit<br/>middleware / API Gateway<br/>por IP ou token]
    end

    subgraph Catalog["Catálogo"]
        CS1[Catálogo #1]
        CS2[Catálogo #N]
    end

    POOL[PgBouncer<br/>transaction pooling]
    PG[(PostgreSQL primary<br/>statement_timeout<br/>índice PK eventos.id)]

    U --> LB --> RL --> VS1
    VS1 --> CS1 & CS2
    CS1 & CS2 --> POOL --> PG

    subgraph Opcional[Evolução arquitetural]
        Q[Fila de reservas<br/>RabbitMQ / SQS]
        WORKER[Workers consumidores]
        Q --> WORKER --> PG
    end

    CS1 -.->|se latência > SLO| Q
```




| **Onde**              | **Tecnologia**                                                  | **Por quê**                                                      |
| --------------------- | --------------------------------------------------------------- | ---------------------------------------------------------------- |
| Vendas                | **Rate limiting** (middleware Laravel, Kong, Nginx `limit_req`) | Evita thundering herd; fila humana na UX                         |
| Vendas → Catálogo     | **Circuit breaker** (Guzzle)                                    | Falha rápida; protege Catálogo saturado                          |
| Catálogo → DB         | **PgBouncer**                                                   | Multiplexa conexões; primary não explode `max_connections`       |
| PostgreSQL            | **Primary tuning**, `statement_timeout`, fila curta             | UPDATE atômico permanece; sem oversell                           |
| Sharding (conceitual) | Particionar por `evento_id` em DBs diferentes                   | Só se um evento único exceder capacidade de **uma** instância PG |




### Opcional documentado (mudaria o modelo de estudo)

```mermaid
flowchart LR
    A[REST síncrono atual] -->|Alta fila de espera| B[Async reserva via fila]
    B --> C[Resposta 202 + polling ou webhook]
```



Usar fila **só** se a apresentação incluir trade-off: maior throughput vs. fluxo síncrono mais simples. O código de estudo permanece no caminho **A**.