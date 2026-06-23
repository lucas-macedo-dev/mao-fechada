import { Popover, Text, Box } from '@mantine/core'
import { useTranslation } from 'react-i18next'
import { useLocation } from 'react-router-dom'
import { useTutorial } from '../../context/TutorialContext'
import type { TutorialStepId } from '../../tutorial/steps'

interface TutorialHintProps {
  stepId: TutorialStepId
  children: React.ReactNode
}

export function TutorialHint({ stepId, children }: Readonly<TutorialHintProps>) {
  const { t } = useTranslation()
  const { activeStep } = useTutorial()
  const location = useLocation()

  const isActive = activeStep?.id === stepId
  const isOnPage = activeStep?.path === location.pathname

  if (!isActive || !isOnPage) {
    return <>{children}</>
  }

  return (
    <Popover opened position="top" withArrow shadow="md" offset={8}>
      <Popover.Target>
        <Box
          data-tutorial-step={stepId}
          style={{ display: 'contents' }}
        >
          {children}
        </Box>
      </Popover.Target>
      <Popover.Dropdown
        style={{
          background: 'var(--mantine-color-indigo-6)',
          border: 'none',
          borderRadius: 8,
          maxWidth: 220,
        }}
      >
        <Text size="xs" fw={600} c="white" mb={2}>
          <i className="fa-solid fa-lightbulb" style={{ marginRight: 4 }} />
          {t('tutorial.hint')}
        </Text>
        <Text size="xs" c="white" style={{ opacity: 0.9 }}>
          {t(activeStep.descriptionKey)}
        </Text>
      </Popover.Dropdown>
    </Popover>
  )
}
