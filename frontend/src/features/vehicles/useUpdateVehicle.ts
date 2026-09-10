import { useMutation, useQueryClient } from '@tanstack/react-query'
import type { ApiError } from '../../api/client'
import { updateVehicle, type UpdateVehiclePayload } from '../../api/vehicles'
import type { ApiResponse, Vehicle } from '../../types/api'

export function useUpdateVehicle(id: number) {
  const queryClient = useQueryClient()

  return useMutation<ApiResponse<Vehicle>, ApiError, UpdateVehiclePayload>({
    mutationFn: (data) => updateVehicle(id, data),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['vehicle', id] })
      void queryClient.invalidateQueries({ queryKey: ['vehicles'] })
    },
  })
}
