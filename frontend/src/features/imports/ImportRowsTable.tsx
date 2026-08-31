import { useTranslation } from 'react-i18next'
import { Pagination } from '../../components/Pagination'
import type { ImportRow, ImportRowStatus, PaginationMeta } from '../../types/api'

const ROW_STATUS_CLASSES: Record<ImportRowStatus, string> = {
  pending: 'text-gray-500',
  processed: 'text-green-700',
  needs_review: 'text-amber-700',
  failed: 'text-red-700',
}

interface ImportRowsTableProps {
  rows: ImportRow[]
  meta: PaginationMeta
  onPageChange: (page: number) => void
  status: ImportRowStatus | ''
  onStatusChange: (status: string) => void
}

function formatRoute(row: ImportRow, t: (key: string) => string): string {
  if (row.route_computation_status !== 'computed' || row.route === null) {
    return t(`imports.routeStatus.${row.route_computation_status}`)
  }

  const km = (row.route.distance_meters / 1000).toFixed(1)
  const minutes = Math.round(row.route.duration_seconds / 60)

  return `${km} km, ${minutes} dk`
}

export function ImportRowsTable({ rows, meta, onPageChange, status, onStatusChange }: ImportRowsTableProps) {
  const { t } = useTranslation()

  return (
    <div>
      <div className="py-4">
        <select
          value={status}
          onChange={(event) => onStatusChange(event.target.value)}
          className="rounded border border-gray-300 px-3 py-1.5 text-sm"
        >
          <option value="">{t('imports.rows.filters.allStatuses')}</option>
          <option value="pending">{t('imports.rowStatus.pending')}</option>
          <option value="processed">{t('imports.rowStatus.processed')}</option>
          <option value="needs_review">{t('imports.rowStatus.needs_review')}</option>
          <option value="failed">{t('imports.rowStatus.failed')}</option>
        </select>
      </div>
      <div className="overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead>
            <tr>
              <th className="border-b px-3 py-2 font-medium text-gray-700">{t('imports.rows.columns.rowNumber')}</th>
              <th className="border-b px-3 py-2 font-medium text-gray-700">{t('imports.rows.columns.vin')}</th>
              <th className="border-b px-3 py-2 font-medium text-gray-700">{t('imports.rows.columns.plate')}</th>
              <th className="border-b px-3 py-2 font-medium text-gray-700">{t('imports.rows.columns.brandModel')}</th>
              <th className="border-b px-3 py-2 font-medium text-gray-700">{t('imports.rows.columns.institution')}</th>
              <th className="border-b px-3 py-2 font-medium text-gray-700">{t('imports.rows.columns.status')}</th>
              <th className="border-b px-3 py-2 font-medium text-gray-700">{t('imports.rows.columns.address')}</th>
              <th className="border-b px-3 py-2 font-medium text-gray-700">{t('imports.rows.columns.route')}</th>
            </tr>
          </thead>
          <tbody>
            {rows.length === 0 && (
              <tr>
                <td colSpan={8} className="px-3 py-6 text-center text-gray-500">
                  {t('imports.rows.empty')}
                </td>
              </tr>
            )}
            {rows.map((row) => (
              <tr key={row.id} className="border-b align-top">
                <td className="px-3 py-2">{row.row_number}</td>
                <td className="px-3 py-2">{row.vin}</td>
                <td className="px-3 py-2">{row.plate}</td>
                <td className="px-3 py-2">
                  {row.brand} {row.model}
                </td>
                <td className="px-3 py-2">{row.institution_code}</td>
                <td className={`px-3 py-2 font-medium ${ROW_STATUS_CLASSES[row.status]}`}>
                  <div>{t(`imports.rowStatus.${row.status}`)}</div>
                  {row.status === 'needs_review' && row.conflicting_vehicle_vin !== null && (
                    <div className="text-xs font-normal text-gray-500">
                      {t('imports.rows.conflictingVin', { vin: row.conflicting_vehicle_vin })}
                    </div>
                  )}
                  {row.status === 'failed' && row.error_message !== null && (
                    <div className="text-xs font-normal text-gray-500">{row.error_message}</div>
                  )}
                </td>
                <td className="px-3 py-2 text-gray-700">{t(`imports.addressStatus.${row.address_resolution_status}`)}</td>
                <td className="px-3 py-2 text-gray-700">{formatRoute(row, t)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <Pagination meta={meta} onPageChange={onPageChange} />
    </div>
  )
}
