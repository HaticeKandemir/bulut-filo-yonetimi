import { useTranslation } from 'react-i18next'
import type { PaginationMeta } from '../types/api'

interface PaginationProps {
  meta: PaginationMeta
  onPageChange: (page: number) => void
}

export function Pagination({ meta, onPageChange }: PaginationProps) {
  const { t } = useTranslation()

  return (
    <nav className="flex items-center justify-between py-3 text-sm text-gray-600" aria-label={t('pagination.next')}>
      <span>{t('pagination.summary', { total: meta.total, from: meta.from ?? 0, to: meta.to ?? 0 })}</span>
      <div className="flex gap-2">
        <button
          type="button"
          disabled={meta.current_page <= 1}
          onClick={() => onPageChange(meta.current_page - 1)}
          className="disabled:opacity-40"
        >
          {t('pagination.previous')}
        </button>
        <span>{t('pagination.pageOf', { current: meta.current_page, last: meta.last_page })}</span>
        <button
          type="button"
          disabled={meta.current_page >= meta.last_page}
          onClick={() => onPageChange(meta.current_page + 1)}
          className="disabled:opacity-40"
        >
          {t('pagination.next')}
        </button>
      </div>
    </nav>
  )
}
