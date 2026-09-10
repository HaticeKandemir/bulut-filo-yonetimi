import { clearAuthToken, getAuthToken } from '../utils/authToken'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL
const API_V1_BASE_URL = `${API_BASE_URL}/api/v1`

export interface ValidationErrorBody {
  message: string
  errors?: Record<string, string[]>
  /** Present only on the 409 plate-conflict response from PATCH /vehicles/{id}. */
  conflicting_vehicle_vin?: string
}

export class ApiError extends Error {
  readonly status: number
  readonly body: ValidationErrorBody | undefined

  constructor(message: string, status: number, body?: ValidationErrorBody) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.body = body
  }
}

function authHeaders(): Record<string, string> {
  const token = getAuthToken()

  return token === null ? {} : { Authorization: `Bearer ${token}` }
}

async function throwApiError(method: string, path: string, response: Response): Promise<never> {
  const body = (await response.json().catch(() => undefined)) as ValidationErrorBody | undefined

  // A 401 means the stored token is missing/invalid/expired — drop it so
  // AuthProvider's listener sends the user back to the login screen.
  if (response.status === 401) {
    clearAuthToken()
  }

  throw new ApiError(`${method} ${path} failed with status ${response.status}`, response.status, body)
}

export async function apiGet<T>(path: string, params?: URLSearchParams): Promise<T> {
  const queryString = params?.toString()
  const url = `${API_V1_BASE_URL}${path}${queryString ? `?${queryString}` : ''}`
  const response = await fetch(url, { headers: authHeaders() })

  if (!response.ok) {
    await throwApiError('GET', path, response)
  }

  return (await response.json()) as T
}

export async function apiPost<T>(path: string, body?: unknown): Promise<T> {
  const response = await fetch(`${API_V1_BASE_URL}${path}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', ...authHeaders() },
    body: body === undefined ? undefined : JSON.stringify(body),
  })

  if (!response.ok) {
    await throwApiError('POST', path, response)
  }

  if (response.status === 204) {
    return undefined as T
  }

  return (await response.json()) as T
}

export async function apiPostForm<T>(path: string, formData: FormData): Promise<T> {
  const response = await fetch(`${API_V1_BASE_URL}${path}`, {
    method: 'POST',
    headers: authHeaders(),
    body: formData,
  })

  if (!response.ok) {
    await throwApiError('POST', path, response)
  }

  return (await response.json()) as T
}

export async function apiPatch<T>(path: string, body: unknown): Promise<T> {
  const response = await fetch(`${API_V1_BASE_URL}${path}`, {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json', ...authHeaders() },
    body: JSON.stringify(body),
  })

  if (!response.ok) {
    await throwApiError('PATCH', path, response)
  }

  return (await response.json()) as T
}
