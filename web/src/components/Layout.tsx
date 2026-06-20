import { useTranslation } from 'react-i18next'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../hooks/api'
import '../styles/layout.css'

export function Layout({ children }: { children: React.ReactNode }) {
  const { t } = useTranslation()
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const [isMobile, setIsMobile] = React.useState(window.innerWidth < 768)

  React.useEffect(() => {
    const handleResize = () => setIsMobile(window.innerWidth < 768)
    window.addEventListener('resize', handleResize)
    return () => window.removeEventListener('resize', handleResize)
  }, [])

  const handleLogout = async () => {
    await logout.mutateAsync()
    navigate('/login')
  }

  return (
    <div className="layout-container">
      {!isMobile && (
        <aside className="sidebar">
          <div className="sidebar-header">
            <h1 className="app-title">{t('app.title')}</h1>
            <p className="user-name">{user?.name}</p>
          </div>

          <nav className="sidebar-nav">
            <Link to="/home" className="nav-link">
              {t('nav.home')}
            </Link>
            <Link to="/categories" className="nav-link">
              {t('nav.categories')}
            </Link>
            <Link to="/transactions" className="nav-link">
              {t('nav.transactions')}
            </Link>
          </nav>

          <div className="sidebar-footer">
            <button onClick={handleLogout} className="btn-logout">
              {t('nav.logout')}
            </button>
          </div>
        </aside>
      )}

      <main className="main-content">
        {children}

        {isMobile && (
          <nav className="bottom-nav">
            <Link to="/home" className="nav-link">
              {t('nav.home')}
            </Link>
            <Link to="/categories" className="nav-link">
              {t('nav.categories')}
            </Link>
            <Link to="/transactions" className="nav-link">
              {t('nav.transactions')}
            </Link>
            <button onClick={handleLogout} className="nav-link danger">
              {t('nav.logout')}
            </button>
          </nav>
        )}
      </main>
    </div>
  )
}

// Needed for useEffect hook
import * as React from 'react'
