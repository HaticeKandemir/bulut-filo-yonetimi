import { createBrowserRouter } from 'react-router'
import { RequireAuth } from './components/RequireAuth'
import { RootLayout } from './components/RootLayout'
import { FleetMapPage } from './pages/FleetMapPage'
import { ImportBatchPage } from './pages/ImportBatchPage'
import { ImportsPage } from './pages/ImportsPage'
import { HomePage } from './pages/HomePage'
import { InstitutionsPage } from './pages/InstitutionsPage'
import { LoginPage } from './pages/LoginPage'
import { RoutesPage } from './pages/RoutesPage'
import { VehicleDetailPage } from './pages/VehicleDetailPage'
import { VehiclesPage } from './pages/VehiclesPage'

export const router = createBrowserRouter([
  { path: '/login', element: <LoginPage /> },
  {
    element: (
      <RequireAuth>
        <RootLayout />
      </RequireAuth>
    ),
    children: [
      { path: '/', element: <HomePage /> },
      { path: '/vehicles', element: <VehiclesPage /> },
      { path: '/vehicles/:id', element: <VehicleDetailPage /> },
      { path: '/fleet-map', element: <FleetMapPage /> },
      { path: '/routes', element: <RoutesPage /> },
      { path: '/institutions', element: <InstitutionsPage /> },
      { path: '/imports', element: <ImportsPage /> },
      { path: '/imports/:id', element: <ImportBatchPage /> },
    ],
  },
])
