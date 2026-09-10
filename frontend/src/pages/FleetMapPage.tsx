import { useTranslation } from 'react-i18next'
import { Card } from '../components/Card'
import { PageHeader } from '../components/PageHeader'
import { FleetMap } from '../features/vehicles/FleetMap'
import { useVehicleMapPins } from '../features/vehicles/useVehicleMapPins'

export function FleetMapPage() {
  const { t } = useTranslation()
  const { data, isPending, isError } = useVehicleMapPins()
  const pins = data?.data ?? []

  return (
    <main className="mx-auto max-w-6xl px-4 py-8">
      <PageHeader title={t('fleetMap.title')} />
      {isPending && <p className="text-sm text-gray-500">{t('common.loading')}</p>}
      {isError && <p className="text-sm text-red-600">{t('common.error')}</p>}
      {!isPending && !isError && pins.length === 0 && <p className="text-sm text-gray-500">{t('fleetMap.empty')}</p>}
      {pins.length > 0 && (
        <Card className="overflow-hidden p-4">
          <FleetMap pins={pins} />
        </Card>
      )}
    </main>
  )
}
