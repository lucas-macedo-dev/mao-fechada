import { useTranslation } from 'react-i18next'
import { useDashboardSummary, useDashboardByCategory, useDashboardByDay, useTransactions, useInstallmentsTotal } from '../hooks/api'
import { useTutorial } from '../context/TutorialContext'
import { TutorialHint } from '../components/tutorial/TutorialHint'
import { getCategoryIconClass } from '../constants/categoryIcons'
import { useEffect, useState } from 'react'
import {
  Title,
  Text,
  Group,
  SimpleGrid,
  Paper,
  Box,
  TextInput,
  ActionIcon,
  Stack,
  LoadingOverlay
} from '@mantine/core'
import { DonutChart, BarChart } from '@mantine/charts'
import { PageContainer } from '../components/ui/PageContainer'
import { SectionCard } from '../components/ui/SectionCard'

function formatTransactionDate(value: string): string {
  const datePart = value.slice(0, 10)

  if (/^\d{4}-\d{2}-\d{2}$/.test(datePart)) {
    const [year, month, day] = datePart.split('-').map(Number)
    return new Date(year, month - 1, day).toLocaleDateString()
  }

  const parsed = new Date(value)
  return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleDateString()
}

function shiftMonth(value: string, delta: number): string {
  const [year, mon] = value.split('-').map(Number)
  const d = new Date(year, mon - 1 + delta)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
}

const summaryCardStyles: Record<string, { borderLeftColor: string }> = {
  income: { borderLeftColor: '#4caf50' },
  expense: { borderLeftColor: '#dd3442' },
  balance: { borderLeftColor: '#2196f3' },
}

