/**
 * /admin/comptes — vue globale de tous les comptes (toutes catégories).
 */
import { createFileRoute } from "@tanstack/react-router";
import { ComptesTable } from "@/components/AdminComptesTable";

export const Route = createFileRoute("/admin/_authenticated/comptes")({
  component: () => (
    <ComptesTable
      title="Tous les comptes"
      subtitle="candidats, préadmis et étudiants confondus."
      showCategorie
    />
  ),
  head: () => ({ meta: [{ title: "IPEC | Comptes" }] }),
});
