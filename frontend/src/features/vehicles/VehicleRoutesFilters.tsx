import { useTranslation } from 'react-i18next'
import { DebouncedTextFilter } from '../../components/DebouncedTextFilter'
import type { InstitutionNode } from '../../types/api'
import { flattenInstitutions } from '../../utils/institutions'
import type { VehicleRoutesFilterKey, VehicleRoutesParams } from './useVehicleRoutesParams'

interface VehicleRoutesFiltersProps {
  params: VehicleRoutesParams
  institutions: InstitutionNode[]
  onFilterChange: (key: VehicleRoutesFilterKey, value: string) => void
}

const controlClassName =
  'rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500'

export function VehicleRoutesFilters({ params, institutions, onFilterChange }: VehicleRoutesFiltersProps) {
  const { t } = useTranslation()
  const flatInstitutions = flattenInstitutions(institutions)

  return (
    <div className="flex flex-wrap gap-3">
      <DebouncedTextFilter
        value={params.vin}
        placeholder={t('vehicles.filters.vin')}
        onChange={(value) => onFilterChange('filter[vin]', value)}
        className={controlClassName}
      />
      <DebouncedTextFilter
        value={params.plate}
        placeholder={t('vehicles.filters.plate')}
        onChange={(value) => onFilterChange('filter[plate]', value)}
        className={controlClassName}
      />
      <select
        value={params.institutionId}
        onChange={(event) => onFilterChange('filter[institution_id]', event.target.value)}
        className={controlClassName}
      >
        <option value="">{t('vehicles.filters.allInstitutions')}</option>
        {flatInstitutions.map((institution) => (
          <option key={institution.id} value={String(institution.id)}>
            {institution.label}
          </option>
        ))}
      </select>
    </div>
  )
}
