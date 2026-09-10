import { useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useParams } from 'react-router'
import { Card } from '../components/Card'
import { StatusBadge, type BadgeColor } from '../components/StatusBadge'
import type { ImportBatchStatus, ImportRow } from '../types/api'
import { ImportRowsTable } from '../features/imports/ImportRowsTable'
import { RouteMap } from '../features/imports/RouteMap'
import { useImportBatch } from '../features/imports/useImportBatch'
import { useImportBatchRows } from '../features/imports/useImportBatchRows'
import { useImportRowsParams } from '../features/imports/useImportRowsParams'

const STATUS_COLORS: Record<ImportBatchStatus, BadgeColor> = {
  pending: 'gray',
  processing: 'blue',
  completed: 'green',
  failed: 'red',
}

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

  // Keyed by row id, not just a Set of ids: the table only ever renders one
  // page of rows, but a selection must survive navigating to other pages
  // (or changing the status filter) to be compared together on the map, so
  // the row data itself has to be captured at selection time.
  const [selectedRows, setSelectedRows] = useState<Map<number, ImportRow>>(new Map())

  const handleToggleSelect = (row: ImportRow) => {
    setSelectedRows((prev) => {
      const next = new Map(prev)
      if (next.has(row.id)) {
        next.delete(row.id)
      } else {
        next.set(row.id, row)
      }
      return next
    })
  }

  const clearSelection = () => setSelectedRows(new Map())

  const selectedRowIds = useMemo(() => new Set(selectedRows.keys()), [selectedRows])

  const selectedRoutes = useMemo(
    () =>
      Array.from(selectedRows.values())
        .filter((row) => row.route !== null && row.start_coordinates !== null && row.end_coordinates !== null)
        .map((row) => ({
          id: row.id,
          label: `${row.plate ?? row.vin ?? ''}`,
          encodedPath: row.route!.polyline,
          start: row.start_coordinates!,
          end: row.end_coordinates!,
        })),
    [selectedRows],
  )

  return (
    <main className="mx-auto max-w-6xl px-4 py-8">
      <Link to="/imports" className="text-sm font-medium text-blue-600 hover:text-blue-700 hover:underline">
        {t('imports.batch.backToList')}
      </Link>
      <h1 className="mt-2 text-2xl font-semibold tracking-tight text-gray-900">{t('imports.batch.title', { id: batchId })}</h1>

      {batchPending && <p className="mt-4 text-sm text-gray-500">{t('common.loading')}</p>}
      {batchError && <p className="mt-4 text-sm text-red-600">{t('imports.batch.notFound')}</p>}
      {batchResponse && (
        <p className="mt-2 flex items-center gap-2 text-sm text-gray-700">
          <span className="font-medium text-gray-900">{batchResponse.data.original_filename}</span>
          <StatusBadge color={STATUS_COLORS[batchResponse.data.status]}>
            {t(`imports.batchStatus.${batchResponse.data.status}`)}
          </StatusBadge>
        </p>
      )}

      {rowsPending && <p className="mt-6 text-sm text-gray-500">{t('common.loading')}</p>}
      {rowsError && <p className="mt-6 text-sm text-red-600">{t('common.error')}</p>}
      {rowsResponse && (
        <Card className="mt-6 overflow-hidden">
          <ImportRowsTable
            rows={rowsResponse.data}
            meta={rowsResponse.meta}
            onPageChange={setPage}
            status={status}
            onStatusChange={setStatus}
            selectedRowIds={selectedRowIds}
            onToggleSelect={handleToggleSelect}
          />
        </Card>
      )}

      {selectedRoutes.length > 0 && (
        <section className="mt-6">
          <div className="mb-2 flex items-center justify-between gap-4">
            <h2 className="text-lg font-semibold text-gray-900">{t('imports.map.title', { count: selectedRoutes.length })}</h2>
            <button
              type="button"
              onClick={clearSelection}
              className="text-sm font-medium text-blue-600 hover:text-blue-700 hover:underline"
            >
              {t('imports.map.clearSelection')}
            </button>
          </div>
          <Card className="overflow-hidden p-4">
            <RouteMap routes={selectedRoutes} />
          </Card>
        </section>
      )}
    </main>
  )
}
