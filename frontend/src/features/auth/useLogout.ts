import { useMutation, useQueryClient } from '@tanstack/react-query'
import { logout } from '../../api/auth'
import { useAuth } from './useAuth'

export function useLogout() {
  const auth = useAuth()
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: logout,
    // Clear the local session and cached data even if the API call itself
    // fails (e.g. the token was already invalid) — logging out must never
    // get stuck because the server-side revoke didn't succeed.
    onSettled: () => {
      auth.logout()
      queryClient.clear()
    },
  })
}
