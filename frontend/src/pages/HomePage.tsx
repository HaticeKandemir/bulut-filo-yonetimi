import { useTranslation } from 'react-i18next'
import { Card } from '../components/Card'
import { useHealthCheck } from '../hooks/useHealthCheck'

export function HomePage() {
  const { t } = useTranslation()
  const { data: isHealthy, isPending, isError } = useHealthCheck()

  const isConnected = !isPending && !isError && isHealthy
  const statusLabel = isPending ? t('common.loading') : isConnected ? t('home.connected') : t('common.error')
  const dotClassName = isPending ? 'bg-gray-300' : isConnected ? 'bg-green-500' : 'bg-red-500'

  return (
    <main className="mx-auto flex max-w-3xl flex-col items-center gap-6 px-4 py-24 text-center">
      <h1 className="text-3xl font-semibold tracking-tight text-gray-900">{t('app.title')}</h1>
      <Card className="px-6 py-4">
        <p className="flex items-center gap-2 text-sm text-gray-700">
          <span aria-hidden="true" className={`inline-block h-2 w-2 rounded-full ${dotClassName}`} />
          {t('home.backendStatus')}: <span className="font-medium text-gray-900">{statusLabel}</span>
        </p>
      </Card>
    </main>
  )
}
