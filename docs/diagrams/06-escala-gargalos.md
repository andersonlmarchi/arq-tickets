# Análise de gargalos sob alta demanda

Cenário: pico de compras no **mesmo evento** (hot row no PostgreSQL).

```mermaid
flowchart TB
    subgraph Leitura["Tráfego majoritariamente leitura"]
        U1[Milhares de usuários<br/>listando eventos]
        FE1[Frontend estático]
        VS_GET[GET /api/eventos]
        CS_GET[GET catálogo]
        U1 --> FE1 --> VS_GET --> CS_GET
    end

    subgraph Escrita["Hot path crítico - compra"]
        U2[Milhares de cliques<br/>Comprar no mesmo evento]
        FE2[POST /api/compras/iniciar]
        VS_POST[Vendas orquestra]
        CS_POST[POST reservar]
        PG[(PostgreSQL Catálogo<br/>UPDATE na mesma linha eventos.id)]
        U2 --> FE2 --> VS_POST --> CS_POST --> PG
    end

    PG -.->|Contention na linha| GARGALO((Gargalo principal<br/>escrita serializada por evento))

    style GARGALO fill:#ffe6e6
    style PG fill:#fff3cd
```

| Camada | Tipo de carga | Sensibilidade ao pico |
|--------|---------------|------------------------|
| Frontend (assets) | Leitura estática | Baixa - escala com CDN |
| Vendas | CPU + conexões HTTP outbound | Média - escala horizontal |
| Catálogo (reserva) | CPU + **1 UPDATE/ compra** | **Alta** |
| PostgreSQL Catálogo | Lock/row update no `evento_id` | **Muito alta** |
| PostgreSQL Vendas | INSERT por compra confirmada | Média |
