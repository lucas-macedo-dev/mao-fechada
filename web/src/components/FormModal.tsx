import { Modal } from '@mantine/core'
import type { ReactNode } from 'react'

interface FormModalProps {
  title: string
  opened: boolean
  onClose: () => void
  children: ReactNode
}

export function FormModal({ title, opened, onClose, children }: FormModalProps) {
  return (
    <Modal opened={opened} onClose={onClose} title={title} centered>
      {children}
    </Modal>
  )
}
