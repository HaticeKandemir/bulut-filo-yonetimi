import { useCallback, useMemo } from 'react'
import { useSearchParams } from 'react-router'
import type { VehicleStatus } from '../../types/api'

export interface VehicleListParams {
  vin: string
  plate: string
  brand: string
  status: VehicleStatus | ''
  institutionId: string
  sort: string
}

export type VehicleFilterKey = `filter[${'vin' | 'plate' | 'brand' | 'status' | 'institution_id'}]`

export function useVehicleListParams() {
  const [searchParams, setSearchParams] = useSearchParams()

  const params = useMemo<VehicleListParams>(
    () => ({
      vin: searchParams.get('filter[vin]') ?? '',
      plate: searchParams.get('filter[plate]') ?? '',
      brand: searchParams.get('filter[brand]') ?? '',
      status: (searchParams.get('filter[status]') ?? '') as VehicleStatus | '',
      institutionId: searchParams.get('filter[institution_id]') ?? '',
      sort: searchParams.get('sort') ?? 'vin',
    }),
    [searchParams],
  )

  const setFilter = useCallback(
    (key: VehicleFilterKey, value: string) => {
      setSearchParams((prev) => {
        const next = new URLSearchParams(prev)
        if (value === '') {
          next.delete(key)
        } else {
          next.set(key, value)
        }
        next.delete('page')
        return next
      })
    },
    [setSearchParams],
  )

  const setSort = useCallback(
    (columnId: string) => {
      setSearchParams((prev) => {
        const next = new URLSearchParams(prev)
        next.set('sort', next.get('sort') === columnId ? `-${columnId}` : columnId)
        next.delete('page')
        return next
      })
    },
    [setSearchParams],
  )

  const setPage = useCallback(
    (page: number) => {
      setSearchParams((prev) => {
        const next = new URLSearchParams(prev)
        next.set('page', String(page))
        return next
      })
    },
    [setSearchParams],
  )

  return { params, searchParams, setFilter, setSort, setPage }
}
