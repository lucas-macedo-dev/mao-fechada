import { useTranslation } from 'react-i18next'
import { useState, type SyntheticEvent } from 'react'
import { useForm } from '@mantine/form'
import appLogo from '../assets/icon_mao_fechada.png'
import { api } from '../services/api'
import {
  Box,
  Paper,
  Center,
  Title,
  Text,
  TextInput,
  Button,
  Alert,
  Stack,
  Anchor,
} from '@mantine/core'

export function ForgotPasswordPage() {
  const { t } = useTranslation()
  const [sent, setSent] = useState(false)
  const [loading, setLoading] = useState(false)

  const form = useForm({
    initialValues: { email: '' },
    validate: {
      email: (v) => (!v ? t('auth.field_required') : !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? t('auth.email_invalid') : null),
    },
  })

  const handleSubmit = async (e: SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault()
    const validation = form.validate()
    if (validation.hasErrors) return

    setLoading(true)
    try {
      await api.forgotPassword(form.values.email)
    } finally {
      setLoading(false)
      setSent(true)
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
          {t('auth.forgot_password_title')}
        </Title>
        <Text ta="center" size="sm" c="dimmed" mb="xl">
          {t('auth.forgot_password_description')}
        </Text>

        {sent ? (
          <Stack gap="md">
            <Alert color="green" radius="md">
              {t('auth.forgot_password_sent')}
            </Alert>
            <Text ta="center" size="sm">
              <Anchor href="/login">{t('auth.back_to_login')}</Anchor>
            </Text>
          </Stack>
        ) : (
          <form onSubmit={handleSubmit}>
            <Stack gap="md">
              <TextInput
                label={t('auth.email')}
                type="email"
                {...form.getInputProps('email')}
              />
              <Button type="submit" fullWidth loading={loading}>
                {t('auth.forgot_password_submit')}
              </Button>
              <Text ta="center" size="sm" c="dimmed">
                <Anchor href="/login">{t('auth.back_to_login')}</Anchor>
              </Text>
            </Stack>
          </form>
        )}
      </Paper>
    </Box>
  )
}
