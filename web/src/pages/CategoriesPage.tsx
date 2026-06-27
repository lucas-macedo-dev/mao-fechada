import { useTranslation } from 'react-i18next'
import { useCategories, useCreateCategory, useDeleteCategory, useUpdateCategory } from '../hooks/api'
import { useTutorial } from '../context/TutorialContext'
import { TutorialHint } from '../components/tutorial/TutorialHint'
import { useState, type SyntheticEvent } from 'react'
import { CATEGORY_ICON_OPTIONS, DEFAULT_CATEGORY_ICON, getCategoryIconClass } from '../constants/categoryIcons'
import { extractApiError } from '../services/api'
import {
  Title,
  Text,
  Select,
  TextInput,
  Button,
  ActionIcon,
  Badge,
  Alert,
  Stack,
  Group,
  Box,
  SimpleGrid,
  UnstyledButton,
} from '@mantine/core'
import { PageContainer } from '../components/ui/PageContainer'
import { ActionBar } from '../components/ui/ActionBar'
import { FormModal } from '../components/FormModal'
import { ConfirmDialog } from '../components/ConfirmDialog'
import type { Category } from '../types/api'

type FormModalState = null | { mode: 'create' } | { mode: 'edit'; item: Category }
type ConfirmState = null | { title: string; message: string; onConfirm: () => void }

