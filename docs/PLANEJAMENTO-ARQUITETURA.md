# Sistema de Venda de Ingressos - Planejamento Arquitetural

> **Natureza do repositório:** projeto de **arquitetura**.
> **Modelo de código:** monorepo (`arq-tickets`).  
> **Diagramas:** pasta `[diagrams/](./diagrams/README.md)` (Mermaid, um arquivo por diagrama).

---

## 1. Visão geral

Três microsserviços com **HTTP REST síncrono**, **PostgreSQL por serviço**, ambiente local via **Docker Compose**. Prioridade: **consistência** (nunca registrar venda sem confirmação de estoque) em detrimento de disponibilidade parcial.

### 1.1 Princípios


| Princípio            | Aplicação                                                                       |
| -------------------- | ------------------------------------------------------------------------------- |
| Baixo acoplamento    | Front → Vendas → Catálogo; sem acesso cruzado a bancos                          |
| Consistência         | Reserva atômica no Catálogo **antes** do INSERT em vendas                       |
| Simplicidade         | Sem filas, sagas ou event bus na **implementação de referência**                |
| Escalabilidade       | Diagramas de evolução em `[diagrams/](./diagrams/README.md)` - não implementado |
| Segurança em camadas | Catálogo só na rede interna; API Key entre serviços                             |


---



## 2. Diagramas - baseline


| #   | Diagrama                   | Arquivo                                                                         |
| --- | -------------------------- | ------------------------------------------------------------------------------- |
| 1   | Arquitetura geral          | [01-arquitetura-geral.md](./diagrams/01-arquitetura-geral.md)                   |
| 2   | Fluxo de compra (sequence) | [02-fluxo-compra-sequencia.md](./diagrams/02-fluxo-compra-sequencia.md)         |
| 3   | Fluxo de falha (sequence)  | [03-fluxo-falha-sequencia.md](./diagrams/03-fluxo-falha-sequencia.md)           |
| 4   | Componentes                | [04-componentes.md](./diagrams/04-componentes.md)                               |
| 5   | Implantação Docker Compose | [05-implantacao-docker-compose.md](./diagrams/05-implantacao-docker-compose.md) |
| 12  | Compra + pagamento simulado | [12-fluxo-pagamento-simulado.md](./diagrams/12-fluxo-pagamento-simulado.md) |


**Notas (arquitetura geral):**

- Frontend usa apenas `VITE_SALES_API_URL` (Serviço de Vendas).
- Catálogo sem exposição ao browser (rede Docker + API Key).
- Listagem de eventos via **Vendas** (`GET /api/eventos` proxy interno), mantendo Catálogo privado.

**Regras (fluxo de falha):**

- Timeout padrão **2000 ms**; **1 retry** em falhas transitórias (não em 409).
- Sem confirmação de reserva → nenhum INSERT em `vendas` → **503** ao frontend.

---



## 3. Diagramas - evolução para alta demanda

Cenário **hipotético** para apresentação: abertura de vendas com milhares de acessos simultâneos. A stack de estudo continua no Compose; os diagramas mostram **técnicas e onde aplicá-las**.


| #   | Tema                                            | Arquivo                                                                                   |
| --- | ----------------------------------------------- | ----------------------------------------------------------------------------------------- |
| 6   | Mapa de gargalos (leitura vs escrita)           | [06-escala-gargalos.md](./diagrams/06-escala-gargalos.md)                                 |
| 7   | Nível 1: CDN + load balancer + réplicas         | [07-escala-nivel-1-horizontal.md](./diagrams/07-escala-nivel-1-horizontal.md)             |
| 8   | Nível 2: Redis + read replicas (só leitura)     | [08-escala-nivel-2-leitura-cache.md](./diagrams/08-escala-nivel-2-leitura-cache.md)       |
| 9   | Nível 3: PgBouncer, rate limit, circuit breaker | [09-escala-nivel-3-escrita-catalogo.md](./diagrams/09-escala-nivel-3-escrita-catalogo.md) |
| 10  | Roadmap em estágios (apresentação)              | [10-escala-roadmap-evolucao.md](./diagrams/10-escala-roadmap-evolucao.md)                 |




### 3.1 Síntese para oral

1. **Quem sente primeiro:** Catálogo + PostgreSQL do catálogo (UPDATE na mesma linha do evento).
2. **Nível 1:** escalar HTTP (CDN, LB, réplicas stateless) - não remove gargalo de escrita.
3. **Nível 2:** aliviar **listagens** (CDN, Redis, read replica); compra sempre no primary.
4. **Nível 3:** proteger o primary (PgBouncer, rate limit, circuit breaker, tuning).
5. **Opcional teórico:** fila async ou sharding - trade-off de complexidade; **fora** do REST síncrono do lab.

