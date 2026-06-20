import { useTranslation } from 'react-i18next'
import { useCategories, useCreateTransaction, useTransactions } from '../hooks/api'
import { getCategoryIconClass } from '../constants/categoryIcons'
import { extractApiError } from '../services/api'
import { useMemo, useState, type SyntheticEvent } from 'react'
import '../styles/pages.css'

const paymentMethods = ['cartao_credito', 'cartao_debito', 'dinheiro', 'pix', 'boleto', 'ted'] as const

function normalizeType(type: string | undefined): 'entrada' | 'saida' {
  if (type === 'income' || type === 'entrada') {
    return 'entrada'
  }

  return 'saida'
}

function formatTransactionDate(value: string): string {
  const datePart = value.slice(0, 10)

  if (/^\d{4}-\d{2}-\d{2}$/.test(datePart)) {
    const [year, month, day] = datePart.split('-').map(Number)
    return new Date(year, month - 1, day).toLocaleDateString()
  }

  const parsed = new Date(value)
  return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleDateString()
}

export function TransactionsPage() {
  const { t } = useTranslation()
  const [month, setMonth] = useState(new Date().toISOString().slice(0, 7))
  const [type, setType] = useState('')
  const [page, setPage] = useState(1)
  const [createType, setCreateType] = useState<'entrada' | 'saida'>('saida')
  const [categoryId, setCategoryId] = useState('')
  const [paymentMethod, setPaymentMethod] = useState('pix')
  const [amount, setAmount] = useState('')
  const [transactedAt, setTransactedAt] = useState(new Date().toISOString().slice(0, 10))
  const [notes, setNotes] = useState('')
  const [error, setError] = useState('')
  const [success, setSuccess] = useState('')

  const createTransaction = useCreateTransaction()
  const { data: categories = [] } = useCategories()

  const params = {
    month,
    type: type || undefined,
    per_page: 20,
    page,
  }

  const { data: transactionsResponse, isLoading } = useTransactions(params)
  const transactions = transactionsResponse?.data || []
  const meta = transactionsResponse?.meta

  const creatableCategories = useMemo(
    () => categories.filter((category) => normalizeType(category.type) === createType),
    [categories, createType],
  )

  const selectedCategory = useMemo(
    () => creatableCategories.find((category) => String(category.id) === categoryId),
    [creatableCategories, categoryId],
  )

  const handleCreate = async (event: SyntheticEvent<HTMLFormElement>) => {
    event.preventDefault()
    setError('')
    setSuccess('')

    if (!categoryId) {
      setError(t('transactions.select_category'))
      return
    }

    try {
      await createTransaction.mutateAsync({
        category_id: Number(categoryId),
        type: createType,
        payment_method: paymentMethod,
        amount: Number(amount),
        transacted_at: transactedAt,
        notes: notes || undefined,
      })

      setAmount('')
      setNotes('')
      setSuccess(t('transactions.create_success'))
      setPage(1)
    } catch (err) {
      setError(extractApiError(err).message)
    }
  }

  if (isLoading) {
    return <div className="page">{t('common.loading')}</div>
  }

  return (
    <div className="page transactions-page">
      <h1>{t('transactions.title')}</h1>

      <form className="card form-card" onSubmit={handleCreate}>
        <h2>{t('transactions.new_entry')}</h2>

        <div className="form-grid">
          <div className="form-group">
            <label htmlFor="category">{t('transactions.category')}</label>
            <select id="category" value={categoryId} onChange={(event) => setCategoryId(event.target.value)} required>
              <option value="">{t('transactions.select_category')}</option>
              {creatableCategories.map((category) => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>
            {selectedCategory && (
              <p className="selected-category-icon">
                <span className="category-icon-wrapper" aria-hidden="true">
                  <i className={getCategoryIconClass(selectedCategory.icon)} />
                </span>
                {selectedCategory.name}
              </p>
            )}
          </div>

          <div className="form-group">
            <label htmlFor="create-type">{t('transactions.type')}</label>
            <select
              id="create-type"
              value={createType}
              onChange={(event) => {
                const nextType = event.target.value === 'entrada' ? 'entrada' : 'saida'
                setCreateType(nextType)
                setCategoryId('')
              }}
            >
              <option value="entrada">{t('categories.type_income')}</option>
              <option value="saida">{t('categories.type_expense')}</option>
            </select>
          </div>

          <div className="form-group">
            <label htmlFor="payment-method">{t('transactions.payment_method')}</label>
            <select id="payment-method" value={paymentMethod} onChange={(event) => setPaymentMethod(event.target.value)}>
              {paymentMethods.map((method) => (
                <option key={method} value={method}>
                  {t(`transactions.payment.${method}`)}
                </option>
              ))}
            </select>
          </div>

          <div className="form-group">
            <label htmlFor="amount">{t('transactions.amount')}</label>
            <input
              id="amount"
              type="number"
              min="0.01"
              step="0.01"
              value={amount}
              onChange={(event) => setAmount(event.target.value)}
              required
            />
          </div>

          <div className="form-group">
            <label htmlFor="transacted-at">{t('transactions.date')}</label>
            <input id="transacted-at" type="date" value={transactedAt} onChange={(event) => setTransactedAt(event.target.value)} required />
          </div>

          <div className="form-group full-width">
            <label htmlFor="notes">{t('transactions.notes')}</label>
            <input id="notes" type="text" value={notes} onChange={(event) => setNotes(event.target.value)} />
          </div>
        </div>

        {error && <div className="error-message">{error}</div>}
        {success && <div className="success-message">{success}</div>}

        <button type="submit" className="btn btn-primary" disabled={createTransaction.isPending}>
          {createTransaction.isPending ? t('common.loading') : t('common.create')}
        </button>
      </form>

      <div className="filters card">
        <div className="form-group">
          <label htmlFor="month">{t('transactions.period')}</label>
          <input
            type="month"
            id="month"
            value={month}
            onChange={(event) => {
              setMonth(event.target.value)
              setPage(1)
            }}
          />
        </div>

        <div className="form-group">
          <label htmlFor="filter-type">{t('transactions.type')}</label>
          <select
            id="filter-type"
            value={type}
            onChange={(event) => {
              setType(event.target.value)
              setPage(1)
            }}
          >
            <option value="">{t('transactions.all_types')}</option>
            <option value="entrada">{t('categories.type_income')}</option>
            <option value="saida">{t('categories.type_expense')}</option>
          </select>
        </div>
      </div>

      {transactions.length === 0 ? (
        <p className="empty-state">{t('transactions.empty')}</p>
      ) : (
        <>
          <div className="transaction-list">
            {transactions.map((tx) => (
              <div key={tx.id} className="transaction-row card">
                <div className="tx-details">
                  <p className="tx-category">
                    <span className="category-icon-wrapper" aria-hidden="true">
                      <i className={getCategoryIconClass(tx.category?.icon)} />
                    </span>
                    {tx.category?.name}
                  </p>
                  <p className="tx-date">{formatTransactionDate(tx.transacted_at)}</p>
                  {tx.notes && <p className="tx-notes">{tx.notes}</p>}
                </div>
                <p className={`tx-amount ${normalizeType(tx.type) === 'entrada' ? 'income' : 'expense'}`}>
                  {normalizeType(tx.type) === 'entrada' ? '+' : '-'} R$ {Number(tx.amount).toFixed(2)}
                </p>
              </div>
            ))}
          </div>

          {meta && meta.last_page > 1 && (
            <div className="pagination">
              <button disabled={page === 1} onClick={() => setPage(page - 1)}>
                ← {t('common.previous')}
              </button>
              <span>
                {page} / {meta.last_page}
              </span>
              <button disabled={page === meta.last_page} onClick={() => setPage(page + 1)}>
                {t('common.next')} →
              </button>
            </div>
          )}
        </>
      )}
    </div>
  )
}
