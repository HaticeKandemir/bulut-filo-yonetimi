import { useTranslation } from 'react-i18next'
import { Card } from '../components/Card'
import { PageHeader } from '../components/PageHeader'
import { InstitutionTree } from '../features/institutions/InstitutionTree'
import { useInstitutions } from '../hooks/useInstitutions'

export function InstitutionsPage() {
  const { t } = useTranslation()
  const { data, isPending, isError } = useInstitutions()

  return (
    <main className="mx-auto max-w-3xl px-4 py-8">
      <PageHeader title={t('institutions.title')} />
      {isPending && <p className="text-sm text-gray-500">{t('common.loading')}</p>}
      {isError && <p className="text-sm text-red-600">{t('common.error')}</p>}
      {data && (
        <Card className="p-6">
          {data.length === 0 ? (
            <p className="text-sm text-gray-500">{t('institutions.empty')}</p>
          ) : (
            <InstitutionTree nodes={data} />
          )}
        </Card>
      )}
    </main>
  )
}
