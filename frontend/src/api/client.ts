const API_BASE_URL = import.meta.env.VITE_API_BASE_URL

export async function checkBackendHealth(): Promise<boolean> {
  const response = await fetch(`${API_BASE_URL}/up`)

  return response.ok
}
