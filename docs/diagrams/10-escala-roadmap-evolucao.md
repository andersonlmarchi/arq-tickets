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

```mermaid
quadrantChart
    title Onde investir esforço vs. tipo de tráfego
    x-axis Baixo impacto --> Alto impacto
    y-axis Leitura --> Escrita
    quadrant-1 Escrita crítica
    quadrant-2 Leitura crítica
    CDN assets: [0.85, 0.25]
    Redis GET eventos: [0.7, 0.35]
    LB Vendas: [0.55, 0.55]
    LB Catálogo: [0.6, 0.7]
    PgBouncer: [0.75, 0.8]
    UPDATE atômico PG: [0.95, 0.95]
    Rate limit compra: [0.8, 0.75]
```

| Estágio | Problema que endereça | Mantém consistência anti-oversell? |
|---------|------------------------|-----------------------------------|
| Baseline | Aprendizado do fluxo | Sim |
| Nível 1 | Volume de conexões HTTP | Sim |
| Nível 2 | Picos em listagem | Sim (compra sempre no primary) |
| Nível 3 | Saturação DB / thundering herd | Sim |
| Opcional fila | Throughput extremo de reserva | Requer desenho de idempotência/compensação |
