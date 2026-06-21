import { Paper, type PaperProps } from '@mantine/core'
import type { ReactNode } from 'react'

interface SectionCardProps extends Omit<PaperProps, 'children'> {
  children: ReactNode
}

export function SectionCard({ children, ...props }: SectionCardProps) {
  return (
    <Paper shadow="xs" radius="md" p="lg" withBorder mb="md" {...props}>
      {children}
    </Paper>
  )
}