export function HomePage() {
  const { t } = useTranslation()
  const { completeStep } = useTutorial()
  const [month, setMonth] = useState(new Date().toISOString().slice(0, 7))
  const { data: summary, isLoading } = useDashboardSummary(month)
  const { data: installmentsData } = useInstallmentsTotal(month)

  useEffect(() => {
    if (summary) completeStep('view-summary')
  }, [summary, completeStep])
  const { data: rawCategoryData = [] } = useDashboardByCategory(month)
  const { data: dayData = [] } = useDashboardByDay(month)
  const { data: transactionsResponse } = useTransactions({ month, per_page: 5 })

  const CHART_COLORS = ['indigo.6', 'orange.5', 'teal.6', 'pink.5', 'yellow.5', 'cyan.6', 'grape.5', 'green.6', 'red.5']
  const categoryData = rawCategoryData.map((item, i) => ({
    ...item,
    color: CHART_COLORS[i % CHART_COLORS.length],
  }))

  const transactions = transactionsResponse?.data || []

  if (isLoading) {
    return (
      <PageContainer>
        <LoadingOverlay
          visible={isLoading}
          overlayProps={{ radius: "sm", blur: 2 }}
        />
      </PageContainer>
    )
  }

  return (
    <PageContainer>
      <Group align="center" gap="xs" mb="md">
        <Title order={1}>{t('dashboard.title')}</Title>
      </Group>

      <Box mb="lg">
        <Text size="sm" fw={500} c="dimmed" mb="xs">
          {t('dashboard.month_selector')}
        </Text>
        <Group gap="xs" align="center" wrap="nowrap">
          <ActionIcon
            variant="default"
            size="lg"
            onClick={() => setMonth(shiftMonth(month, -1))}
            aria-label={t('dashboard.month_selector')}
          >
            <i className="fa-solid fa-chevron-left" />
          </ActionIcon>
          <TextInput
            type="month"
            value={month}
            onChange={(e) => setMonth(e.target.value)}
            style={{ width: 160 }}
          />
          <ActionIcon
            variant="default"
            size="lg"
            onClick={() => setMonth(shiftMonth(month, 1))}
            aria-label={t('dashboard.month_selector')}
          >
            <i className="fa-solid fa-chevron-right" />
          </ActionIcon>
        </Group>
      </Box>

      {summary && (
        <>
          <TutorialHint stepId="view-summary">
            <SimpleGrid cols={{ base: 1, sm: 2, md: 4 }} mb="lg">
              {(['income', 'expense', 'balance'] as const).map((key) => {
                const labels = {
                  income: t('dashboard.income'),
                  expense: t('dashboard.expense'),
                  balance: t('dashboard.balance'),
                }
                const values = {
                  income: summary.totals.entradas.toFixed(2),
                  expense: summary.totals.saidas.toFixed(2),
                  balance: summary.totals.saldo.toFixed(2),
                }

                return (
                  <Paper
                    key={key}
                    shadow="xs"
                    radius="md"
                    p="lg"
                    withBorder
                    style={{ borderLeft: `4px solid ${summaryCardStyles[key].borderLeftColor}` }}
                  >
                    <Text size="sm" c="dimmed" tt="uppercase" fw={500} mb="xs">
                      {labels[key]}
                    </Text>
                    <Text size="xl" fw={700}>
                      R$ {values[key]}
                    </Text>
                  </Paper>
                )
              })}

              <Paper
                shadow="xs"
                radius="md"
                p="lg"
                withBorder
                style={{ borderLeft: '4px solid #9c27b0' }}
              >
                <Text size="sm" c="dimmed" tt="uppercase" fw={500} mb="xs">
                  {t('dashboard.installments')}
                </Text>
                <Text size="xl" fw={700}>
                  R$ {(installmentsData?.total ?? 0).toFixed(2)}
                </Text>
              </Paper>
            </SimpleGrid>
          </TutorialHint>

          <SimpleGrid cols={{ base: 1, md: 2 }} mb="lg">
            <SectionCard>
              <Title order={2} size="h4" mb="md">
                {t('dashboard.expenses_by_category')}
              </Title>
              {categoryData.length === 0 ? (
                <Text c="dimmed" ta="center" py="xl" fs="italic" size="sm">
                  {t('dashboard.no_expenses')}
                </Text>
              ) : (
                <>
                  <Group justify="center">
                    <DonutChart
                      data={categoryData}
                      size={200}
                      thickness={36}
                      withTooltip
                      tooltipDataSource="segment"
                      paddingAngle={2}
                    />
                  </Group>
                  <Box mt="md">
                    {categoryData.map((item) => {
                      const [colorName, shade] = item.color.split('.')
                      const cssColor = `var(--mantine-color-${colorName}-${shade ?? '6'})`
                      return (
                        <Group key={item.name} gap="xs" mb={4} align="center">
                          <Box
                            style={{
                              width: 12,
                              height: 12,
                              borderRadius: 3,
                              background: cssColor,
                              flexShrink: 0,
                            }}
                          />
                          <Text size="sm" style={{ flex: 1 }}>{item.name}</Text>
                          <Text size="sm" fw={600} c="dimmed">R$ {item.value.toFixed(2)}</Text>
                        </Group>
                      )
                    })}
                  </Box>
                </>
              )}
            </SectionCard>

            <SectionCard>
              <Title order={2} size="h4" mb="md">
                {t('dashboard.income_expense_by_day')}
              </Title>
              {dayData.every((d) => d.income === 0 && d.expense === 0) ? (
                <Text c="dimmed" ta="center" py="xl" fs="italic" size="sm">
                  {t('dashboard.no_transactions')}
                </Text>
              ) : (
                <BarChart
                  h={240}
                  data={dayData}
                  dataKey="day"
                  series={[
                    { name: 'income', color: 'green.6', label: t('dashboard.income') },
                    { name: 'expense', color: 'red.5', label: t('dashboard.expense') },
                  ]}
                  gridAxis="x"
                  withLegend
                  valueFormatter={(v) => `R$ ${v.toFixed(2)}`}
                />
              )}
            </SectionCard>
          </SimpleGrid>

          <SectionCard>
            <Title order={2} mb="md">
              {t('dashboard.recent')}
            </Title>
            {transactions.length === 0 ? (
              <Text c="dimmed" ta="center" py="xl" fs="italic">
                {t('transactions.empty')}
              </Text>
            ) : (
              <Stack gap="sm">
                {transactions.map((tx) => (
                  <Paper key={tx.id} shadow="xs" radius="md" p="md" withBorder>
                    <Group justify="space-between" align="center" wrap="wrap">
                      <Group gap="xs" align="center" wrap="nowrap">
                        <Box
                          component="span"
                          style={{
                            width: '1.5rem',
                            height: '1.5rem',
                            borderRadius: 999,
                            display: 'inline-flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            background: 'var(--mantine-color-indigo-0)',
                            color: 'var(--mantine-color-indigo-6)',
                            flexShrink: 0,
                          }}
                          aria-hidden="true"
                        >
                          <i className={getCategoryIconClass(tx.category?.icon)} />
                        </Box>
                        <Stack gap={2}>
                          <Text fw={600}>{tx.category?.name}</Text>
                          <Text size="xs" c="dimmed">
                            {formatTransactionDate(tx.transacted_at)}
                          </Text>
                        </Stack>
                      </Group>
                      <Text
                        fw={700}
                        c={tx.type === 'income' ? 'green' : 'red'}
                      >
                        {tx.type === 'income' ? '+' : '−'} R$ {Number(tx.amount).toFixed(2)}
                      </Text>
                    </Group>
                  </Paper>
                ))}
              </Stack>
            )}
          </SectionCard>
        </>
      )}
    </PageContainer>
  )
}
