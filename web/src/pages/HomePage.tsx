import { useTranslation } from 'react-i18next'
import { useDashboardSummary, useDashboardByCategory, useDashboardByDay, useTransactions } from '../hooks/api'
import { getCategoryIconClass } from '../constants/categoryIcons'
import { useState } from 'react'
import appLogo from '../assets/icon_mao_fechada.png'
import {
  Title,
  Text,
  Group,
  SimpleGrid,
  Paper,
  Box,
  TextInput,
  ActionIcon,
  Table,
  ScrollArea,
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

function normalizeType(type: string | undefined): 'entrada' | 'saida' {
  if (type === 'income' || type === 'entrada') {
    return 'entrada'
  }

  return 'saida'
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
  const [month, setMonth] = useState(new Date().toISOString().slice(0, 7))
  const { data: summary, isLoading } = useDashboardSummary(month)
  const { data: rawCategoryData = [] } = useDashboardByCategory(month)
  const { data: dayData = [] } = useDashboardByDay(month)
  const { data: transactionsResponse } = useTransactions({ month, per_page: 15 })

  const CHART_COLORS = ['indigo.6', 'orange.5', 'teal.6', 'pink.5', 'yellow.5', 'cyan.6', 'grape.5', 'green.6', 'red.5']
  const categoryData = rawCategoryData.map((item, i) => ({
    ...item,
    color: CHART_COLORS[i % CHART_COLORS.length],
  }))

  const transactions = transactionsResponse?.data || []

  if (isLoading) {
    return (
      <PageContainer>
        <Text>{t('common.loading')}</Text>
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
          <SimpleGrid cols={{ base: 1, sm: 2, md: 3 }} mb="lg">
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
          </SimpleGrid>

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
                <Group justify="center">
                  <DonutChart
                    data={categoryData}
                    size={200}
                    thickness={36}
                    withTooltip
                    tooltipDataSource="segment"
                  />
                </Group>
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
              <ScrollArea>
                <Table striped withTableBorder highlightOnHover>
                  <Table.Thead>
                    <Table.Tr>
                      <Table.Th>{t('transactions.date')}</Table.Th>
                      <Table.Th>{t('transactions.category')}</Table.Th>
                      <Table.Th>{t('transactions.amount')}</Table.Th>
                    </Table.Tr>
                  </Table.Thead>
                  <Table.Tbody>
                    {transactions.map((tx) => (
                      <Table.Tr key={tx.id}>
                        <Table.Td style={{ whiteSpace: 'nowrap' }}>
                          {formatTransactionDate(tx.transacted_at)}
                        </Table.Td>
                        <Table.Td>
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
                            {tx.category?.name}
                          </Group>
                        </Table.Td>
                        <Table.Td style={{ whiteSpace: 'nowrap' }}>
                          <Text
                            fw={700}
                            c={normalizeType(tx.type) === 'entrada' ? 'green' : 'red'}
                          >
                            {normalizeType(tx.type) === 'entrada' ? '+' : '−'} R$ {Number(tx.amount).toFixed(2)}
                          </Text>
                        </Table.Td>
                      </Table.Tr>
                    ))}
                  </Table.Tbody>
                </Table>
              </ScrollArea>
            )}
          </SectionCard>
        </>
      )}
    </PageContainer>
  )
}
