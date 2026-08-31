import { useTranslation } from 'react-i18next'
import type { PaginationMeta } from '../types/api'

interface PaginationProps {
  meta: PaginationMeta
  onPageChange: (page: number) => void
}

const buttonClassName =
  'rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-white'

export function Pagination({ meta, onPageChange }: PaginationProps) {
  const { t } = useTranslation()

  return (
    <nav
      className="flex flex-col items-center gap-2 border-t border-gray-200 px-4 py-3 text-sm text-gray-600 sm:flex-row sm:justify-between"
      aria-label={t('pagination.next')}
    >
      <span>{t('pagination.summary', { total: meta.total, from: meta.from ?? 0, to: meta.to ?? 0 })}</span>
      <div className="flex items-center gap-3">
        <button
          type="button"
          disabled={meta.current_page <= 1}
          onClick={() => onPageChange(meta.current_page - 1)}
          className={buttonClassName}
        >
          {t('pagination.previous')}
        </button>
        <span>{t('pagination.pageOf', { current: meta.current_page, last: meta.last_page })}</span>
        <button
          type="button"
          disabled={meta.current_page >= meta.last_page}
          onClick={() => onPageChange(meta.current_page + 1)}
          className={buttonClassName}
        >
          {t('pagination.next')}
        </button>
      </div>
    </nav>
  )
}
