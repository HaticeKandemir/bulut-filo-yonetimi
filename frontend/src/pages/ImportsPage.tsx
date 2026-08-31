import { useTranslation } from 'react-i18next'
import { useNavigate, useSearchParams } from 'react-router'
import { Card } from '../components/Card'
import { PageHeader } from '../components/PageHeader'
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
      <PageHeader title={t('imports.title')} />

      <Card className="p-6">
        <UploadImportForm onUploaded={(batchId) => void navigate(`/imports/${batchId}`)} />
      </Card>

      {isPending && <p className="py-6 text-sm text-gray-500">{t('common.loading')}</p>}
      {isError && <p className="py-6 text-sm text-red-600">{t('common.error')}</p>}
      {data && (
        <Card className="mt-6 overflow-hidden">
          <ImportBatchList batches={data.data} meta={data.meta} onPageChange={handlePageChange} />
        </Card>
      )}
    </main>
  )
}
