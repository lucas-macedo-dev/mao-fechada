export type TutorialStepId =
  | 'create-category'
  | 'record-transaction'
  | 'view-summary'

export interface TutorialStep {
  id: TutorialStepId
  titleKey: string
  descriptionKey: string
  page: string
  path: string
}

export const TUTORIAL_STEPS: TutorialStep[] = [
  {
    id: 'create-category',
    titleKey: 'tutorial.steps.create_category.title',
    descriptionKey: 'tutorial.steps.create_category.description',
    page: 'Categories',
    path: '/categories',
  },
  {
    id: 'record-transaction',
    titleKey: 'tutorial.steps.record_transaction.title',
    descriptionKey: 'tutorial.steps.record_transaction.description',
    page: 'Transactions',
    path: '/transactions',
  },
  {
    id: 'view-summary',
    titleKey: 'tutorial.steps.view_summary.title',
    descriptionKey: 'tutorial.steps.view_summary.description',
    page: 'Home',
    path: '/home',
  },
]
