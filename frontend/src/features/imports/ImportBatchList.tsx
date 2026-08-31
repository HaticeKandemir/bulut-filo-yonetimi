import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { Pagination } from '../../components/Pagination'
import { ScrollHint } from '../../components/ScrollHint'
import { StatusBadge, type BadgeColor } from '../../components/StatusBadge'
import type { ImportBatch, ImportBatchStatus, PaginationMeta } from '../../types/api'

const STATUS_COLORS: Record<ImportBatchStatus, BadgeColor> = {
  pending: 'gray',
  processing: 'blue',
  completed: 'green',
  failed: 'red',
}

interface ImportBatchListProps {
  batches: ImportBatch[]
  meta: PaginationMeta
  onPageChange: (page: number) => void
}

export function ImportBatchList({ batches, meta, onPageChange }: ImportBatchListProps) {
  const { t } = useTranslation()

  return (
    <div>
      <ScrollHint />
      <div className="overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                {t('imports.list.columns.filename')}
              </th>
              <th className="px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                {t('imports.list.columns.status')}
              </th>
              <th className="px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                {t('imports.list.columns.createdAt')}
              </th>
              <th className="px-4 py-3" />
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {batches.length === 0 && (
              <tr>
                <td colSpan={4} className="px-4 py-8 text-center text-gray-500">
                  {t('imports.list.empty')}
                </td>
              </tr>
            )}
            {batches.map((batch) => (
              <tr key={batch.id} className="hover:bg-gray-50">
                <td className="px-4 py-3 font-medium text-gray-900">{batch.original_filename}</td>
                <td className="px-4 py-3">
                  <StatusBadge color={STATUS_COLORS[batch.status]}>{t(`imports.batchStatus.${batch.status}`)}</StatusBadge>
                </td>
                <td className="px-4 py-3 text-gray-700">{new Date(batch.created_at).toLocaleString('tr-TR')}</td>
                <td className="px-4 py-3 text-right">
                  <Link to={`/imports/${batch.id}`} className="text-sm font-medium text-blue-600 hover:text-blue-700 hover:underline">
                    {t('imports.list.view')}
                  </Link>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <Pagination meta={meta} onPageChange={onPageChange} />
    </div>
  )
}
