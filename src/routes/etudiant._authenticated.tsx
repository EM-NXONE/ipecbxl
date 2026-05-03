/**
 * Layout authentifié de l'espace étudiant.
 */
import { createFileRoute, Outlet, Navigate, useNavigate } from "@tanstack/react-router";
import { LayoutDashboard, Receipt, FolderOpen, User } from "lucide-react";
import { PortalLayout, type PortalNavItem } from "@/components/PortalLayout";
import { useEtudiantAuth } from "@/lib/auth-etudiant";

export const Route = createFileRoute("/etudiant/_authenticated")({
  component: EtudiantAuthenticatedLayout,
});

const NAV: PortalNavItem[] = [
  { to: "/etudiant", label: "Tableau de bord", icon: <LayoutDashboard size={16} />, exact: true },
  { to: "/etudiant/factures", label: "Factures", icon: <Receipt size={16} /> },
  { to: "/etudiant/documents", label: "Documents", icon: <FolderOpen size={16} /> },
  { to: "/etudiant/profil", label: "Profil", icon: <User size={16} /> },
];

function EtudiantAuthenticatedLayout() {
  const { user, loading, logout } = useEtudiantAuth();
  const navigate = useNavigate();

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background text-muted-foreground text-sm">
        Chargement…
      </div>
    );
  }
  if (!user) {
    return <Navigate to="/etudiant/login" />;
  }

  const cat = user.categorie ?? "candidat";
  const catLabel = cat === "etudiant" ? "Étudiant" : cat === "preadmis" ? "Préadmis" : "Candidat";
  const userLabel = `${user.prenom} ${user.nom}`.trim() || user.email;
  const userLabelWithBadge = `${userLabel} · ${catLabel}`;

  return (
    <PortalLayout
      brandSubtitle="Espace étudiant" brandHref="/etudiant"
      nav={NAV}
      userLabel={userLabel}
      onLogout={async () => {
        await logout();
        navigate({ to: "/etudiant/login" });
      }}
    >
      <Outlet />
    </PortalLayout>
  );
}
