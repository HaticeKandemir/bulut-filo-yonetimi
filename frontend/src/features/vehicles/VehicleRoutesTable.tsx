import { useTranslation } from 'react-i18next'
import { Pagination } from '../../components/Pagination'
import { ScrollHint } from '../../components/ScrollHint'
import type { PaginationMeta, VehicleRoute } from '../../types/api'
import { formatRouteSummary } from '../../utils/route'

const SORTABLE_COLUMNS = new Set(['vin', 'brand', 'model'])

interface VehicleRoutesTableProps {
  routes: VehicleRoute[]
  meta: PaginationMeta
  sort: string
  onSortChange: (columnId: string) => void
  onPageChange: (page: number) => void
  selectedIds: ReadonlySet<number>
  onToggleSelect: (vehicle: VehicleRoute) => void
}

export function VehicleRoutesTable({
  routes,
  meta,
  sort,
  onSortChange,
  onPageChange,
  selectedIds,
  onToggleSelect,
}: VehicleRoutesTableProps) {
  const { t } = useTranslation()

  const columns = [
    { id: 'vin', label: t('vehicles.columns.vin') },
    { id: 'plate', label: t('vehicles.columns.plate') },
    { id: 'brand', label: t('routes.columns.brandModel') },
    { id: 'institution', label: t('vehicles.columns.institution') },
    { id: 'distance', label: t('routes.columns.distance') },
  ]

  return (
    <div>
      <ScrollHint />
      <div className="overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-4 py-3">
                <span className="sr-only">{t('routes.columns.select')}</span>
              </th>
              {columns.map((column) => {
                const isSortable = SORTABLE_COLUMNS.has(column.id)
                const isActive = sort === column.id || sort === `-${column.id}`

                return (
                  <th key={column.id} className="px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                    {isSortable ? (
                      <button
                        type="button"
                        onClick={() => onSortChange(column.id)}
                        className="flex items-center gap-1 uppercase hover:text-gray-800"
                      >
                        {column.label}
                        {isActive && <span aria-hidden="true">{sort.startsWith('-') ? '↓' : '↑'}</span>}
                      </button>
                    ) : (
                      column.label
                    )}
                  </th>
                )
              })}
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {routes.length === 0 && (
              <tr>
                <td colSpan={columns.length + 1} className="px-4 py-8 text-center text-gray-500">
                  {t('routes.empty')}
                </td>
              </tr>
            )}
            {routes.map((vehicle) => (
              <tr key={vehicle.id} className="hover:bg-gray-50">
                <td className="px-4 py-3">
                  <input
                    type="checkbox"
                    checked={selectedIds.has(vehicle.id)}
                    onChange={() => onToggleSelect(vehicle)}
                    className="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  />
                </td>
                <td className="px-4 py-3 font-medium text-gray-900">{vehicle.vin}</td>
                <td className="px-4 py-3 text-gray-700">{vehicle.plate ?? '—'}</td>
                <td className="px-4 py-3 text-gray-700">
                  {vehicle.brand} {vehicle.model}
                </td>
                <td className="px-4 py-3 text-gray-700">{vehicle.institution.name}</td>
                <td className="px-4 py-3 text-gray-700">{formatRouteSummary(vehicle.route)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <Pagination meta={meta} onPageChange={onPageChange} />
    </div>
  )
}
