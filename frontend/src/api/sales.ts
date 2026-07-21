import axios from 'axios'
import { correlationConfig, salesApi } from './client'
import type {
  ApiErrorBody,
  ChaveIncorretaBody,
  ConfirmarCompraResponse,
  Evento,
  IniciarCompraResponse,
} from '../types'

export async function fetchEventos(): Promise<Evento[]> {
  const { config } = correlationConfig()
  const { data } = await salesApi.get<{ data: Evento[] }>('/eventos', config)
  return data.data
}

function prazoSegundos(value: unknown): number {
  if (typeof value === 'number' && value > 0) {
    return value
  }
  return 30
}

export async function iniciarCompra(
  eventoId: number,
  quantidade: number,
  correlationId?: string,
): Promise<IniciarCompraResponse> {
  const { correlationId: id, config } = correlationConfig(correlationId)
  const { data } = await salesApi.post<IniciarCompraResponse>(
    '/compras/iniciar',
    { evento_id: eventoId, quantidade },
    config,
  )
  if (!data?.token || !data?.chave_exibicao) {
    throw new Error('Resposta de iniciar invalida (sem token ou chave).')
  }
  return {
    ...data,
    expira_em_segundos: prazoSegundos(data.expira_em_segundos),
    correlation_id: data.correlation_id ?? id,
  }
}

export async function confirmarCompra(
  token: string,
  chaveDigitada: string,
  correlationId?: string,
): Promise<ConfirmarCompraResponse> {
  const { config } = correlationConfig(correlationId)
  const { data } = await salesApi.post<ConfirmarCompraResponse>(
    '/compras/confirmar',
    { token, chave_digitada: chaveDigitada },
    config,
  )
  return data
}

export async function cancelarCompra(
  token: string,
  correlationId?: string,
): Promise<void> {
  const { config } = correlationConfig(correlationId)
  await salesApi.post('/compras/cancelar', { token }, config)
}

export function getApiError(error: unknown): ApiErrorBody | null {
  if (!axios.isAxiosError(error) || !error.response?.data) {
    return null
  }
  const body = error.response.data as ApiErrorBody
  if (typeof body.code === 'string' && typeof body.message === 'string') {
    return body
  }
  return null
}

export function getErrorMessage(error: unknown): string {
  const apiErr = getApiError(error)
  if (apiErr) {
    return apiErr.message
  }
  if (axios.isAxiosError(error) && error.response?.data) {
    const body = error.response.data as { message?: string }
    if (typeof body.message === 'string') {
      return body.message
    }
  }
  if (error instanceof Error) {
    return error.message
  }
  return 'Erro inesperado.'
}

export function getChaveIncorretaBody(error: unknown): ChaveIncorretaBody | null {
  if (!axios.isAxiosError(error) || error.response?.status !== 422) {
    return null
  }
  const body = error.response.data as ChaveIncorretaBody
  if (body?.code === 'CHAVE_INCORRETA' && body.chave_exibicao) {
    return body
  }
  return null
}

export function getHttpStatus(error: unknown): number | undefined {
  if (axios.isAxiosError(error)) {
    return error.response?.status
  }
  return undefined
}
