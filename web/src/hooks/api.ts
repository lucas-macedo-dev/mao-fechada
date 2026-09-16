import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { api, setAuthToken } from '../services/api'

// Auth
export function useAuth() {
  const queryClient = useQueryClient()

  const registerMutation = useMutation({
    mutationFn: (payload: { name: string; email: string; password: string; locale?: string }) => api.register(payload),
    onSuccess: (data) => {
      setAuthToken(data.token)
      localStorage.setItem('auth_token', data.token)
      queryClient.setQueryData(['auth', 'me'], data.user)
    },
  })

  const loginMutation = useMutation({
    mutationFn: (payload: { email: string; password: string }) => api.login(payload),
    onSuccess: (data) => {
      setAuthToken(data.token)
      localStorage.setItem('auth_token', data.token)
      queryClient.setQueryData(['auth', 'me'], data.user)
    },
  })

  const logoutMutation = useMutation({
    mutationFn: () => api.logout(),
    onSuccess: () => {
      setAuthToken(null)
      localStorage.removeItem('auth_token')
      queryClient.clear()
    },
  })

  const meQuery = useQuery({
    queryKey: ['auth', 'me'],
    queryFn: () => api.me(),
    retry: false,
    enabled: !!localStorage.getItem('auth_token'),
  })

  return {
    register: registerMutation,
    login: loginMutation,
    logout: logoutMutation,
    me: meQuery,
    user: meQuery.data,
    isAuthenticated: !!meQuery.data,
  }
}

// Categories
export function useCategories(tree?: boolean) {
  return useQuery({
    queryKey: ['categories', { tree }],
    queryFn: () => api.listCategories(tree),
    enabled: !!localStorage.getItem('auth_token'),
  })
}

export function useCreateCategory() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (payload: { name: string; type: string; icon?: string; parent_id?: number }) => api.createCategory(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['categories'] })
    },
  })
}

export function useUpdateCategory() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (params: { id: number; payload: Partial<{ name: string; type: string; icon?: string; parent_id?: number }> }) =>
      api.updateCategory(params.id, params.payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['categories'] })
      queryClient.invalidateQueries({ queryKey: ['transactions'] })
      queryClient.invalidateQueries({ queryKey: ['dashboard'] })
    },
  })
}

export function useDeleteCategory() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => api.deleteCategory(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['categories'] })
      queryClient.invalidateQueries({ queryKey: ['transactions'] })
      queryClient.invalidateQueries({ queryKey: ['dashboard'] })
    },
  })
}

// Transactions
export function useTransactions(params?: {
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
}) {
  return useQuery({
    queryKey: ['transactions', params],
    queryFn: () => api.listTransactions(params),
    enabled: !!localStorage.getItem('auth_token'),
  })
}

export function useCreateTransaction() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (payload: { category_id: number; type: string; payment_method: string; amount: number; transacted_at: string; notes?: string; installment_number?: number; installment_total?: number; recurring?: boolean }) =>
      api.createTransaction(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['transactions'] })
      queryClient.invalidateQueries({ queryKey: ['dashboard'] })
      queryClient.invalidateQueries({ queryKey: ['recurring-transactions'] })
    },
  })
}

export function useUpdateTransaction() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (params: {
      id: number
      payload: Partial<{ category_id: number; type: string; payment_method: string; amount: number; transacted_at: string; notes?: string }>
    }) => api.updateTransaction(params.id, params.payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['transactions'] })
      queryClient.invalidateQueries({ queryKey: ['dashboard'] })
    },
  })
}

export function useDeleteTransaction() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => api.deleteTransaction(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['transactions'] })
      queryClient.invalidateQueries({ queryKey: ['dashboard'] })
    },
  })
}

// Recurring Transactions
export function useRecurringTransactions() {
  return useQuery({
    queryKey: ['recurring-transactions'],
    queryFn: () => api.listRecurringTransactions(),
    enabled: !!localStorage.getItem('auth_token'),
  })
}

