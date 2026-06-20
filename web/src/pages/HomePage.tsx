import { useTranslation } from 'react-i18next'
import { useDashboardSummary, useTransactions } from '../hooks/api'
import { useState } from 'react'
import '../styles/pages.css'

export function HomePage() {
  const { t } = useTranslation()
  const [month, setMonth] = useState(new Date().toISOString().slice(0, 7))
  const { data: summary, isLoading } = useDashboardSummary(month)
  const { data: transactionsResponse } = useTransactions({ month, per_page: 5 })

  const transactions = transactionsResponse?.data || []

  if (isLoading) {
    return <div className="page">{t('common.loading')}</div>
  }

  return (
    <div className="page home-page">
      <h1>{t('dashboard.title')}</h1>

      <div className="month-selector">
        <input type="month" value={month} onChange={(e) => setMonth(e.target.value)} />
      </div>

      {summary && (
        <>
          <div className="summary-cards">
            <div className="card income">
              <p className="label">{t('dashboard.income')}</p>
              <p className="value">R$ {summary.totals.entradas.toFixed(2)}</p>
            </div>
            <div className="card expense">
              <p className="label">{t('dashboard.expense')}</p>
              <p className="value">R$ {summary.totals.saidas.toFixed(2)}</p>
            </div>
            <div className="card balance">
              <p className="label">{t('dashboard.balance')}</p>
              <p className="value">R$ {summary.totals.saldo.toFixed(2)}</p>
            </div>
          </div>

          <section className="card">
            <h2>{t('dashboard.recent')}</h2>
            {transactions.length === 0 ? (
              <p className="empty-state">{t('transactions.empty')}</p>
            ) : (
              <div className="transaction-list">
                {transactions.map((tx) => (
                  <div key={tx.id} className="transaction-item">
                    <div className="tx-info">
                      <p className="tx-category">{tx.category?.name}</p>
                      <p className="tx-date">{new Date(tx.transacted_at).toLocaleDateString()}</p>
                    </div>
                    <p className={`tx-amount ${tx.type === 'entrada' ? 'income' : 'expense'}`}>
                      {tx.type === 'entrada' ? '+' : '-'} R$ {Number(tx.amount).toFixed(2)}
                    </p>
                  </div>
                ))}
              </div>
            )}
          </section>
        </>
      )}
    </div>
  )
}
