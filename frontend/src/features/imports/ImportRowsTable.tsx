import { useTranslation } from 'react-i18next'
import { Pagination } from '../../components/Pagination'
import { StatusBadge, type BadgeColor } from '../../components/StatusBadge'
import type { AddressResolutionStatus, ImportRow, ImportRowStatus, PaginationMeta, RouteComputationStatus } from '../../types/api'

const ROW_STATUS_COLORS: Record<ImportRowStatus, BadgeColor> = {
  pending: 'gray',
  processed: 'green',
  needs_review: 'amber',
  failed: 'red',
}

const ADDRESS_STATUS_COLORS: Record<AddressResolutionStatus, BadgeColor> = {
  pending: 'gray',
  resolved: 'green',
  failed: 'red',
  skipped: 'gray',
}

const ROUTE_STATUS_COLORS: Record<RouteComputationStatus, BadgeColor> = {
  pending: 'gray',
  computed: 'green',
  failed: 'red',
  skipped: 'gray',
}

interface ImportRowsTableProps {
  rows: ImportRow[]
  meta: PaginationMeta
  onPageChange: (page: number) => void
  status: ImportRowStatus | ''
  onStatusChange: (status: string) => void
  selectedRowIds: ReadonlySet<number>
  onToggleSelect: (rowId: number) => void
}

function formatRouteSummary(route: NonNullable<ImportRow['route']>): string {
  const km = (route.distance_meters / 1000).toFixed(1)
  const minutes = Math.round(route.duration_seconds / 60)

  return `${km} km, ${minutes} dk`
}

export function ImportRowsTable({
  rows,
  meta,
  onPageChange,
  status,
  onStatusChange,
  selectedRowIds,
  onToggleSelect,
}: ImportRowsTableProps) {
  const { t } = useTranslation()

  return (
    <div>
      <div className="border-b border-gray-200 p-4">
        <select
          value={status}
          onChange={(event) => onStatusChange(event.target.value)}
          className="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
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
          <thead className="bg-gray-50">
            <tr>
              <th className="px-4 py-3">
                <span className="sr-only">{t('imports.rows.columns.select')}</span>
              </th>
              <th className="px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase">{t('imports.rows.columns.rowNumber')}</th>
              <th className="px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase">{t('imports.rows.columns.vin')}</th>
              <th className="px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase">{t('imports.rows.columns.plate')}</th>
              <th className="px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase">{t('imports.rows.columns.brandModel')}</th>
              <th className="px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase">{t('imports.rows.columns.institution')}</th>
              <th className="px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase">{t('imports.rows.columns.status')}</th>
              <th className="px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase">{t('imports.rows.columns.address')}</th>
              <th className="px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase">{t('imports.rows.columns.route')}</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {rows.length === 0 && (
              <tr>
                <td colSpan={9} className="px-4 py-8 text-center text-gray-500">
                  {t('imports.rows.empty')}
                </td>
              </tr>
            )}
            {rows.map((row) => {
              const hasRoute = row.route_computation_status === 'computed' && row.route !== null

              return (
                <tr key={row.id} className="align-top hover:bg-gray-50">
                  <td className="px-4 py-3">
                    <input
                      type="checkbox"
                      checked={selectedRowIds.has(row.id)}
                      disabled={!hasRoute}
                      onChange={() => onToggleSelect(row.id)}
                      title={hasRoute ? undefined : t('imports.map.noRouteHint')}
                      className="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-40"
                    />
                  </td>
                  <td className="px-4 py-3 text-gray-700">{row.row_number}</td>
                  <td className="px-4 py-3 font-medium text-gray-900">{row.vin}</td>
                  <td className="px-4 py-3 text-gray-700">{row.plate}</td>
                  <td className="px-4 py-3 text-gray-700">
                    {row.brand} {row.model}
                  </td>
                  <td className="px-4 py-3 text-gray-700">{row.institution_code}</td>
                  <td className="px-4 py-3">
                    <StatusBadge color={ROW_STATUS_COLORS[row.status]}>{t(`imports.rowStatus.${row.status}`)}</StatusBadge>
                    {row.status === 'needs_review' && row.conflicting_vehicle_vin !== null && (
                      <div className="mt-1 text-xs text-gray-500">{t('imports.rows.conflictingVin', { vin: row.conflicting_vehicle_vin })}</div>
                    )}
                    {row.status === 'failed' && row.error_message !== null && (
                      <div className="mt-1 text-xs text-gray-500">{row.error_message}</div>
                    )}
                  </td>
                  <td className="px-4 py-3">
                    <StatusBadge color={ADDRESS_STATUS_COLORS[row.address_resolution_status]}>
                      {t(`imports.addressStatus.${row.address_resolution_status}`)}
                    </StatusBadge>
                  </td>
                  <td className="px-4 py-3">
                    {hasRoute && row.route ? (
                      <span className="text-gray-700">{formatRouteSummary(row.route)}</span>
                    ) : (
                      <StatusBadge color={ROUTE_STATUS_COLORS[row.route_computation_status]}>
                        {t(`imports.routeStatus.${row.route_computation_status}`)}
                      </StatusBadge>
                    )}
                  </td>
                </tr>
              )
            })}
          </tbody>
        </table>
      </div>
      <Pagination meta={meta} onPageChange={onPageChange} />
    </div>
  )
}
