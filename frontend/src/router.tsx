import { createBrowserRouter } from 'react-router'
import { HomePage } from './pages/HomePage'
import { VehiclesPage } from './pages/VehiclesPage'

export const router = createBrowserRouter([
  {
    path: '/',
    element: <HomePage />,
  },
  {
    path: '/vehicles',
    element: <VehiclesPage />,
  },
])
