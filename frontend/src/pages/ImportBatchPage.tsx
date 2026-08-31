import { useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useParams } from 'react-router'
import { ImportRowsTable } from '../features/imports/ImportRowsTable'
import { RouteMap } from '../features/imports/RouteMap'
import { useImportBatch } from '../features/imports/useImportBatch'
import { useImportBatchRows } from '../features/imports/useImportBatchRows'
import { useImportRowsParams } from '../features/imports/useImportRowsParams'

export function ImportBatchPage() {
  const { t } = useTranslation()
  const params = useParams<{ id: string }>()
  const batchId = Number(params.id)

  const { data: batchResponse, isPending: batchPending, isError: batchError } = useImportBatch(batchId)
  const { status, searchParams, setStatus, setPage } = useImportRowsParams()
  const {
    data: rowsResponse,
    isPending: rowsPending,
    isError: rowsError,
  } = useImportBatchRows(batchId, searchParams, batchResponse?.data.status, status !== '')

  const [selectedRowIds, setSelectedRowIds] = useState<Set<number>>(new Set())

  const handleStatusChange = (value: string) => {
    setSelectedRowIds(new Set())
    setStatus(value)
  }

  const handlePageChange = (page: number) => {
    setSelectedRowIds(new Set())
    setPage(page)
  }

  const handleToggleSelect = (rowId: number) => {
    setSelectedRowIds((prev) => {
      const next = new Set(prev)
      if (next.has(rowId)) {
        next.delete(rowId)
      } else {
        next.add(rowId)
      }
      return next
    })
  }

  const selectedRoutes = useMemo(
    () =>
      (rowsResponse?.data ?? [])
        .filter((row) => selectedRowIds.has(row.id) && row.route !== null && row.start_coordinates !== null && row.end_coordinates !== null)
        .map((row) => ({
          id: row.id,
          label: `${row.plate ?? row.vin ?? ''}`,
          encodedPath: row.route!.polyline,
          start: row.start_coordinates!,
          end: row.end_coordinates!,
        })),
    [rowsResponse, selectedRowIds],
  )

  return (
    <main className="mx-auto max-w-6xl px-4 py-8">
      <Link to="/imports" className="text-sm text-gray-600 underline">
        {t('imports.batch.backToList')}
      </Link>
      <h1 className="mt-2 text-2xl font-semibold text-gray-900">{t('imports.batch.title', { id: batchId })}</h1>

      {batchPending && <p>{t('common.loading')}</p>}
      {batchError && <p>{t('imports.batch.notFound')}</p>}
      {batchResponse && (
        <p className="py-2 text-sm text-gray-700">
          {batchResponse.data.original_filename} — {t(`imports.batchStatus.${batchResponse.data.status}`)}
        </p>
      )}

      {rowsPending && <p>{t('common.loading')}</p>}
      {rowsError && <p>{t('common.error')}</p>}
      {rowsResponse && (
        <ImportRowsTable
          rows={rowsResponse.data}
          meta={rowsResponse.meta}
          onPageChange={handlePageChange}
          status={status}
          onStatusChange={handleStatusChange}
          selectedRowIds={selectedRowIds}
          onToggleSelect={handleToggleSelect}
        />
      )}

      {selectedRoutes.length > 0 && (
        <section className="pt-6">
          <h2 className="pb-2 text-lg font-semibold text-gray-900">
            {t('imports.map.title', { count: selectedRoutes.length })}
          </h2>
          <RouteMap routes={selectedRoutes} />
        </section>
      )}
    </main>
  )
}
