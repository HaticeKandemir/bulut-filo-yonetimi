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

  return (
    <APIProvider apiKey={apiKey}>
      <Map
        mapId="DEMO_MAP_ID"
        defaultBounds={computeBounds(routes)}
        style={{ width: '100%', height: '480px' }}
        gestureHandling="greedy"
        disableDefaultUI={false}
      >
        {routes.map((route, index) => {
          const color = ROUTE_COLORS[index % ROUTE_COLORS.length]

          return (
            <Fragment key={route.id}>
              <Polyline encodedPath={route.encodedPath} strokeColor={color} strokeOpacity={0.8} strokeWeight={4} />
              <AdvancedMarker position={route.start} title={`${route.label} — ${t('imports.map.start')}`}>
                <Pin background={color} borderColor={color} glyphColor="#ffffff" />
              </AdvancedMarker>
              <AdvancedMarker position={route.end} title={`${route.label} — ${t('imports.map.end')}`}>
                <Pin background="#ffffff" borderColor={color} glyphColor={color} />
              </AdvancedMarker>
            </Fragment>
          )
        })}
      </Map>
    </APIProvider>
  )
}
