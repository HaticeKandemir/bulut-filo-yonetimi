import { useTranslation } from 'react-i18next'

/**
 * Shown only below the sm breakpoint, right above a horizontally
 * scrollable table. The table itself never overflows the page (it's
 * wrapped in its own overflow-x-auto container), but on a narrow
 * screen there's no visual cue that more columns exist off-screen
 * without this.
 */
export function ScrollHint() {
  const { t } = useTranslation()

  return <p className="border-b border-gray-100 bg-gray-50 px-4 py-1.5 text-xs text-gray-500 sm:hidden">{t('common.scrollHint')}</p>
}
