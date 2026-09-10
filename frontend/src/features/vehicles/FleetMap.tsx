import { AdvancedMarker, APIProvider, InfoWindow, Map, Pin } from '@vis.gl/react-google-maps'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import type { VehicleMapPin } from '../../types/api'

interface FleetMapProps {
  pins: VehicleMapPin[]
}

function computeBounds(pins: VehicleMapPin[]) {
  const lats = pins.map((pin) => pin.start.lat)
  const lngs = pins.map((pin) => pin.start.lng)

  return {
    north: Math.max(...lats),
    south: Math.min(...lats),
    east: Math.max(...lngs),
    west: Math.min(...lngs),
    padding: 48,
  }
}

export function FleetMap({ pins }: FleetMapProps) {
  const { t } = useTranslation()
  const apiKey = import.meta.env.VITE_GOOGLE_MAPS_BROWSER_KEY
  const [openPinId, setOpenPinId] = useState<number | null>(null)

  if (pins.length === 0) {
    return null
  }

  const openPin = pins.find((pin) => pin.id === openPinId) ?? null

  return (
    <APIProvider apiKey={apiKey}>
      <Map
        mapId="DEMO_MAP_ID"
        defaultBounds={computeBounds(pins)}
        style={{ width: '100%', height: '520px' }}
        gestureHandling="greedy"
        disableDefaultUI={false}
      >
        {pins.map((pin) => (
          <AdvancedMarker
            key={pin.id}
            position={pin.start}
            // Hover opens the info window on desktop; click toggles it so
            // the same info is reachable on mobile, where hover never fires.
            onMouseEnter={() => setOpenPinId(pin.id)}
            onMouseLeave={() => setOpenPinId((current) => (current === pin.id ? null : current))}
            onClick={() => setOpenPinId((current) => (current === pin.id ? null : pin.id))}
          >
            <Pin />
          </AdvancedMarker>
        ))}
        {openPin && (
          <InfoWindow position={openPin.start} onCloseClick={() => setOpenPinId(null)}>
            <div className="text-sm">
              <p className="font-semibold text-gray-900">{openPin.plate ?? t('fleetMap.noPlate')}</p>
              <p className="text-gray-700">
                {openPin.brand} {openPin.model}
              </p>
              <p className="text-gray-500">{openPin.institution.name}</p>
            </div>
          </InfoWindow>
        )}
      </Map>
    </APIProvider>
  )
}