export function CategoriesPage() {
  const { t } = useTranslation()
  const { completeStep } = useTutorial()
  const { data: categories = [], isLoading } = useCategories(true)
  const createMutation = useCreateCategory()
  const updateMutation = useUpdateCategory()
  const deleteMutation = useDeleteCategory()

  const [formModal, setFormModal] = useState<FormModalState>(null)
  const [formName, setFormName] = useState('')
  const [formType, setFormType] = useState<'entrada' | 'saida'>('saida')
  const [formIcon, setFormIcon] = useState(DEFAULT_CATEGORY_ICON)
  const [formParentId, setFormParentId] = useState<string | null>(null)

  const [confirmState, setConfirmState] = useState<ConfirmState>(null)
  const [error, setError] = useState('')
  const [success, setSuccess] = useState('')

  const rootCategories = (categories as Category[]).filter((c) => c.parent_id == null)

  const formTypeNorm = formType === 'entrada' ? 'income' : 'expense'
  const formParentOptions = rootCategories
    .filter((c) => {
      if (formModal?.mode === 'edit' && c.id === formModal.item.id) return false
      const cType = c.type === 'entrada' ? 'income' : 'expense'
      return cType === formTypeNorm
    })
    .map((c) => ({ value: String(c.id), label: c.name }))

  const handleOpenCreate = () => {
    setFormName('')
    setFormType('saida')
    setFormIcon(DEFAULT_CATEGORY_ICON)
    setFormParentId(null)
    setError('')
    setFormModal({ mode: 'create' })
  }

  const handleOpenEdit = (category: Category) => {
    setFormName(category.name)
    setFormType(category.type === 'entrada' ? 'entrada' : 'saida')
    setFormIcon(getCategoryIconClass(category.icon ?? undefined))
    setFormParentId(category.parent_id ? String(category.parent_id) : null)
    setError('')
    setFormModal({ mode: 'edit', item: category })
  }

  const handleCloseModal = () => {
    setFormModal(null)
    setError('')
  }

  const handleFormTypeChange = (val: string | null) => {
    setFormType(val === 'entrada' ? 'entrada' : 'saida')
    setFormParentId(null)
  }

  const handleFormParentChange = (val: string | null) => {
    setFormParentId(val)
    if (val) {
      const parent = rootCategories.find((c) => String(c.id) === val)
      if (parent) {
        const parentDbType = parent.type === 'entrada' ? 'entrada' : 'saida'
        setFormType(parentDbType)
      }
    }
  }

  const handleFormSubmit = async (e: SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault()
    if (!formModal) return
    setError('')

    try {
      if (formModal.mode === 'create') {
        await createMutation.mutateAsync({
          name: formName,
          type: formType,
          icon: formIcon,
          parent_id: formParentId ? Number(formParentId) : undefined,
        })
        completeStep('create-category')
        setSuccess(t('categories.create_success'))
      } else {
        await updateMutation.mutateAsync({
          id: formModal.item.id,
          payload: {
            name: formName,
            type: formType,
            icon: formIcon,
            parent_id: formParentId ? Number(formParentId) : undefined,
          },
        })
        setSuccess(t('categories.update_success'))
      }
      setFormModal(null)
    } catch (err) {
      setError(extractApiError(err).message)
    }
  }

  const executeDelete = async (id: number) => {
    setError('')
    setSuccess('')
    try {
      await deleteMutation.mutateAsync(id)
      if (formModal?.mode === 'edit' && formModal.item.id === id) {
        setFormModal(null)
      }
      setSuccess(t('categories.delete_success'))
    } catch (err) {
      setError(extractApiError(err).message)
    } finally {
      setConfirmState(null)
    }
  }

  const handleDelete = (id: number) => {
    setConfirmState({
      title: t('categories.delete_confirm_title'),
      message: t('categories.delete_confirm'),
      onConfirm: () => executeDelete(id),
    })
  }

  if (isLoading) {
    return (
      <PageContainer>
        <Text>{t('common.loading')}</Text>
      </PageContainer>
    )
  }

  const getCategoryColor = (type: string) =>
    type === 'entrada' || type === 'income' ? 'green' : 'red'

  const renderCategory = (category: Category) => {
    const color = getCategoryColor(category.type)

    return (
      <Box
        key={category.id}
        style={{
          background: 'white',
          borderRadius: 8,
          padding: '1rem',
          borderLeft: `4px solid var(--mantine-color-${color}-6)`,
        }}
      >
        <Group justify="space-between" align="center">
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
                background: `var(--mantine-color-${color}-0)`,
                color: `var(--mantine-color-${color}-6)`,
                marginRight: '0.5rem',
                flexShrink: 0,
              }}
              aria-hidden="true"
            >
              <i className={getCategoryIconClass(category.icon ?? undefined)} />
            </Box>
            {category.name}
          </Text>
          <Group gap="xs" align="center">
            <Badge size="sm" variant="light" color={color}>
              {t(`categories.type_${category.type}`)}
            </Badge>
            <ActionIcon
              variant="subtle"
              radius="xl"
              size="sm"
              onClick={() => handleOpenEdit(category)}
              aria-label={t('categories.edit')}
              title={t('categories.edit')}
            >
              <i className="fa-solid fa-pen-to-square" aria-hidden="true" style={{ fontSize: '0.8rem' }} />
            </ActionIcon>
            <ActionIcon
              variant="subtle"
              color="red"
              radius="xl"
              size="sm"
              onClick={() => handleDelete(category.id)}
              aria-label={t('categories.delete')}
              title={t('categories.delete')}
            >
              <i className="fa-solid fa-trash-can" aria-hidden="true" style={{ fontSize: '0.8rem' }} />
            </ActionIcon>
          </Group>
        </Group>

        {category.children && category.children.length > 0 && (
          <Box mt="xs" pt="xs" style={{ borderTop: '1px solid var(--mantine-color-gray-2)' }}>
            <Stack gap="xs">
              {category.children.map((subcategory) => {
                const subColor = getCategoryColor(subcategory.type)

                return (
                  <Box key={subcategory.id} pl="md" style={{ borderLeft: `2px solid var(--mantine-color-${subColor}-4)` }}>
                    <Group justify="space-between" align="center">
                      <Text size="sm" style={{ display: 'inline-flex', alignItems: 'center' }}>
                        <Box
                          component="span"
                          style={{
                            width: '1.5rem',
                            height: '1.5rem',
                            borderRadius: 999,
                            display: 'inline-flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            background: `var(--mantine-color-${subColor}-0)`,
                            color: `var(--mantine-color-${subColor}-6)`,
                            marginRight: '0.5rem',
                            flexShrink: 0,
                          }}
                          aria-hidden="true"
                        >
                          <i className={getCategoryIconClass(subcategory.icon ?? undefined)} />
                        </Box>
                        {subcategory.name}
                      </Text>
                      <Group gap="xs">
                        <ActionIcon
                          variant="subtle"
                          radius="xl"
                          size="sm"
                          onClick={() => handleOpenEdit(subcategory)}
                          aria-label={t('categories.edit')}
                          title={t('categories.edit')}
                        >
                          <i className="fa-solid fa-pen-to-square" aria-hidden="true" style={{ fontSize: '0.8rem' }} />
                        </ActionIcon>
                        <ActionIcon
                          variant="subtle"
                          color="red"
                          radius="xl"
                          size="sm"
                          onClick={() => handleDelete(subcategory.id)}
                          aria-label={t('categories.delete')}
                          title={t('categories.delete')}
                        >
                          <i className="fa-solid fa-trash-can" aria-hidden="true" style={{ fontSize: '0.8rem' }} />
                        </ActionIcon>
                      </Group>
                    </Group>
                  </Box>
                )
              })}
            </Stack>
          </Box>
        )}
      </Box>
    )
  }

  const isSubmitting = formModal?.mode === 'create' ? createMutation.isPending : updateMutation.isPending
  const isEditingSubcategory = formModal?.mode === 'edit' && formModal.item.parent_id != null

  return (
    <PageContainer>
      <Group justify="space-between" align="center" mb="lg">
        <Title order={1}>{t('categories.title')}</Title>
        <TutorialHint stepId="create-category">
          <Button onClick={handleOpenCreate} leftSection={<i className="fa-solid fa-plus" aria-hidden="true" />}>
            {t('categories.create')}
          </Button>
        </TutorialHint>
      </Group>

      {error && (
        <Alert color="red" mb="md" radius="md">
          {error}
        </Alert>
      )}
      {success && (
        <Alert color="green" mb="md" radius="md">
          {success}
        </Alert>
      )}

      <Stack gap="md">
        {rootCategories.map(renderCategory)}
      </Stack>

      <FormModal
        opened={formModal !== null}
        onClose={handleCloseModal}
        title={formModal?.mode === 'create' ? t('categories.create') : t('categories.modal_edit_title')}
      >
        <Box component="form" onSubmit={handleFormSubmit}>
          <Stack gap="sm">
              <SimpleGrid cols={{ base: 1, sm: 2 }}>
                <TextInput
                  label={t('categories.name')}
                  value={formName}
                  onChange={(e) => setFormName(e.target.value)}
                  required
                />
                <Select
                  label={t('categories.type')}
                  value={formType}
                  onChange={handleFormTypeChange}
                  disabled={!!formParentId}
                  data={[
                    { value: 'saida', label: t('categories.type_expense') },
                    { value: 'entrada', label: t('categories.type_income') },
                  ]}
                />
              </SimpleGrid>

              {!isEditingSubcategory && (
                <Select
                  label={t('categories.parent')}
                  value={formParentId}
                  onChange={handleFormParentChange}
                  data={[
                    { value: '', label: t('categories.parent_none') },
                    ...formParentOptions,
                  ]}
                  clearable
                  placeholder={t('categories.parent_none')}
                />
              )}

              <Box>
                <Text size="sm" fw={500} mb="xs">
                  {t('categories.icon')}
                </Text>
                <SimpleGrid cols={{ base: 4, xs: 6 }}>
                  {CATEGORY_ICON_OPTIONS.map((option) => {
                    const selected = option.className === formIcon
                    return (
                      <UnstyledButton
                        key={option.className}
                        onClick={() => setFormIcon(option.className)}
                        aria-label={t(option.key)}
                        title={t(option.key)}
                        style={{
                          border: `1px solid ${selected ? 'var(--mantine-color-indigo-6)' : 'var(--mantine-color-indigo-2)'}`,
                          background: selected ? 'var(--mantine-color-indigo-6)' : 'var(--mantine-color-indigo-0)',
                          color: selected ? '#ffffff' : 'var(--mantine-color-dark-4)',
                          borderRadius: 8,
                          height: '2.5rem',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          cursor: 'pointer',
                          transition: 'all 0.15s ease',
                        }}
                      >
                        <i className={option.className} aria-hidden="true" style={{ fontSize: '1rem' }} />
                      </UnstyledButton>
                    )
                  })}
                </SimpleGrid>
              </Box>

              {error && (
                <Alert color="red" radius="md">
                  {error}
                </Alert>
              )}

              <ActionBar>
                <Button type="submit" loading={isSubmitting}>
                  {t('common.save')}
                </Button>
                <Button type="button" variant="light" onClick={handleCloseModal}>
                  {t('common.cancel')}
                </Button>
              </ActionBar>
          </Stack>
        </Box>
      </FormModal>

      <ConfirmDialog
        opened={confirmState !== null}
        title={confirmState?.title ?? ''}
        message={confirmState?.message ?? ''}
        onConfirm={confirmState?.onConfirm ?? (() => {})}
        onCancel={() => setConfirmState(null)}
        loading={deleteMutation.isPending}
      />
    </PageContainer>
  )
}
