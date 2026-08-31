const API_BASE_URL = import.meta.env.VITE_API_BASE_URL
const API_V1_BASE_URL = `${API_BASE_URL}/api/v1`

export async function checkBackendHealth(): Promise<boolean> {
  const response = await fetch(`${API_BASE_URL}/up`)

  return response.ok
}

export interface ValidationErrorBody {
  message: string
  errors?: Record<string, string[]>
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

async function throwApiError(method: string, path: string, response: Response): Promise<never> {
  const body = (await response.json().catch(() => undefined)) as ValidationErrorBody | undefined
  throw new ApiError(`${method} ${path} failed with status ${response.status}`, response.status, body)
}

export async function apiGet<T>(path: string, params?: URLSearchParams): Promise<T> {
  const queryString = params?.toString()
  const url = `${API_V1_BASE_URL}${path}${queryString ? `?${queryString}` : ''}`
  const response = await fetch(url)

  if (!response.ok) {
    await throwApiError('GET', path, response)
  }

  return (await response.json()) as T
}

export async function apiPostForm<T>(path: string, formData: FormData): Promise<T> {
  const response = await fetch(`${API_V1_BASE_URL}${path}`, {
    method: 'POST',
    body: formData,
  })

  if (!response.ok) {
    await throwApiError('POST', path, response)
  }

  return (await response.json()) as T
}
