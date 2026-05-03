/**
 * Layout authentifié de l'admin : garde sur user + PortalLayout avec sidebar.
 */
import { createFileRoute, Outlet, Navigate, useNavigate } from "@tanstack/react-router";
import { LayoutDashboard, FileText, UserCheck, GraduationCap, XCircle, Users } from "lucide-react";
import { PortalLayout, type PortalNavItem } from "@/components/PortalLayout";
import { useAdminAuth } from "@/lib/auth-admin";

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
  const { user, loading, logout } = useAdminAuth();
  const navigate = useNavigate();

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
