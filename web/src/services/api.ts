import axios from 'axios'
import type { ApiError, User, Category, Transaction, RecurringTransaction, DashboardSummary, MonthlySummary, SubscriptionStatus, PaginatedResponse, Plan, DashboardCategoryItem, DashboardDayItem, DashboardMonthlyComparisonItem, DashboardMtdComparison, DashboardPaymentMethodItem, DashboardWeeklyExpenseItem, Report } from '../types/api'

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
    amount_min?: number
    amount_max?: number
    notes?: string
    per_page?: number
    page?: number
    installment?: boolean
  }) => {
    const response = await client.get<PaginatedResponse<Transaction>>('/v1/transactions', { params })
    return response.data
  },

  createTransaction: async (payload: {
    category_id: number
    type: string
    payment_method: string
    amount: number
    transacted_at: string
    notes?: string
    installment_number?: number
    installment_total?: number
    recurring?: boolean
  }) => {
    const response = await client.post<{ data: Transaction | Transaction[] }>('/v1/transactions', payload)
    return response.data.data
  },

  updateTransaction: async (id: number, payload: Partial<{ category_id: number; type: string; payment_method: string; amount: number; transacted_at: string; notes?: string }>) => {
    const response = await client.patch<{ data: Transaction }>(`/v1/transactions/${id}`, payload)
    return response.data.data
  },

  deleteTransaction: async (id: number) => {
    await client.delete(`/v1/transactions/${id}`)
  },

  convertTransactionToRecurring: async (id: number) => {
    const response = await client.post<{ data: Transaction }>(`/v1/transactions/${id}/convert-to-recurring`)
    return response.data.data
  },

  // Recurring Transactions
  listRecurringTransactions: async () => {
    const response = await client.get<{ data: RecurringTransaction[] }>('/v1/recurring-transactions')
    return response.data.data
  },

  cancelRecurringTransaction: async (id: number) => {
    const response = await client.post<{ data: RecurringTransaction }>(`/v1/recurring-transactions/${id}/cancel`)
    return response.data.data
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

  getDashboardByCategory: async (month?: string) => {
    const response = await client.get<{ data: DashboardCategoryItem[] }>('/v1/dashboard/by-category', {
      params: { month },
    })
    return response.data.data
  },

  getDashboardByDay: async (month?: string) => {
    const response = await client.get<{ data: DashboardDayItem[] }>('/v1/dashboard/by-day', {
      params: { month },
    })
    return response.data.data
  },

  getDashboardInstallmentsTotal: async (month?: string) => {
    const response = await client.get<{ data: { month: string; total: number } }>('/v1/dashboard/installments-total', {
      params: { month },
    })
    return response.data.data
  },

  getDashboardMonthlyComparison: async (month?: string) => {
    const response = await client.get<{ data: { months: DashboardMonthlyComparisonItem[] } }>('/v1/dashboard/monthly-comparison', {
      params: { month },
    })
    return response.data.data
  },

  getDashboardMtdComparison: async (month?: string) => {
    const response = await client.get<{ data: DashboardMtdComparison }>('/v1/dashboard/expenses-mtd-comparison', {
      params: { month },
    })
    return response.data.data
  },

  getDashboardByPaymentMethod: async (month?: string) => {
    const response = await client.get<{ data: DashboardPaymentMethodItem[] }>('/v1/dashboard/by-payment-method', {
      params: { month },
    })
    return response.data.data
  },

  getDashboardWeeklyExpenses: async () => {
    const response = await client.get<{ data: DashboardWeeklyExpenseItem[] }>('/v1/dashboard/weekly-expenses')
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

  // Email verification
  verifyEmail: async (id: string, hash: string, expires: string, signature: string) => {
    const response = await client.get<{ data: { message: string } }>(
      `/v1/auth/email/verify/${id}/${hash}`,
      { params: { expires, signature } }
    )
    return response.data.data
  },

  resendVerificationEmail: async () => {
    const response = await client.post<{ data: { message: string } }>('/v1/auth/email/resend', {})
    return response.data.data
  },

  // Password reset
  forgotPassword: async (email: string) => {
    const response = await client.post<{ data: { message: string } }>('/v1/auth/forgot-password', { email })
    return response.data.data
  },

  resetPassword: async (payload: { token: string; email: string; password: string; password_confirmation: string }) => {
    const response = await client.post<{ data: { message: string } }>('/v1/auth/reset-password', payload)
    return response.data.data
  },

  // Tutorial
  updateTutorialProgress: async (payload: { reset?: boolean; step_id?: string; completed?: boolean; dismissed?: boolean }) => {
    const response = await client.put<{ data: User }>('/v1/users/me/tutorial', payload)
    return response.data.data.tutorial_progress ?? null
  },

  // Budgets
  upsertBudget: async (payload: { category_id: number; year: number; month: number; amount: number }) => {
    const response = await client.post('/v1/budgets', payload)
    return response.data
  },

  // Reports
  listReports: async (params?: { status?: string; per_page?: number; page?: number }) => {
    const response = await client.get<PaginatedResponse<Report>>('/v1/reports', { params })
    return response.data
  },

  createReport: async (payload: {
    format: 'csv' | 'pdf'
    category_id?: number
    type?: string
    payment_method?: string
    month?: string
    date_from?: string
    date_to?: string
    installment?: boolean
  }) => {
    const response = await client.post<{ data: Report }>('/v1/reports', payload)
    return response.data.data
  },

  getReport: async (id: number) => {
    const response = await client.get<{ data: Report }>(`/v1/reports/${id}`)
    return response.data.data
  },

  downloadReport: async (id: number, filename: string) => {
    const response = await client.get(`/v1/reports/${id}/download`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  },
}
