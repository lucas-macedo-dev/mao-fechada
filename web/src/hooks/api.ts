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
export function useTransactions(params?: { category_id?: number; type?: string; payment_method?: string; month?: string; per_page?: number; page?: number }) {
  return useQuery({
    queryKey: ['transactions', params],
    queryFn: () => api.listTransactions(params),
    enabled: !!localStorage.getItem('auth_token'),
  })
}

export function useCreateTransaction() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (payload: { category_id: number; type: string; payment_method: string; amount: number; transacted_at: string; notes?: string }) =>
      api.createTransaction(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['transactions'] })
      queryClient.invalidateQueries({ queryKey: ['dashboard'] })
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
