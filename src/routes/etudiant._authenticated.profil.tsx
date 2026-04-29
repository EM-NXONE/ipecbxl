/**
 * /etudiant/profil — profil + changement de mot de passe (stub).
 */
import { createFileRoute } from "@tanstack/react-router";
import { useEtudiantAuth } from "@/lib/auth-etudiant";

export const Route = createFileRoute("/etudiant/_authenticated/profil")({
  component: EtudiantProfilPage,
});

function EtudiantProfilPage() {
  const { user } = useEtudiantAuth();
  return (
    <div>
      <h1 className="font-display text-3xl text-cream mb-2">Mon profil</h1>
      <p className="text-sm text-muted-foreground mb-8">Tes informations personnelles.</p>

      <div className="bg-card border border-border/40 rounded-md p-6 space-y-3">
        <Row label="Email" value={user?.email} />
        <Row label="Prénom" value={user?.prenom} />
        <Row label="Nom" value={user?.nom} />
        <Row label="N° étudiant" value={user?.numero_etudiant || "—"} />
      </div>

      <p className="mt-6 text-sm text-muted-foreground">
        ⚙️ Le formulaire de changement de mot de passe sera ajouté à la prochaine
        itération (endpoint <code className="text-blue">/api/change-password.php</code>).
      </p>
    </div>
  );
}

function Row({ label, value }: { label: string; value?: string | null }) {
  return (
    <div className="flex justify-between gap-4 text-sm border-b border-border/30 pb-2 last:border-0 last:pb-0">
      <span className="text-muted-foreground">{label}</span>
      <span className="text-cream font-medium">{value || "—"}</span>
    </div>
  );
}
