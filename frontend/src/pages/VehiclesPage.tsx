import { useTranslation } from 'react-i18next'
import { Card } from '../components/Card'
import { PageHeader } from '../components/PageHeader'
import { VehicleFilters } from '../features/vehicles/VehicleFilters'
import { VehicleTable } from '../features/vehicles/VehicleTable'
import { useVehicleListParams } from '../features/vehicles/useVehicleListParams'
import { useVehicles } from '../features/vehicles/useVehicles'
import { useInstitutions } from '../hooks/useInstitutions'

export function VehiclesPage() {
  const { t } = useTranslation()
  const { params, searchParams, setFilter, setSort, setPage } = useVehicleListParams()
  const { data: institutions } = useInstitutions()
  const { data, isPending, isError } = useVehicles(searchParams)

  return (
    <main className="mx-auto max-w-6xl px-4 py-8">
      <PageHeader title={t('vehicles.title')} />
      <VehicleFilters params={params} institutions={institutions ?? []} onFilterChange={setFilter} />
      {isPending && <p className="py-6 text-sm text-gray-500">{t('common.loading')}</p>}
      {isError && <p className="py-6 text-sm text-red-600">{t('common.error')}</p>}
      {data && (
        <Card className="mt-4 overflow-hidden">
          <VehicleTable
            vehicles={data.data}
            sort={params.sort}
            onSortChange={setSort}
            meta={data.meta}
            onPageChange={setPage}
          />
        </Card>
      )}
    </main>
  )
}
