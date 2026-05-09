/**
 * Layout authentifié de l'espace étudiant.
 */
import { createFileRoute, Outlet, Navigate, useNavigate } from "@tanstack/react-router";
import { useEffect } from "react";
import { LayoutDashboard, Receipt, FolderOpen, User } from "lucide-react";
import { PortalLayout, type PortalNavItem } from "@/components/PortalLayout";
import { useEtudiantAuth } from "@/lib/auth-etudiant";
import { useIdleLogout } from "@/hooks/use-idle-logout";

export const Route = createFileRoute("/etudiant/_authenticated")({
  component: EtudiantAuthenticatedLayout,
});

const NAV: PortalNavItem[] = [
  { to: "/etudiant", label: "Tableau de bord", icon: <LayoutDashboard size={16} />, exact: true },
  { to: "/etudiant/factures", label: "Factures", icon: <Receipt size={16} /> },
  { to: "/etudiant/documents", label: "Documents", icon: <FolderOpen size={16} /> },
  { to: "/etudiant/profil", label: "Profil", icon: <User size={16} /> },
];

// Délai de revalidation périodique de la session (l'admin peut suspendre/archiver
// le compte à tout moment côté back — on veut décrocher l'étudiant rapidement).
const SESSION_POLL_MS = 30_000;
// Déconnexion auto après 3 h sans aucune interaction utilisateur sur l'onglet.
const IDLE_LOGOUT_MS = 3 * 60 * 60 * 1000;

function EtudiantAuthenticatedLayout() {
  const { user, loading, logout, refresh } = useEtudiantAuth();
  const navigate = useNavigate();

  // Revalide la session : (1) périodiquement, (2) au retour de focus / changement
  // de visibilité onglet. Si l'admin a suspendu le compte, /me.php renvoie 401 →
  // refresh() met user à null → l'écran ci-dessous redirige vers /login.
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
      userLabel={userLabelWithBadge}
      onLogout={async () => {
        await logout();
        navigate({ to: "/etudiant/login" });
      }}
    >
      <Outlet />
    </PortalLayout>
  );
}
