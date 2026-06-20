import { useTranslation } from 'react-i18next'
import { useCategories, useCreateCategory, useDeleteCategory, useUpdateCategory } from '../hooks/api'
import { useState, type SyntheticEvent } from 'react'
import { CATEGORY_ICON_OPTIONS, DEFAULT_CATEGORY_ICON, getCategoryIconClass } from '../constants/categoryIcons'
import { extractApiError } from '../services/api'
import '../styles/pages.css'

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
    return <div className="page">{t('common.loading')}</div>
  }

  const rootCategories = categories.filter((c) => !c.parent_id)

  const renderCategory = (category: {
    id: number
    name: string
    type: string
    icon?: string
    children?: Array<{ id: number; name: string; type: string; icon?: string }>
  }) => {
    const isEditing = editingCategoryId === category.id

    return (
      <div key={category.id} className="category-item">
        <div className="category-header">
          <p className="category-name">
            <span className="category-icon-wrapper" aria-hidden="true">
              <i className={getCategoryIconClass(category.icon)} />
            </span>
            {category.name}
          </p>
          <div className="item-actions">
            <p className="category-type">{t(`categories.type_${category.type}`)}</p>
            <button
              type="button"
              className="icon-action-btn"
              onClick={() => handleStartEdit(category)}
              aria-label={t('categories.edit')}
              title={t('categories.edit')}
            >
              <i className="fa-solid fa-pen-to-square" aria-hidden="true" />
            </button>
            <button
              type="button"
              className="icon-action-btn danger"
              onClick={() => handleDelete(category.id)}
              aria-label={t('categories.delete')}
              title={t('categories.delete')}
            >
              <i className="fa-solid fa-trash-can" aria-hidden="true" />
            </button>
          </div>
        </div>

        {isEditing && (
          <form className="inline-edit-form" onSubmit={(event) => handleSaveEdit(event, category.id)}>
            <div className="form-grid">
              <div className="form-group">
                <label htmlFor={`category-name-${category.id}`}>{t('categories.name')}</label>
                <input
                  id={`category-name-${category.id}`}
                  type="text"
                  value={editName}
                  onChange={(event) => setEditName(event.target.value)}
                  required
                />
              </div>

              <div className="form-group">
                <label htmlFor={`category-type-${category.id}`}>{t('categories.type')}</label>
                <select
                  id={`category-type-${category.id}`}
                  value={editType}
                  onChange={(event) => setEditType(event.target.value === 'entrada' ? 'entrada' : 'saida')}
                >
                  <option value="saida">{t('categories.type_expense')}</option>
                  <option value="entrada">{t('categories.type_income')}</option>
                </select>
              </div>

              <div className="form-group full-width">
                <label>{t('categories.icon')}</label>
                <div className="icon-picker-grid">
                  {CATEGORY_ICON_OPTIONS.map((option) => {
                    const selected = option.className === editIcon

                    return (
                      <button
                        key={`${category.id}-${option.className}`}
                        type="button"
                        className={`icon-picker-button ${selected ? 'selected' : ''}`}
                        onClick={() => setEditIcon(option.className)}
                        aria-label={t(option.key)}
                        title={t(option.key)}
                      >
                        <i className={option.className} aria-hidden="true" />
                      </button>
                    )
                  })}
                </div>
              </div>
            </div>

            <div className="inline-actions">
              <button type="submit" className="btn btn-primary" disabled={updateMutation.isPending}>
                {updateMutation.isPending ? t('common.loading') : t('common.save')}
              </button>
              <button type="button" className="btn btn-secondary" onClick={handleCancelEdit}>
                {t('common.cancel')}
              </button>
            </div>
          </form>
        )}

        {category.children && category.children.length > 0 && (
          <div className="subcategories">
            {category.children.map((subcategory) => (
              <div key={subcategory.id} className="subcategory-item">
                <p className="subcategory-name">
                  <span className="category-icon-wrapper" aria-hidden="true">
                    <i className={getCategoryIconClass(subcategory.icon)} />
                  </span>
                  {subcategory.name}
                </p>
                <div className="item-actions">
                  <button
                    type="button"
                    className="icon-action-btn"
                    onClick={() => handleStartEdit(subcategory)}
                    aria-label={t('categories.edit')}
                    title={t('categories.edit')}
                  >
                    <i className="fa-solid fa-pen-to-square" aria-hidden="true" />
                  </button>
                  <button
                    type="button"
                    className="icon-action-btn danger"
                    onClick={() => handleDelete(subcategory.id)}
                    aria-label={t('categories.delete')}
                    title={t('categories.delete')}
                  >
                    <i className="fa-solid fa-trash-can" aria-hidden="true" />
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    )
  }

  return (
    <div className="page categories-page">
      <h1>{t('categories.title')}</h1>

      <form onSubmit={handleSubmit} className="card form-card">
        <h2>{t('categories.create')}</h2>

        <div className="form-group">
          <label htmlFor="name">{t('categories.name')}</label>
          <input id="name" type="text" value={name} onChange={(e) => setName(e.target.value)} required />
        </div>

        <div className="form-group">
          <label htmlFor="type">{t('categories.type')}</label>
          <select id="type" value={type} onChange={(e) => setType(e.target.value)}>
            <option value="saida">{t('categories.type_expense')}</option>
            <option value="entrada">{t('categories.type_income')}</option>
          </select>
        </div>

        <div className="form-group">
          <label>{t('categories.icon')}</label>
          <div className="icon-picker-grid">
            {CATEGORY_ICON_OPTIONS.map((option) => {
              const selected = option.className === icon

              return (
                <button
                  key={option.className}
                  type="button"
                  className={`icon-picker-button ${selected ? 'selected' : ''}`}
                  onClick={() => setIcon(option.className)}
                  aria-label={t(option.key)}
                  title={t(option.key)}
                >
                  <i className={option.className} aria-hidden="true" />
                </button>
              )
            })}
          </div>
        </div>

        <button type="submit" disabled={createMutation.isPending} className="btn btn-primary">
          {createMutation.isPending ? t('common.loading') : t('common.save')}
        </button>
      </form>

      {error && <div className="error-message">{error}</div>}
      {success && <div className="success-message">{success}</div>}

      <div className="categories-list">
        {rootCategories.map(renderCategory)}
      </div>
    </div>
  )
}
