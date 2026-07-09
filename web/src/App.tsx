import { useCallback, useEffect, useMemo, useState } from 'react'
import type { FormEvent } from 'react'
import './App.css'
import { extractApiError, setAuthToken } from './api/client'
import { financeApi } from './api/finance'
import type { Category, MonthlySummary, Transaction, User } from './api/types'

type Credentials = {
  name: string
  email: string
  password: string
}

const now = new Date()

function App() {
  const [token, setToken] = useState<string | null>(localStorage.getItem('auth_token'))
  const [user, setUser] = useState<User | null>(null)
  const [categories, setCategories] = useState<Category[]>([])
  const [transactions, setTransactions] = useState<Transaction[]>([])
  const [summary, setSummary] = useState<MonthlySummary | null>(null)
  const [message, setMessage] = useState('')
  const [loading, setLoading] = useState(false)
  const [year, setYear] = useState(now.getFullYear())
  const [month, setMonth] = useState(now.getMonth() + 1)

  const [credentials, setCredentials] = useState<Credentials>({
    name: '',
    email: '',
    password: '',
  })
  const [categoryForm, setCategoryForm] = useState({
    name: '',
    type: 'expense' as 'income' | 'expense',
  })
  const [transactionForm, setTransactionForm] = useState({
    category_id: 0,
    type: 'expense' as 'income' | 'expense',
    amount: '',
    transacted_at: new Date().toISOString().slice(0, 10),
    notes: '',
  })
  const [editingTransactionId, setEditingTransactionId] = useState<number | null>(null)
  const [budgetForm, setBudgetForm] = useState({
    category_id: 0,
    amount: '',
  })

  const expenseCategories = useMemo(
    () => categories.filter((category) => category.type === 'expense'),
    [categories],
  )

  const loadFinanceData = useCallback(async () => {
    try {
      const [loadedCategories, loadedTransactions, loadedSummary] = await Promise.all([
        financeApi.listCategories(),
        financeApi.listTransactions(),
        financeApi.getMonthlySummary(year, month),
      ])
      setCategories(loadedCategories)
      setTransactions(loadedTransactions)
      setSummary(loadedSummary)
      setMessage('')
    } catch (error) {
      setMessage(extractApiError(error).message)
    }
  }, [year, month])

  useEffect(() => {
    setAuthToken(token)

    if (!token) {
      return
    }

    financeApi
      .me()
      .then(setUser)
      .catch(() => {
        localStorage.removeItem('auth_token')
        setToken(null)
        setAuthToken(null)
      })
  }, [token])

  useEffect(() => {
    if (!user) return

    const timeoutId = window.setTimeout(() => {
      void loadFinanceData()
    }, 0)

    return () => {
      window.clearTimeout(timeoutId)
    }
  }, [user, loadFinanceData])

  async function handleRegister(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    await authenticate('register')
  }

  async function handleLogin(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    await authenticate('login')
  }

  async function authenticate(mode: 'register' | 'login') {
    setLoading(true)
    try {
      const payload =
        mode === 'register'
          ? await financeApi.register(credentials)
          : await financeApi.login({
              email: credentials.email,
              password: credentials.password,
            })

      localStorage.setItem('auth_token', payload.token)
      setToken(payload.token)
      setUser(payload.user)
      setMessage('')
    } catch (error) {
      setMessage(extractApiError(error).message)
    } finally {
      setLoading(false)
    }
  }

  async function handleLogout() {
    if (!token) return
    try {
      await financeApi.logout()
    } finally {
      localStorage.removeItem('auth_token')
      setToken(null)
      setAuthToken(null)
      setCategories([])
      setTransactions([])
      setSummary(null)
    }
  }

  async function handleCreateCategory(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setLoading(true)
    try {
      await financeApi.createCategory(categoryForm)
      setCategoryForm({ name: '', type: 'expense' })
      await loadFinanceData()
    } catch (error) {
      setMessage(extractApiError(error).message)
    } finally {
      setLoading(false)
    }
  }

  async function handleSaveTransaction(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setLoading(true)

    try {
      const payload = {
        category_id: Number(transactionForm.category_id),
        type: transactionForm.type,
        amount: Number(transactionForm.amount),
        transacted_at: transactionForm.transacted_at,
        notes: transactionForm.notes || undefined,
      }

      if (editingTransactionId) {
        await financeApi.updateTransaction(editingTransactionId, payload)
      } else {
        await financeApi.createTransaction(payload)
      }

      resetTransactionForm()
      await loadFinanceData()
    } catch (error) {
      setMessage(extractApiError(error).message)
    } finally {
      setLoading(false)
    }
  }

  async function handleDeleteTransaction(id: number) {
    setLoading(true)
    try {
      await financeApi.deleteTransaction(id)
      await loadFinanceData()
    } catch (error) {
      setMessage(extractApiError(error).message)
    } finally {
      setLoading(false)
    }
  }

  async function handleUpsertBudget(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setLoading(true)
    try {
      await financeApi.upsertBudget({
        category_id: Number(budgetForm.category_id),
        year,
        month,
        amount: Number(budgetForm.amount),
      })
      setBudgetForm({ category_id: 0, amount: '' })
      await loadFinanceData()
    } catch (error) {
      setMessage(extractApiError(error).message)
    } finally {
      setLoading(false)
    }
  }

  function resetTransactionForm() {
    setEditingTransactionId(null)
    setTransactionForm({
      category_id: 0,
      type: 'expense',
      amount: '',
      transacted_at: new Date().toISOString().slice(0, 10),
      notes: '',
    })
  }

  function beginEdit(transaction: Transaction) {
    setEditingTransactionId(transaction.id)
    setTransactionForm({
      category_id: transaction.category_id,
      type: transaction.type,
      amount: transaction.amount,
      transacted_at: transaction.transacted_at,
      notes: transaction.notes ?? '',
    })
  }

  if (!user) {
    return (
      <main className="layout auth-layout">
        <section className="card hero">
          <h1>Mão Fechada Controle Financeiro</h1>
          <p className="subtitle">Simple personal finance now, mobile-ready later.</p>
        </section>

        <div className="grid two auth-grid">
          <form className="card form-card" onSubmit={handleRegister}>
            <h2>Register</h2>
            <input
              placeholder="Name"
              value={credentials.name}
              onChange={(event) => setCredentials((old) => ({ ...old, name: event.target.value }))}
            />
            <input
              placeholder="Email"
              type="email"
              value={credentials.email}
              onChange={(event) => setCredentials((old) => ({ ...old, email: event.target.value }))}
            />
            <input
              placeholder="Password"
              type="password"
              value={credentials.password}
              onChange={(event) => setCredentials((old) => ({ ...old, password: event.target.value }))}
            />
            <button disabled={loading} type="submit">
              Register
            </button>
          </form>

          <form className="card form-card" onSubmit={handleLogin}>
            <h2>Login</h2>
            <input
              placeholder="Email"
              type="email"
              value={credentials.email}
              onChange={(event) => setCredentials((old) => ({ ...old, email: event.target.value }))}
            />
            <input
              placeholder="Password"
              type="password"
              value={credentials.password}
              onChange={(event) => setCredentials((old) => ({ ...old, password: event.target.value }))}
            />
            <button disabled={loading} type="submit">
              Login
            </button>
          </form>
        </div>

        {message ? <p className="message">{message}</p> : null}
      </main>
    )
  }

  return (
    <main className="layout">
      <header className="header">
        <div>
          <h1>Mão Fechada Controle Financeiro</h1>
          <p className="subtitle">
            {user.name} • Plan: {user.plan} • Subscription: {user.subscription_status}
          </p>
        </div>
        <button onClick={handleLogout} type="button" className="secondary">
          Logout
        </button>
      </header>

      <section className="card filters">
        <h2>Month</h2>
        <div className="grid three">
          <input
            type="number"
            value={year}
            onChange={(event) => setYear(Number(event.target.value))}
            min={2000}
            max={2100}
          />
          <input
            type="number"
            value={month}
            onChange={(event) => setMonth(Number(event.target.value))}
            min={1}
            max={12}
          />
          <button type="button" onClick={() => void loadFinanceData()}>
            Refresh
          </button>
        </div>
      </section>

      <section className="grid two">
        <form className="card form-card" onSubmit={handleCreateCategory}>
          <h2>Create Category</h2>
          <input
            placeholder="Category name"
            value={categoryForm.name}
            onChange={(event) => setCategoryForm((old) => ({ ...old, name: event.target.value }))}
          />
          <select
            value={categoryForm.type}
            onChange={(event) =>
              setCategoryForm((old) => ({ ...old, type: event.target.value as 'income' | 'expense' }))
            }
          >
            <option value="expense">Expense</option>
            <option value="income">Income</option>
          </select>
          <button disabled={loading} type="submit">
            Save Category
          </button>
        </form>

        <form className="card form-card" onSubmit={handleUpsertBudget}>
          <h2>Monthly Budget</h2>
          <select
            value={budgetForm.category_id}
            onChange={(event) => setBudgetForm((old) => ({ ...old, category_id: Number(event.target.value) }))}
          >
            <option value={0}>Select expense category</option>
            {expenseCategories.map((category) => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </select>
          <input
            placeholder="Amount"
            type="number"
            min={0.01}
            step={0.01}
            value={budgetForm.amount}
            onChange={(event) => setBudgetForm((old) => ({ ...old, amount: event.target.value }))}
          />
          <button disabled={loading} type="submit">
            Save Budget
          </button>
        </form>
      </section>

      <section className="card">
        <h2>{editingTransactionId ? 'Edit Transaction' : 'Create Transaction'}</h2>
        <form className="grid two" onSubmit={handleSaveTransaction}>
          <select
            value={transactionForm.category_id}
            onChange={(event) =>
              setTransactionForm((old) => ({ ...old, category_id: Number(event.target.value) }))
            }
          >
            <option value={0}>Select category</option>
            {categories.map((category) => (
              <option key={category.id} value={category.id}>
                {category.name} ({category.type})
              </option>
            ))}
          </select>
          <select
            value={transactionForm.type}
            onChange={(event) =>
              setTransactionForm((old) => ({ ...old, type: event.target.value as 'income' | 'expense' }))
            }
          >
            <option value="expense">Expense</option>
            <option value="income">Income</option>
          </select>
          <input
            type="number"
            min={0.01}
            step={0.01}
            placeholder="Amount"
            value={transactionForm.amount}
            onChange={(event) => setTransactionForm((old) => ({ ...old, amount: event.target.value }))}
          />
          <input
            type="date"
            value={transactionForm.transacted_at}
            onChange={(event) => setTransactionForm((old) => ({ ...old, transacted_at: event.target.value }))}
          />
          <input
            className="span-two"
            placeholder="Notes (optional)"
            value={transactionForm.notes}
            onChange={(event) => setTransactionForm((old) => ({ ...old, notes: event.target.value }))}
          />
          <div className="row span-two">
            <button disabled={loading} type="submit">
              {editingTransactionId ? 'Update' : 'Create'}
            </button>
            {editingTransactionId ? (
              <button type="button" className="secondary" onClick={resetTransactionForm}>
                Cancel Edit
              </button>
            ) : null}
          </div>
        </form>
      </section>

      <section className="card">
        <h2>Transactions</h2>
        {transactions.length === 0 ? (
          <p className="empty-state">No transactions yet for this month.</p>
        ) : (
          <div className="table">
            {transactions.map((transaction) => (
              <div key={transaction.id} className="table-row transaction-row">
                <span className="field">
                  <small>Date</small>
                  <strong>{transaction.transacted_at}</strong>
                </span>
                <span className="field">
                  <small>Category</small>
                  <strong>{transaction.category?.name ?? '-'}</strong>
                </span>
                <span className="field">
                  <small>Type</small>
                  <strong>{transaction.type}</strong>
                </span>
                <span className="field">
                  <small>Amount</small>
                  <strong>{transaction.amount}</strong>
                </span>
                <span className="field">
                  <small>Notes</small>
                  <strong>{transaction.notes ?? '-'}</strong>
                </span>
                <div className="row actions">
                  <button type="button" className="secondary" onClick={() => beginEdit(transaction)}>
                    Edit
                  </button>
                  <button
                    type="button"
                    className="secondary danger"
                    onClick={() => void handleDeleteTransaction(transaction.id)}
                  >
                    Delete
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
      </section>

      <section className="card">
        <h2>Monthly Summary</h2>
        <div className="summary-totals">
          <p>Income: {summary?.income_total ?? 0}</p>
          <p>Expense: {summary?.expense_total ?? 0}</p>
          <p>Net: {summary?.net_balance ?? 0}</p>
        </div>
        <div className="table">
          {(summary?.category_variance ?? []).map((item) => (
            <div key={item.category_id} className="table-row summary-row">
              <span className="field">
                <small>Category</small>
                <strong>{item.category_name ?? `Category ${item.category_id}`}</strong>
              </span>
              <span className="field">
                <small>Budget</small>
                <strong>{item.budget}</strong>
              </span>
              <span className="field">
                <small>Actual</small>
                <strong>{item.actual}</strong>
              </span>
              <span className="field">
                <small>Variance</small>
                <strong>{item.variance}</strong>
              </span>
            </div>
          ))}
        </div>
      </section>

      {message ? <p className="message">{message}</p> : null}
    </main>
  )
}

export default App
