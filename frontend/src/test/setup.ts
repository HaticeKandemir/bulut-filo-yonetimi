import { cleanup } from '@testing-library/react'
import { afterEach } from 'vitest'
import '@testing-library/jest-dom/vitest'
import '../i18n'

// vite.config.ts sets test.globals=false (explicit imports everywhere else
// in this codebase), so @testing-library/react's auto-cleanup — which
// detects a *global* afterEach — never registers on its own.
afterEach(() => {
  cleanup()
})
