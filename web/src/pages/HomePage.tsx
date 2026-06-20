import { useTranslation } from 'react-i18next'
import { useDashboardSummary, useTransactions } from '../hooks/api'
import { getCategoryIconClass } from '../constants/categoryIcons'
import { useState } from 'react'
import appLogo from '../assets/icon_mao_fechada.png'
import '../styles/pages.css'

function formatTransactionDate(value: string): string {
  const datePart = value.slice(0, 10)

  if (/^\d{4}-\d{2}-\d{2}$/.test(datePart)) {
    const [year, month, day] = datePart.split('-').map(Number)
    return new Date(year, month - 1, day).toLocaleDateString()
  }

  const parsed = new Date(value)
  return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleDateString()
}

function normalizeType(type: string | undefined): 'entrada' | 'saida' {
  if (type === 'income' || type === 'entrada') {
    return 'entrada'
  }

  return 'saida'
}

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
      <div className="page-title-with-logo">
        <img src={appLogo} alt={t('app.title')} className="page-logo" />
        <h1>{t('dashboard.title')}</h1>
      </div>

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
                      <p className="tx-category">
                        <span className="category-icon-wrapper" aria-hidden="true">
                          <i className={getCategoryIconClass(tx.category?.icon)} />
                        </span>
                        {tx.category?.name}
                      </p>
                      <p className="tx-date">{formatTransactionDate(tx.transacted_at)}</p>
                    </div>
                    <p className={`tx-amount ${normalizeType(tx.type) === 'entrada' ? 'income' : 'expense'}`}>
                      {normalizeType(tx.type) === 'entrada' ? '+' : '-'} R$ {Number(tx.amount).toFixed(2)}
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
