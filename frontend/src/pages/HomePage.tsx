import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { Card } from '../components/Card'
import { StatusBadge, type BadgeColor } from '../components/StatusBadge'
import { useImportBatches } from '../features/imports/useImportBatches'
import { useVehicles } from '../features/vehicles/useVehicles'
import { useInstitutions } from '../hooks/useInstitutions'
import type { ImportBatchStatus } from '../types/api'
import { flattenInstitutions } from '../utils/institutions'

const BATCH_STATUS_COLORS: Record<ImportBatchStatus, BadgeColor> = {
  pending: 'gray',
  processing: 'blue',
  completed: 'green',
  failed: 'red',
}

const TOTAL_VEHICLES_PARAMS = new URLSearchParams({ per_page: '1' })
const ACTIVE_VEHICLES_PARAMS = new URLSearchParams({ per_page: '1', 'filter[status]': 'active' })
const LATEST_IMPORT_PARAMS = new URLSearchParams({ per_page: '1' })

interface QuickLink {
  to: string
  titleKey: string
  descriptionKey: string
}

const QUICK_LINKS: QuickLink[] = [
  { to: '/vehicles', titleKey: 'nav.vehicles', descriptionKey: 'home.links.vehicles' },
  { to: '/fleet-map', titleKey: 'nav.fleetMap', descriptionKey: 'home.links.fleetMap' },
  { to: '/routes', titleKey: 'nav.routes', descriptionKey: 'home.links.routes' },
  { to: '/institutions', titleKey: 'nav.institutions', descriptionKey: 'home.links.institutions' },
  { to: '/imports', titleKey: 'nav.imports', descriptionKey: 'home.links.imports' },
]

interface StatCardProps {
  label: string
  value: number | undefined
}

function StatCard({ label, value }: StatCardProps) {
  return (
    <Card className="p-4">
      <p className="text-sm text-gray-500">{label}</p>
      <p className="mt-2 text-2xl font-semibold text-gray-900">{value ?? '—'}</p>
    </Card>
  )
}

export function HomePage() {
  const { t } = useTranslation()
  const { data: totalVehicles } = useVehicles(TOTAL_VEHICLES_PARAMS)
  const { data: activeVehicles } = useVehicles(ACTIVE_VEHICLES_PARAMS)
  const { data: institutions } = useInstitutions()
  const { data: latestImports } = useImportBatches(LATEST_IMPORT_PARAMS)

  const institutionCount = institutions ? flattenInstitutions(institutions).length : undefined
  const latestBatch = latestImports?.data[0]

  return (
    <main className="mx-auto max-w-5xl px-4 py-10">
      <div className="mb-8">
        <h1 className="text-3xl font-semibold tracking-tight text-gray-900">{t('app.title')}</h1>
        <p className="mt-1 text-sm text-gray-500">{t('home.tagline')}</p>
      </div>

      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <StatCard label={t('home.stats.totalVehicles')} value={totalVehicles?.meta.total} />
        <StatCard label={t('home.stats.activeVehicles')} value={activeVehicles?.meta.total} />
        <StatCard label={t('home.stats.institutions')} value={institutionCount} />
        <Card className="p-4">
          <p className="text-sm text-gray-500">{t('home.stats.latestImport')}</p>
          {latestBatch ? (
            <div className="mt-2 flex items-center gap-2">
              <StatusBadge color={BATCH_STATUS_COLORS[latestBatch.status]}>
                {t(`imports.batchStatus.${latestBatch.status}`)}
              </StatusBadge>
              <span className="truncate text-sm text-gray-700">{latestBatch.original_filename}</span>
            </div>
          ) : (
            <p className="mt-2 text-2xl font-semibold text-gray-400">—</p>
          )}
        </Card>
      </div>

      <h2 className="mt-10 mb-3 text-lg font-semibold text-gray-900">{t('home.quickLinksTitle')}</h2>
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {QUICK_LINKS.map((link) => (
          <Link key={link.to} to={link.to}>
            <Card className="h-full p-5 transition-shadow hover:shadow-md">
              <p className="font-medium text-gray-900">{t(link.titleKey)}</p>
              <p className="mt-1 text-sm text-gray-500">{t(link.descriptionKey)}</p>
            </Card>
          </Link>
        ))}
      </div>
    </main>
  )
}
