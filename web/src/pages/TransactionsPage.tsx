import { useTranslation } from 'react-i18next'
import { useTransactions } from '../hooks/api'
import { useState } from 'react'
import '../styles/pages.css'

export function TransactionsPage() {
  const { t } = useTranslation()
  const [month, setMonth] = useState(new Date().toISOString().slice(0, 7))
  const [type, setType] = useState('')
  const [page, setPage] = useState(1)

  const params = {
    month,
    type: type || undefined,
    per_page: 20,
  }

  const { data: transactionsResponse, isLoading } = useTransactions(params)
  const transactions = transactionsResponse?.data || []
  const meta = transactionsResponse?.meta

  if (isLoading) {
    return <div className="page">{t('common.loading')}</div>
  }

  return (
    <div className="page transactions-page">
      <h1>{t('transactions.title')}</h1>

      <div className="filters card">
        <div className="form-group">
          <label htmlFor="month">Período</label>
          <input type="month" id="month" value={month} onChange={(e) => setMonth(e.target.value)} />
        </div>

        <div className="form-group">
          <label htmlFor="type">{t('transactions.type')}</label>
          <select id="type" value={type} onChange={(e) => setType(e.target.value)}>
            <option value="">Todos</option>
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
                  <p className="tx-category">{tx.category?.name}</p>
                  <p className="tx-date">{new Date(tx.transacted_at).toLocaleDateString()}</p>
                  {tx.notes && <p className="tx-notes">{tx.notes}</p>}
                </div>
                <p className={`tx-amount ${tx.type === 'entrada' ? 'income' : 'expense'}`}>
                  {tx.type === 'entrada' ? '+' : '-'} R$ {Number(tx.amount).toFixed(2)}
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
