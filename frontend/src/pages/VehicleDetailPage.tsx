import { useTranslation } from 'react-i18next'
import { Link, useParams } from 'react-router'
import { Card } from '../components/Card'
import { StatusBadge, type BadgeColor } from '../components/StatusBadge'
import type { VehicleStatus } from '../types/api'
import { useVehicle } from '../features/vehicles/useVehicle'

const STATUS_COLORS: Record<VehicleStatus, BadgeColor> = {
  active: 'green',
  passive: 'gray',
  left_fleet: 'red',
}

export function VehicleDetailPage() {
  const { t } = useTranslation()
  const params = useParams<{ id: string }>()
  const vehicleId = Number(params.id)

  const { data, isPending, isError } = useVehicle(vehicleId)

  return (
    <main className="mx-auto max-w-4xl px-4 py-8">
      <Link to="/vehicles" className="text-sm font-medium text-blue-600 hover:text-blue-700 hover:underline">
        {t('vehicles.detail.backToList')}
      </Link>

      {isPending && <p className="mt-4 text-sm text-gray-500">{t('common.loading')}</p>}
      {isError && <p className="mt-4 text-sm text-red-600">{t('vehicles.detail.notFound')}</p>}

      {data && (
        <>
          <h1 className="mt-2 text-2xl font-semibold tracking-tight text-gray-900">
            {data.data.brand} {data.data.model}
          </h1>

          <Card className="mt-4 p-6">
            <dl className="grid grid-cols-2 gap-x-6 gap-y-4 text-sm sm:grid-cols-4">
              <div>
                <dt className="text-gray-500">{t('vehicles.columns.vin')}</dt>
                <dd className="mt-1 font-medium text-gray-900">{data.data.vin}</dd>
              </div>
              <div>
                <dt className="text-gray-500">{t('vehicles.columns.status')}</dt>
                <dd className="mt-1">
                  <StatusBadge color={STATUS_COLORS[data.data.status]}>{t(`vehicles.status.${data.data.status}`)}</StatusBadge>
                </dd>
              </div>
              <div>
                <dt className="text-gray-500">{t('vehicles.columns.institution')}</dt>
                <dd className="mt-1 font-medium text-gray-900">{data.data.institution.name}</dd>
              </div>
              <div>
                <dt className="text-gray-500">{t('vehicles.detail.activePlate')}</dt>
                <dd className="mt-1 font-medium text-gray-900">
                  {data.data.active_plate?.plate ?? t('vehicles.detail.noActivePlate')}
                </dd>
              </div>
            </dl>
          </Card>

          <h2 className="mt-8 mb-2 text-lg font-semibold text-gray-900">{t('vehicles.detail.plateHistory.title')}</h2>
          <Card className="overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                      {t('vehicles.detail.plateHistory.columns.plate')}
                    </th>
                    <th className="px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                      {t('vehicles.detail.plateHistory.columns.assignedAt')}
                    </th>
                    <th className="px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                      {t('vehicles.detail.plateHistory.columns.releasedAt')}
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {(data.data.plate_history?.length ?? 0) === 0 && (
                    <tr>
                      <td colSpan={3} className="px-4 py-8 text-center text-gray-500">
                        {t('vehicles.detail.plateHistory.empty')}
                      </td>
                    </tr>
                  )}
                  {data.data.plate_history?.map((entry) => (
                    <tr key={`${entry.plate}-${entry.assigned_at}`} className="hover:bg-gray-50">
                      <td className="px-4 py-3 font-medium text-gray-900">{entry.plate}</td>
                      <td className="px-4 py-3 text-gray-700">{new Date(entry.assigned_at).toLocaleString('tr-TR')}</td>
                      <td className="px-4 py-3 text-gray-700">
                        {entry.is_active ? (
                          <StatusBadge color="green">{t('vehicles.detail.plateHistory.active')}</StatusBadge>
                        ) : (
                          new Date(entry.released_at).toLocaleString('tr-TR')
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </Card>
        </>
      )}
    </main>
  )
}