Índice completo: `[docs/diagrams/README.md](./diagrams/README.md)`.

---



## 4. Estrutura do monorepo

```text
arq-tickets/
│
├── docs/
│   ├── PLANEJAMENTO-ARQUITETURA.md     # este documento
│   └── diagrams/                       # todos os diagramas Mermaid
│       ├── README.md
│       ├── 01-arquitetura-geral.md
│       ├── …
│       └── 10-escala-roadmap-evolucao.md
│
├── frontend/
├── sales-service/
├── catalog-service/
├── docker-compose.yml
├── .env.example
└── README.md
```

Estrutura interna dos serviços (Controllers, `CatalogClient`, middleware de correlation-id, etc.) segue o mesmo desenho já definido nas seções de API e dados abaixo - detalhamento de pastas omitido aqui para foco em arquitetura.

### Docker, env, portas, rede


| Serviço             | Host (dev)       | Container |
| ------------------- | ---------------- | --------- |
| Frontend            | 5173             | 5173      |
| Vendas              | 8080             | 8080      |
| Catálogo            | *(não publicar)* | 8000      |
| PostgreSQL Vendas   | 5433             | 5432      |
| PostgreSQL Catálogo | 5434             | 5432      |


Rede: `ticket-net` (bridge). Volumes: `postgres_sales_data`, `postgres_catalog_data`.

Variáveis principais: `VITE_SALES_API_URL`, `CATALOG_SERVICE_URL`, `CATALOG_API_KEY`, `CATALOG_HTTP_TIMEOUT_MS`, `CATALOG_HTTP_RETRY_COUNT`, credenciais PG por serviço.

---



## 5. Contratos de API

Convenções: `application/json`; header `X-Correlation-Id`; erros `{ "code", "message", "correlation_id" }`.

### 5.1 Frontend → Vendas

Fluxo em **duas fases** (pagamento simulado). Diagrama: [12-fluxo-pagamento-simulado.md](./diagrams/12-fluxo-pagamento-simulado.md).

#### `POST /api/compras/iniciar`

Request: `{ "evento_id": 1, "quantidade": 2 }`

Reserva estoque no Catálogo e abre sessão de pagamento (chave 4 dígitos, prazo **30s**).

| Status | code | Quando |
| ------ | ---- | ------ |
| 200 | - | Pendente criado; corpo inclui `token`, `chave_exibicao` (4 dígitos), `expira_em_segundos` |
| 400 | `VALIDATION_ERROR` | Payload inválido |
| 409 | `ESTOQUE_INSUFICIENTE` | Catálogo conflito |
| 503 | `CATALOGO_INDISPONIVEL` | Timeout/retry esgotado |

#### `POST /api/compras/confirmar`

Request: `{ "token": "uuid", "chave_digitada": "4829" }`

| Status | code | Quando |
| ------ | ---- | ------ |
| 201 | - | Venda confirmada |
| 422 | `CHAVE_INCORRETA` | 1a ou 2a chave errada; nova `chave_exibicao` + `tentativas_restantes` |
| 400 | `CHAVE_INVALIDA` | 3a chave errada; estoque devolvido; sessão encerrada |
| 410 | `PAGAMENTO_EXPIRADO` | Prazo esgotado; estoque devolvido |
| 404 | `PAGAMENTO_NAO_ENCONTRADO` | Token inválido |

Resposta **422** (exemplo): `{ "code": "CHAVE_INCORRETA", "chave_exibicao": "7391", "tentativas_restantes": 1, "correlation_id": "..." }` - front substitui a chave exibida.

#### `POST /api/compras/cancelar`

Request: `{ "token": "uuid" }` - timeout no front, botão cancelar ou fechar modal.

| Status | code | Quando |
| ------ | ---- | ------ |
| 200 | - | Pendente encerrado; estoque devolvido no Catálogo |
| 404 | `PAGAMENTO_NAO_ENCONTRADO` | Token inválido ou já finalizado |

`GET /api/eventos` - proxy interno ao Catálogo; resposta com lista de eventos (estoque opcional na UI).

### 5.2 Vendas → Catálogo (interno)

Header: `X-API-Key`.

`POST /api/catalogo/reservar` - 200 reserva OK; 409 estoque; 401 API Key; 404 evento.

`POST /api/catalogo/devolver` - devolve quantidade ao estoque (compensação após pagamento expirado/cancelado). Body: `{ "evento_id", "quantidade" }`. 200 OK.

`GET /api/catalogo/eventos` - listagem para proxy do Vendas.

Exemplos JSON completos permanecem válidos conforme versão anterior deste documento (payloads idênticos); alterações futuras devem ser refletidas aqui e nos testes manuais do lab.

