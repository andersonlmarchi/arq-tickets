# Evolução Nível 1 - Escala horizontal (stateless)

**Objetivo:** absorver mais requisições HTTP sem alterar o contrato REST síncrono da compra.

```mermaid
flowchart TB
    U[Usuários]
    CDN[CDN ou Nginx<br/>assets estáticos]
    LB[Load Balancer<br/>Nginx / HAProxy / ALB]

    subgraph VendasPool["Serviço de Vendas - N réplicas"]
        VS1[Vendas #1]
        VS2[Vendas #2]
        VSN[Vendas #N]
    end

    subgraph CatalogPool["Serviço de Catálogo - N réplicas"]
        CS1[Catálogo #1]
        CS2[Catálogo #2]
        CSN[Catálogo #N]
    end

    PGS[(PostgreSQL Vendas<br/>primary)]
    PGC[(PostgreSQL Catálogo<br/>primary)]

    U --> CDN
    U --> LB
    LB --> VS1 & VS2 & VSN
    VS1 & VS2 & VSN --> CS1 & CS2 & CSN
    VS1 & VS2 & VSN --> PGS
    CS1 & CS2 & CSN --> PGC
```

| Onde | Técnica | Efeito |
|------|---------|--------|
| Frontend | Build estático + **CDN** (CloudFront, Cloudflare) | Reduz hit no origin; HTML/JS/CSS no edge |
| Vendas | **Load balancer** + múltiplos containers/pods | Mais requisições paralelas; serviço stateless |
| Catálogo | **Load balancer** + múltiplas instâncias | Mais workers Uvicorn; ainda compartilham **um** primary DB |
| Bancos | Connection pool por instância | Evita esgotar `max_connections` - ver Nível 3 (PgBouncer) |

**Limite deste nível:** réplicas do Catálogo aumentam capacidade de **aceitar** HTTP, mas todas convergem no **mesmo row UPDATE** - o gargalo de escrita permanece.
