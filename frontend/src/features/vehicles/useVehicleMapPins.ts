import { useQuery } from '@tanstack/react-query'
import { fetchVehicleMapPins } from '../../api/vehicles'

export function useVehicleMapPins() {
  return useQuery({
    queryKey: ['vehicle-map-pins'],
    queryFn: fetchVehicleMapPins,
  })
}
