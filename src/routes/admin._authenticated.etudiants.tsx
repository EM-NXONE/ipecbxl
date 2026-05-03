/**
 * /admin/etudiants — comptes en catégorie "etudiant" (1ère tranche payée).
 * Rend l'Outlet pour la sous-route /admin/etudiants/$numero.
 */
import { createFileRoute, Outlet, useRouterState } from "@tanstack/react-router";
import { ComptesTable } from "@/components/AdminComptesTable";

export const Route = createFileRoute("/admin/_authenticated/etudiants")({
  component: AdminEtudiantsPage,
  head: () => ({ meta: [{ title: "IPEC | Étudiants" }] }),
});

function AdminEtudiantsPage() {
  const showingDetail = useRouterState({
    select: (s) => s.matches.some((m) => m.routeId === "/admin/_authenticated/etudiants/$numero"),
  });
  if (showingDetail) return <Outlet />;
  return (
    <ComptesTable
      title="Étudiants"
      subtitle="1ère tranche de scolarité payée."
      categorie="etudiant"
    />
  );
}
