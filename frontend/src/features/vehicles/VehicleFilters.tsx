import { useTranslation } from 'react-i18next'
import { DebouncedTextFilter } from '../../components/DebouncedTextFilter'
import type { InstitutionNode } from '../../types/api'
import { flattenInstitutions } from '../../utils/institutions'
import type { VehicleFilterKey, VehicleListParams } from './useVehicleListParams'

interface VehicleFiltersProps {
  params: VehicleListParams
  institutions: InstitutionNode[]
  onFilterChange: (key: VehicleFilterKey, value: string) => void
}

const controlClassName =
  'rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500'

export function VehicleFilters({ params, institutions, onFilterChange }: VehicleFiltersProps) {
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
      <DebouncedTextFilter
        value={params.brand}
        placeholder={t('vehicles.filters.brand')}
        onChange={(value) => onFilterChange('filter[brand]', value)}
        className={controlClassName}
      />
      <DebouncedTextFilter
        value={params.model}
        placeholder={t('vehicles.filters.model')}
        onChange={(value) => onFilterChange('filter[model]', value)}
        className={controlClassName}
      />
      <select
        value={params.status}
        onChange={(event) => onFilterChange('filter[status]', event.target.value)}
        className={controlClassName}
      >
        <option value="">{t('vehicles.filters.allStatuses')}</option>
        <option value="active">{t('vehicles.status.active')}</option>
        <option value="passive">{t('vehicles.status.passive')}</option>
        <option value="left_fleet">{t('vehicles.status.left_fleet')}</option>
      </select>
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
