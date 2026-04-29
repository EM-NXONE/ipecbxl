/**
 * /etudiant/activer/$token — activation initiale du compte (création du mot de passe).
 * Même formulaire que reset, endpoint différent.
 */
import { createFileRoute, Link, useNavigate } from "@tanstack/react-router";
import { useState, type FormEvent } from "react";
import { PortalAuthShell } from "@/components/PortalLayout";
import { etuApi } from "@/lib/api";

export const Route = createFileRoute("/etudiant/activer/$token")({
  component: ActiverPage,
});

function ActiverPage() {
  const { token } = Route.useParams();
  const navigate = useNavigate();
  const [pwd, setPwd] = useState("");
  const [pwd2, setPwd2] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    if (pwd !== pwd2) {
      setError("Les mots de passe ne correspondent pas.");
      return;
    }
    setSubmitting(true);
    try {
      await etuApi.post("/activer.php", { token, password: pwd, password2: pwd2 });
      navigate({ to: "/etudiant/login" });
    } catch (err) {
      setError(err instanceof Error ? err.message : "Lien invalide ou expiré.");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <PortalAuthShell brandSubtitle="Espace étudiant">
      <div className="bg-card border border-border/40 rounded-md p-8 shadow-elegant">
        <h1 className="font-display text-2xl text-cream text-center mb-2">Activation du compte</h1>
        <p className="text-center text-sm text-muted-foreground mb-6">
          Bienvenue à l'IPEC. Définis ton mot de passe pour accéder à ton espace.
          Au moins 10 caractères, avec majuscules, minuscules et chiffres.
        </p>

        {error && (
          <div className="mb-4 px-3 py-2 rounded-sm bg-destructive/10 border border-destructive/30 text-sm text-destructive">
            {error}
          </div>
        )}

        <form onSubmit={onSubmit} className="space-y-4">
          <Field id="pwd" label="Mot de passe" value={pwd} onChange={setPwd} />
          <Field id="pwd2" label="Confirmer" value={pwd2} onChange={setPwd2} />
          <button
            type="submit"
            disabled={submitting}
            className="w-full py-2.5 rounded-sm bg-gradient-blue text-ink text-sm font-medium hover:opacity-90 transition-opacity disabled:opacity-50"
          >
            {submitting ? "Validation…" : "Activer mon compte"}
          </button>
        </form>

        <p className="text-center text-xs mt-6">
          <Link to="/etudiant/login" className="text-blue hover:underline">
            Déjà activé ? Se connecter
          </Link>
        </p>
      </div>
    </PortalAuthShell>
  );
}

function Field({ id, label, value, onChange }: { id: string; label: string; value: string; onChange: (v: string) => void }) {
  return (
    <div>
      <label htmlFor={id} className="block text-xs uppercase tracking-wider text-muted-foreground mb-1.5">
        {label}
      </label>
      <input
        id={id}
        type="password"
        required
        minLength={10}
        autoComplete="new-password"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        className="w-full px-3 py-2.5 bg-input/40 border border-border rounded-sm text-cream focus:outline-none focus:ring-2 focus:ring-ring"
      />
    </div>
  );
}
