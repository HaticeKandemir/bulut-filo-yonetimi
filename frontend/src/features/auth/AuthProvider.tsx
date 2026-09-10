import { useEffect, useState, type PropsWithChildren } from 'react'
import { AUTH_TOKEN_CLEARED_EVENT, clearAuthToken, getAuthToken, setAuthToken } from '../../utils/authToken'
import { AuthContext, type AuthContextValue } from './authContext'

export function AuthProvider({ children }: PropsWithChildren) {
  const [token, setToken] = useState<string | null>(() => getAuthToken())

  useEffect(() => {
    const handleCleared = () => setToken(null)
    window.addEventListener(AUTH_TOKEN_CLEARED_EVENT, handleCleared)

    return () => window.removeEventListener(AUTH_TOKEN_CLEARED_EVENT, handleCleared)
  }, [])

  const value: AuthContextValue = {
    token,
    login: (newToken) => {
      setAuthToken(newToken)
      setToken(newToken)
    },
    // Clearing the token dispatches AUTH_TOKEN_CLEARED_EVENT, which the
    // listener above turns into the state update — the same path a 401
    // from client.ts uses, so both cases stay in sync through one route.
    logout: () => clearAuthToken(),
  }

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
