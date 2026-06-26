import { useTranslation } from 'react-i18next'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import appLogo from '../assets/icon_mao_fechada.png'
import { api } from '../services/api'
import {
  Box,
  Paper,
  Center,
  Title,
  Text,
  Button,
  Alert,
  Stack,
} from '@mantine/core'

export function VerifyEmailPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [resendStatus, setResendStatus] = useState<'idle' | 'loading' | 'success' | 'error'>('idle')

  const handleResend = async () => {
    setResendStatus('loading')
    try {
      await api.resendVerificationEmail()
      setResendStatus('success')
    } catch {
      setResendStatus('error')
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
          {t('auth.verify_email_title')}
        </Title>
        <Text ta="center" size="sm" c="dimmed" mb="xl">
          {t('auth.verify_email_description')}
        </Text>

        <Stack gap="md">
          {resendStatus === 'success' && (
            <Alert color="green" radius="md">
              {t('auth.verify_email_resend_success')}
            </Alert>
          )}
          {resendStatus === 'error' && (
            <Alert color="red" radius="md">
              {t('common.error')}
            </Alert>
          )}

          <Button
            fullWidth
            onClick={handleResend}
            loading={resendStatus === 'loading'}
            variant="light"
          >
            {t('auth.verify_email_resend')}
          </Button>

          <Button
            fullWidth
            variant="subtle"
            onClick={() => navigate('/login')}
          >
            {t('auth.back_to_login')}
          </Button>
        </Stack>
      </Paper>
    </Box>
  )
}
