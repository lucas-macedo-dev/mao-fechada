import { useAuth } from '../hooks/api'
import { Navigate } from 'react-router-dom'

export function PrivateRoute({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, me, user } = useAuth()

  if (me.isLoading) {
    return <div>Loading...</div>
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />
  }

  if (user && !user.email_verified_at) {
    return <Navigate to="/auth/verify-email" replace />
  }

  return children
}
