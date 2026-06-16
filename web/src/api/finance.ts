import { get, post, put, remove } from './client'
import type { AuthPayload, Budget, Category, MonthlySummary, Transaction, User } from './types'

export const financeApi = {
  register(payload: { name: string; email: string; password: string }) {
    return post<AuthPayload>('/v1/auth/register', payload)
  },
  login(payload: { email: string; password: string }) {
    return post<AuthPayload>('/v1/auth/login', payload)
  },
  me() {
    return get<User>('/v1/auth/me')
  },
  logout() {
    return post<{ message: string }>('/v1/auth/logout', {})
  },
  listCategories() {
    return get<Category[]>('/v1/categories')
  },
  createCategory(payload: { name: string; type: 'income' | 'expense' }) {
    return post<Category>('/v1/categories', payload)
  },
  listTransactions() {
    return get<Transaction[]>('/v1/transactions')
  },
  createTransaction(payload: {
    category_id: number
    type: 'income' | 'expense'
    amount: number
    transacted_at: string
    notes?: string
  }) {
    return post<Transaction>('/v1/transactions', payload)
  },
  updateTransaction(
    id: number,
    payload: Partial<{
      category_id: number
      type: 'income' | 'expense'
      amount: number
      transacted_at: string
      notes: string
    }>,
  ) {
    return put<Transaction>(`/v1/transactions/${id}`, payload)
  },
  deleteTransaction(id: number) {
    return remove(`/v1/transactions/${id}`)
  },
  listBudgets(year: number, month: number) {
    return get<Budget[]>('/v1/budgets', { year, month })
  },
  upsertBudget(payload: { category_id: number; year: number; month: number; amount: number }) {
    return post<Budget>('/v1/budgets', payload)
  },
  getMonthlySummary(year: number, month: number) {
    return get<MonthlySummary>('/v1/summaries/monthly', { year, month })
  },
}
