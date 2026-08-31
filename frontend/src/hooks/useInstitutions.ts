import { useQuery } from '@tanstack/react-query'
import { fetchInstitutions } from '../api/institutions'

export function useInstitutions() {
  return useQuery({
    queryKey: ['institutions'],
    queryFn: async () => (await fetchInstitutions()).data,
    staleTime: Infinity,
  })
}
