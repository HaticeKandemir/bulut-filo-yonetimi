import { useTranslation } from 'react-i18next'
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
      <h1 className="text-2xl font-semibold text-gray-900">{t('vehicles.title')}</h1>
      <VehicleFilters params={params} institutions={institutions ?? []} onFilterChange={setFilter} />
      {isPending && <p>{t('common.loading')}</p>}
      {isError && <p>{t('common.error')}</p>}
      {data && (
        <VehicleTable
          vehicles={data.data}
          sort={params.sort}
          onSortChange={setSort}
          meta={data.meta}
          onPageChange={setPage}
        />
      )}
    </main>
  )
}
