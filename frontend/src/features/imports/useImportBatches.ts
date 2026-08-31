import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { fetchImportBatches } from '../../api/imports'

export function useImportBatches(searchParams: URLSearchParams) {
  return useQuery({
    queryKey: ['import-batches', searchParams.toString()],
    queryFn: () => fetchImportBatches(searchParams),
    placeholderData: keepPreviousData,
  })
}
