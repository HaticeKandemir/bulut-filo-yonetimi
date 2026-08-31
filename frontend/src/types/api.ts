export interface PaginationLink {
  url: string | null
  label: string
  active: boolean
}

export interface PaginationMeta {
  current_page: number
  from: number | null
  last_page: number
  links: PaginationLink[]
  path: string
  per_page: number
  to: number | null
  total: number
}

export interface PaginationLinks {
  first: string | null
  last: string | null
  prev: string | null
  next: string | null
}

export interface PaginatedResponse<T> {
  data: T[]
  links: PaginationLinks
  meta: PaginationMeta
}

export interface ApiResponse<T> {
  data: T
}

export type VehicleStatus = 'active' | 'passive' | 'left_fleet'

export interface InstitutionSummary {
  id: number
  name: string
  code: string
}

export interface InstitutionNode extends InstitutionSummary {
  children: InstitutionNode[]
}

export interface VehicleActivePlate {
  plate: string
  assigned_at: string
}

export interface Vehicle {
  id: number
  vin: string
  brand: string
  model: string
  status: VehicleStatus
  institution: InstitutionSummary
  active_plate: VehicleActivePlate | null
  created_at: string
}

export type ImportBatchStatus = 'pending' | 'processing' | 'completed' | 'failed'
export type ImportRowStatus = 'pending' | 'processed' | 'needs_review' | 'failed'
export type AddressResolutionStatus = 'pending' | 'resolved' | 'failed' | 'skipped'
export type RouteComputationStatus = 'pending' | 'computed' | 'failed' | 'skipped'

export interface ImportBatch {
  id: number
  original_filename: string
  status: ImportBatchStatus
  created_at: string
}

export interface ImportRowRoute {
  distance_meters: number
  duration_seconds: number
  polyline: string
}

export interface GeoCoordinates {
  lat: number
  lng: number
}

export interface ImportRow {
  id: number
  row_number: number
  vin: string | null
  plate: string | null
  brand: string | null
  model: string | null
  institution_code: string | null
  start_address: string | null
  end_address: string | null
  status: ImportRowStatus
  error_message: string | null
  vehicle_id: number | null
  conflicting_vehicle_vin: string | null
  address_resolution_status: AddressResolutionStatus
  address_resolution_error: string | null
  start_coordinates: GeoCoordinates | null
  end_coordinates: GeoCoordinates | null
  route_computation_status: RouteComputationStatus
  route_computation_error: string | null
  route: ImportRowRoute | null
}