export function useCancelRecurringTransaction() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => api.cancelRecurringTransaction(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['recurring-transactions'] })
      queryClient.invalidateQueries({ queryKey: ['transactions'] })
      queryClient.invalidateQueries({ queryKey: ['dashboard'] })
    },
  })
}

// Dashboard
export function useDashboardSummary(month?: string) {
  return useQuery({
    queryKey: ['dashboard', 'summary', month],
    queryFn: () => api.getDashboardSummary(month),
    enabled: !!localStorage.getItem('auth_token'),
  })
}

export function useDashboardByCategory(month?: string) {
  return useQuery({
    queryKey: ['dashboard', 'by-category', month],
    queryFn: () => api.getDashboardByCategory(month),
    enabled: !!localStorage.getItem('auth_token'),
  })
}

export function useDashboardByDay(month?: string) {
  return useQuery({
    queryKey: ['dashboard', 'by-day', month],
    queryFn: () => api.getDashboardByDay(month),
    enabled: !!localStorage.getItem('auth_token'),
  })
}

export function useInstallmentsTotal(month?: string) {
  return useQuery({
    queryKey: ['dashboard', 'installments-total', month],
    queryFn: () => api.getDashboardInstallmentsTotal(month),
    enabled: !!localStorage.getItem('auth_token'),
  })
}

export function useDashboardMonthlyComparison(month?: string) {
  return useQuery({
    queryKey: ['dashboard', 'monthly-comparison', month],
    queryFn: () => api.getDashboardMonthlyComparison(month),
    enabled: !!localStorage.getItem('auth_token'),
  })
}

export function useDashboardMtdComparison(month?: string) {
  return useQuery({
    queryKey: ['dashboard', 'mtd-comparison', month],
    queryFn: () => api.getDashboardMtdComparison(month),
    enabled: !!localStorage.getItem('auth_token'),
  })
}

export function useDashboardByPaymentMethod(month?: string) {
  return useQuery({
    queryKey: ['dashboard', 'by-payment-method', month],
    queryFn: () => api.getDashboardByPaymentMethod(month),
    enabled: !!localStorage.getItem('auth_token'),
  })
}

export function useDashboardWeeklyExpenses() {
  return useQuery({
    queryKey: ['dashboard', 'weekly-expenses'],
    queryFn: () => api.getDashboardWeeklyExpenses(),
    enabled: !!localStorage.getItem('auth_token'),
  })
}

export function useSubscription() {
  return useQuery({
    queryKey: ['subscription'],
    queryFn: () => api.getSubscription(),
    enabled: !!localStorage.getItem('auth_token'),
  })
}

export function usePlans() {
  return useQuery({
    queryKey: ['plans'],
    queryFn: () => api.listPlans(),
    enabled: !!localStorage.getItem('auth_token'),
  })
}

export function useUpdateProfile() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (payload: { name?: string; email?: string; password?: string; password_confirmation?: string; locale?: string } | FormData) => api.updateProfile(payload),
    onSuccess: (user) => {
      queryClient.setQueryData(['auth', 'me'], user)
      queryClient.invalidateQueries({ queryKey: ['subscription'] })
    },
  })
}


// Reports
export function useReports(params?: { status?: string; per_page?: number; page?: number }) {
  return useQuery({
    queryKey: ['reports', params],
    queryFn: () => api.listReports(params),
    enabled: !!localStorage.getItem('auth_token'),
    refetchInterval: (query) => {
      const items = query.state.data?.data ?? []
      return items.some((r) => r.status === 'pending' || r.status === 'processing') ? 5000 : false
    },
  })
}

export function useCreateReport() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (payload: {
      format: 'csv' | 'pdf'
      category_id?: number
      type?: string
      payment_method?: string
      month?: string
      date_from?: string
      date_to?: string
      installment?: boolean
    }) => api.createReport(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['reports'] })
    },
  })
}

export function useDownloadReport() {
  return useMutation({
    mutationFn: (params: { id: number; filename: string }) => api.downloadReport(params.id, params.filename),
  })
}