import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { Pagination } from '../../components/Pagination'
import type { ImportBatch, PaginationMeta } from '../../types/api'

interface ImportBatchListProps {
  batches: ImportBatch[]
  meta: PaginationMeta
  onPageChange: (page: number) => void
}

export function ImportBatchList({ batches, meta, onPageChange }: ImportBatchListProps) {
  const { t } = useTranslation()

  return (
    <div>
      <div className="overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead>
            <tr>
              <th className="border-b px-3 py-2 font-medium text-gray-700">{t('imports.list.columns.filename')}</th>
              <th className="border-b px-3 py-2 font-medium text-gray-700">{t('imports.list.columns.status')}</th>
              <th className="border-b px-3 py-2 font-medium text-gray-700">{t('imports.list.columns.createdAt')}</th>
              <th className="border-b px-3 py-2 font-medium text-gray-700" />
            </tr>
          </thead>
          <tbody>
            {batches.length === 0 && (
              <tr>
                <td colSpan={4} className="px-3 py-6 text-center text-gray-500">
                  {t('imports.list.empty')}
                </td>
              </tr>
            )}
            {batches.map((batch) => (
              <tr key={batch.id} className="border-b">
                <td className="px-3 py-2">{batch.original_filename}</td>
                <td className="px-3 py-2">{t(`imports.batchStatus.${batch.status}`)}</td>
                <td className="px-3 py-2">{new Date(batch.created_at).toLocaleString('tr-TR')}</td>
                <td className="px-3 py-2 text-right">
                  <Link to={`/imports/${batch.id}`} className="text-gray-700 underline">
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
