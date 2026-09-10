import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { AuthProvider } from '../features/auth/AuthProvider'
import { LoginPage } from './LoginPage'

function renderLoginPage() {
  const queryClient = new QueryClient()

  return render(
    <QueryClientProvider client={queryClient}>
      <AuthProvider>
        <LoginPage />
      </AuthProvider>
    </QueryClientProvider>,
  )
}

describe('LoginPage', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('shows a validation error when submitting without a password', async () => {
    const user = userEvent.setup()
    renderLoginPage()

    await user.type(screen.getByLabelText('E-posta'), 'demo@example.com')
    await user.click(screen.getByRole('button', { name: 'Giriş Yap' }))

    expect(await screen.findByText('Bu alan zorunludur.')).toBeInTheDocument()
  })

  it('shows an error message when the API rejects the credentials', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue(new Response(JSON.stringify({ message: 'Invalid email or password.' }), { status: 401 })),
    )

    const user = userEvent.setup()
    renderLoginPage()

    await user.type(screen.getByLabelText('E-posta'), 'demo@example.com')
    await user.type(screen.getByLabelText('Şifre'), 'wrong-password')
    await user.click(screen.getByRole('button', { name: 'Giriş Yap' }))

    expect(await screen.findByText('E-posta veya şifre hatalı.')).toBeInTheDocument()
  })
})
