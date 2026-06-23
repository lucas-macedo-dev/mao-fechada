import { useTranslation } from 'react-i18next'
import { useCategories, useCreateTransaction, useDeleteTransaction, useTransactions, useUpdateTransaction } from '../hooks/api'
import { useTutorial } from '../context/TutorialContext'
import { TutorialHint } from '../components/tutorial/TutorialHint'
import { getCategoryIconClass } from '../constants/categoryIcons'
import { extractApiError } from '../services/api'
import { useMemo, useState, type SyntheticEvent } from 'react'
import {
  Title,
  Text,
  Select,
  TextInput,
  NumberInput,
  Button,
  ActionIcon,
  Alert,
  Stack,
  Group,
  Box,
  SimpleGrid,
  Paper,
} from '@mantine/core'
import { PageContainer } from '../components/ui/PageContainer'
import { SectionCard } from '../components/ui/SectionCard'
import { ActionBar } from '../components/ui/ActionBar'

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
  const { completeStep } = useTutorial()
  const [month, setMonth] = useState(new Date().toISOString().slice(0, 7))
  const [type, setType] = useState('')
  const [page, setPage] = useState(1)
  const [createType, setCreateType] = useState<'entrada' | 'saida'>('saida')
  const [categoryId, setCategoryId] = useState('')
  const [paymentMethod, setPaymentMethod] = useState('pix')
  const [amount, setAmount] = useState<number | string>('')
  const [transactedAt, setTransactedAt] = useState(new Date().toISOString().slice(0, 10))
  const [notes, setNotes] = useState('')
  const [error, setError] = useState('')
  const [success, setSuccess] = useState('')

  const createTransaction = useCreateTransaction()
  const updateTransaction = useUpdateTransaction()
  const deleteTransaction = useDeleteTransaction()
  const { data: categories = [] } = useCategories()
  const [editingTransactionId, setEditingTransactionId] = useState<number | null>(null)
  const [editType, setEditType] = useState<'entrada' | 'saida'>('saida')
  const [editCategoryId, setEditCategoryId] = useState('')
  const [editPaymentMethod, setEditPaymentMethod] = useState('pix')
  const [editAmount, setEditAmount] = useState<number | string>('')
  const [editTransactedAt, setEditTransactedAt] = useState('')
  const [editNotes, setEditNotes] = useState('')

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

  const editableCategories = useMemo(
    () => categories.filter((category) => normalizeType(category.type) === editType),
    [categories, editType],
  )

  const handleStartEdit = (transaction: {
    id: number
    type: string
    category_id: number
    payment_method: string
    amount: string | number
    transacted_at: string
    notes?: string
  }) => {
    setEditingTransactionId(transaction.id)
    setEditType(normalizeType(transaction.type))
    setEditCategoryId(String(transaction.category_id))
    setEditPaymentMethod(transaction.payment_method)
    setEditAmount(Number(transaction.amount))
    setEditTransactedAt(transaction.transacted_at.slice(0, 10))
    setEditNotes(transaction.notes || '')
    setError('')
    setSuccess('')
  }

  const handleCancelEdit = () => {
    setEditingTransactionId(null)
    setEditCategoryId('')
    setEditPaymentMethod('pix')
    setEditAmount('')
    setEditTransactedAt('')
    setEditNotes('')
  }

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

      completeStep('record-transaction')
      setAmount('')
      setNotes('')
      setSuccess(t('transactions.create_success'))
      setPage(1)
    } catch (err) {
      setError(extractApiError(err).message)
    }
  }

  const handleUpdate = async (event: SyntheticEvent<HTMLFormElement>, id: number) => {
    event.preventDefault()
    setError('')
    setSuccess('')

    if (!editCategoryId) {
      setError(t('transactions.select_category'))
      return
    }

    try {
      await updateTransaction.mutateAsync({
        id,
        payload: {
          category_id: Number(editCategoryId),
          type: editType,
          payment_method: editPaymentMethod,
          amount: Number(editAmount),
          transacted_at: editTransactedAt,
          notes: editNotes || undefined,
        },
      })

      handleCancelEdit()
      setSuccess(t('transactions.update_success'))
    } catch (err) {
      setError(extractApiError(err).message)
    }
  }

  const handleDelete = async (id: number) => {
    if (!window.confirm(t('transactions.delete_confirm'))) {
      return
    }

    setError('')
    setSuccess('')

    try {
      await deleteTransaction.mutateAsync(id)
      if (editingTransactionId === id) {
        handleCancelEdit()
      }
      setSuccess(t('transactions.delete_success'))
    } catch (err) {
      setError(extractApiError(err).message)
    }
  }

  if (isLoading) {
    return (
      <PageContainer>
        <Text>{t('common.loading')}</Text>
      </PageContainer>
    )
  }

  return (
    <PageContainer>
      <Title order={1} mb="lg">
        {t('transactions.title')}
      </Title>

      <SectionCard mb="lg">
        <Title order={2} mb="md">
          {t('transactions.new_entry')}
        </Title>

        <form onSubmit={handleCreate}>
          <SimpleGrid cols={{ base: 1, sm: 2 }} mb="sm">
            <Select
              label={t('transactions.type')}
              value={createType}
              onChange={(val) => {
                const nextType = val === 'entrada' ? 'entrada' : 'saida'
                setCreateType(nextType)
                setCategoryId('')
              }}
              data={[
                { value: 'saida', label: t('categories.type_expense') },
                { value: 'entrada', label: t('categories.type_income') },
              ]}
            />

            <Select
              label={t('transactions.category')}
              value={categoryId}
              onChange={(val) => setCategoryId(val ?? '')}
              data={creatableCategories.map((cat) => ({ value: String(cat.id), label: cat.name }))}
              placeholder={t('transactions.select_category')}
              required
            />

            {selectedCategory && (
              <Text size="sm" style={{ display: 'inline-flex', alignItems: 'center' }} c="dimmed">
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
                    marginRight: '0.5rem',
                    flexShrink: 0,
                  }}
                  aria-hidden="true"
                >
                  <i className={getCategoryIconClass(selectedCategory.icon)} />
                </Box>
                {selectedCategory.name}
              </Text>
            )}

            <Select
              label={t('transactions.payment_method')}
              value={paymentMethod}
              onChange={(val) => setPaymentMethod(val ?? 'pix')}
              data={paymentMethods.map((method) => ({ value: method, label: t(`transactions.payment.${method}`) }))}
            />

            <NumberInput
              label={t('transactions.amount')}
              value={amount}
              onChange={setAmount}
              min={0.01}
              step={0.01}
              decimalScale={2}
              required
            />

            <TextInput
              label={t('transactions.date')}
              type="date"
              value={transactedAt}
              onChange={(e) => setTransactedAt(e.target.value)}
              required
            />

            <TextInput
              label={t('transactions.notes')}
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              style={{ gridColumn: '1 / -1' }}
            />
          </SimpleGrid>

          {error && (
            <Alert color="red" mb="sm" radius="md">
              {error}
            </Alert>
          )}
          {success && (
            <Alert color="green" mb="sm" radius="md">
              {success}
            </Alert>
          )}

          <TutorialHint stepId="record-transaction">
            <Button type="submit" loading={createTransaction.isPending}>
              {t('common.create')}
            </Button>
          </TutorialHint>
        </form>
      </SectionCard>

      <SectionCard mb="lg">
        <SimpleGrid cols={{ base: 1, sm: 2 }}>
          <TextInput
            label={t('transactions.period')}
            type="month"
            value={month}
            onChange={(e) => {
              setMonth(e.target.value)
              setPage(1)
            }}
          />

          <Select
            label={t('transactions.type')}
            value={type}
            onChange={(val) => {
              setType(val ?? '')
              setPage(1)
            }}
            data={[
              { value: '', label: t('transactions.all_types') },
              { value: 'entrada', label: t('categories.type_income') },
              { value: 'saida', label: t('categories.type_expense') },
            ]}
          />
        </SimpleGrid>
      </SectionCard>

      {transactions.length === 0 ? (
        <Text c="dimmed" ta="center" py="xl" fs="italic">
          {t('transactions.empty')}
        </Text>
      ) : (
        <>
          <Stack gap="sm" mb="md">
            {transactions.map((tx) => (
              <Paper key={tx.id} shadow="xs" radius="md" p="md" withBorder>
                {editingTransactionId === tx.id ? (
                  <form onSubmit={(event) => handleUpdate(event, tx.id)}>
                    <SimpleGrid cols={{ base: 1, sm: 2 }} mb="sm">
                      <Select
                        label={t('transactions.type')}
                        value={editType}
                        onChange={(val) => {
                          const nextType = val === 'entrada' ? 'entrada' : 'saida'
                          setEditType(nextType)
                          setEditCategoryId('')
                        }}
                        data={[
                          { value: 'saida', label: t('categories.type_expense') },
                          { value: 'entrada', label: t('categories.type_income') },
                        ]}
                      />

                      <Select
                        label={t('transactions.category')}
                        value={editCategoryId}
                        onChange={(val) => setEditCategoryId(val ?? '')}
                        data={editableCategories.map((cat) => ({ value: String(cat.id), label: cat.name }))}
                        placeholder={t('transactions.select_category')}
                        required
                      />

                      <Select
                        label={t('transactions.payment_method')}
                        value={editPaymentMethod}
                        onChange={(val) => setEditPaymentMethod(val ?? 'pix')}
                        data={paymentMethods.map((method) => ({ value: method, label: t(`transactions.payment.${method}`) }))}
                      />

                      <NumberInput
                        label={t('transactions.amount')}
                        value={editAmount}
                        onChange={setEditAmount}
                        min={0.01}
                        step={0.01}
                        decimalScale={2}
                        required
                      />

                      <TextInput
                        label={t('transactions.date')}
                        type="date"
                        value={editTransactedAt}
                        onChange={(e) => setEditTransactedAt(e.target.value)}
                        required
                      />

                      <TextInput
                        label={t('transactions.notes')}
                        value={editNotes}
                        onChange={(e) => setEditNotes(e.target.value)}
                        style={{ gridColumn: '1 / -1' }}
                      />
                    </SimpleGrid>

                    <ActionBar>
                      <Button type="submit" size="sm" loading={updateTransaction.isPending}>
                        {t('common.save')}
                      </Button>
                      <Button type="button" size="sm" variant="light" onClick={handleCancelEdit}>
                        {t('common.cancel')}
                      </Button>
                    </ActionBar>
                  </form>
                ) : (
                  <Group justify="space-between" align="center">
                    <Stack gap={2}>
                      <Text fw={600} style={{ display: 'inline-flex', alignItems: 'center' }}>
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
                            marginRight: '0.5rem',
                            flexShrink: 0,
                          }}
                          aria-hidden="true"
                        >
                          <i className={getCategoryIconClass(tx.category?.icon)} />
                        </Box>
                        {tx.category?.name}
                      </Text>
                      <Text size="xs" c="dimmed">
                        {formatTransactionDate(tx.transacted_at)}
                      </Text>
                      {tx.notes && (
                        <Text size="sm" c="dimmed">
                          {tx.notes}
                        </Text>
                      )}
                    </Stack>

                    <Group gap="xs" align="center">
                      <Text fw={700} size="lg" c={normalizeType(tx.type) === 'entrada' ? 'green' : 'red'}>
                        {normalizeType(tx.type) === 'entrada' ? '+' : '-'} R$ {Number(tx.amount).toFixed(2)}
                      </Text>
                      <ActionIcon
                        variant="subtle"
                        radius="xl"
                        size="sm"
                        onClick={() => handleStartEdit(tx)}
                        aria-label={t('transactions.edit')}
                        title={t('transactions.edit')}
                      >
                        <i className="fa-solid fa-pen-to-square" aria-hidden="true" style={{ fontSize: '0.8rem' }} />
                      </ActionIcon>
                      <ActionIcon
                        variant="subtle"
                        color="red"
                        radius="xl"
                        size="sm"
                        onClick={() => handleDelete(tx.id)}
                        aria-label={t('transactions.delete')}
                        title={t('transactions.delete')}
                      >
                        <i className="fa-solid fa-trash-can" aria-hidden="true" style={{ fontSize: '0.8rem' }} />
                      </ActionIcon>
                    </Group>
                  </Group>
                )}
              </Paper>
            ))}
          </Stack>

          {meta && meta.last_page > 1 && (
            <Group justify="center" gap="md" p="md">
              <Button
                variant="filled"
                size="sm"
                disabled={page === 1}
                onClick={() => setPage(page - 1)}
                leftSection={<span>←</span>}
              >
                {t('common.previous')}
              </Button>
              <Text c="dimmed" fw={500}>
                {page} / {meta.last_page}
              </Text>
              <Button
                variant="filled"
                size="sm"
                disabled={page === meta.last_page}
                onClick={() => setPage(page + 1)}
                rightSection={<span>→</span>}
              >
                {t('common.next')}
              </Button>
            </Group>
          )}
        </>
      )}
    </PageContainer>
  )
}
