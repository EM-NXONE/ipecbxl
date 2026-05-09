/**
 * Layout authentifié de l'admin : garde sur user + PortalLayout avec sidebar.
 */
import { createFileRoute, Outlet, Navigate, useNavigate } from "@tanstack/react-router";
import { useEffect } from "react";
import { LayoutDashboard, FileText, UserCheck, GraduationCap, XCircle, Users } from "lucide-react";
import { PortalLayout, type PortalNavItem } from "@/components/PortalLayout";
import { useAdminAuth } from "@/lib/auth-admin";
import { useIdleLogout } from "@/hooks/use-idle-logout";

const SESSION_POLL_MS = 30_000;
const IDLE_LOGOUT_MS = 3 * 60 * 60 * 1000;

export const Route = createFileRoute("/admin/_authenticated")({
  component: AdminAuthenticatedLayout,
});

const NAV: PortalNavItem[] = [
  { to: "/admin", label: "Tableau de bord", icon: <LayoutDashboard size={16} />, exact: true },
  { to: "/admin/candidatures", label: "Candidatures", icon: <FileText size={16} /> },
  { to: "/admin/preadmis", label: "Préadmis", icon: <UserCheck size={16} /> },
  { to: "/admin/etudiants", label: "Étudiants", icon: <GraduationCap size={16} /> },
  { to: "/admin/refuses", label: "Refusés", icon: <XCircle size={16} /> },
  { to: "/admin/comptes", label: "Tous les comptes", icon: <Users size={16} /> },
];

function AdminAuthenticatedLayout() {
  const { user, loading, logout, refresh } = useAdminAuth();
  const navigate = useNavigate();

  // Revalide la session périodiquement et au retour de focus / visibilité.
  useEffect(() => {
    if (!user) return;
    const id = window.setInterval(() => { refresh(); }, SESSION_POLL_MS);
    const onVisible = () => { if (document.visibilityState === "visible") refresh(); };
    window.addEventListener("focus", refresh);
    document.addEventListener("visibilitychange", onVisible);
    return () => {
      window.clearInterval(id);
      window.removeEventListener("focus", refresh);
      document.removeEventListener("visibilitychange", onVisible);
    };
  }, [user, refresh]);

  // Déconnexion auto après 3 h sans interaction réelle.
  useIdleLogout(!!user, IDLE_LOGOUT_MS, () => {
    logout().finally(() => navigate({ to: "/admin/login" }));
  });

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background text-muted-foreground text-sm">
        Chargement…
      </div>
    );
  }
  if (!user) {
    return <Navigate to="/admin/login" />;
  }

  return (
    <PortalLayout
      brandSubtitle="Administration" brandHref="/admin"
      nav={NAV}
      userLabel={user.username}
      onLogout={async () => {
        await logout();
        navigate({ to: "/admin/login" });
      }}
    >
      <Outlet />
    </PortalLayout>
  );
}
