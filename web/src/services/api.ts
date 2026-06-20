import axios from 'axios'
import type { ApiError, User, Category, Transaction, DashboardSummary, MonthlySummary, SubscriptionStatus, PaginatedResponse, Plan } from '../types/api'

const baseURL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

const client = axios.create({
  baseURL,
  headers: {
    'Content-Type': 'application/json',
  },
})

export function setAuthToken(token: string | null) {
  if (token) {
    client.defaults.headers.common['Authorization'] = `Bearer ${token}`
  } else {
    delete client.defaults.headers.common['Authorization']
  }
}

export function extractApiError(error: unknown): ApiError {
  if (axios.isAxiosError(error)) {
    const responseData = error.response?.data
    if (responseData && typeof responseData === 'object' && 'error' in responseData) {
      const apiError = (responseData as { error?: ApiError }).error
      if (apiError?.type && apiError?.message) {
        return apiError
      }
    }

    return { type: 'unknown_error', message: error.message }
  }

  const fallbackMessage = error instanceof Error ? error.message : 'Unknown error'
  return { type: 'unknown_error', message: fallbackMessage }
}

export const api = {
  // Auth
  register: async (payload: { name: string; email: string; password: string; locale?: string }) => {
    const response = await client.post<{ data: { token: string; user: User } }>('/v1/auth/register', payload)
    return response.data.data
  },

  login: async (payload: { email: string; password: string }) => {
    const response = await client.post<{ data: { token: string; user: User } }>('/v1/auth/login', payload)
    return response.data.data
  },

  me: async () => {
    const response = await client.get<{ data: User }>('/v1/auth/me')
    return response.data.data
  },

  logout: async () => {
    await client.post('/v1/auth/logout')
  },

  updatePreferences: async (locale: string) => {
    const response = await client.patch<{ data: User }>('/v1/users/me/preferences', { locale })
    return response.data.data
  },

  updateProfile: async (payload: { name?: string; email?: string; password?: string; password_confirmation?: string; locale?: string } | FormData) => {
    const isFormData = payload instanceof FormData
    const response = await client.patch<{ data: User }>('/v1/users/me', payload, isFormData
      ? { headers: { 'Content-Type': 'multipart/form-data' } }
      : undefined)
    return response.data.data
  },

  // Categories
  listCategories: async (tree?: boolean) => {
    const response = await client.get<{ data: Category[] }>('/v1/categories', {
      params: { tree: tree ? 1 : undefined },
    })
    return response.data.data
  },

  createCategory: async (payload: { name: string; type: string; icon?: string; parent_id?: number }) => {
    const response = await client.post<{ data: Category }>('/v1/categories', payload)
    return response.data.data
  },

  updateCategory: async (id: number, payload: Partial<{ name: string; type: string; icon?: string; parent_id?: number }>) => {
    const response = await client.patch<{ data: Category }>(`/v1/categories/${id}`, payload)
    return response.data.data
  },

  deleteCategory: async (id: number) => {
    await client.delete(`/v1/categories/${id}`)
  },

  // Transactions
  listTransactions: async (params?: {
    category_id?: number
    type?: string
    payment_method?: string
    month?: string
    date_from?: string
    date_to?: string
    per_page?: number
    page?: number
  }) => {
    const response = await client.get<PaginatedResponse<Transaction>>('/v1/transactions', { params })
    return response.data
  },

  createTransaction: async (payload: { category_id: number; type: string; payment_method: string; amount: number; transacted_at: string; notes?: string }) => {
    const response = await client.post<{ data: Transaction }>('/v1/transactions', payload)
    return response.data.data
  },

  updateTransaction: async (id: number, payload: Partial<{ category_id: number; type: string; payment_method: string; amount: number; transacted_at: string; notes?: string }>) => {
    const response = await client.patch<{ data: Transaction }>(`/v1/transactions/${id}`, payload)
    return response.data.data
  },

  deleteTransaction: async (id: number) => {
    await client.delete(`/v1/transactions/${id}`)
  },

  // Dashboard
  getDashboardSummary: async (month?: string) => {
    const response = await client.get<{ data: DashboardSummary }>('/v1/dashboard/summary', {
      params: { month },
    })
    return response.data.data
  },

  getDashboardChart: async (month?: string) => {
    const response = await client.get<{ data: { month: string; series: { entradas: number; saidas: number } } }>('/v1/dashboard/chart', {
      params: { month },
    })
    return response.data.data
  },

  getDashboardRecent: async (limit?: number, month?: string) => {
    const response = await client.get<{ data: Transaction[] }>('/v1/dashboard/recent', {
      params: { limit, month },
    })
    return response.data.data
  },

  // Monthly Summary (legacy, may deprecate)
  getMonthlySummary: async (year: number, month: number) => {
    const response = await client.get<{ data: MonthlySummary }>('/v1/summaries/monthly', {
      params: { year, month },
    })
    return response.data.data
  },

  // Billing
  listPlans: async () => {
    const response = await client.get<{ data: Plan[] }>('/v1/plans')
    return response.data.data
  },

  getSubscription: async () => {
    const response = await client.get<{ data: SubscriptionStatus }>('/v1/subscription')
    return response.data.data
  },

  // Budgets
  upsertBudget: async (payload: { category_id: number; year: number; month: number; amount: number }) => {
    const response = await client.post('/v1/budgets', payload)
    return response.data
  },
}
