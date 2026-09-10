const STORAGE_KEY = 'auth_token'

/** Fired whenever the token is cleared, so AuthProvider can sync its React state without a prop/callback wired through every caller (including client.ts, which isn't a React module). */
export const AUTH_TOKEN_CLEARED_EVENT = 'auth-token-cleared'

export function getAuthToken(): string | null {
  try {
    return localStorage.getItem(STORAGE_KEY)
  } catch {
    return null
  }
}

export function setAuthToken(token: string): void {
  try {
    localStorage.setItem(STORAGE_KEY, token)
  } catch {
    // localStorage unavailable (private mode, disabled storage) — the session just won't persist across reloads.
  }
}

export function clearAuthToken(): void {
  try {
    localStorage.removeItem(STORAGE_KEY)
  } catch {
    // see setAuthToken
  }
  window.dispatchEvent(new Event(AUTH_TOKEN_CLEARED_EVENT))
}
