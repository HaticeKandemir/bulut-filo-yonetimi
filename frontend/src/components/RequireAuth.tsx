import type { PropsWithChildren } from 'react'
import { Navigate } from 'react-router'
import { useAuth } from '../features/auth/useAuth'

export function RequireAuth({ children }: PropsWithChildren) {
  const { token } = useAuth()

  if (token === null) {
    return <Navigate to="/login" replace />
  }

  return children
}
