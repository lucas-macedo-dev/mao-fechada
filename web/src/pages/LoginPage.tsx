import { useTranslation } from "react-i18next";
import { useAuth } from "../hooks/api";
import type { FormEvent } from "react";
import { useState } from "react";
import { useNavigate } from "react-router-dom";
import appLogo from "../assets/icon_mao_fechada.png";
import "../styles/auth.css";

export function LoginPage() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const { login, isAuthenticated } = useAuth();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");

  if (isAuthenticated) {
    navigate("/home");
  }

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError("");
    try {
      await login.mutateAsync({ email, password });
      navigate("/home");
    } catch (err: any) {
      setError(err?.response?.data?.error?.message || t("auth.failed"));
    }
  };

  return (
    <div className="auth-page">
      <div className="auth-card">
        <div className="auth-brand">
          <img src={appLogo} alt={t("app.title")} className="auth-logo" />
        </div>
        <h1>{t("app.title")}</h1>
        <p className="subtitle">{t("app.subtitle")}</p>

        <form onSubmit={handleSubmit} className="auth-form">
          <div className="form-group">
            <label htmlFor="email">{t("auth.email")}</label>
            <input
              id="email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
            />
          </div>

          <div className="form-group">
            <label htmlFor="password">{t("auth.password")}</label>
            <input
              id="password"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
          </div>

          {error && <div className="error-message">{error}</div>}

          <button
            type="submit"
            disabled={login.isPending}
            className="btn btn-primary"
          >
            {login.isPending ? t("common.loading") : t("auth.login")}
          </button>
        </form>

        <p className="auth-link">
          {t("auth.no_account")} <a href="/register">{t("auth.register")}</a>
        </p>
      </div>
    </div>
  );
}
