import { useMutation, useQueryClient } from '@tanstack/react-query'
import type { ApiError } from '../../api/client'
import { uploadImportBatch } from '../../api/imports'
import type { ApiResponse, ImportBatch } from '../../types/api'

export function useUploadImportBatch() {
  const queryClient = useQueryClient()

  return useMutation<ApiResponse<ImportBatch>, ApiError, File>({
    mutationFn: uploadImportBatch,
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['import-batches'] })
    },
  })
}
