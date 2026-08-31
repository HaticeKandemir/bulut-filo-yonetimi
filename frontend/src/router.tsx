import { createBrowserRouter } from 'react-router'
import { RootLayout } from './components/RootLayout'
import { ImportBatchPage } from './pages/ImportBatchPage'
import { ImportsPage } from './pages/ImportsPage'
import { HomePage } from './pages/HomePage'
import { VehiclesPage } from './pages/VehiclesPage'

export const router = createBrowserRouter([
  {
    element: <RootLayout />,
    children: [
      { path: '/', element: <HomePage /> },
      { path: '/vehicles', element: <VehiclesPage /> },
      { path: '/imports', element: <ImportsPage /> },
      { path: '/imports/:id', element: <ImportBatchPage /> },
    ],
  },
])
