import { useTranslation } from 'react-i18next'
import { useHealthCheck } from '../hooks/useHealthCheck'

export function HomePage() {
  const { t } = useTranslation()
  const { data: isHealthy, isPending, isError } = useHealthCheck()

  const statusLabel = isPending
    ? t('common.loading')
    : isError || !isHealthy
      ? t('common.error')
      : t('home.connected')

  return (
    <main className="flex min-h-screen flex-col items-center justify-center gap-4 bg-white text-gray-900">
      <h1 className="text-2xl font-semibold">{t('app.title')}</h1>
      <p className="text-gray-600">
        {t('home.backendStatus')}: <span className="font-medium">{statusLabel}</span>
      </p>
    </main>
  )
}
