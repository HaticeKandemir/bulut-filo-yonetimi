import type { RouteSummary } from '../types/api'

export function formatRouteSummary(route: RouteSummary): string {
  const km = (route.distance_meters / 1000).toFixed(1)
  const minutes = Math.round(route.duration_seconds / 60)

  return `${km} km, ${minutes} dk`
}
