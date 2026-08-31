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
