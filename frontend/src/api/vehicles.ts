import type { ApiResponse, PaginatedResponse, Vehicle } from '../types/api'
import { apiGet } from './client'

export function fetchVehicles(searchParams: URLSearchParams): Promise<PaginatedResponse<Vehicle>> {
  return apiGet<PaginatedResponse<Vehicle>>('/vehicles', searchParams)
}

export function fetchVehicle(id: number): Promise<ApiResponse<Vehicle>> {
  return apiGet<ApiResponse<Vehicle>>(`/vehicles/${id}`)
}
