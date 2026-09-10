import type { ApiResponse, PaginatedResponse, Vehicle, VehicleMapPin, VehicleRoute, VehicleStatus } from '../types/api'
import { apiGet, apiPatch } from './client'

export function fetchVehicles(searchParams: URLSearchParams): Promise<PaginatedResponse<Vehicle>> {
  return apiGet<PaginatedResponse<Vehicle>>('/vehicles', searchParams)
}

export function fetchVehicle(id: number): Promise<ApiResponse<Vehicle>> {
  return apiGet<ApiResponse<Vehicle>>(`/vehicles/${id}`)
}

export function fetchVehicleMapPins(): Promise<ApiResponse<VehicleMapPin[]>> {
  return apiGet<ApiResponse<VehicleMapPin[]>>('/vehicles/map')
}

export function fetchVehicleRoutes(searchParams: URLSearchParams): Promise<PaginatedResponse<VehicleRoute>> {
  return apiGet<PaginatedResponse<VehicleRoute>>('/vehicles/routes', searchParams)
}

export interface UpdateVehiclePayload {
  brand: string
  model: string
  institution_id: number
  plate: string
  status: VehicleStatus
}

export function updateVehicle(id: number, data: UpdateVehiclePayload): Promise<ApiResponse<Vehicle>> {
  return apiPatch<ApiResponse<Vehicle>>(`/vehicles/${id}`, data)
}
