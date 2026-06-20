import { useTranslation } from 'react-i18next'
import { useAuth } from '../hooks/api'
import type { FormEvent } from 'react'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import '../styles/auth.css'

export function RegisterPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { register, isAuthenticated } = useAuth()
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')

  if (isAuthenticated) {
    navigate('/home')
  }

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault()
    setError('')
    try {
      await register.mutateAsync({ name, email, password, locale: localStorage.getItem('app_locale') || 'pt-BR' })
      navigate('/home')
    } catch (err: any) {
      setError(err?.response?.data?.error?.message || 'Registration failed')
    }
  }

  return (
    <div className="auth-page">
      <div className="auth-card">
        <h1>{t('app.title')}</h1>
        <p className="subtitle">{t('app.subtitle')}</p>

        <form onSubmit={handleSubmit} className="auth-form">
          <div className="form-group">
            <label htmlFor="name">{t('auth.name')}</label>
            <input id="name" type="text" value={name} onChange={(e) => setName(e.target.value)} required />
          </div>

          <div className="form-group">
            <label htmlFor="email">{t('auth.email')}</label>
            <input id="email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
          </div>

          <div className="form-group">
            <label htmlFor="password">{t('auth.password')}</label>
            <input id="password" type="password" value={password} onChange={(e) => setPassword(e.target.value)} required />
          </div>

          {error && <div className="error-message">{error}</div>}

          <button type="submit" disabled={register.isPending} className="btn btn-primary">
            {register.isPending ? t('common.loading') : t('auth.register')}
          </button>
        </form>

        <p className="auth-link">
          {t('auth.has_account')} <a href="/login">{t('auth.login')}</a>
        </p>
      </div>
    </div>
  )
}
