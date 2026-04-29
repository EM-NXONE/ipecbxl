/**
 * /etudiant/mot-de-passe-oublie — demande de réinitialisation.
 */
import { createFileRoute, Link } from "@tanstack/react-router";
import { useState, type FormEvent } from "react";
import { PortalAuthShell } from "@/components/PortalLayout";
import { etuApi } from "@/lib/api";

export const Route = createFileRoute("/etudiant/mot-de-passe-oublie")({
  component: ForgotPasswordPage,
});

function ForgotPasswordPage() {
  const [email, setEmail] = useState("");
  const [sent, setSent] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      await etuApi.post("/mot-de-passe-oublie.php", { email: email.trim().toLowerCase() });
      setSent(true);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Erreur, réessaie.");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <PortalAuthShell brandSubtitle="Espace étudiant" brandHref="/etudiant">
      <div className="bg-card border border-border/40 rounded-md p-8 shadow-elegant">
        <h1 className="font-display text-2xl text-cream text-center mb-2">Mot de passe oublié</h1>

        {sent ? (
          <div className="space-y-4">
            <p className="text-sm text-muted-foreground text-center">
              Si un compte existe avec cette adresse, un e-mail de réinitialisation vient
              de t'être envoyé.
            </p>
            <Link to="/etudiant/login" className="block text-center text-sm text-blue hover:underline">
              Retour à la connexion
            </Link>
          </div>
        ) : (
          <>
            <p className="text-center text-sm text-muted-foreground mb-6">
              Indique ton adresse e-mail. Un lien de réinitialisation te sera envoyé.
            </p>

            {error && (
              <div className="mb-4 px-3 py-2 rounded-sm bg-destructive/10 border border-destructive/30 text-sm text-destructive">
                {error}
              </div>
            )}

            <form onSubmit={onSubmit} className="space-y-4">
              <div>
                <label htmlFor="email" className="block text-xs uppercase tracking-wider text-muted-foreground mb-1.5">
                  Adresse e-mail
                </label>
                <input
                  id="email"
                  type="email"
                  required
                  autoComplete="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="w-full px-3 py-2.5 bg-input/40 border border-border rounded-sm text-cream focus:outline-none focus:ring-2 focus:ring-ring"
                />
              </div>
              <button
                type="submit"
                disabled={submitting}
                className="w-full py-2.5 rounded-sm bg-gradient-blue text-ink text-sm font-medium hover:opacity-90 transition-opacity disabled:opacity-50"
              >
                {submitting ? "Envoi…" : "Envoyer le lien"}
              </button>
            </form>

            <p className="text-center text-xs mt-6">
              <Link to="/etudiant/login" className="text-blue hover:underline">
                Retour à la connexion
              </Link>
            </p>
          </>
        )}
      </div>
    </PortalAuthShell>
  );
}
