/**
 * Layout racine de l'espace étudiant.
 */
import { createFileRoute, Outlet } from "@tanstack/react-router";
import { EtudiantAuthProvider } from "@/lib/auth-etudiant";

export const Route = createFileRoute("/etudiant")({
  component: EtudiantRoot,
  head: () => ({
    meta: [
      { title: "Espace étudiant — IPEC" },
      { name: "robots", content: "noindex, nofollow" },
    ],
  }),
});

function EtudiantRoot() {
  return (
    <EtudiantAuthProvider>
      <Outlet />
    </EtudiantAuthProvider>
  );
}
