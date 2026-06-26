import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useParams, useSearchParams, useNavigate } from 'react-router-dom'
import { useQueryClient } from '@tanstack/react-query'
import appLogo from '../assets/icon_mao_fechada.png'
import { api } from '../services/api'
import { Box, Paper, Center, Title, Text, Alert, Loader, Stack } from '@mantine/core'

export function EmailVerificationCallbackPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { id, hash } = useParams<{ id: string; hash: string }>()
  const [searchParams] = useSearchParams()
  const [status, setStatus] = useState<'verifying' | 'success' | 'error'>('verifying')

  useEffect(() => {
    const expires = searchParams.get('expires') ?? ''
    const signature = searchParams.get('signature') ?? ''

    if (!id || !hash || !expires || !signature) {
      setStatus('error')
      return
    }

    api
      .verifyEmail(id, hash, expires, signature)
      .then(() => {
        setStatus('success')
        queryClient.invalidateQueries({ queryKey: ['auth', 'me'] })
        setTimeout(() => navigate('/home'), 2000)
      })
      .catch(() => setStatus('error'))
  }, [])

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

        <Title order={2} ta="center" mb="xl">
          {t('auth.verify_email_title')}
        </Title>

        <Stack gap="md" align="center">
          {status === 'verifying' && (
            <>
              <Loader />
              <Text size="sm" c="dimmed">{t('auth.verify_email_verifying')}</Text>
            </>
          )}
          {status === 'success' && (
            <Alert color="green" radius="md" w="100%">
              {t('auth.verify_email_success')}
            </Alert>
          )}
          {status === 'error' && (
            <Alert color="red" radius="md" w="100%">
              {t('auth.verify_email_error')}
            </Alert>
          )}
        </Stack>
      </Paper>
    </Box>
  )
}
