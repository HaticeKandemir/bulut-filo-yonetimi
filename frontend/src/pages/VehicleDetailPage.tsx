import { useTranslation } from 'react-i18next'
import { Link, useParams } from 'react-router'
import { useVehicle } from '../features/vehicles/useVehicle'

export function VehicleDetailPage() {
  const { t } = useTranslation()
  const params = useParams<{ id: string }>()
  const vehicleId = Number(params.id)

  const { data, isPending, isError } = useVehicle(vehicleId)

  return (
    <main className="mx-auto max-w-4xl px-4 py-8">
      <Link to="/vehicles" className="text-sm text-gray-600 underline">
        {t('vehicles.detail.backToList')}
      </Link>

      {isPending && <p className="mt-4">{t('common.loading')}</p>}
      {isError && <p className="mt-4">{t('vehicles.detail.notFound')}</p>}

      {data && (
        <>
          <h1 className="mt-2 text-2xl font-semibold text-gray-900">
            {data.data.brand} {data.data.model}
          </h1>
          <dl className="mt-4 grid grid-cols-2 gap-x-6 gap-y-3 text-sm sm:grid-cols-3">
            <div>
              <dt className="text-gray-500">{t('vehicles.columns.vin')}</dt>
              <dd className="font-medium text-gray-900">{data.data.vin}</dd>
            </div>
            <div>
              <dt className="text-gray-500">{t('vehicles.columns.status')}</dt>
              <dd className="font-medium text-gray-900">{t(`vehicles.status.${data.data.status}`)}</dd>
            </div>
            <div>
              <dt className="text-gray-500">{t('vehicles.columns.institution')}</dt>
              <dd className="font-medium text-gray-900">{data.data.institution.name}</dd>
            </div>
            <div>
              <dt className="text-gray-500">{t('vehicles.detail.activePlate')}</dt>
              <dd className="font-medium text-gray-900">
                {data.data.active_plate?.plate ?? t('vehicles.detail.noActivePlate')}
              </dd>
            </div>
          </dl>

          <h2 className="mt-8 text-lg font-semibold text-gray-900">{t('vehicles.detail.plateHistory.title')}</h2>
          <div className="mt-2 overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead>
                <tr>
                  <th className="border-b px-3 py-2 font-medium text-gray-700">
                    {t('vehicles.detail.plateHistory.columns.plate')}
                  </th>
                  <th className="border-b px-3 py-2 font-medium text-gray-700">
                    {t('vehicles.detail.plateHistory.columns.assignedAt')}
                  </th>
                  <th className="border-b px-3 py-2 font-medium text-gray-700">
                    {t('vehicles.detail.plateHistory.columns.releasedAt')}
                  </th>
                </tr>
              </thead>
              <tbody>
                {(data.data.plate_history?.length ?? 0) === 0 && (
                  <tr>
                    <td colSpan={3} className="px-3 py-6 text-center text-gray-500">
                      {t('vehicles.detail.plateHistory.empty')}
                    </td>
                  </tr>
                )}
                {data.data.plate_history?.map((entry) => (
                  <tr key={`${entry.plate}-${entry.assigned_at}`} className="border-b">
                    <td className="px-3 py-2">{entry.plate}</td>
                    <td className="px-3 py-2">{new Date(entry.assigned_at).toLocaleString('tr-TR')}</td>
                    <td className="px-3 py-2">
                      {entry.is_active ? t('vehicles.detail.plateHistory.active') : new Date(entry.released_at).toLocaleString('tr-TR')}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </>
      )}
    </main>
  )
}
