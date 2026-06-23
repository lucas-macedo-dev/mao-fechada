import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import { api } from '../services/api'
import { TUTORIAL_STEPS } from '../tutorial/steps'
import type { TutorialStep, TutorialStepId } from '../tutorial/steps'
import type { User } from '../types/api'

interface TutorialContextValue {
  steps: Array<TutorialStep & { completed: boolean }>
  activeStep: TutorialStep | null
  isDismissed: boolean
  isAllDone: boolean
  checklistOpen: boolean
  openChecklist: () => void
  closeChecklist: () => void
  completeStep: (id: TutorialStepId) => void
  dismiss: () => void
  restart: () => void
}

const TutorialContext = createContext<TutorialContextValue | null>(null)

export function TutorialProvider({ children }: Readonly<{ children: React.ReactNode }>) {
  const queryClient = useQueryClient()
  const user = queryClient.getQueryData<User>(['auth', 'me'])

  const progress = user?.tutorial_progress
  const completedSteps: string[] = progress?.completed_steps ?? []
  const isDismissed = progress?.dismissed ?? false

  const [checklistOpen, setChecklistOpen] = useState(false)
  const autoShownRef = useRef(false)

  const steps = useMemo(
    () => TUTORIAL_STEPS.map((s) => ({ ...s, completed: completedSteps.includes(s.id) })),
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [completedSteps.join(',')],
  )

  const isAllDone = steps.every((s) => s.completed)
  const activeStep = steps.find((s) => !s.completed) ?? null

  useEffect(() => {
    if (autoShownRef.current || user === undefined || isDismissed || isAllDone) return
    setChecklistOpen(true)
    autoShownRef.current = true
  }, [user, isDismissed, isAllDone])

  const updateProgress = useCallback(
    async (payload: { step_id?: string; completed?: boolean; dismissed?: boolean }) => {
      await api.updateTutorialProgress(payload)
      await queryClient.invalidateQueries({ queryKey: ['auth', 'me'] })
    },
    [queryClient],
  )

  const completeStep = useCallback(
    (id: TutorialStepId) => {
      if (completedSteps.includes(id)) return
      void updateProgress({ step_id: id, completed: true })
    },
    [completedSteps, updateProgress],
  )

  const dismiss = useCallback(() => {
    setChecklistOpen(false)
    void updateProgress({ dismissed: true })
  }, [updateProgress])

  const restart = useCallback(() => {
    autoShownRef.current = false
    void api.updateTutorialProgress({ reset: true }).then(async () => {
      await queryClient.invalidateQueries({ queryKey: ['auth', 'me'] })
      setChecklistOpen(true)
    })
  }, [queryClient])

  const value = useMemo<TutorialContextValue>(
    () => ({
      steps,
      activeStep,
      isDismissed,
      isAllDone,
      checklistOpen,
      openChecklist: () => setChecklistOpen(true),
      closeChecklist: () => setChecklistOpen(false),
      completeStep,
      dismiss,
      restart,
    }),
    [steps, activeStep, isDismissed, isAllDone, checklistOpen, completeStep, dismiss, restart],
  )

  return <TutorialContext.Provider value={value}>{children}</TutorialContext.Provider>
}

export function useTutorial() {
  const ctx = useContext(TutorialContext)
  if (!ctx) throw new Error('useTutorial must be used within TutorialProvider')
  return ctx
}
