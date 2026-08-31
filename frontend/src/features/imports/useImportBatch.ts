import { useQuery } from '@tanstack/react-query'
import { fetchImportBatch } from '../../api/imports'

const ACTIVE_BATCH_STATUSES = new Set(['pending', 'processing'])

export function useImportBatch(id: number) {
  return useQuery({
    queryKey: ['import-batch', id],
    queryFn: () => fetchImportBatch(id),
    refetchInterval: (query) => (ACTIVE_BATCH_STATUSES.has(query.state.data?.data.status ?? '') ? 2000 : false),
  })
}
