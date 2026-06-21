import { useTranslation } from 'react-i18next'
import { useAuth } from '../hooks/api'
import { useState, type SyntheticEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { useForm } from '@mantine/form'
import appLogo from '../assets/icon_mao_fechada.png'
import {
  Center,
  Paper,
  Title,
  Text,
  TextInput,
  PasswordInput,
  Button,
  Alert,
  Stack,
  Anchor,
  Box,
} from '@mantine/core'

export function RegisterPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { register, isAuthenticated } = useAuth()
  const [generalError, setGeneralError] = useState('')

  if (isAuthenticated) {
    navigate('/home')
  }

  const form = useForm({
    initialValues: { name: '', email: '', password: '', passwordConfirmation: '' },
    validate: {
      name: (v) => (!v ? t('auth.field_required') : null),
      email: (v) => (!v ? t('auth.field_required') : !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? t('auth.email_invalid') : null),
      password: (v) => (!v ? t('auth.field_required') : v.length < 8 ? t('auth.password_too_short') : !/[0-9\W]/.test(v) ? t('auth.password_complexity') : null),
      passwordConfirmation: (v, values) => (v !== values.password ? t('auth.password_mismatch') : null),
    },
  })

  const handleSubmit = async (e: SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault()
    setGeneralError('')

    const validation = form.validate()
    if (validation.hasErrors) return

    try {
      const { passwordConfirmation: _ignored, ...payload } = form.values
      await register.mutateAsync({ ...payload, locale: localStorage.getItem('app_locale') || 'pt-BR' })
      navigate('/home')
    } catch (err: unknown) {
      const data = (err as any)?.response?.data
      if (data?.errors && typeof data.errors === 'object') {
        Object.entries(data.errors as Record<string, string[]>).forEach(([field, messages]) => {
          form.setFieldError(field, messages[0])
        })
      } else {
        setGeneralError(t('auth.register_failed'))
      }
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

        <Title order={1} ta="center" mb="xs">
          {t('app.title')}
        </Title>
        <Text ta="center" size="sm" c="dimmed" mb="xl">
          {t('app.subtitle')}
        </Text>

        <form onSubmit={handleSubmit}>
          <Stack gap="md">
            <TextInput
              label={t('auth.name')}
              id="name"
              type="text"
              {...form.getInputProps('name')}
            />

            <TextInput
              label={t('auth.email')}
              id="email"
              type="email"
              {...form.getInputProps('email')}
            />

            <div>
              <PasswordInput
                label={t('auth.password')}
                id="password"
                {...form.getInputProps('password')}
              />
              <Text size="xs" c="dimmed" mt={4}>
                {t('auth.password_hint')}
              </Text>
            </div>

            <PasswordInput
              label={t('auth.confirm_password')}
              id="passwordConfirmation"
              {...form.getInputProps('passwordConfirmation')}
            />

            {generalError && (
              <Alert color="red" radius="md">
                {generalError}
              </Alert>
            )}

            <Button type="submit" fullWidth loading={register.isPending}>
              {t('auth.register')}
            </Button>
          </Stack>
        </form>

        <Text ta="center" size="sm" c="dimmed" mt="md">
          {t('auth.has_account')}{' '}
          <Anchor href="/login" fw={600}>
            {t('auth.login')}
          </Anchor>
        </Text>
      </Paper>
    </Box>
  )
}
