import { useMutation } from '@tanstack/react-query'
import { login, type LoginPayload, type LoginResponse } from '../../api/auth'
import type { ApiError } from '../../api/client'
import { useAuth } from './useAuth'

export function useLogin() {
  const auth = useAuth()

  return useMutation<LoginResponse, ApiError, LoginPayload>({
    mutationFn: login,
    onSuccess: (response) => {
      auth.login(response.token)
    },
  })
}
