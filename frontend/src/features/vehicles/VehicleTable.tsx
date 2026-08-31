import { createColumnHelper, flexRender, tableFeatures, useTable } from '@tanstack/react-table'
import { useMemo } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { Pagination } from '../../components/Pagination'
import { StatusBadge, type BadgeColor } from '../../components/StatusBadge'
import type { PaginationMeta, Vehicle, VehicleStatus } from '../../types/api'

const features = tableFeatures({})
const columnHelper = createColumnHelper<typeof features, Vehicle>()

const SORTABLE_COLUMNS = new Set(['vin', 'brand', 'model', 'status'])

const STATUS_COLORS: Record<VehicleStatus, BadgeColor> = {
  active: 'green',
  passive: 'gray',
  left_fleet: 'red',
}

interface VehicleTableProps {
  vehicles: Vehicle[]
  sort: string
  onSortChange: (columnId: string) => void
  meta: PaginationMeta
  onPageChange: (page: number) => void
}

export function VehicleTable({ vehicles, sort, onSortChange, meta, onPageChange }: VehicleTableProps) {
  const { t } = useTranslation()

  const columns = useMemo(
    () =>
      columnHelper.columns([
        columnHelper.accessor('vin', { header: t('vehicles.columns.vin'), cell: (info) => info.getValue() }),
        columnHelper.accessor('brand', { header: t('vehicles.columns.brand'), cell: (info) => info.getValue() }),
        columnHelper.accessor('model', { header: t('vehicles.columns.model'), cell: (info) => info.getValue() }),
        columnHelper.accessor((vehicle) => vehicle.active_plate?.plate ?? '—', {
          id: 'plate',
          header: t('vehicles.columns.plate'),
          cell: (info) => info.getValue(),
        }),
        columnHelper.accessor((vehicle) => vehicle.institution.name, {
          id: 'institution',
          header: t('vehicles.columns.institution'),
          cell: (info) => info.getValue(),
        }),
        columnHelper.accessor('status', {
          header: t('vehicles.columns.status'),
          cell: (info) => <StatusBadge color={STATUS_COLORS[info.getValue()]}>{t(`vehicles.status.${info.getValue()}`)}</StatusBadge>,
        }),
        columnHelper.display({
          id: 'detail',
          header: t('vehicles.columns.detail'),
          cell: ({ row }) => (
            <Link to={`/vehicles/${row.original.id}`} className="text-sm font-medium text-blue-600 hover:text-blue-700 hover:underline">
              {t('vehicles.columns.detail')}
            </Link>
          ),
        }),
      ]),
    [t],
  )

  const table = useTable({ features, columns, data: vehicles })

  return (
    <div>
      <div className="overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead className="bg-gray-50">
            {table.getHeaderGroups().map((headerGroup) => (
              <tr key={headerGroup.id}>
                {headerGroup.headers.map((header) => {
                  const columnId = header.column.id
                  const isSortable = SORTABLE_COLUMNS.has(columnId)
                  const isActive = sort === columnId || sort === `-${columnId}`

                  return (
                    <th key={header.id} className="px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                      {isSortable ? (
                        <button
                          type="button"
                          onClick={() => onSortChange(columnId)}
                          className="flex items-center gap-1 uppercase hover:text-gray-800"
                        >
                          {flexRender(header.column.columnDef.header, header.getContext())}
                          {isActive && <span aria-hidden="true">{sort.startsWith('-') ? '↓' : '↑'}</span>}
                        </button>
                      ) : (
                        flexRender(header.column.columnDef.header, header.getContext())
                      )}
                    </th>
                  )
                })}
              </tr>
            ))}
          </thead>
          <tbody className="divide-y divide-gray-100">
            {table.getRowModel().rows.length === 0 && (
              <tr>
                <td colSpan={columns.length} className="px-4 py-8 text-center text-gray-500">
                  {t('vehicles.empty')}
                </td>
              </tr>
            )}
            {table.getRowModel().rows.map((row) => (
              <tr key={row.id} className="hover:bg-gray-50">
                {row.getAllCells().map((cell) => (
                  <td key={cell.id} className="px-4 py-3 text-gray-700">
                    {flexRender(cell.column.columnDef.cell, cell.getContext())}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <Pagination meta={meta} onPageChange={onPageChange} />
    </div>
  )
}
