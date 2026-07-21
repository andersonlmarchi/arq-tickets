import { useCallback, useEffect, useState } from 'react'
import { createCorrelationId } from './api/client'
import {
  fetchEventos,
  getErrorMessage,
  getHttpStatus,
  iniciarCompra,
} from './api/sales'
import { PaymentModal, type PaymentCloseReason } from './components/PaymentModal'
import type { CheckoutState, Evento } from './types'
import './App.css'

function App() {
  const [eventos, setEventos] = useState<Evento[]>([])
  const [loading, setLoading] = useState(true)
  const [quantidades, setQuantidades] = useState<Record<number, number>>({})
  const [checkout, setCheckout] = useState<CheckoutState | null>(null)
  const [banner, setBanner] = useState<{
    kind: 'info' | 'error' | 'success'
    text: string
  } | null>(null)

  const loadEventos = useCallback(async (options?: { preserveBanner?: boolean }) => {
    setLoading(true)
    if (!options?.preserveBanner) {
      setBanner(null)
    }
    try {
      const list = await fetchEventos()
      setEventos(list)
      setQuantidades((prev) => {
        const next = { ...prev }
        for (const ev of list) {
          if (!next[ev.id]) {
            next[ev.id] = 1
          }
        }
        return next
      })
    } catch (err) {
      setBanner({
        kind: 'error',
        text:
          getErrorMessage(err) ??
          'Nao foi possivel carregar os eventos. Verifique se o sales-service esta no ar.',
      })
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    void loadEventos()
  }, [loadEventos])

  const setQty = (eventoId: number, value: number) => {
    const qty = Math.min(10, Math.max(1, value))
    setQuantidades((prev) => ({ ...prev, [eventoId]: qty }))
  }

  const handleComprar = async (evento: Evento) => {
    const quantidade = quantidades[evento.id] ?? 1
    if (quantidade > evento.estoque_disponivel) {
      setBanner({
        kind: 'error',
        text: 'Quantidade maior que o estoque disponivel.',
      })
      return
    }

    const correlationId = createCorrelationId()
    setBanner(null)
    setCheckout({
      phase: 'iniciando',
      eventoId: evento.id,
      eventoNome: evento.nome,
      quantidade,
      correlationId,
    })

    try {
      const res = await iniciarCompra(evento.id, quantidade, correlationId)
      setCheckout({
        phase: 'pagamento',
        token: res.token,
        chaveExibicao: res.chave_exibicao,
        expiraEmSegundos: res.expira_em_segundos,
        eventoId: evento.id,
        eventoNome: evento.nome,
        quantidade,
        correlationId: res.correlation_id,
      })
    } catch (err) {
      setCheckout(null)
      const status = getHttpStatus(err)
      if (status === 409) {
        setBanner({
          kind: 'error',
          text: getErrorMessage(err),
        })
      } else if (status === 503) {
        setBanner({
          kind: 'error',
          text: getErrorMessage(err),
        })
      } else if (status === 500) {
        setBanner({
          kind: 'error',
          text: `${getErrorMessage(err)} (HTTP 500). Ingressos podem ter sido reservados; atualize a lista. Se persistir, rebuild do sales-service.`,
        })
        void loadEventos({ preserveBanner: true })
      } else {
        setBanner({
          kind: 'error',
          text: getErrorMessage(err),
        })
      }
    }
  }

  const handlePaymentClose = (reason: PaymentCloseReason) => {
    setCheckout(null)
    const messages: Record<
      PaymentCloseReason,
      { kind: 'info' | 'error' | 'success'; text: string }
    > = {
      success: {
        kind: 'success',
        text: 'Compra confirmada.',
      },
      cancelled: {
        kind: 'info',
        text: 'Pagamento nao concluido. Estoque devolvido.',
      },
      timeout: {
        kind: 'info',
        text: 'Tempo esgotado. Pagamento cancelado e estoque devolvido.',
      },
      max_errors: {
        kind: 'error',
        text: 'CHAVE_INVALIDA: tentativas esgotadas. Estoque devolvido.',
      },
      expired: {
        kind: 'info',
        text: 'PAGAMENTO_EXPIRADO. Estoque devolvido.',
      },
    }
    setBanner(messages[reason])
    void loadEventos({ preserveBanner: true })
  }

  const emCheckout = checkout !== null

  return (
    <div className="app">
      <header className="header">
        <h1>arq-tickets</h1>
        <p>Comprar ingressos</p>
      </header>

      {banner ? (
        <p className={`alert alert-${banner.kind}`} role="status">
          {banner.text}
        </p>
      ) : null}

      {checkout ? (
        <PaymentModal checkout={checkout} onClose={handlePaymentClose} />
      ) : null}

      {!emCheckout ? (
        <section className="panel">
          <div className="panel-head">
            <h2>Eventos</h2>
            <button
              type="button"
              className="btn ghost"
              onClick={() => void loadEventos()}
              disabled={loading}
            >
              Atualizar
            </button>
          </div>

          {loading ? (
            <p className="muted">Carregando...</p>
          ) : eventos.length === 0 ? (
            <p className="muted">Nenhum evento disponivel.</p>
          ) : (
            <ul className="event-list">
              {eventos.map((evento) => {
                const qty = quantidades[evento.id] ?? 1
                const semEstoque = evento.estoque_disponivel <= 0
                return (
                  <li key={evento.id} className="event-card">
                    <div>
                      <h3>{evento.nome}</h3>
                      <p className="muted">
                        Estoque: {evento.estoque_disponivel}
                      </p>
                    </div>
                    <div className="event-actions">
                      <label className="qty">
                        <span>Qtd</span>
                        <input
                          type="number"
                          min={1}
                          max={10}
                          value={qty}
                          disabled={semEstoque}
                          onChange={(e) =>
                            setQty(evento.id, Number(e.target.value))
                          }
                        />
                      </label>
                      <button
                        type="button"
                        className="btn primary"
                        disabled={
                          semEstoque || qty > evento.estoque_disponivel
                        }
                        onClick={() => void handleComprar(evento)}
                      >
                        Comprar
                      </button>
                    </div>
                  </li>
                )
              })}
            </ul>
          )}
        </section>
      ) : null}
    </div>
  )
}

export default App
