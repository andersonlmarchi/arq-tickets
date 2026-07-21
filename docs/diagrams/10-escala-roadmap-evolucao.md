# Roadmap de evolução arquitetural (alta demanda)

Visão em estágios - **documentação para apresentação**, não roadmap de produto.

```mermaid
flowchart LR
    subgraph S0["Baseline - lab Docker Compose"]
        B0[3 serviços + 2 PostgreSQL<br/>REST síncrono<br/>UPDATE atômico]
    end

    subgraph S1["Nível 1"]
        B1[CDN + LB<br/>Réplicas Vendas/Catálogo]
    end

    subgraph S2["Nível 2"]
        B2[Redis cache listagem<br/>Read replicas PG]
    end

    subgraph S3["Nível 3"]
        B3[PgBouncer + rate limit<br/>circuit breaker<br/>tuning primary]
    end

    subgraph S4["Opcional teórico"]
        B4[Fila async reserva<br/>ou sharding por evento]
    end

    S0 --> S1 --> S2 --> S3 --> S4
```

Onde investir esforço vs. tipo de tráfego:

```mermaid
flowchart TB
    subgraph Escrita["Escrita crítica - alto impacto"]
        C[UPDATE atômico PG]
        D[Rate limit compra]
        E[PgBouncer]
        F[LB Catálogo]
    end

    subgraph Leitura["Leitura - alto impacto"]
        A[CDN assets]
        B[Redis GET eventos]
    end

    subgraph Meio["Volume HTTP - médio"]
        G[LB Vendas]
    end
```

| Estágio | Problema que endereça | Mantém consistência anti-oversell? |
|---------|------------------------|-----------------------------------|
| Baseline | Aprendizado do fluxo | Sim |
| Nível 1 | Volume de conexões HTTP | Sim |
| Nível 2 | Picos em listagem | Sim (compra sempre no primary) |
| Nível 3 | Saturação DB / thundering herd | Sim |
| Opcional fila | Throughput extremo de reserva | Requer desenho de idempotência/compensação |
