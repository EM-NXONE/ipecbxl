/**
 * /etudiant/login — connexion étudiant.
 * Reproduit le formulaire PHP actuel : email + identité (prénom/nom/date naissance) + mot de passe.
 * L'identité supplémentaire est une protection contre le credential stuffing brut.
 */
import { createFileRoute, Link, Navigate, useNavigate } from "@tanstack/react-router";
import { useState, type FormEvent } from "react";
import { PortalAuthShell } from "@/components/PortalLayout";
import { useEtudiantAuth } from "@/lib/auth-etudiant";

export const Route = createFileRoute("/etudiant/login")({
  component: EtudiantLoginPage,
});

function EtudiantLoginPage() {
  const { user, loading, login } = useEtudiantAuth();
  const navigate = useNavigate();
  const [numero, setNumero] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  if (loading) {
    return (
      <PortalAuthShell brandSubtitle="Espace étudiant">
        <p className="text-center text-sm text-muted-foreground">Chargement…</p>
      </PortalAuthShell>
    );
  }
  if (user) {
    return <Navigate to="/etudiant" />;
  }

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      await login({
        numero_etudiant: numero.trim().toUpperCase(),
        password,
      });
      navigate({ to: "/etudiant" });
    } catch (err) {
      setError(err instanceof Error ? err.message : "Identifiants invalides ou compte non activé.");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <PortalAuthShell brandSubtitle="Espace étudiant">
      <div className="bg-card border border-border/40 rounded-md p-8 shadow-elegant">
        <h1 className="font-display text-2xl text-cream text-center mb-2">Connexion</h1>
        <p className="text-center text-sm text-muted-foreground mb-6">
          Accède à ton dossier, tes factures et tes documents administratifs.
        </p>

        {error && (
          <div className="mb-4 px-3 py-2 rounded-sm bg-destructive/10 border border-destructive/30 text-sm text-destructive">
            {error}
          </div>
        )}

        <form onSubmit={onSubmit} className="space-y-4">
          <Field
            id="numero_etudiant"
            label="Numéro étudiant"
            type="text"
            autoComplete="username"
            value={numero}
            onChange={setNumero}
            placeholder="IPEC-ETU-2026-XXXX"
          />
          <Field
            id="password"
            label="Mot de passe"
            type="password"
            autoComplete="current-password"
            value={password}
            onChange={setPassword}
          />

          <button
            type="submit"
            disabled={submitting}
            className="w-full py-2.5 rounded-sm bg-gradient-blue text-ink text-sm font-medium hover:opacity-90 transition-opacity disabled:opacity-50"
          >
            {submitting ? "Connexion…" : "Se connecter"}
          </button>
        </form>

        <div className="mt-6 space-y-2 text-center text-xs">
          <p className="text-muted-foreground">
            Mot de passe oublié ? Contacte{" "}
            <a href="mailto:admission@ipec.school" className="text-blue hover:underline">
              admission@ipec.school
            </a>
            <br />
            pour recevoir un nouveau lien d'activation.
          </p>
          <p className="text-muted-foreground">
            Pas encore de compte ? Il est créé par l'administration de l'IPEC<br />
            après réception de ta candidature.
          </p>
        </div>
      </div>
    </PortalAuthShell>
  );
}

function Field({
  id,
  label,
  type,
  autoComplete,
  value,
  onChange,
}: {
  id: string;
  label: string;
  type: string;
  autoComplete?: string;
  value: string;
  onChange: (v: string) => void;
}) {
  return (
    <div>
      <label htmlFor={id} className="block text-xs uppercase tracking-wider text-muted-foreground mb-1.5">
        {label}
      </label>
      <input
        id={id}
        type={type}
        autoComplete={autoComplete}
        required
        value={value}
        onChange={(e) => onChange(e.target.value)}
        className="w-full px-3 py-2.5 bg-input/40 border border-border rounded-sm text-cream focus:outline-none focus:ring-2 focus:ring-ring"
      />
    </div>
  );
}
