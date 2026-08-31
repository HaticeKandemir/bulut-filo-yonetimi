import { useTranslation } from 'react-i18next'
import { NavLink, Outlet } from 'react-router'

const linkClassName = ({ isActive }: { isActive: boolean }) =>
  `rounded-md px-3 py-2 text-sm font-medium transition-colors ${
    isActive ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
  }`

export function RootLayout() {
  const { t } = useTranslation()

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="sticky top-0 z-10 border-b border-gray-200 bg-white">
        <div className="mx-auto flex max-w-6xl items-center gap-6 px-4 py-3">
          <span className="text-sm font-semibold tracking-tight text-gray-900">{t('app.title')}</span>
          <nav className="flex gap-1">
            <NavLink to="/" end className={linkClassName}>
              {t('nav.home')}
            </NavLink>
            <NavLink to="/vehicles" className={linkClassName}>
              {t('nav.vehicles')}
            </NavLink>
            <NavLink to="/imports" className={linkClassName}>
              {t('nav.imports')}
            </NavLink>
          </nav>
        </div>
      </header>
      <Outlet />
    </div>
  )
}
