import { useMutation, useQueryClient } from '@tanstack/react-query'
import { uploadImportBatch } from '../../api/imports'

export function useUploadImportBatch() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: uploadImportBatch,
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['import-batches'] })
    },
  })
}
