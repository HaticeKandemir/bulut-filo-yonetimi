import { useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Card } from '../components/Card'
import { PageHeader } from '../components/PageHeader'
import { RouteMap } from '../features/imports/RouteMap'
import { VehicleRoutesFilters } from '../features/vehicles/VehicleRoutesFilters'
import { VehicleRoutesTable } from '../features/vehicles/VehicleRoutesTable'
import { useVehicleRoutes } from '../features/vehicles/useVehicleRoutes'
import { useVehicleRoutesParams } from '../features/vehicles/useVehicleRoutesParams'
import { useInstitutions } from '../hooks/useInstitutions'
import type { VehicleRoute } from '../types/api'

export function RoutesPage() {
  const { t } = useTranslation()
  const { params, searchParams, setFilter, setSort, setPage } = useVehicleRoutesParams()
  const { data: institutions } = useInstitutions()
  const { data, isPending, isError } = useVehicleRoutes(searchParams)

  // Keyed by vehicle id, not just a Set of ids: the table only ever renders
  // one page of vehicles, but a selection must survive navigating to other
  // pages (or changing filters) to be compared together on the map, so the
  // route data itself has to be captured at selection time rather than
  // re-derived from the current page's rows.
  const [selectedVehicles, setSelectedVehicles] = useState<Map<number, VehicleRoute>>(new Map())

  const handleToggleSelect = (vehicle: VehicleRoute) => {
    setSelectedVehicles((prev) => {
      const next = new Map(prev)
      if (next.has(vehicle.id)) {
        next.delete(vehicle.id)
      } else {
        next.set(vehicle.id, vehicle)
      }
      return next
    })
  }

  const clearSelection = () => setSelectedVehicles(new Map())

  const selectedIds = useMemo(() => new Set(selectedVehicles.keys()), [selectedVehicles])

  const selectedRoutes = useMemo(
    () =>
      Array.from(selectedVehicles.values()).map((vehicle) => ({
        id: vehicle.id,
        label: vehicle.plate ?? vehicle.vin,
        encodedPath: vehicle.route.polyline,
        start: vehicle.start,
        end: vehicle.end,
      })),
    [selectedVehicles],
  )

  return (
    <main className="mx-auto max-w-6xl px-4 py-8">
      <PageHeader title={t('routes.title')} />
      <VehicleRoutesFilters params={params} institutions={institutions ?? []} onFilterChange={setFilter} />

      {isPending && <p className="py-6 text-sm text-gray-500">{t('common.loading')}</p>}
      {isError && <p className="py-6 text-sm text-red-600">{t('common.error')}</p>}
      {data && (
        <Card className="mt-4 overflow-hidden">
          <VehicleRoutesTable
            routes={data.data}
            meta={data.meta}
            sort={params.sort}
            onSortChange={setSort}
            onPageChange={setPage}
            selectedIds={selectedIds}
            onToggleSelect={handleToggleSelect}
          />
        </Card>
      )}

      {selectedRoutes.length > 0 && (
        <section className="mt-6">
          <div className="mb-2 flex items-center justify-between gap-4">
            <h2 className="text-lg font-semibold text-gray-900">{t('routes.map.title', { count: selectedRoutes.length })}</h2>
            <button
              type="button"
              onClick={clearSelection}
              className="text-sm font-medium text-blue-600 hover:text-blue-700 hover:underline"
            >
              {t('routes.map.clearSelection')}
            </button>
          </div>
          <Card className="overflow-hidden p-4">
            <RouteMap routes={selectedRoutes} />
          </Card>
        </section>
      )}
    </main>
  )
}
