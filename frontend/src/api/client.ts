import axios from 'axios'

const baseURL = import.meta.env.VITE_SALES_API_URL ?? 'http://localhost:8080'

export function createCorrelationId(): string {
  return crypto.randomUUID()
}

export const salesApi = axios.create({
  baseURL: `${baseURL.replace(/\/$/, '')}/api`,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

export function correlationConfig(correlationId?: string) {
  const id = correlationId ?? createCorrelationId()
  return {
    correlationId: id,
    config: { headers: { 'X-Correlation-Id': id } },
  }
}
