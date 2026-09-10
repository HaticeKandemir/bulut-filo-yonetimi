import { describe, expect, it } from 'vitest'
import { formatRouteSummary } from './route'

describe('formatRouteSummary', () => {
  it('formats distance in kilometers and duration in minutes', () => {
    expect(formatRouteSummary({ distance_meters: 12345, duration_seconds: 1800, polyline: '' })).toBe('12.3 km, 30 dk')
  })

  it('rounds duration to the nearest minute', () => {
    expect(formatRouteSummary({ distance_meters: 1000, duration_seconds: 89, polyline: '' })).toBe('1.0 km, 1 dk')
  })
})
