import { useQuery } from '@tanstack/react-query'
import { fetchCurrentUser } from '../../api/auth'
import { useAuth } from './useAuth'

export function useCurrentUser() {
  const { token } = useAuth()

  return useQuery({
    queryKey: ['current-user'],
    queryFn: fetchCurrentUser,
    enabled: token !== null,
  })
}
