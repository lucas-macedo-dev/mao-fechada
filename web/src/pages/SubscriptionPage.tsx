import { useTranslation } from 'react-i18next'
import { usePlans, useSubscription } from '../hooks/api'
import {
  Title,
  Text,
  SimpleGrid,
  Paper,
  Group,
  Badge,
  Stack,
  List,
} from '@mantine/core'
import { PageContainer } from '../components/ui/PageContainer'
import { SectionCard } from '../components/ui/SectionCard'

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
    return (
      <PageContainer>
        <Text>{t('common.loading')}</Text>
      </PageContainer>
    )
  }

  return (
    <PageContainer>
      <Title order={1} mb="lg">
        {t('subscription.title')}
      </Title>

      {subscription && (
        <SectionCard mb="lg">
          <Title order={2} mb="md">
            {t('subscription.current')}
          </Title>
          <Stack gap="xs">
            <Text>
              {t('subscription.plan')}:{' '}
              <Text component="span" fw={700}>
                {t(`subscription.plan_value.${subscription.plan_code}`, { defaultValue: subscription.plan_code })}
              </Text>
            </Text>
            <Text>
              {t('subscription.status')}:{' '}
              <Text component="span" fw={700}>
                {t(`subscription.status_value.${subscription.status}`, { defaultValue: subscription.status })}
              </Text>
            </Text>
            <Text>
              {t('subscription.provider')}:{' '}
              <Text component="span" fw={700}>
                {subscription.provider || t('subscription.none')}
              </Text>
            </Text>
          </Stack>
        </SectionCard>
      )}

      <SimpleGrid cols={{ base: 1, md: 2 }} spacing="md">
        {plans.map((plan) => {
          const isCurrent = subscription?.plan_code === plan.code

          return (
            <Paper
              key={plan.code}
              shadow="xs"
              radius="md"
              p="lg"
              withBorder
              style={isCurrent ? { borderColor: 'var(--mantine-color-green-6)' } : undefined}
            >
              <Group justify="space-between" align="center" mb="md">
                <Title order={2}>{plan.name}</Title>
                {isCurrent && (
                  <Badge color="green" variant="light">
                    {t('subscription.current_badge')}
                  </Badge>
                )}
              </Group>

              <List spacing="sm" listStyleType="none">
                {Object.entries(plan.features || {}).map(([featureKey, feature]) => (
                  <List.Item
                    key={featureKey}
                    style={{ borderBottom: '1px dashed var(--mantine-color-gray-3)', paddingBottom: '0.5rem' }}
                  >
                    <Group justify="space-between">
                      <Text size="sm">
                        {t(`subscription.feature.${featureKey}`, { defaultValue: featureKey })}
                      </Text>
                      <Text size="sm" fw={700}>
                        {formatLimit(feature.limit, t)}
                      </Text>
                    </Group>
                  </List.Item>
                ))}
              </List>
            </Paper>
          )
        })}
      </SimpleGrid>
    </PageContainer>
  )
}
