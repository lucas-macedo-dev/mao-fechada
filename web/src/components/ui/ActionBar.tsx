import { Group, type GroupProps } from '@mantine/core'
import type { ReactNode } from 'react'

interface ActionBarProps extends Omit<GroupProps, 'children'> {
  children: ReactNode
}

export function ActionBar({ children, ...props }: ActionBarProps) {
  return (
    <Group mt="sm" gap="xs" {...props}>
      {children}
    </Group>
  )
}
