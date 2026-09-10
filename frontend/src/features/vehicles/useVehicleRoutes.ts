import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { fetchVehicleRoutes } from '../../api/vehicles'

export function useVehicleRoutes(searchParams: URLSearchParams) {
  return useQuery({
    queryKey: ['vehicle-routes', searchParams.toString()],
    queryFn: () => fetchVehicleRoutes(searchParams),
    placeholderData: keepPreviousData,
  })
}
