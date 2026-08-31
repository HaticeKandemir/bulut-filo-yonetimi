import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { fetchVehicles } from '../../api/vehicles'

export function useVehicles(searchParams: URLSearchParams) {
  return useQuery({
    queryKey: ['vehicles', searchParams.toString()],
    queryFn: () => fetchVehicles(searchParams),
    placeholderData: keepPreviousData,
  })
}
