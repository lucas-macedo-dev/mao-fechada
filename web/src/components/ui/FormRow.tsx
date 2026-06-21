import { SimpleGrid, type SimpleGridProps } from '@mantine/core'
import type { ReactNode } from 'react'

interface FormRowProps extends Omit<SimpleGridProps, 'children'> {
  children: ReactNode
}

export function FormRow({ children, ...props }: FormRowProps) {
  return (
    <SimpleGrid cols={{ base: 1, sm: 2 }} mb="sm" {...props}>
      {children}
    </SimpleGrid>
  )
}
