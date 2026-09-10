import { useState } from 'react'
import { useDebouncedCallback } from '../hooks/useDebouncedCallback'

interface DebouncedTextFilterProps {
  value: string
  onChange: (value: string) => void
  placeholder: string
  className: string
}

export function DebouncedTextFilter({ value, onChange, placeholder, className }: DebouncedTextFilterProps) {
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
      className={className}
    />
  )
}
