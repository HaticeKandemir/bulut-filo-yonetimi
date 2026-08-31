import { useTranslation } from 'react-i18next'
import { useNavigate, useSearchParams } from 'react-router'
import { ImportBatchList } from '../features/imports/ImportBatchList'
import { UploadImportForm } from '../features/imports/UploadImportForm'
import { useImportBatches } from '../features/imports/useImportBatches'

export function ImportsPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [searchParams, setSearchParams] = useSearchParams()
  const { data, isPending, isError } = useImportBatches(searchParams)

  const handlePageChange = (page: number) => {
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev)
      next.set('page', String(page))
      return next
    })
  }

  return (
    <main className="mx-auto max-w-6xl px-4 py-8">
      <h1 className="text-2xl font-semibold text-gray-900">{t('imports.title')}</h1>
      <div className="py-4">
        <UploadImportForm onUploaded={(batchId) => void navigate(`/imports/${batchId}`)} />
      </div>
      {isPending && <p>{t('common.loading')}</p>}
      {isError && <p>{t('common.error')}</p>}
      {data && <ImportBatchList batches={data.data} meta={data.meta} onPageChange={handlePageChange} />}
    </main>
  )
}
