import { useEffect, useState } from 'react'
import type { SyntheticEvent } from 'react'
import { useTranslation } from 'react-i18next'
import i18n from '../i18n/config'
import { extractApiError } from '../services/api'
import { useAuth, useUpdateProfile } from '../hooks/api'
import '../styles/pages.css'

export function ProfilePage() {
  const { t } = useTranslation()
  const { user } = useAuth()
  const updateProfile = useUpdateProfile()

  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [locale, setLocale] = useState<'pt-BR' | 'en'>('pt-BR')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [error, setError] = useState('')
  const [success, setSuccess] = useState('')

  useEffect(() => {
    if (!user) {
      return
    }

    setName(user.name)
    setEmail(user.email)
    setLocale(user.locale === 'en' ? 'en' : 'pt-BR')
  }, [user])

  const handleSubmit = async (event: SyntheticEvent<HTMLFormElement>) => {
    event.preventDefault()
    setError('')
    setSuccess('')

    if (password && password !== passwordConfirmation) {
      setError(t('profile.password_mismatch'))
      return
    }

    try {
      const payload: {
        name: string
        email: string
        locale: 'pt-BR' | 'en'
        password?: string
        password_confirmation?: string
      } = {
        name,
        email,
        locale,
      }

      if (password) {
        payload.password = password
        payload.password_confirmation = passwordConfirmation
      }

      const updatedUser = await updateProfile.mutateAsync(payload)

      localStorage.setItem('app_locale', updatedUser.locale)
      await i18n.changeLanguage(updatedUser.locale)

      setPassword('')
      setPasswordConfirmation('')
      setSuccess(t('profile.saved'))
    } catch (err) {
      setError(extractApiError(err).message)
    }
  }

  return (
    <div className="page profile-page">
      <h1>{t('profile.title')}</h1>

      <form className="card form-card" onSubmit={handleSubmit}>
        <div className="form-group">
          <label htmlFor="profile-name">{t('auth.name')}</label>
          <input id="profile-name" type="text" value={name} onChange={(event) => setName(event.target.value)} required />
        </div>

        <div className="form-group">
          <label htmlFor="profile-email">{t('auth.email')}</label>
          <input id="profile-email" type="email" value={email} onChange={(event) => setEmail(event.target.value)} required />
        </div>

        <div className="form-group">
          <label htmlFor="profile-locale">{t('profile.locale')}</label>
          <select id="profile-locale" value={locale} onChange={(event) => setLocale(event.target.value === 'en' ? 'en' : 'pt-BR')}>
            <option value="pt-BR">Português (Brasil)</option>
            <option value="en">English</option>
          </select>
        </div>

        <div className="form-group">
          <label htmlFor="profile-password">{t('profile.new_password')}</label>
          <input
            id="profile-password"
            type="password"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            minLength={8}
            placeholder={t('profile.password_placeholder')}
          />
        </div>

        <div className="form-group">
          <label htmlFor="profile-password-confirmation">{t('profile.confirm_password')}</label>
          <input
            id="profile-password-confirmation"
            type="password"
            value={passwordConfirmation}
            onChange={(event) => setPasswordConfirmation(event.target.value)}
            minLength={8}
            placeholder={t('profile.password_placeholder')}
          />
        </div>

        {error && <div className="error-message">{error}</div>}
        {success && <div className="success-message">{success}</div>}

        <button type="submit" className="btn btn-primary" disabled={updateProfile.isPending}>
          {updateProfile.isPending ? t('common.loading') : t('common.save')}
        </button>
      </form>
    </div>
  )
}
