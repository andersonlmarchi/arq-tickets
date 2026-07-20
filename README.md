# arq-tickets - Venda de Ingressos

Repositório de estudo: microsserviços simples, REST síncrono, Docker Compose. 

## Documentação


| Documento                                                             | Conteúdo                                                    |
| --------------------------------------------------------------------- | ----------------------------------------------------------- |
| [Planejamento arquitetural](./docs/PLANEJAMENTO-ARQUITETURA.md)       | Visão, APIs, dados, ADRs                                    |
| [Planejamento de implementação](./docs/PLANEJAMENTO-IMPLEMENTACAO.md) | Roteiro simples: ordem, arquivos, Docker, contratos, testes |
| [Diagramas (Mermaid)](./docs/diagrams/README.md)                      | Baseline + evolução para alta demanda                       |




## Stack (referência)


| Serviço               | Stack                | Porta (dev)         |
| --------------------- | -------------------- | ------------------- |
| Frontend              | React + Vite + Axios | 5173                |
| Vendas                | PHP 8.4 + Laravel    | 8080                |
| Catálogo              | Python + FastAPI     | 8000 (rede interna) |
| PostgreSQL (vendas)   | PostgreSQL           | 5433                |
| PostgreSQL (catálogo) | PostgreSQL           | 5434                |


Fluxo de estudo: **Usuário -> Frontend -> Vendas -> Catálogo (reserva) -> pagamento simulado (chave 4 dígitos, 30s) -> Vendas confirma venda -> Frontend**.

Implementação de código: a iniciar após validação do planejamento.