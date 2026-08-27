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
  { key: 'categories.icon.pets', className: 'fa-solid fa-paw' },
  { key: 'categories.icon.utilities', className: 'fa-solid fa-bolt' },
  { key: 'categories.icon.subscriptions', className: 'fa-solid fa-rotate' },
  { key: 'categories.icon.groceries', className: 'fa-solid fa-cart-shopping' },
  { key: 'categories.icon.gifts', className: 'fa-solid fa-gift' },
  { key: 'categories.icon.insurance', className: 'fa-solid fa-shield-halved' },
  { key: 'categories.icon.taxes', className: 'fa-solid fa-file-invoice-dollar' },
  { key: 'categories.icon.investments', className: 'fa-solid fa-chart-line' },
  { key: 'categories.icon.family', className: 'fa-solid fa-baby' },
  { key: 'categories.icon.personal_care', className: 'fa-solid fa-spa' },
  { key: 'categories.icon.donations', className: 'fa-solid fa-hand-holding-heart' },
  { key: 'categories.icon.fuel', className: 'fa-solid fa-gas-pump' },
]

const iconClassSet = new Set(CATEGORY_ICON_OPTIONS.map((option) => option.className))

export function getCategoryIconClass(icon?: string | null): string {
  if (icon && iconClassSet.has(icon)) {
    return icon
  }

  return DEFAULT_CATEGORY_ICON
}
