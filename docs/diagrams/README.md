# Diagramas (Mermaid)

Diagramas do projeto **arq-tickets** - laboratório de arquitetura de microsserviços (estudo, não produto).

Cada arquivo contém um bloco Mermaid pronto para visualização (GitHub, VS Code, Mermaid Live) ou exportação para Excalidraw.

## Baseline (arquitetura de referência)

| Arquivo | Descrição |
|---------|-----------|
| [01-arquitetura-geral.md](./01-arquitetura-geral.md) | Componentes, HTTP e bancos isolados |
| [02-fluxo-compra-sequencia.md](./02-fluxo-compra-sequencia.md) | Compra direta (referência); lab usa [12](./12-fluxo-pagamento-simulado.md) |
| [03-fluxo-falha-sequencia.md](./03-fluxo-falha-sequencia.md) | Catálogo indisponível, retry, 503 |
| [04-componentes.md](./04-componentes.md) | Responsabilidades por camada |
| [05-implantacao-docker-compose.md](./05-implantacao-docker-compose.md) | Containers, rede e portas |

## Implementação (lab)

| Arquivo | Descrição |
|---------|-----------|
| [11-ordem-implementacao.md](./11-ordem-implementacao.md) | Sequência de codificação do monorepo |
| [12-fluxo-pagamento-simulado.md](./12-fluxo-pagamento-simulado.md) | Reserva, chave 4 dígitos, 30s, ate 3 erros de chave |

## Evolução para alta demanda (conceitual)

Cenário hipotético: abertura de vendas com **milhares de requisições simultâneas** no mesmo evento.  
A implementação local permanece simples (Docker Compose); os diagramas abaixo mostram **o que aplicar onde** se a carga crescer.

| Arquivo | Foco | Técnicas / tecnologias |
|---------|------|-------------------------|
| [06-escala-gargalos.md](./06-escala-gargalos.md) | Onde a pressão aparece primeiro | Análise de hot path |
| [07-escala-nivel-1-horizontal.md](./07-escala-nivel-1-horizontal.md) | Réplicas stateless | Load balancer, Nginx/HAProxy, múltiplas instâncias |
| [08-escala-nivel-2-leitura-cache.md](./08-escala-nivel-2-leitura-cache.md) | Listagens e leituras | CDN, Redis, read replicas PostgreSQL |
| [09-escala-nivel-3-escrita-catalogo.md](./09-escala-nivel-3-escrita-catalogo.md) | Gargalo de reserva | PgBouncer, rate limit, fila opcional*, particionamento |
| [10-escala-roadmap-evolucao.md](./10-escala-roadmap-evolucao.md) | Visão em estágios | Roadmap para apresentação |

\* Fila (ex.: RabbitMQ) aparece **apenas** como evolução opcional documentada - fora do escopo da implementação de estudo, que mantém REST síncrono.
