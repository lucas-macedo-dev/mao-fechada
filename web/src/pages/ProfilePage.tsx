import { useEffect, useState } from 'react'
import type { ChangeEvent, SyntheticEvent } from 'react'
import { useTranslation } from 'react-i18next'
import i18n from '../i18n/config'
import { extractApiError } from '../services/api'
import { useAuth, useUpdateProfile } from '../hooks/api'
import appLogo from '../assets/icon_mao_fechada.png'
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
  const [profilePhotoFile, setProfilePhotoFile] = useState<File | null>(null)
  const [previewUrl, setPreviewUrl] = useState<string | null>(null)
  const [removePhoto, setRemovePhoto] = useState(false)
  const [error, setError] = useState('')
  const [success, setSuccess] = useState('')

  useEffect(() => {
    if (!user) {
      return
    }

    setName(user.name)
    setEmail(user.email)
    setLocale(user.locale === 'en' ? 'en' : 'pt-BR')
    setPreviewUrl(user.profile_photo_url || null)
  }, [user])

  useEffect(() => {
    return () => {
      if (previewUrl?.startsWith('blob:')) {
        URL.revokeObjectURL(previewUrl)
      }
    }
  }, [previewUrl])

  const handleProfilePhotoChange = (event: ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0]

    if (!file) {
      return
    }

    if (previewUrl?.startsWith('blob:')) {
      URL.revokeObjectURL(previewUrl)
    }

    setProfilePhotoFile(file)
    setRemovePhoto(false)
    setPreviewUrl(URL.createObjectURL(file))
  }

  const handleRemovePhoto = () => {
    if (previewUrl?.startsWith('blob:')) {
      URL.revokeObjectURL(previewUrl)
    }

    setProfilePhotoFile(null)
    setPreviewUrl(null)
    setRemovePhoto(true)
  }

  const handleSubmit = async (event: SyntheticEvent<HTMLFormElement>) => {
    event.preventDefault()
    setError('')
    setSuccess('')

    if (password && password !== passwordConfirmation) {
      setError(t('profile.password_mismatch'))
      return
    }

    try {
      const payload = new FormData()

      payload.append('name', name)
      payload.append('email', email)
      payload.append('locale', locale)

      if (password) {
        payload.append('password', password)
        payload.append('password_confirmation', passwordConfirmation)
      }

      if (profilePhotoFile) {
        payload.append('profile_photo', profilePhotoFile)
      }

      if (removePhoto) {
        payload.append('remove_profile_photo', '1')
      }

      const updatedUser = await updateProfile.mutateAsync(payload)

      localStorage.setItem('app_locale', updatedUser.locale)
      await i18n.changeLanguage(updatedUser.locale)

      setPassword('')
      setPasswordConfirmation('')
      setProfilePhotoFile(null)
      setRemovePhoto(false)
      setPreviewUrl(updatedUser.profile_photo_url || null)
      setSuccess(t('profile.saved'))
    } catch (err) {
      setError(extractApiError(err).message)
    }
  }

  return (
    <div className="page profile-page">
      <div className="page-title-with-logo">
        <img src={appLogo} alt={t('app.title')} className="page-logo" />
        <h1>{t('profile.title')}</h1>
      </div>

      <form className="card form-card" onSubmit={handleSubmit}>
        <div className="profile-photo-section">
          <div className="profile-photo-preview">
            {previewUrl ? (
              <img src={previewUrl} alt={t('profile.photo')} />
            ) : (
              <img src={appLogo} alt={t('app.title')} className="profile-photo-fallback" />
            )}
          </div>

          <div className="profile-photo-actions">
            <label htmlFor="profile-photo" className="btn btn-secondary">
              {t('profile.change_photo')}
            </label>
            <input id="profile-photo" type="file" accept="image/*" onChange={handleProfilePhotoChange} className="hidden-file-input" />
            {(previewUrl || user?.profile_photo_url) && (
              <button type="button" className="btn btn-danger" onClick={handleRemovePhoto}>
                {t('profile.remove_photo')}
              </button>
            )}
            <p className="profile-photo-hint">{t('profile.photo_hint')}</p>
          </div>
        </div>

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
