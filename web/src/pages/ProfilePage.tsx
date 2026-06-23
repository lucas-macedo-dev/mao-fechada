import { useEffect, useState } from 'react'
import type { ChangeEvent, SyntheticEvent } from 'react'
import { useTranslation } from 'react-i18next'
import i18n from '../i18n/config'
import { extractApiError } from '../services/api'
import { useAuth, useUpdateProfile } from '../hooks/api'
import { useTutorial } from '../context/TutorialContext'
import appLogo from '../assets/icon_mao_fechada.png'
import {
  Title,
  Text,
  TextInput,
  PasswordInput,
  Select,
  Button,
  Alert,
  Stack,
  Group,
  Avatar,
} from '@mantine/core'
import { PageContainer } from '../components/ui/PageContainer'
import { SectionCard } from '../components/ui/SectionCard'

export function ProfilePage() {
  const { t } = useTranslation()
  const { user } = useAuth()
  const updateProfile = useUpdateProfile()
  const { restart } = useTutorial()

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
    <PageContainer>
      <Group align="center" gap="xs" mb="lg">
        <Title order={1}>{t('profile.title')}</Title>
      </Group>

      <SectionCard>
        <form onSubmit={handleSubmit}>
          <Stack gap="md">
            <Stack align="center" gap="xs" mb="xs">
              <Avatar
                src={previewUrl}
                size={90}
                radius="xl"
                style={{ border: '1px solid var(--mantine-color-indigo-2)' }}
              >
                <img src={appLogo} alt={t('app.title')} style={{ width: '100%', height: '100%', objectFit: 'contain', padding: '0.75rem' }} />
              </Avatar>

              <Group gap="xs" justify="center">
                <Button
                  component="label"
                  htmlFor="profile-photo"
                  variant="light"
                  size="sm"
                >
                  {t('profile.change_photo')}
                </Button>
                <input
                  id="profile-photo"
                  type="file"
                  accept="image/*"
                  onChange={handleProfilePhotoChange}
                  style={{ display: 'none' }}
                />
                {(previewUrl || user?.profile_photo_url) && (
                  <Button variant="light" color="red" size="sm" onClick={handleRemovePhoto} type="button">
                    {t('profile.remove_photo')}
                  </Button>
                )}
              </Group>

              <Text size="xs" c="dimmed">{t('profile.photo_hint')}</Text>
            </Stack>

            <TextInput
              id="profile-name"
              label={t('auth.name')}
              value={name}
              onChange={(event) => setName(event.target.value)}
              required
            />

            <TextInput
              id="profile-email"
              label={t('auth.email')}
              type="email"
              value={email}
              onChange={(event) => setEmail(event.target.value)}
              required
            />

            <Select
              id="profile-locale"
              label={t('profile.locale')}
              value={locale}
              onChange={(val) => setLocale(val === 'en' ? 'en' : 'pt-BR')}
              data={[
                { value: 'pt-BR', label: 'Português (Brasil)' },
                { value: 'en', label: 'English' },
              ]}
            />

            <PasswordInput
              id="profile-password"
              label={t('profile.new_password')}
              value={password}
              onChange={(event) => setPassword(event.target.value)}
              minLength={8}
              placeholder={t('profile.password_placeholder')}
            />

            <PasswordInput
              id="profile-password-confirmation"
              label={t('profile.confirm_password')}
              value={passwordConfirmation}
              onChange={(event) => setPasswordConfirmation(event.target.value)}
              minLength={8}
              placeholder={t('profile.password_placeholder')}
            />

            {error && (
              <Alert color="red" radius="md">
                {error}
              </Alert>
            )}
            {success && (
              <Alert color="green" radius="md">
                {success}
              </Alert>
            )}

            <Button type="submit" loading={updateProfile.isPending}>
              {t('common.save')}
            </Button>
          </Stack>
        </form>
      </SectionCard>

      <SectionCard mt="md">
        <Stack gap="xs">
          <Text fw={600}>{t('tutorial.title')}</Text>
          <Text size="sm" c="dimmed">{t('tutorial.restart')}</Text>
          <Button variant="light" onClick={restart} leftSection={<i className="fa-solid fa-rotate-left" />}>
            {t('tutorial.restart')}
          </Button>
        </Stack>
      </SectionCard>
    </PageContainer>
  )
}
