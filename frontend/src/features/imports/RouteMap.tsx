import { AdvancedMarker, APIProvider, Map, Pin, Polyline } from '@vis.gl/react-google-maps'
import { Fragment } from 'react'
import { useTranslation } from 'react-i18next'
import type { GeoCoordinates } from '../../types/api'

const ROUTE_COLORS = ['#2563eb', '#dc2626', '#16a34a', '#d97706', '#7c3aed', '#0891b2']

export interface RouteMapRoute {
  id: number
  label: string
  encodedPath: string
  start: GeoCoordinates
  end: GeoCoordinates
}

interface RouteMapProps {
  routes: RouteMapRoute[]
}

function computeBounds(routes: RouteMapRoute[]) {
  const lats = routes.flatMap((route) => [route.start.lat, route.end.lat])
  const lngs = routes.flatMap((route) => [route.start.lng, route.end.lng])

  return {
    north: Math.max(...lats),
    south: Math.min(...lats),
    east: Math.max(...lngs),
    west: Math.min(...lngs),
    padding: 48,
  }
}

export function RouteMap({ routes }: RouteMapProps) {
  const { t } = useTranslation()
  const apiKey = import.meta.env.VITE_GOOGLE_MAPS_BROWSER_KEY

  if (routes.length === 0) {
    return null
  }

  const coloredRoutes = routes.map((route, index) => ({ ...route, color: ROUTE_COLORS[index % ROUTE_COLORS.length] }))

  return (
    <div>
      {/* Always-visible color legend: AdvancedMarker's `title` is a hover-only
          tooltip, which doesn't work on touch devices, so the route-to-color
          mapping can't rely on it as the only way to read it. */}
      <ul className="flex flex-wrap gap-x-4 gap-y-1 pb-2 text-sm text-gray-700">
        {coloredRoutes.map((route) => (
          <li key={route.id} className="flex items-center gap-1.5">
            <span aria-hidden="true" className="inline-block h-2.5 w-2.5 rounded-full" style={{ backgroundColor: route.color }} />
            {route.label}
          </li>
        ))}
      </ul>
      <APIProvider apiKey={apiKey}>
        <Map
          mapId="DEMO_MAP_ID"
          defaultBounds={computeBounds(routes)}
          style={{ width: '100%', height: '480px' }}
          gestureHandling="greedy"
          disableDefaultUI={false}
        >
          {coloredRoutes.map((route) => (
            <Fragment key={route.id}>
              <Polyline encodedPath={route.encodedPath} strokeColor={route.color} strokeOpacity={0.8} strokeWeight={4} />
              <AdvancedMarker position={route.start} title={`${route.label} — ${t('imports.map.start')}`}>
                <Pin background={route.color} borderColor={route.color} glyphColor="#ffffff" />
              </AdvancedMarker>
              <AdvancedMarker position={route.end} title={`${route.label} — ${t('imports.map.end')}`}>
                <Pin background="#ffffff" borderColor={route.color} glyphColor={route.color} />
              </AdvancedMarker>
            </Fragment>
          ))}
        </Map>
      </APIProvider>
    </div>
  )
}
