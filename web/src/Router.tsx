import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { QueryClientProvider, QueryClient } from '@tanstack/react-query'
import { setAuthToken } from './services/api'
import { Layout } from './components/Layout'
import { PrivateRoute } from './components/PrivateRoute'
import { TutorialProvider } from './context/TutorialContext'
import { LoginPage } from './pages/LoginPage'
import { RegisterPage } from './pages/RegisterPage'
import { VerifyEmailPage } from './pages/VerifyEmailPage'
import { EmailVerificationCallbackPage } from './pages/EmailVerificationCallbackPage'
import { ForgotPasswordPage } from './pages/ForgotPasswordPage'
import { ResetPasswordPage } from './pages/ResetPasswordPage'
import { HomePage } from './pages/HomePage'
import { CategoriesPage } from './pages/CategoriesPage'
import { TransactionsPage } from './pages/TransactionsPage'
import { ProfilePage } from './pages/ProfilePage'
import { SubscriptionPage } from './pages/SubscriptionPage'
import { PaymentMethodsPage } from './pages/PaymentMethodsPage'
import i18n from './i18n/config'
import { ReportsPage } from './pages/ReportsPage'

// Initialize i18n and auth
i18n.changeLanguage(localStorage.getItem('app_locale') || 'pt-BR')
setAuthToken(localStorage.getItem('auth_token'))

const queryClient = new QueryClient()

function AppRoutes() {
  return (
    <Routes>
      {/* Public routes */}
      <Route path="/login" element={<LoginPage />} />
      <Route path="/register" element={<RegisterPage />} />
      <Route path="/auth/verify-email" element={<VerifyEmailPage />} />
      <Route path="/auth/verify-email/:id/:hash" element={<EmailVerificationCallbackPage />} />
      <Route path="/auth/forgot-password" element={<ForgotPasswordPage />} />
      <Route path="/auth/reset-password" element={<ResetPasswordPage />} />

      {/* Protected routes */}
      <Route
        path="/home"
        element={
          <PrivateRoute>
            <Layout>
              <HomePage />
            </Layout>
          </PrivateRoute>
        }
      />
      <Route
        path="/categories"
        element={
          <PrivateRoute>
            <Layout>
              <CategoriesPage />
            </Layout>
          </PrivateRoute>
        }
      />
      <Route
        path="/transactions"
        element={
          <PrivateRoute>
            <Layout>
              <TransactionsPage />
            </Layout>
          </PrivateRoute>
        }
      />
      <Route
        path="/profile"
        element={
          <PrivateRoute>
            <Layout>
              <ProfilePage />
            </Layout>
          </PrivateRoute>
        }
      />
      <Route
        path="/subscription"
        element={
          <PrivateRoute>
            <Layout>
              <SubscriptionPage />
            </Layout>
          </PrivateRoute>
        }
      />
      <Route
        path="/payment"
        element={
          <PrivateRoute>
            <Layout>
              <PaymentMethodsPage />
            </Layout>
          </PrivateRoute>
        }
      />
      <Route
        path="/Reports"
        element={
          <PrivateRoute>
            <Layout>
              <ReportsPage />
            </Layout>
          </PrivateRoute>
        }
      />

      {/* Redirect root to home or login */}
      <Route path="/" element={<Navigate to="/home" replace />} />
    </Routes>
  )
}

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <TutorialProvider>
          <AppRoutes />
        </TutorialProvider>
      </BrowserRouter>
    </QueryClientProvider>
  )
}

export default App
