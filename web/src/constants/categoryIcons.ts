export interface CategoryIconOption {
  key: string
  className: string
}

export const DEFAULT_CATEGORY_ICON = 'fa-solid fa-tag'

export const CATEGORY_ICON_OPTIONS: CategoryIconOption[] = [
  { key: 'categories.icon.tag', className: 'fa-solid fa-tag' },
  { key: 'categories.icon.wallet', className: 'fa-solid fa-wallet' },
  { key: 'categories.icon.food', className: 'fa-solid fa-utensils' },
  { key: 'categories.icon.home', className: 'fa-solid fa-house' },
  { key: 'categories.icon.transport', className: 'fa-solid fa-car-side' },
  { key: 'categories.icon.health', className: 'fa-solid fa-heart-pulse' },
  { key: 'categories.icon.education', className: 'fa-solid fa-graduation-cap' },
  { key: 'categories.icon.shopping', className: 'fa-solid fa-bag-shopping' },
  { key: 'categories.icon.salary', className: 'fa-solid fa-hand-holding-dollar' },
  { key: 'categories.icon.work', className: 'fa-solid fa-briefcase' },
  { key: 'categories.icon.leisure', className: 'fa-solid fa-film' },
  { key: 'categories.icon.travel', className: 'fa-solid fa-plane' },
]

const iconClassSet = new Set(CATEGORY_ICON_OPTIONS.map((option) => option.className))

export function getCategoryIconClass(icon?: string | null): string {
  if (icon && iconClassSet.has(icon)) {
    return icon
  }

  return DEFAULT_CATEGORY_ICON
}