---



## 6. Modelo de dados

**Catálogo -** `eventos`**:** `id`, `nome`, `estoque` (CHECK ≥ 0).

**Vendas -** `vendas`**:** `id`, `evento_id`, `quantidade`, `status`, `correlation_id`, `created_at`.

**Vendas -** `pagamentos_pendentes`**:** `id` (uuid), `evento_id`, `quantidade`, `chave_pagamento` (4 dígitos), `tentativas_chave_errada` (0-3), `status` (`aguardando`, `confirmado`, `expirado`, `cancelado`, `chave_esgotada`), `correlation_id`, `expires_at`, `created_at`, `venda_id` (nullable).

Reserva atômica:

```sql
UPDATE eventos
SET estoque = estoque - :quantidade, updated_at = NOW()
WHERE id = :evento_id AND estoque >= :quantidade
RETURNING id, estoque;
```

Sem gateway real, usuário cadastrado ou ingresso numerado. **Pagamento:** apenas simulação didática (chave 4 dígitos + prazo 30s).

Devolução atômica (Catálogo):

```sql
UPDATE eventos
SET estoque = estoque + :quantidade, updated_at = NOW()
WHERE id = :evento_id
RETURNING id, estoque;
```

---



## 7. Consistência, segurança e observabilidade


| Tópico           | Decisão                                               |
| ---------------- | ----------------------------------------------------- |
| Overselling      | Impedido pelo UPDATE condicional no Catálogo          |
| Ordem da compra  | reservar -> pagamento simulado -> INSERT venda; cancelar/expirar -> devolver |
| Falha Catálogo   | 503; nada gravado em vendas                           |
| Catálogo privado | Rede interna + API Key                                |
| Logs             | JSON stdout; `correlation_id`; handlers centralizados |
| Timeout          | Configurável via env                                  |


**Caso limite (aula):** Vendas cai após reserva e antes de confirmar/cancelar -> estoque preso até expirar (job opcional) ou devolução manual; idempotência avançada fora do mínimo.

**Pagamento simulado:** separa tempo de "pagar" do commit da venda; exige **devolver** para não esgotar estoque com abandonos.

---



## 8. Decisões arquiteturais (ADR resumido)


| ID     | Decisão                       | Justificativa                               |
| ------ | ----------------------------- | ------------------------------------------- |
| ADR-01 | REST síncrono                 | Clareza pedagógica e fluxo linear           |
| ADR-02 | Um DB por serviço             | Boundaries e independência de schema        |
| ADR-03 | API Key                       | Proteção simples serviço-a-serviço          |
| ADR-04 | HTTP 409 estoque              | Semântica de conflito de negócio            |
| ADR-05 | HTTP 503 upstream             | Consistência > venda “cega”                 |
| ADR-06 | UPDATE atômico                | Anti-oversell sem infra distribuída         |
| ADR-07 | Vendas como API pública       | Esconde Catálogo; centraliza políticas HTTP |
| ADR-08 | Monorepo                      | Um compose; contratos alinhados para estudo |
| ADR-09 | Timeout 2s + 1 retry          | Falhas transitórias sem loop infinito       |
| ADR-10 | Diagramas de escala separados | Evolução conceitual sem complicar o lab     |
| ADR-11 | Pagamento simulado 2 fases    | Pausa didática; chave 4 dígitos, 30s; ate 2 erros troca chave (422); 3o erro devolve estoque (400) |


---



## 9. Implementação simples (detalhamento)

Roteiro completo para codificar o lab (ordem, arquivos, Compose, contratos, testes manuais, o que não fazer):

**→ [PLANEJAMENTO-IMPLEMENTACAO.md](./PLANEJAMENTO-IMPLEMENTACAO.md)**

Resumo:

- [ ] Compose + Dockerfiles + `.env.example`
- [ ] Catálogo: eventos, reservar, **devolver**, API Key, logs
- [ ] Vendas: `CatalogClient` (+ devolver), compras iniciar/confirmar/cancelar, GET eventos proxy, 409/503
- [ ] Compra: iniciar / confirmar / cancelar + modal pagamento 30s
- [ ] Experimentos manuais: sucesso, 409, 503, concorrência no último ingresso

Novos diagramas devem ser adicionados em `[docs/diagrams/](./diagrams/README.md)` e referenciados neste documento e no índice.

---



## 10. Revisão do desenho


| Item                   | Revisado | Observações |
| ---------------------- | -------- | ----------- |
| Baseline + fluxos      |          |             |
| Evolução escala        |          |             |
| APIs e modelo de dados |          |             |


**Próximo passo:** validar o desenho; em seguida implementar o exercício mantendo REST síncrono e consistência de estoque.