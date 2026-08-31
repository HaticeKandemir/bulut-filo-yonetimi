import type { ApiResponse, InstitutionNode } from '../types/api'
import { apiGet } from './client'

export function fetchInstitutions(): Promise<ApiResponse<InstitutionNode[]>> {
  return apiGet<ApiResponse<InstitutionNode[]>>('/institutions')
}
