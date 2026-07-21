# Planejamento de implementação - versão simples (lab)

> **Escopo:** baseline do [planejamento arquitetural](./PLANEJAMENTO-ARQUITETURA.md): REST síncrono, Docker Compose, três serviços, dois PostgreSQL, **pagamento simulado** (chave 4 dígitos, 30s).  
> **Fora de escopo:** cache, filas, Kubernetes, auth de usuário, gateway real (PIX/cartão), idempotency-key.

**Referências (não repetidas aqui):**

| Tema | Documento |
|------|-----------|
| Contratos HTTP, erros, payloads | [PLANEJAMENTO-ARQUITETURA.md §5](./PLANEJAMENTO-ARQUITETURA.md#5-contratos-de-api) |
| Modelo de dados e SQL de reserva | [PLANEJAMENTO-ARQUITETURA.md §6](./PLANEJAMENTO-ARQUITETURA.md#6-modelo-de-dados) |
| Fluxo compra + pagamento | [12-fluxo-pagamento-simulado.md](./diagrams/12-fluxo-pagamento-simulado.md) |
| Falha catálogo na reserva | [03-fluxo-falha-sequencia.md](./diagrams/03-fluxo-falha-sequencia.md) |
| Compose / rede | [05-implantacao-docker-compose.md](./diagrams/05-implantacao-docker-compose.md) |
| Ordem de codificação | [11-ordem-implementacao.md](./diagrams/11-ordem-implementacao.md) |

---

## 1. Critérios de “pronto” (Definition of Done)

| # | Critério | Como validar |
|---|----------|--------------|
| 1 | Stack sobe com Compose | Containers healthy ou running |
| 2 | Frontend lista eventos | GET via Vendas |
| 3 | Compra completa | iniciar -> confirmar chave -> 201; estoque - qty; linha em `vendas` |
| 4 | Sem estoque | iniciar -> 409; sem pendente |
| 5 | Expirar 30s | cancelar automático -> estoque restaurado; sem venda |
| 6 | Chave errada | 1o/2o erro -> 422 + nova chave; 3o -> 400 + estoque devolvido |
| 7 | Catálogo parado | iniciar -> 503 |
| 8 | Correlation-ID | Mesmo id nos logs Vendas + Catálogo |
| 9 | API Key | Catálogo sem key -> 401 |

---

## 2. Ordem de implementação

Ver **[11-ordem-implementacao.md](./diagrams/11-ordem-implementacao.md)**.

---

## 3. Monorepo - árvore de arquivos alvo

```text
frontend/src/components/PaymentModal.tsx   # chave exibida, input, timer 30s no botão
sales-service/.../CompraController.php     # iniciar, confirmar, cancelar
sales-service/.../PagamentoPendente.php
catalog-service/.../devolver no repository
```

Demais pastas: igual resumo anterior (`CatalogClient` + `devolver`).

---

## 4. Infraestrutura - Docker Compose

Sem mudança de topologia [05](./diagrams/05-implantacao-docker-compose.md).

**Env Vendas:** `PAGAMENTO_PRAZO_SEGUNDOS=30`, `PAGAMENTO_MAX_ERROS_CHAVE=3` (default 3).  
Contador do botao no front usa `expira_em_segundos` da resposta de `iniciar` (fallback 30 no codigo).

---

## 5. Catálogo (FastAPI) - o que codificar

| Item | Detalhe |
|------|---------|
| Rotas existentes | reservar, GET eventos |
| **Novo** | `POST /api/catalogo/devolver` - UPDATE estoque + qty ([§6 arquitetura](./PLANEJAMENTO-ARQUITETURA.md#6-modelo-de-dados)) |

---

## 6. Vendas (Laravel) - o que codificar

| Item | Detalhe |
|------|---------|
| `CatalogClient` | + `devolver(eventoId, qty)` |
| Migration | `pagamentos_pendentes` + `vendas` |
| `POST /api/compras/iniciar` | reservar -> criar pendente + `chave_pagamento` aleatória 0000-9999 (4 chars) |
| `POST /api/compras/confirmar` | validar token, prazo, chave; se erro 1-2: nova chave + `tentativas_chave_errada++`; se erro 3: devolver + status `chave_esgotada` + 400 |
| `POST /api/compras/cancelar` | devolver catálogo + status expirado/cancelado |
| Remover | fluxo único `POST /api/vendas` (substituído pelos três endpoints) |

Gerar chave no **backend**; resposta inclui `chave_exibicao` para o front **mostrar** e comparar com `chave_digitada` no confirmar.

---

## 7. Frontend - o que codificar

1. Comprar -> `POST /api/compras/iniciar`.
2. Abrir modal: exibe `chave_exibicao`, input 4 dígitos, botão **Confirmar pagamento (30s)** com countdown.
3. Confirmar -> `POST /api/compras/confirmar`.
4. **422**: atualizar chave exibida (troca simulada), limpar input, mostrar `tentativas_restantes`.
5. **400** `CHAVE_INVALIDA`: fechar modal, mensagem, refetch eventos (estoque devolvido).
6. Ao chegar 0s: `POST /api/compras/cancelar` + fechar modal.
7. **201**: refetch eventos.

---

## 8. Matriz de erros (implementação)

| Condição | HTTP | Venda? | Estoque após |
|----------|------|--------|--------------|
| iniciar 409 | 409 | Não | Inalterado |
| confirmar OK | 201 | Sim | Reservado (já baixou no iniciar) |
| cancelar / timeout | 200 | Não | Devolvido |
| 1o ou 2o erro chave | 422 | Não | Reservado; nova chave |
| 3o erro chave | 400 | Não | Devolvido |
| confirmar expirado | 410 | Não | Devolvido |

---

## 9. Testes manuais

| Cenário | Passos |
|---------|--------|
| Happy path | iniciar -> ver chave -> digitar -> confirmar antes 30s -> 201 |
| Timeout | iniciar -> esperar 30s -> estoque volta |
| Chave errada | 1o e 2o -> 422 nova chave; 3o -> 400 devolve estoque |
| 409 | iniciar sem estoque |
| 503 | catálogo parado no iniciar |

Fluxo: [12](./diagrams/12-fluxo-pagamento-simulado.md).

---

## 10. Não implementar

Filas, Redis, K8s, login, admin CRUD, PIX/cartão real, HTTPS local, E2E automatizado obrigatório.

---

## 11. Decisões default

| # | Decisão | Default |
|---|---------|---------|
| 1 | Prazo pagamento | 30s (env) |
| 2 | Chave | 4 dígitos; servidor gera; rotaciona a cada erro (max 3 erros) |
| 3 | Timer | Contador no botão Confirmar (UI) |
| 4-8 | (Compose, seed, CORS, retry, TS) | Igual revisão anterior |

---

## 12. Próximo passo

Codificar conforme [11](./diagrams/11-ordem-implementacao.md) e [12](./diagrams/12-fluxo-pagamento-simulado.md).
