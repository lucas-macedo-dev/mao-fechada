export interface TutorialProgress {
  completed_steps: string[]
  dismissed: boolean
}

export interface User {
  id: string
  name: string
  email: string
  email_verified: boolean
  locale: string
  profile_photo_url?: string | null
  tutorial_progress?: TutorialProgress | null
}

export interface Category {
  id: number
  name: string
  type: 'income' | 'expense'
  icon?: string
  parent_id?: number | null
  children?: Category[]
  created_at: string
  updated_at: string
}

export interface Transaction {
  id: number
  category_id: number
  type: 'income' | 'expense'
  payment_method: 'credit_card' | 'debit_card' | 'cash' | 'pix' | 'bank_slip' | 'bank_transfer'
  amount: string | number
  transacted_at: string
  notes?: string
  installment_group_id?: string | null
  installment_number?: number | null
  installment_total?: number | null
  category?: Category
  created_at: string
  updated_at: string
}

export interface DashboardSummary {
  month: string
  totals: {
    entradas: number
    saidas: number
    saldo: number
  }
  chart: {
    entradas: number
    saidas: number
  }
  recent_transactions: Transaction[]
}

export interface MonthlySummary {
  year: number
  month: number
  income_total: number
  expense_total: number
  net_balance: number
  category_variance: Array<{
    category_id: number
    category_name: string | null
    budget: number
    actual: number
    variance: number
  }>
}

export interface SubscriptionStatus {
  plan_code: string
  status: string
  provider: string | null
  trial_ends_at: string | null
  current_period_ends_at: string | null
  canceled_at: string | null
  entitlements: Record<string, { limit: number | null; is_enforced: boolean }>
}

export interface Plan {
  code: string
  name: string
  features: Record<string, { limit: number | null; is_enforced: boolean }>
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface DashboardCategoryItem {
  name: string
  value: number
}

export interface DashboardDayItem {
  day: number
  income: number
  expense: number
}

export interface DashboardMonthlyComparisonItem {
  month: string
  income: number
  expense: number
}

export interface DashboardMtdComparisonMetric {
  current: { month: string; through_day: number; total: number }
  previous: { month: string; through_day: number; total: number }
  change_percent: number | null
}

export interface DashboardMtdComparison {
  income: DashboardMtdComparisonMetric
  expense: DashboardMtdComparisonMetric
  balance: DashboardMtdComparisonMetric
  installments: DashboardMtdComparisonMetric
}

export interface DashboardPaymentMethodItem {
  name: 'credit_card' | 'debit_card' | 'cash' | 'pix' | 'bank_slip' | 'bank_transfer'
  value: number
}

export interface DashboardWeeklyExpenseItem {
  weekday: number
  date: string
  expense: number
}

export interface ApiError {
  type: string
  message: string
  details?: Record<string, string[]>
}

export interface Report {
  id: number
  format: 'csv' | 'pdf'
  status: 'pending' | 'processing' | 'completed' | 'failed'
  filters: Record<string, unknown> | null
  failure_message?: string | null
  expires_at?: string | null
  completed_at?: string | null
  created_at: string
  updated_at: string
}
