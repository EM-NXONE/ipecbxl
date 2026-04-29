/**
 * /admin/login — formulaire de connexion administrateur.
 */
import { createFileRoute, useNavigate, Navigate } from "@tanstack/react-router";
import { useState, type FormEvent } from "react";
import { PortalAuthShell } from "@/components/PortalLayout";
import { useAdminAuth } from "@/lib/auth-admin";

export const Route = createFileRoute("/admin/login")({
  component: AdminLoginPage,
});

function AdminLoginPage() {
  const { user, loading, login } = useAdminAuth();
  const navigate = useNavigate();
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  if (loading) {
    return (
      <PortalAuthShell brandSubtitle="Administration" brandHref="/admin">
        <p className="text-center text-sm text-muted-foreground">Chargement…</p>
      </PortalAuthShell>
    );
  }
  if (user) {
    return <Navigate to="/admin" />;
  }

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      await login(username, password);
      navigate({ to: "/admin" });
    } catch (err) {
      setError(err instanceof Error ? err.message : "Identifiants invalides.");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <PortalAuthShell brandSubtitle="Administration" brandHref="/admin">
      <div className="bg-card border border-border/40 rounded-md p-8 shadow-elegant">
        <h1 className="font-display text-2xl text-cream text-center mb-2">Espace administration</h1>
        <p className="text-center text-sm text-muted-foreground mb-6">
          Accès restreint au personnel autorisé.
        </p>

        {error && (
          <div className="mb-4 px-3 py-2 rounded-sm bg-destructive/10 border border-destructive/30 text-sm text-destructive">
            {error}
          </div>
        )}

        <form onSubmit={onSubmit} className="space-y-4">
          <div>
            <label htmlFor="username" className="block text-xs uppercase tracking-wider text-muted-foreground mb-1.5">
              Identifiant
            </label>
            <input
              id="username"
              type="text"
              autoFocus
              autoComplete="username"
              required
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              className="w-full px-3 py-2.5 bg-input/40 border border-border rounded-sm text-cream focus:outline-none focus:ring-2 focus:ring-ring"
            />
          </div>
          <div>
            <label htmlFor="password" className="block text-xs uppercase tracking-wider text-muted-foreground mb-1.5">
              Mot de passe
            </label>
            <input
              id="password"
              type="password"
              autoComplete="current-password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full px-3 py-2.5 bg-input/40 border border-border rounded-sm text-cream focus:outline-none focus:ring-2 focus:ring-ring"
            />
          </div>
          <button
            type="submit"
            disabled={submitting}
            className="w-full py-2.5 rounded-sm bg-gradient-blue text-ink text-sm font-medium hover:opacity-90 transition-opacity disabled:opacity-50"
          >
            {submitting ? "Connexion…" : "Se connecter"}
          </button>
        </form>
      </div>
    </PortalAuthShell>
  );
}
