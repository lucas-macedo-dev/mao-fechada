import axios, { AxiosError } from 'axios'
import type { ApiEnvelope, ApiError } from './types'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
  },
})

export function setAuthToken(token: string | null) {
  if (token) {
    api.defaults.headers.common.Authorization = `Bearer ${token}`
    return
  }

  delete api.defaults.headers.common.Authorization
}

export function extractApiError(error: unknown): ApiError {
  const axiosError = error as AxiosError<{ error?: ApiError }>
  return (
    axiosError.response?.data?.error ?? {
      type: 'unknown_error',
      message: 'Unexpected request error.',
    }
  )
}

export async function get<T>(path: string, params?: Record<string, unknown>) {
  const response = await api.get<ApiEnvelope<T>>(path, { params })
  return response.data.data
}

export async function post<T, P = Record<string, unknown>>(path: string, payload: P) {
  const response = await api.post<ApiEnvelope<T>>(path, payload)
  return response.data.data
}

export async function put<T, P = Record<string, unknown>>(path: string, payload: P) {
  const response = await api.put<ApiEnvelope<T>>(path, payload)
  return response.data.data
}

export async function remove(path: string) {
  await api.delete(path)
}
