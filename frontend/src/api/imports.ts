import type { ApiResponse, ImportBatch, ImportRow, PaginatedResponse } from '../types/api'
import { apiGet, apiPostForm } from './client'

export function fetchImportBatches(searchParams: URLSearchParams): Promise<PaginatedResponse<ImportBatch>> {
  return apiGet<PaginatedResponse<ImportBatch>>('/vehicle-imports', searchParams)
}

export function fetchImportBatch(id: number): Promise<ApiResponse<ImportBatch>> {
  return apiGet<ApiResponse<ImportBatch>>(`/vehicle-imports/${id}`)
}

export function fetchImportBatchRows(id: number, searchParams?: URLSearchParams): Promise<PaginatedResponse<ImportRow>> {
  return apiGet<PaginatedResponse<ImportRow>>(`/vehicle-imports/${id}/rows`, searchParams)
}

export function uploadImportBatch(file: File): Promise<ApiResponse<ImportBatch>> {
  const formData = new FormData()
  formData.append('file', file)

  return apiPostForm<ApiResponse<ImportBatch>>('/vehicle-imports', formData)
}
