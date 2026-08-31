import { useTranslation } from 'react-i18next'
import { NavLink, Outlet } from 'react-router'

const linkClassName = ({ isActive }: { isActive: boolean }) =>
  isActive ? 'font-semibold text-gray-900' : 'text-gray-600 hover:text-gray-900'

export function RootLayout() {
  const { t } = useTranslation()

  return (
    <div className="min-h-screen bg-white">
      <header className="border-b border-gray-200">
        <nav className="mx-auto flex max-w-6xl gap-6 px-4 py-3 text-sm">
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
      </header>
      <Outlet />
    </div>
  )
}
