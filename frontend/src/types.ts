export interface Evento {
  id: number
  nome: string
  estoque_disponivel: number
}

export interface IniciarCompraResponse {
  token: string
  chave_exibicao: string
  expira_em_segundos: number
  evento_id: number
  quantidade: number
  correlation_id: string
}

export interface ConfirmarCompraResponse {
  id: number
  evento_id: number
  quantidade: number
  status: string
  created_at: string
  correlation_id: string
}

export interface ApiErrorBody {
  code: string
  message: string
  correlation_id?: string
}

export interface ChaveIncorretaBody extends ApiErrorBody {
  code: 'CHAVE_INCORRETA'
  chave_exibicao: string
  tentativas_restantes: number
}

export interface PaymentSession {
  token: string
  chaveExibicao: string
  expiraEmSegundos: number
  eventoId: number
  eventoNome: string
  quantidade: number
  correlationId: string
}

export type CheckoutState =
  | {
      phase: 'iniciando'
      eventoId: number
      eventoNome: string
      quantidade: number
      correlationId: string
    }
  | ({ phase: 'pagamento' } & PaymentSession)
