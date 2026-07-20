# Fluxo de compra com pagamento simulado (sequence)

Simula QR code / cartão: chave de 4 dígitos exibida na tela, usuário digita e confirma em até **30 segundos**. A **reserva** ocorre ao iniciar; a **venda** só após pagamento confirmado. Expirou ou cancelou -> **devolver** estoque.

```mermaid
sequenceDiagram
    autonumber
    actor User as Usuário
    participant FE as Frontend
    participant VS as Serviço de Vendas
    participant CS as Serviço de Catálogo
    participant DBC as DB Catálogo
    participant DBS as DB Vendas

    User->>FE: Clica Comprar
    FE->>VS: POST /api/compras/iniciar<br/>{ evento_id, quantidade }
    VS->>CS: POST /api/catalogo/reservar
    alt Sem estoque
        CS-->>VS: 409
        VS-->>FE: 409
        FE-->>User: Sem ingressos
    else Reserva OK
        CS->>DBC: UPDATE estoque -
        CS-->>VS: 200
        VS->>DBS: INSERT pagamento_pendente<br/>chave 4 dígitos, expira +30s
        VS-->>FE: 200 { token, chave_exibicao, expira_em_segundos: 30 }
        FE-->>User: Modal: mostra chave + campo digitar<br/>Botão Confirmar (30s...)
    end

    alt Usuário confirma a tempo
        User->>FE: Digita chave e confirma
        FE->>VS: POST /api/compras/confirmar<br/>{ token, chave_digitada }
        alt Chave correta
            VS->>DBS: INSERT venda confirmada
            VS->>DBS: UPDATE pagamento confirmado
            VS-->>FE: 201
            FE-->>User: Compra concluída
        else Chave errada (1a ou 2a tentativa)
            VS->>VS: Nova chave (troca forma pagamento)
            VS->>DBS: tentativas_erradas +1
            VS-->>FE: 422 CHAVE_INCORRETA<br/>{ chave_exibicao nova, tentativas_restantes }
            FE-->>User: Atualiza chave na tela, limpa input
        else Chave errada (3a tentativa)
            VS->>CS: POST devolver
            VS->>DBS: pagamento encerrado
            VS-->>FE: 400 CHAVE_INVALIDA
            FE-->>User: Compra encerrada
        end
    else Timer 30s ou Cancelar
        FE->>VS: POST /api/compras/cancelar { token }
        VS->>CS: POST /api/catalogo/devolver
        CS->>DBC: UPDATE estoque +
        VS->>DBS: pagamento expirado/cancelado
        VS-->>FE: 200
        FE-->>User: Pagamento não concluído
    end
```

**Regras:**

- Contador de **30s** no botão de confirmar (frontend); backend valida `expires_at` na confirmação.
- Chave errada: até **2** erros -> **422** `CHAVE_INCORRETA`, gera **nova** `chave_exibicao` (simula troca QR/cartão), corpo inclui `tentativas_restantes`.
- **3o** erro de chave -> **400** `CHAVE_INVALIDA`, **devolver** estoque, encerrar pendente (mesmo efeito de abandono).
- Confirmar após expirar: **410** `PAGAMENTO_EXPIRADO` + devolver estoque se ainda pendente.

Fluxo catálogo indisponível na reserva: igual [03-fluxo-falha-sequencia.md](./03-fluxo-falha-sequencia.md) (503, sem pendente).

O fluxo [02-fluxo-compra-sequencia.md](./02-fluxo-compra-sequencia.md) descreve apenas a fase reserva + venda imediata (referência histórica); o lab implementa **este** fluxo.
