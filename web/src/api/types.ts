export type ApiError = {
  type: string
  message: string
  details?: Record<string, string[]>
}

export type ApiEnvelope<T> = {
  data: T
  meta?: Record<string, unknown>
}

export type User = {
  id: number
  name: string
  email: string
  plan: string
  subscription_status: string
}

export type AuthPayload = {
  token: string
  user: User
}

export type Category = {
  id: number
  user_id: number
  name: string
  type: 'income' | 'expense'
  created_at: string
  updated_at: string
}

export type Transaction = {
  id: number
  user_id: number
  category_id: number
  type: 'income' | 'expense'
  amount: string
  transacted_at: string
  notes: string | null
  category?: Category
  created_at: string
  updated_at: string
}

export type Budget = {
  id: number
  user_id: number
  category_id: number
  year: number
  month: number
  amount: string
  category?: Category
  created_at: string
  updated_at: string
}

export type CategoryVariance = {
  category_id: number
  category_name: string | null
  budget: number
  actual: number
  variance: number
}

export type MonthlySummary = {
  year: number
  month: number
  income_total: number
  expense_total: number
  net_balance: number
  category_variance: CategoryVariance[]
}
