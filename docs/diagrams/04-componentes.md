# Componentes e responsabilidades

```mermaid
flowchart TB
    subgraph Frontend["Frontend"]
        F1[UI: listagem e compra]
        F2[Cliente HTTP → Vendas]
        F3[Correlation-ID por requisição]
        F4[Tratamento de erros 409/503/5xx]
    end

    subgraph Sales["Serviço de Vendas"]
        S1[API pública: vendas]
        S2[Orquestração da compra]
        S3[Cliente HTTP → Catálogo<br/>timeout + retry + API Key]
        S4[Persistência de vendas]
        S5[Logs estruturados + exception handler]
        S6[Proxy GET /api/eventos]
    end

    subgraph Catalog["Serviço de Catálogo"]
        C1[API interna: reservar estoque]
        C2[Operação atômica no banco]
        C3[Validação + API Key middleware]
        C4[Logs estruturados + exception handler]
        C5[Seed / manutenção de eventos]
    end

    subgraph Data["Persistência"]
        DB1[(sales_db: vendas)]
        DB2[(catalog_db: eventos)]
    end

    Frontend --> Sales
    Sales --> Catalog
    Sales --> DB1
    Catalog --> DB2
```

| Componente | Responsabilidades |
|------------|-------------------|
| **Frontend** | Exibir eventos; iniciar compra; propagar `X-Correlation-Id`; mapear HTTP para UX; não conhecer Catálogo |
| **Vendas** | Único backend exposto ao browser; validar payload; chamar reserva; gravar venda só após 200 do Catálogo |
| **Catálogo** | Fonte da verdade do estoque; reserva atômica; 409 se insuficiente |
| **DB Vendas** | Registro de vendas confirmadas |
| **DB Catálogo** | Eventos e quantidade em estoque |
