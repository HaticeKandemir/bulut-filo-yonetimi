import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useDebouncedCallback } from '../../hooks/useDebouncedCallback'
import type { InstitutionNode } from '../../types/api'
import type { VehicleFilterKey, VehicleListParams } from './useVehicleListParams'

interface VehicleFiltersProps {
  params: VehicleListParams
  institutions: InstitutionNode[]
  onFilterChange: (key: VehicleFilterKey, value: string) => void
}

interface FlatInstitution {
  id: number
  label: string
}

function flattenInstitutions(nodes: InstitutionNode[], depth = 0): FlatInstitution[] {
  return nodes.flatMap((node) => [
    { id: node.id, label: `${'—'.repeat(depth)} ${node.name}`.trim() },
    ...flattenInstitutions(node.children, depth + 1),
  ])
}

const controlClassName =
  'rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500'

interface DebouncedTextFilterProps {
  value: string
  onChange: (value: string) => void
  placeholder: string
}

function DebouncedTextFilter({ value, onChange, placeholder }: DebouncedTextFilterProps) {
  const [localValue, setLocalValue] = useState(value)
  const [syncedValue, setSyncedValue] = useState(value)
  const debouncedOnChange = useDebouncedCallback(onChange, 300)

  // Adjust local state during render when the external `value` prop changes
  // (e.g. cleared filters, browser back/forward) instead of a useEffect, per
  // https://react.dev/learn/you-might-not-need-an-effect#adjusting-some-state-when-a-prop-changes
  if (value !== syncedValue) {
    setSyncedValue(value)
    setLocalValue(value)
  }

  return (
    <input
      type="text"
      placeholder={placeholder}
      value={localValue}
      onChange={(event) => {
        setLocalValue(event.target.value)
        debouncedOnChange(event.target.value)
      }}
      className={controlClassName}
    />
  )
}

export function VehicleFilters({ params, institutions, onFilterChange }: VehicleFiltersProps) {
  const { t } = useTranslation()
  const flatInstitutions = flattenInstitutions(institutions)

  return (
    <div className="flex flex-wrap gap-3">
      <DebouncedTextFilter
        value={params.vin}
        placeholder={t('vehicles.filters.vin')}
        onChange={(value) => onFilterChange('filter[vin]', value)}
      />
      <DebouncedTextFilter
        value={params.plate}
        placeholder={t('vehicles.filters.plate')}
        onChange={(value) => onFilterChange('filter[plate]', value)}
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
