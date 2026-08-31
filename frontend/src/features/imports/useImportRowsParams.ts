import { useCallback, useMemo } from 'react'
import { useSearchParams } from 'react-router'
import type { ImportRowStatus } from '../../types/api'

export function useImportRowsParams() {
  const [searchParams, setSearchParams] = useSearchParams()

  const status = useMemo<ImportRowStatus | ''>(() => (searchParams.get('status') ?? '') as ImportRowStatus | '', [searchParams])

  const setStatus = useCallback(
    (value: string) => {
      setSearchParams((prev) => {
        const next = new URLSearchParams(prev)
        if (value === '') {
          next.delete('status')
        } else {
          next.set('status', value)
        }
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

  return { status, searchParams, setStatus, setPage }
}
