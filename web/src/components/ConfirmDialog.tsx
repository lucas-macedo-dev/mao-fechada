import { Modal, Text, Group, Button } from '@mantine/core'
import { useTranslation } from 'react-i18next'

interface ConfirmDialogProps {
  opened: boolean
  title: string
  message: string
  onConfirm: () => void
  onCancel: () => void
  loading?: boolean
}

export function ConfirmDialog({ opened, title, message, onConfirm, onCancel, loading }: ConfirmDialogProps) {
  const { t } = useTranslation()
  return (
    <Modal opened={opened} onClose={onCancel} title={title} centered size="sm">
      <Text mb="lg">{message}</Text>
      <Group justify="flex-end">
        <Button variant="light" onClick={onCancel} disabled={loading}>
          {t('common.cancel')}
        </Button>
        <Button color="red" onClick={onConfirm} loading={loading}>
          {t('common.confirm')}
        </Button>
      </Group>
    </Modal>
  )
}
