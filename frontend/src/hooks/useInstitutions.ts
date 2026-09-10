import { useQuery } from '@tanstack/react-query'
import { fetchInstitutions } from '../api/institutions'

// Institutions are read-only and seeded once in normal operation, but not
// truly immutable (a new institution tree can be seeded into a running app
// — see InstitutionSeeder). staleTime: Infinity would mean a tab open since
// before that never sees it without a hard refresh; 5 minutes matches the
// backend's own cache TTL for vehicle reads (VehicleRepository) so this
// stays cheap without going stale indefinitely.
const STALE_TIME_MS = 5 * 60 * 1000

export function useInstitutions() {
  return useQuery({
    queryKey: ['institutions'],
    queryFn: async () => (await fetchInstitutions()).data,
    staleTime: STALE_TIME_MS,
  })
}
