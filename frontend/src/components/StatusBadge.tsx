import type { ReactNode } from 'react'

export type BadgeColor = 'gray' | 'blue' | 'green' | 'amber' | 'red'

const COLOR_CLASSES: Record<BadgeColor, string> = {
  gray: 'bg-gray-100 text-gray-700',
  blue: 'bg-blue-50 text-blue-700',
  green: 'bg-green-50 text-green-700',
  amber: 'bg-amber-50 text-amber-800',
  red: 'bg-red-50 text-red-700',
}

interface StatusBadgeProps {
  color: BadgeColor
  children: ReactNode
}

export function StatusBadge({ color, children }: StatusBadgeProps) {
  return (
    <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap ${COLOR_CLASSES[color]}`}>
      {children}
    </span>
  )
}
