import type { PropsWithChildren } from 'react'

interface CardProps {
  className?: string
}

export function Card({ children, className = '' }: PropsWithChildren<CardProps>) {
  return <div className={`rounded-lg border border-gray-200 bg-white shadow-sm ${className}`}>{children}</div>
}
