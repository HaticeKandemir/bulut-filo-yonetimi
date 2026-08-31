const API_BASE_URL = import.meta.env.VITE_API_BASE_URL
const API_V1_BASE_URL = `${API_BASE_URL}/api/v1`

export async function checkBackendHealth(): Promise<boolean> {
  const response = await fetch(`${API_BASE_URL}/up`)

  return response.ok
}

export class ApiError extends Error {
  readonly status: number

  constructor(message: string, status: number) {
    super(message)
    this.name = 'ApiError'
    this.status = status
  }
}

export async function apiGet<T>(path: string, params?: URLSearchParams): Promise<T> {
  const queryString = params?.toString()
  const url = `${API_V1_BASE_URL}${path}${queryString ? `?${queryString}` : ''}`
  const response = await fetch(url)

  if (!response.ok) {
    throw new ApiError(`GET ${path} failed with status ${response.status}`, response.status)
  }

  return (await response.json()) as T
}
