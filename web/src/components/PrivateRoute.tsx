import { useAuth } from '../hooks/api'
import { Navigate } from 'react-router-dom'

export function PrivateRoute({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, me } = useAuth()

  if (me.isLoading) {
    return <div>Loading...</div>
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />
  }

  return children
}
