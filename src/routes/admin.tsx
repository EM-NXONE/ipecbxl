/**
 * Layout racine de l'espace administrateur.
 * Fournit le contexte d'auth à tout le sous-arbre /admin/*.
 */
import { createFileRoute, Outlet } from "@tanstack/react-router";
import { AdminAuthProvider } from "@/lib/auth-admin";

export const Route = createFileRoute("/admin")({
  component: AdminRoot,
  head: () => ({
    meta: [
      { title: "Administration — IPEC" },
      { name: "robots", content: "noindex, nofollow" },
    ],
  }),
});

function AdminRoot() {
  return (
    <AdminAuthProvider>
      <Outlet />
    </AdminAuthProvider>
  );
}
