import { useCallback, useMemo } from 'react'
import { useSearchParams } from 'react-router'

export interface VehicleRoutesParams {
  vin: string
  plate: string
  brand: string
  institutionId: string
  sort: string
}

export type VehicleRoutesFilterKey = `filter[${'vin' | 'plate' | 'brand' | 'institution_id'}]`

export function useVehicleRoutesParams() {
  const [searchParams, setSearchParams] = useSearchParams()

  const params = useMemo<VehicleRoutesParams>(
    () => ({
      vin: searchParams.get('filter[vin]') ?? '',
      plate: searchParams.get('filter[plate]') ?? '',
      brand: searchParams.get('filter[brand]') ?? '',
      institutionId: searchParams.get('filter[institution_id]') ?? '',
      sort: searchParams.get('sort') ?? 'vin',
    }),
    [searchParams],
  )

  const setFilter = useCallback(
    (key: VehicleRoutesFilterKey, value: string) => {
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
