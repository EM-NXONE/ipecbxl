/**
 * /admin/preadmis — candidatures validées + frais de dossier payés.
 */
import { createFileRoute } from "@tanstack/react-router";
import { ComptesTable } from "@/components/AdminComptesTable";

export const Route = createFileRoute("/admin/_authenticated/preadmis")({
  component: () => (
    <ComptesTable
      title="Préadmis"
      subtitle="Dossier validé, frais de dossier payés, en attente du paiement de la 1ère tranche."
      categorie="preadmis"
    />
  ),
  head: () => ({ meta: [{ title: "IPEC | Préadmis" }] }),
});
