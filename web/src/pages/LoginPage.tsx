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

export function LoginPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { login, isAuthenticated } = useAuth()
  const [generalError, setGeneralError] = useState('')

  if (isAuthenticated) {
    navigate('/home')
  }

  const form = useForm({
    initialValues: { email: '', password: '' },
    validate: {
      email: (v) => (!v ? t('auth.field_required') : !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? t('auth.email_invalid') : null),
      password: (v) => (!v ? t('auth.field_required') : v.length < 8 ? t('auth.password_too_short') : null),
    },
  })

  const handleSubmit = async (e: SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault()
    setGeneralError('')

    const validation = form.validate()
    if (validation.hasErrors) return

    try {
      await login.mutateAsync(form.values)
      navigate('/home')
    } catch (err: unknown) {
      const data = (err as any)?.response?.data
      if (data?.errors && typeof data.errors === 'object') {
        Object.entries(data.errors as Record<string, string[]>).forEach(([field, messages]) => {
          form.setFieldError(field, messages[0])
        })
      } else {
        setGeneralError(t('auth.failed'))
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
              label={t('auth.email')}
              id="email"
              type="email"
              {...form.getInputProps('email')}
            />

            <PasswordInput
              label={t('auth.password')}
              id="password"
              {...form.getInputProps('password')}
            />

            {generalError && (
              <Alert color="red" radius="md">
                {generalError}
              </Alert>
            )}

            <Button type="submit" fullWidth loading={login.isPending}>
              {t('auth.login')}
            </Button>
          </Stack>
        </form>

        <Text ta="center" size="sm" c="dimmed" mt="md">
          {t('auth.no_account')}{' '}
          <Anchor href="/register" fw={600}>
            {t('auth.register')}
          </Anchor>
        </Text>
      </Paper>
    </Box>
  )
}
