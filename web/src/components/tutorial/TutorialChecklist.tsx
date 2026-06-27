import { Affix, Paper, Stack, Text, Group, Button, ActionIcon, Transition, ThemeIcon } from '@mantine/core'
import { useMediaQuery } from '@mantine/hooks'
import { useTranslation } from 'react-i18next'
import { Link, useLocation } from 'react-router-dom'
import { useTutorial } from '../../context/TutorialContext'

export function TutorialChecklist() {
  const { t } = useTranslation()
  const location = useLocation()
  const isMobile = useMediaQuery('(max-width: 767px)')
  const { steps, activeStep, isAllDone, checklistOpen, closeChecklist, dismiss } = useTutorial()

  const completedCount = steps.filter((s) => s.completed).length
  const isOnActivePage = activeStep?.path === location.pathname

  return (
    <Affix position={{ bottom: isMobile ? 80 : 24, right: 14 }} zIndex={200}>
      <Transition transition="slide-up" mounted={checklistOpen} duration={200}>
        {(style) => (
          <Paper
            shadow="md"
            radius="lg"
            p="md"
            withBorder
            style={{
              ...style,
              width: 280,
              borderColor: 'var(--mantine-color-indigo-2)',
              background: 'white',
            }}
          >
            {/* Header */}
            <Group justify="space-between" mb="xs" wrap="nowrap">
              <Group gap="xs" wrap="nowrap">
                <ThemeIcon size="sm" radius="xl" color="indigo" variant="light">
                  <i className="fa-solid fa-graduation-cap" style={{ fontSize: '0.6rem' }} />
                </ThemeIcon>
                <Text size="xs" fw={700} c="indigo" tt="uppercase" style={{ letterSpacing: '0.04em' }}>
                  {t('tutorial.title')}
                </Text>
              </Group>
              <ActionIcon size="xs" variant="subtle" color="gray" onClick={closeChecklist} aria-label="minimize">
                <i className="fa-solid fa-minus" style={{ fontSize: '0.6rem' }} />
              </ActionIcon>
            </Group>

            {isAllDone ? (
              <Stack gap="xs">
                <Group gap="xs">
                  <i className="fa-solid fa-circle-check" style={{ color: 'var(--mantine-color-green-6)' }} />
                  <Text size="sm" fw={600} c="green">
                    {t('tutorial.completed_all')}
                  </Text>
                </Group>
                <Button size="xs" variant="subtle" color="gray" onClick={dismiss}>
                  {t('tutorial.dismiss')}
                </Button>
              </Stack>
            ) : (
              <Stack gap="sm">
                {/* Step progress dots */}
                <Group gap={6}>
                  {steps.map((step) => {
                    let dotColor = 'var(--mantine-color-gray-3)'
                    if (step.completed) dotColor = 'var(--mantine-color-indigo-6)'
                    else if (activeStep?.id === step.id) dotColor = 'var(--mantine-color-indigo-3)'

                    return (
                      <div
                        key={step.id}
                        style={{ width: 6, height: 6, borderRadius: '50%', background: dotColor }}
                      />
                    )
                  })}
                  <Text size="xs" c="dimmed" ml={2}>
                    {completedCount}/{steps.length}
                  </Text>
                </Group>

                {/* Active step content */}
                {activeStep && (
                  <Stack gap={4}>
                    <Text size="sm" fw={600}>
                      {t(activeStep.titleKey)}
                    </Text>
                    <Text size="xs" c="dimmed" lh={1.4}>
                      {t(activeStep.descriptionKey)}
                    </Text>

                    {!isOnActivePage && (
                      <Button
                        component={Link}
                        to={activeStep.path}
                        size="xs"
                        variant="filled"
                        color="indigo"
                        mt={4}
                        rightSection={<i className="fa-solid fa-arrow-right" style={{ fontSize: '0.6rem' }} />}
                      >
                        {t('tutorial.go_to_page', { page: activeStep.page })}
                      </Button>
                    )}
                  </Stack>
                )}

                <Button size="xs" variant="subtle" color="gray" onClick={dismiss} style={{ alignSelf: 'flex-end' }}>
                  {t('tutorial.dismiss')}
                </Button>
              </Stack>
            )}
          </Paper>
        )}
      </Transition>
    </Affix>
  )
}
