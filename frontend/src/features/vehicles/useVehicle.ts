import { useQuery } from '@tanstack/react-query'
import { fetchVehicle } from '../../api/vehicles'

export function useVehicle(id: number) {
  return useQuery({
    queryKey: ['vehicle', id],
    queryFn: () => fetchVehicle(id),
  })
}
