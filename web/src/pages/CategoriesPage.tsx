import { useTranslation } from 'react-i18next'
import { useCategories, useCreateCategory } from '../hooks/api'
import { useState, type FormEvent } from 'react'
import '../styles/pages.css'

export function CategoriesPage() {
  const { t } = useTranslation()
  const { data: categories = [], isLoading } = useCategories(true)
  const createMutation = useCreateCategory()
  const [name, setName] = useState('')
  const [type, setType] = useState('saida')
  const [error, setError] = useState('')

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault()
    setError('')
    try {
      await createMutation.mutateAsync({ name, type })
      setName('')
      setType('saida')
    } catch (err: any) {
      setError(err?.response?.data?.error?.message || 'Failed to create category')
    }
  }

  if (isLoading) {
    return <div className="page">{t('common.loading')}</div>
  }

  const rootCategories = categories.filter((c) => !c.parent_id)

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

        {error && <div className="error-message">{error}</div>}

        <button type="submit" disabled={createMutation.isPending} className="btn btn-primary">
          {createMutation.isPending ? t('common.loading') : t('common.save')}
        </button>
      </form>

      <div className="categories-list">
        {rootCategories.map((category) => (
          <div key={category.id} className="category-item">
            <div className="category-header">
              <p className="category-name">{category.name}</p>
              <p className="category-type">{category.type}</p>
            </div>
            {category.children && category.children.length > 0 && (
              <div className="subcategories">
                {category.children.map((subcategory) => (
                  <div key={subcategory.id} className="subcategory-item">
                    <p className="subcategory-name">{subcategory.name}</p>
                  </div>
                ))}
              </div>
            )}
          </div>
        ))}
      </div>
    </div>
  )
}
