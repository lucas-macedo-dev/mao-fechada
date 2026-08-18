import { useTranslation } from 'react-i18next'
import { useState, type SyntheticEvent } from 'react'
import { useSearchParams, useNavigate } from 'react-router-dom'
import { useForm } from '@mantine/form'
import appLogo from '../assets/icon_mao_fechada.png'
import { api, extractApiError } from '../services/api'
import {
  Box,
  Paper,
  Center,
  Title,
  Text,
  PasswordInput,
  Button,
  Alert,
  Stack,
  Anchor,
} from '@mantine/core'

export function ResetPasswordPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const token = searchParams.get('token') ?? ''
  const email = searchParams.get('email') ?? ''

  const form = useForm({
    initialValues: { password: '', password_confirmation: '' },
    validate: {
      password: (v) => (!v ? t('auth.field_required') : v.length < 8 ? t('auth.password_too_short') : !/[0-9\W]/.test(v) ? t('auth.password_complexity') : null),
      password_confirmation: (v, values) => (v !== values.password ? t('auth.password_mismatch') : null),
    },
  })

  const handleSubmit = async (e: SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault()
    setError('')
    const validation = form.validate()
    if (validation.hasErrors) return

    setLoading(true)
    try {
      await api.resetPassword({
        token,
        email,
        password: form.values.password,
        password_confirmation: form.values.password_confirmation,
      })
      navigate('/login?reset=1')
    } catch (err: unknown) {
      setError(extractApiError(err).message || t('common.error'))
    } finally {
      setLoading(false)
    }
  }

  return (
    <Box
      style={{
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        padding: '1rem',
      }}
    >
      <Paper radius="md" p="xl" shadow="xl" w="100%" maw={400}>
        <Center mb="xs">
          <img src={appLogo} alt={t('app.title')} style={{ width: 72, height: 72, objectFit: 'contain' }} />
        </Center>

        <Title order={2} ta="center" mb="xs">
          {t('auth.reset_password_title')}
        </Title>
        <Text ta="center" size="sm" c="dimmed" mb="xl">
          {t('auth.reset_password_description')}
        </Text>

        <form onSubmit={handleSubmit}>
          <Stack gap="md">
            <PasswordInput
              label={t('auth.new_password')}
              {...form.getInputProps('password')}
            />
            <Text size="xs" c="dimmed" mt={-8}>
              {t('auth.password_hint')}
            </Text>

            <PasswordInput
              label={t('auth.confirm_password')}
              {...form.getInputProps('password_confirmation')}
            />

            {error && (
              <Alert color="red" radius="md">
                {error}
              </Alert>
            )}

            <Button type="submit" fullWidth loading={loading}>
              {t('auth.reset_password_submit')}
            </Button>

            <Text ta="center" size="sm" c="dimmed">
              <Anchor href="/login">{t('auth.back_to_login')}</Anchor>
            </Text>
          </Stack>
        </form>
      </Paper>
    </Box>
  )
}
