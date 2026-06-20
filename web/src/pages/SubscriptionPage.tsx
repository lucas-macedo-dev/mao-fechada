import { useTranslation } from 'react-i18next'
import { usePlans, useSubscription } from '../hooks/api'
import '../styles/pages.css'

function formatLimit(limit: number | null, t: (key: string) => string): string {
  if (limit === null) {
    return t('subscription.unlimited')
  }

  return String(limit)
}

export function SubscriptionPage() {
  const { t } = useTranslation()
  const { data: subscription, isLoading: loadingSubscription } = useSubscription()
  const { data: plans = [], isLoading: loadingPlans } = usePlans()

  if (loadingSubscription || loadingPlans) {
    return <div className="page">{t('common.loading')}</div>
  }

  return (
    <div className="page subscription-page">
      <h1>{t('subscription.title')}</h1>

      {subscription && (
        <section className="card current-subscription-card">
          <h2>{t('subscription.current')}</h2>
          <p>
            {t('subscription.plan')}: <strong>{t(`subscription.plan_value.${subscription.plan_code}`, { defaultValue: subscription.plan_code })}</strong>
          </p>
          <p>
            {t('subscription.status')}: <strong>{t(`subscription.status_value.${subscription.status}`, { defaultValue: subscription.status })}</strong>
          </p>
          <p>
            {t('subscription.provider')}: <strong>{subscription.provider || t('subscription.none')}</strong>
          </p>
        </section>
      )}

      <section className="plans-grid">
        {plans.map((plan) => {
          const isCurrent = subscription?.plan_code === plan.code

          return (
            <article key={plan.code} className={`card plan-card ${isCurrent ? 'current' : ''}`}>
              <div className="plan-header">
                <h2>{plan.name}</h2>
                {isCurrent && <span className="plan-badge">{t('subscription.current_badge')}</span>}
              </div>

              <ul className="feature-list">
                {Object.entries(plan.features || {}).map(([featureKey, feature]) => (
                  <li key={featureKey}>
                    <span>{t(`subscription.feature.${featureKey}`, { defaultValue: featureKey })}</span>
                    <strong>{formatLimit(feature.limit, t)}</strong>
                  </li>
                ))}
              </ul>
            </article>
          )
        })}
      </section>
    </div>
  )
}
