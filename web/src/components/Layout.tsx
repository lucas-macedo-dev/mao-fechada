import { useTranslation } from "react-i18next";
import { Link, useNavigate } from "react-router-dom";
import { useAuth } from "../hooks/api";
import { useTutorial } from "../context/TutorialContext";
import { TutorialChecklist } from "./tutorial/TutorialChecklist";
import appLogo from "../assets/icon_mao_fechada.png";
import "../styles/layout.css";
import { Box, Group, Text, ActionIcon, Menu, Avatar, Tooltip } from "@mantine/core";

export function Layout({ children }: Readonly<{ children: React.ReactNode }>) {
  const { t } = useTranslation();
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const { openChecklist } = useTutorial();
  const [isMobile, setIsMobile] = React.useState(window.innerWidth < 768);

  React.useEffect(() => {
    const handleResize = () => setIsMobile(window.innerWidth < 768);
    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  const handleLogout = async () => {
    await logout.mutateAsync();
    navigate("/login");
  };

  return (
    <div className="layout-container">
      <TutorialChecklist />
      {!isMobile && (
        <aside className="sidebar">
          <div className="sidebar-header">
            <div className="sidebar-brand">
              <img src={appLogo} alt={t("app.title")} className="sidebar-logo" />
              <h1 className="app-title">{t("app.title")}</h1>
            </div>
            <Link to="/profile" className="user-identity" aria-label={t("nav.profile")}>
              {user?.profile_photo_url ? (
                <img src={user.profile_photo_url} alt={user.name} className="user-avatar" />
              ) : (
                <span className="user-avatar user-avatar-fallback" aria-hidden="true">
                  <i className="fa-solid fa-user" />
                </span>
              )}
              <p className="user-name">{user?.name}</p>
            </Link>
          </div>

          <nav className="sidebar-nav">
            <Link to="/home" className="nav-link">
              <i className="fa-solid fa-house" aria-hidden="true"></i>
              &nbsp;{t("nav.home")}
            </Link>
            <Link to="/categories" className="nav-link">
              <i className="fa-solid fa-list" aria-hidden="true"></i>
              &nbsp;{t("nav.categories")}
            </Link>
            <Link to="/transactions" className="nav-link">
              <i className="fa-solid fa-receipt" aria-hidden="true"></i>
              &nbsp;{t("nav.transactions")}
            </Link>
            <Link to="/subscription" className="nav-link">
              <i className="fa-solid fa-bell" aria-hidden="true" />
              &nbsp;{t("nav.subscription")}
            </Link>
          </nav>

          <div className="sidebar-footer">
            <Tooltip label={t("tutorial.title")} position="right" withArrow>
              <ActionIcon
                variant="subtle"
                size="lg"
                onClick={openChecklist}
                aria-label={t("tutorial.title")}
                style={{ marginBottom: 8 }}
              >
                <i className="fa-solid fa-circle-question" />
              </ActionIcon>
            </Tooltip>
            <button onClick={handleLogout} className="btn-logout">
              {t("nav.logout")}
            </button>
          </div>
        </aside>
      )}

      {isMobile && (
        <Box className="mobile-top-bar">
          <Group gap="xs" align="center">
            <img
              src={appLogo}
              alt={t("app.title")}
              style={{ width: 32, height: 32, objectFit: "contain" }}
            />
            <Text fw={700} size="md">
              {t("app.title")}
            </Text>
          </Group>

          <Menu shadow="md" width={200} position="bottom-end" withArrow>
            <Menu.Target>
              <ActionIcon variant="subtle" size="lg" aria-label={t("nav.profile")}>
                {user?.profile_photo_url ? (
                  <Avatar
                    src={user.profile_photo_url}
                    alt={user.name}
                    size={32}
                    radius="xl"
                  />
                ) : (
                  <Avatar size={32} radius="xl">
                    <i className="fa-solid fa-user" />
                  </Avatar>
                )}
              </ActionIcon>
            </Menu.Target>

            <Menu.Dropdown>
              <Menu.Label>{user?.name}</Menu.Label>
              <Menu.Item
                component={Link}
                to="/profile"
                leftSection={<i className="fa-solid fa-user" />}
              >
                {t("nav.profile")}
              </Menu.Item>
              <Menu.Item
                component={Link}
                to="/subscription"
                leftSection={<i className="fa-solid fa-bell" />}
              >
                {t("nav.subscription")}
              </Menu.Item>
              <Menu.Item
                leftSection={<i className="fa-solid fa-circle-question" />}
                onClick={openChecklist}
              >
                {t("tutorial.title")}
              </Menu.Item>
              <Menu.Divider />
              <Menu.Item
                color="red"
                leftSection={<i className="fa-solid fa-right-from-bracket" />}
                onClick={handleLogout}
              >
                {t("nav.logout")}
              </Menu.Item>
            </Menu.Dropdown>
          </Menu>
        </Box>
      )}

      <main className="main-content">
        {children}

        {isMobile && (
          <nav className="bottom-nav">
            <Link to="/home" className="nav-link">
              <i className="fa-solid fa-house" aria-hidden="true"></i>
              &nbsp;{t("nav.home")}
            </Link>
            <Link to="/categories" className="nav-link">
              <i className="fa-solid fa-list" aria-hidden="true"></i>
              &nbsp;{t("nav.categories")}
            </Link>
            <Link to="/transactions" className="nav-link">
              <i className="fa-solid fa-receipt" aria-hidden="true"></i>
              {t("nav.transactions")}
            </Link>
          </nav>
        )}
      </main>
    </div>
  );
}

// Needed for useEffect hook
import * as React from "react";
