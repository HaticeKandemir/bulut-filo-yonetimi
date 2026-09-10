import type { ApiResponse, CurrentUser } from '../types/api'
import { apiGet, apiPost } from './client'

export interface LoginPayload {
  email: string
  password: string
}

export interface LoginResponse {
  token: string
}

export function login(payload: LoginPayload): Promise<LoginResponse> {
  return apiPost<LoginResponse>('/auth/login', payload)
}

export function logout(): Promise<void> {
  return apiPost<void>('/auth/logout')
}

export function fetchCurrentUser(): Promise<ApiResponse<CurrentUser>> {
  return apiGet<ApiResponse<CurrentUser>>('/auth/me')
}
