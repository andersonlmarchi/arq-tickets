import { useCallback, useEffect, useRef, useState } from 'react'
import {
  cancelarCompra,
  confirmarCompra,
  getApiError,
  getChaveIncorretaBody,
  getHttpStatus,
} from '../api/sales'
import type { CheckoutState } from '../types'

export type PaymentCloseReason =
  | 'success'
  | 'cancelled'
  | 'timeout'
  | 'max_errors'
  | 'expired'

interface PaymentModalProps {
  checkout: CheckoutState
  onClose: (reason: PaymentCloseReason) => void
}

export function PaymentModal({ checkout, onClose }: PaymentModalProps) {
  const isIniciando = checkout.phase === 'iniciando'
  const session = checkout.phase === 'pagamento' ? checkout : null

  const [chaveExibicao, setChaveExibicao] = useState(
    session?.chaveExibicao ?? '',
  )
  const [chaveDigitada, setChaveDigitada] = useState('')
  const [secondsLeft, setSecondsLeft] = useState(session?.expiraEmSegundos ?? 30)
  const [hint, setHint] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [submitting, setSubmitting] = useState(false)
  const timeoutHandled = useRef(false)
  const deadlineRef = useRef(0)

  const runTimeoutCancel = useCallback(async () => {
    if (!session || timeoutHandled.current) {
      return
    }
    timeoutHandled.current = true
    try {
      await cancelarCompra(session.token, session.correlationId)
    } catch {
      // Pendente pode ja ter expirado no servidor.
    }
    onClose('timeout')
  }, [onClose, session])

  useEffect(() => {
    if (!session) {
      return
    }
    timeoutHandled.current = false
    setChaveExibicao(session.chaveExibicao)
    setChaveDigitada('')
    setHint(null)
    setError(null)
    setSubmitting(false)

    const prazo = session.expiraEmSegundos > 0 ? session.expiraEmSegundos : 30
    deadlineRef.current = Date.now() + prazo * 1000
    setSecondsLeft(prazo)

    const tick = () => {
      const left = Math.ceil((deadlineRef.current - Date.now()) / 1000)
      const clamped = Math.max(0, left)
      setSecondsLeft(clamped)
      if (clamped <= 0) {
        void runTimeoutCancel()
      }
    }

    tick()
    const timer = window.setInterval(tick, 250)
    return () => window.clearInterval(timer)
  }, [session, runTimeoutCancel])

  const handleConfirm = async () => {
    if (!session || chaveDigitada.length !== 4 || submitting || secondsLeft <= 0) {
      return
    }
    setSubmitting(true)
    setError(null)
    try {
      await confirmarCompra(
        session.token,
        chaveDigitada,
        session.correlationId,
      )
      onClose('success')
    } catch (err) {
      const chaveErr = getChaveIncorretaBody(err)
      if (chaveErr) {
        setChaveExibicao(chaveErr.chave_exibicao)
        setChaveDigitada('')
        setHint(`Tentativas restantes: ${chaveErr.tentativas_restantes}`)
        setSubmitting(false)
        return
      }
      const status = getHttpStatus(err)
      const apiErr = getApiError(err)
      if (status === 400 && apiErr?.code === 'CHAVE_INVALIDA') {
        onClose('max_errors')
        return
      }
      if (status === 410) {
        onClose('expired')
        return
      }
      setError(apiErr?.message ?? 'Nao foi possivel confirmar o pagamento.')
      setSubmitting(false)
    }
  }

  const handleCancel = async () => {
    if (!session || submitting) {
      return
    }
    setSubmitting(true)
    try {
      await cancelarCompra(session.token, session.correlationId)
    } catch {
      // Ignorar.
    }
    onClose('cancelled')
  }

  const onChaveChange = (value: string) => {
    const digits = value.replace(/\D/g, '').slice(0, 4)
    setChaveDigitada(digits)
  }

  const titulo = checkout.eventoNome
  const quantidade = checkout.quantidade

  return (
    <section className="payment-screen panel" aria-labelledby="payment-title">
      <h2 id="payment-title">Pagamento simulado</h2>
      <p className="modal-sub">
        {titulo} - {quantidade} ingresso(s)
      </p>

      {isIniciando ? (
        <p className="payment-loading" role="status">
          Reservando ingressos e gerando chave de pagamento...
        </p>
      ) : (
        <>
          <div className="key-display">
            <span className="key-label">Chave para digitar</span>
            <span className="key-value" aria-live="polite">
              {chaveExibicao}
            </span>
          </div>

          <label className="field">
            <span>Digite a chave (4 digitos)</span>
            <input
              type="text"
              inputMode="numeric"
              autoComplete="one-time-code"
              maxLength={4}
              value={chaveDigitada}
              onChange={(e) => onChaveChange(e.target.value)}
              disabled={submitting || secondsLeft <= 0}
              autoFocus
            />
          </label>

          {hint ? <p className="hint">{hint}</p> : null}
          {error ? <p className="alert alert-error">{error}</p> : null}

          <div className="modal-actions">
            <button
              type="button"
              className="btn primary"
              disabled={
                submitting || chaveDigitada.length !== 4 || secondsLeft <= 0
              }
              onClick={() => void handleConfirm()}
            >
              Confirmar pagamento ({Math.max(secondsLeft, 0)}s)
            </button>
            <button
              type="button"
              className="btn ghost"
              disabled={submitting || secondsLeft <= 0}
              onClick={() => void handleCancel()}
            >
              Cancelar compra
            </button>
          </div>
        </>
      )}
    </section>
  )
}
