import { useTranslation } from 'react-i18next'
import { Stack, Title, Text, Center } from '@mantine/core'

export function PaymentMethodsPage() {
  const { t } = useTranslation()

  return (
    <Stack gap="xl" p="md">
      <Title order={2}>{t('payment.title')}</Title>

      <Center py="xl">
        <Stack align="center" gap="md">
          <i className="fa-solid fa-credit-card" style={{ fontSize: 48, color: 'var(--mantine-color-violet-6)' }} />
          <Title order={3} ta="center">{t('payment.coming_soon')}</Title>
          <Text size="sm" c="dimmed" ta="center" maw={300}>
            {t('payment.coming_soon_description')}
          </Text>
        </Stack>
      </Center>
    </Stack>
  )
}
