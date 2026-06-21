import { useTranslation } from 'react-i18next'
import { useAuth } from '../hooks/api'
import { useState, type SyntheticEvent } from 'react'
import { useNavigate } from 'react-router-dom'
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
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')

  if (isAuthenticated) {
    navigate('/home')
  }

  const handleSubmit = async (e: SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault()
    setError('')
    try {
      await login.mutateAsync({ email, password })
      navigate('/home')
    } catch (err: any) {
      setError(err?.response?.data?.error?.message || t('auth.failed'))
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
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
            />

            <PasswordInput
              label={t('auth.password')}
              id="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />

            {error && (
              <Alert color="red" radius="md">
                {error}
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
