import { createColumnHelper, flexRender, tableFeatures, useTable } from '@tanstack/react-table'
import { useMemo } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { Pagination } from '../../components/Pagination'
import type { PaginationMeta, Vehicle } from '../../types/api'

const features = tableFeatures({})
const columnHelper = createColumnHelper<typeof features, Vehicle>()

const SORTABLE_COLUMNS = new Set(['vin', 'brand', 'model', 'status'])

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
    () => columnHelper.columns([
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
        cell: (info) => t(`vehicles.status.${info.getValue()}`),
      }),
      columnHelper.display({
        id: 'detail',
        header: t('vehicles.columns.detail'),
        cell: ({ row }) => (
          <Link to={`/vehicles/${row.original.id}`} className="text-gray-700 underline">
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
          <thead>
            {table.getHeaderGroups().map((headerGroup) => (
              <tr key={headerGroup.id}>
                {headerGroup.headers.map((header) => {
                  const columnId = header.column.id
                  const isSortable = SORTABLE_COLUMNS.has(columnId)
                  const isActive = sort === columnId || sort === `-${columnId}`

                  return (
                    <th key={header.id} className="border-b px-3 py-2 font-medium text-gray-700">
                      {isSortable ? (
                        <button
                          type="button"
                          onClick={() => onSortChange(columnId)}
                          className="flex items-center gap-1"
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
          <tbody>
            {table.getRowModel().rows.length === 0 && (
              <tr>
                <td colSpan={columns.length} className="px-3 py-6 text-center text-gray-500">
                  {t('vehicles.empty')}
                </td>
              </tr>
            )}
            {table.getRowModel().rows.map((row) => (
              <tr key={row.id} className="border-b">
                {row.getAllCells().map((cell) => (
                  <td key={cell.id} className="px-3 py-2">
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
