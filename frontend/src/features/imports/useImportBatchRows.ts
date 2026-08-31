import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { fetchImportBatchRows } from '../../api/imports'
import type { ImportBatchStatus, ImportRow } from '../../types/api'

const ACTIVE_BATCH_STATUSES = new Set(['pending', 'processing'])
const MAX_EMPTY_POLLS = 20

function hasPendingWork(rows: ImportRow[]): boolean {
  return rows.some(
    (row) => row.status === 'pending' || row.address_resolution_status === 'pending' || row.route_computation_status === 'pending',
  )
}

/**
 * ProcessVehicleImportJob flips the batch to "completed" as soon as the
 * VIN/plate decision tree finishes for every row — it does not wait for the
 * ResolveImportRowAddressesJob/ComputeImportRowRouteJob chain it dispatches
 * per row. So a completed batch can still have rows mid-flight (or, for a
 * very fast batch, not yet seeded at all by the time this first fetches).
 * Polling has to key off the rows themselves, not just the batch status.
 */
export function useImportBatchRows(
  batchId: number,
  searchParams: URLSearchParams,
  batchStatus: ImportBatchStatus | undefined,
  hasStatusFilter: boolean,
) {
  return useQuery({
    queryKey: ['import-batch-rows', batchId, searchParams.toString()],
    queryFn: () => fetchImportBatchRows(batchId, searchParams),
    placeholderData: keepPreviousData,
    refetchInterval: (query) => {
      if (batchStatus === undefined || ACTIVE_BATCH_STATUSES.has(batchStatus)) {
        return 2000
      }

      const rows = query.state.data?.data ?? []

      if (rows.length === 0) {
        return !hasStatusFilter && query.state.dataUpdateCount < MAX_EMPTY_POLLS ? 2000 : false
      }

      return hasPendingWork(rows) ? 2000 : false
    },
  })
}
