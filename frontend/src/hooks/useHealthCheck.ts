import { useQuery } from '@tanstack/react-query'
import { checkBackendHealth } from '../api/client'

export function useHealthCheck() {
  return useQuery({
    queryKey: ['backend-health'],
    queryFn: checkBackendHealth,
  })
}
