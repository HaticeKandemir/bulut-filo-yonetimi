import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { Navigate } from 'react-router'
import { z } from 'zod'
import { Card } from '../components/Card'
import { useAuth } from '../features/auth/useAuth'
import { useLogin } from '../features/auth/useLogin'

const loginSchema = z.object({
  email: z.string().trim().min(1, { message: 'required' }).email({ message: 'required' }),
  password: z.string().min(1, { message: 'required' }),
})

type LoginFormInput = z.input<typeof loginSchema>
type LoginFormValues = z.output<typeof loginSchema>

const controlClassName =
  'mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500'

export function LoginPage() {
  const { t } = useTranslation()
  const { token } = useAuth()
  const login = useLogin()
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LoginFormInput, unknown, LoginFormValues>({ resolver: zodResolver(loginSchema) })

  if (token !== null) {
    return <Navigate to="/" replace />
  }

  const onSubmit = handleSubmit((values) => {
    login.mutate(values)
  })

  return (
    <main className="mx-auto flex max-w-sm flex-col gap-6 px-4 py-24">
      <h1 className="text-center text-2xl font-semibold tracking-tight text-gray-900">{t('app.title')}</h1>
      <Card className="p-6">
        <form onSubmit={(event) => void onSubmit(event)} className="flex flex-col gap-4">
          <div>
            <label htmlFor="login-email" className="text-sm font-medium text-gray-700">
              {t('auth.login.email')}
            </label>
            <input id="login-email" type="email" autoComplete="username" {...register('email')} className={controlClassName} />
            {errors.email && <p className="mt-1 text-sm text-red-600">{t('auth.login.errors.required')}</p>}
          </div>
          <div>
            <label htmlFor="login-password" className="text-sm font-medium text-gray-700">
              {t('auth.login.password')}
            </label>
            <input
              id="login-password"
              type="password"
              autoComplete="current-password"
              {...register('password')}
              className={controlClassName}
            />
            {errors.password && <p className="mt-1 text-sm text-red-600">{t('auth.login.errors.required')}</p>}
          </div>
          {login.isError && <p className="text-sm text-red-600">{t('auth.login.errors.invalidCredentials')}</p>}
          <button
            type="submit"
            disabled={login.isPending}
            className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
          >
            {login.isPending ? t('auth.login.submitting') : t('auth.login.submit')}
          </button>
        </form>
      </Card>
    </main>
  )
}
