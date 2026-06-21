import { Container, type ContainerProps } from '@mantine/core'
import type { ReactNode } from 'react'

interface PageContainerProps extends Omit<ContainerProps, 'children'> {
  children: ReactNode
}

export function PageContainer({ children, ...props }: PageContainerProps) {
  return (
    <Container size="lg" px="md" py="lg" {...props}>
      {children}
    </Container>
  )
}
