import { useTranslation } from 'react-i18next'
import { useCategories, useCreateCategory, useDeleteCategory, useUpdateCategory } from '../hooks/api'
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
import { SectionCard } from '../components/ui/SectionCard'
import { ActionBar } from '../components/ui/ActionBar'

export function CategoriesPage() {
  const { t } = useTranslation()
  const { data: categories = [], isLoading } = useCategories(true)
  const createMutation = useCreateCategory()
  const updateMutation = useUpdateCategory()
  const deleteMutation = useDeleteCategory()
  const [name, setName] = useState('')
  const [type, setType] = useState('saida')
  const [icon, setIcon] = useState(DEFAULT_CATEGORY_ICON)
  const [error, setError] = useState('')
  const [success, setSuccess] = useState('')
  const [editingCategoryId, setEditingCategoryId] = useState<number | null>(null)
  const [editName, setEditName] = useState('')
  const [editType, setEditType] = useState<'entrada' | 'saida'>('saida')
  const [editIcon, setEditIcon] = useState(DEFAULT_CATEGORY_ICON)

  const handleSubmit = async (e: SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault()
    setError('')
    setSuccess('')
    try {
      await createMutation.mutateAsync({ name, type, icon })
      setName('')
      setType('saida')
      setIcon(DEFAULT_CATEGORY_ICON)
      setSuccess(t('categories.create_success'))
    } catch (err) {
      setError(extractApiError(err).message)
    }
  }

  const handleStartEdit = (category: { id: number; name: string; type: string; icon?: string }) => {
    setEditingCategoryId(category.id)
    setEditName(category.name)
    setEditType(category.type === 'entrada' || category.type === 'income' ? 'entrada' : 'saida')
    setEditIcon(getCategoryIconClass(category.icon))
    setError('')
    setSuccess('')
  }

  const handleCancelEdit = () => {
    setEditingCategoryId(null)
    setEditName('')
    setEditType('saida')
    setEditIcon(DEFAULT_CATEGORY_ICON)
  }

  const handleSaveEdit = async (event: SyntheticEvent<HTMLFormElement>, id: number) => {
    event.preventDefault()
    setError('')
    setSuccess('')

    try {
      await updateMutation.mutateAsync({
        id,
        payload: {
          name: editName,
          type: editType,
          icon: editIcon,
        },
      })

      handleCancelEdit()
      setSuccess(t('categories.update_success'))
    } catch (err) {
      setError(extractApiError(err).message)
    }
  }

  const handleDelete = async (id: number) => {
    if (!window.confirm(t('categories.delete_confirm'))) {
      return
    }

    setError('')
    setSuccess('')

    try {
      await deleteMutation.mutateAsync(id)
      if (editingCategoryId === id) {
        handleCancelEdit()
      }
      setSuccess(t('categories.delete_success'))
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

  const rootCategories = categories.filter((c) => !c.parent_id)

  const getCategoryColor = (type: string) =>
    type === 'entrada' || type === 'income' ? 'green' : 'red'

  const renderCategory = (category: {
    id: number
    name: string
    type: string
    icon?: string
    children?: Array<{ id: number; name: string; type: string; icon?: string }>
  }) => {
    const isEditing = editingCategoryId === category.id
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
        <Group justify="space-between" align="center" mb={isEditing ? 'xs' : 0}>
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
              <i className={getCategoryIconClass(category.icon)} />
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
              onClick={() => handleStartEdit(category)}
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

        {isEditing && (
          <Box
            component="form"
            onSubmit={(event: SyntheticEvent<HTMLFormElement>) => handleSaveEdit(event, category.id)}
            mt="xs"
            p="md"
            style={{ border: '1px solid var(--mantine-color-indigo-1)', borderRadius: 8, background: '#fafbff' }}
          >
            <SimpleGrid cols={{ base: 1, sm: 2 }} mb="sm">
              <TextInput
                id={`category-name-${category.id}`}
                label={t('categories.name')}
                value={editName}
                onChange={(event) => setEditName(event.target.value)}
                required
              />
              <Select
                id={`category-type-${category.id}`}
                label={t('categories.type')}
                value={editType}
                onChange={(val) => setEditType(val === 'entrada' ? 'entrada' : 'saida')}
                data={[
                  { value: 'saida', label: t('categories.type_expense') },
                  { value: 'entrada', label: t('categories.type_income') },
                ]}
              />
            </SimpleGrid>

            <Box mb="sm">
              <Text size="sm" fw={500} mb="xs">
                {t('categories.icon')}
              </Text>
              <SimpleGrid cols={{ base: 4, xs: 6 }}>
                {CATEGORY_ICON_OPTIONS.map((option) => {
                  const selected = option.className === editIcon

                  return (
                    <UnstyledButton
                      key={`${category.id}-${option.className}`}
                      onClick={() => setEditIcon(option.className)}
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

            <ActionBar>
              <Button type="submit" size="sm" loading={updateMutation.isPending}>
                {t('common.save')}
              </Button>
              <Button type="button" size="sm" variant="light" onClick={handleCancelEdit}>
                {t('common.cancel')}
              </Button>
            </ActionBar>
          </Box>
        )}

        {category.children && category.children.length > 0 && (
          <Box mt="xs" pt="xs" style={{ borderTop: '1px solid var(--mantine-color-gray-2)' }}>
            <Stack gap="xs">
              {category.children.map((subcategory) => (
                <Group
                  key={subcategory.id}
                  justify="space-between"
                  align="center"
                  pl="md"
                  style={{ borderLeft: `2px solid var(--mantine-color-${getCategoryColor(subcategory.type)}-4)` }}
                >
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
                        background: `var(--mantine-color-${getCategoryColor(subcategory.type)}-0)`,
                        color: `var(--mantine-color-${getCategoryColor(subcategory.type)}-6)`,
                        marginRight: '0.5rem',
                        flexShrink: 0,
                      }}
                      aria-hidden="true"
                    >
                      <i className={getCategoryIconClass(subcategory.icon)} />
                    </Box>
                    {subcategory.name}
                  </Text>
                  <Group gap="xs">
                    <ActionIcon
                      variant="subtle"
                      radius="xl"
                      size="sm"
                      onClick={() => handleStartEdit(subcategory)}
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
              ))}
            </Stack>
          </Box>
        )}
      </Box>
    )
  }

  return (
    <PageContainer>
      <Title order={1} mb="lg">
        {t('categories.title')}
      </Title>

      <SectionCard mb="lg">
        <Title order={2} mb="md">
          {t('categories.create')}
        </Title>

        <form onSubmit={handleSubmit}>
          <Stack gap="sm">
          <TextInput
            id="name"
            label={t('categories.name')}
            value={name}
            onChange={(e) => setName(e.target.value)}
            required
          />

          <Select
            id="type"
            label={t('categories.type')}
            value={type}
            onChange={(val) => setType(val ?? 'saida')}
            data={[
              { value: 'saida', label: t('categories.type_expense') },
              { value: 'entrada', label: t('categories.type_income') },
            ]}
          />

          <Box>
            <Text size="sm" fw={500} mb="xs">
              {t('categories.icon')}
            </Text>
            <SimpleGrid cols={{ base: 4, xs: 6 }}>
              {CATEGORY_ICON_OPTIONS.map((option) => {
                const selected = option.className === icon

                return (
                  <UnstyledButton
                    key={option.className}
                    onClick={() => setIcon(option.className)}
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

          <Button type="submit" loading={createMutation.isPending}>
            {t('common.save')}
          </Button>
          </Stack>
        </form>
      </SectionCard>

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
    </PageContainer>
  )
}
