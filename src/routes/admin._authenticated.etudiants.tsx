/**
 * /admin/etudiants — comptes en catégorie "etudiant" (1ère tranche payée).
 */
import { createFileRoute } from "@tanstack/react-router";
import { ComptesTable } from "@/components/AdminComptesTable";

export const Route = createFileRoute("/admin/_authenticated/etudiants")({
  component: () => (
    <ComptesTable
      title="Étudiants"
      subtitle="1ère tranche de scolarité payée."
      categorie="etudiant"
    />
  ),
  head: () => ({ meta: [{ title: "IPEC | Étudiants" }] }),
});
