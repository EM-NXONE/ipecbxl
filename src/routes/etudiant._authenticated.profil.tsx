/**
 * /etudiant/profil — identité + changement de mot de passe.
 */
import { createFileRoute } from "@tanstack/react-router";
import { useEffect, useState, type FormEvent } from "react";
import { etuApi } from "@/lib/api";
import { formatDate, formatDateTime } from "@/lib/format";

export const Route = createFileRoute("/etudiant/_authenticated/profil")({
  component: EtudiantProfilPage,
});

interface Profil {
  id: number; numero_etudiant: string | null;
  civilite: string | null; prenom: string; nom: string; email: string;
  date_naissance: string | null; nationalite: string | null; telephone: string | null;
  statut: string; derniere_connexion: string | null; created_at: string;
}

function EtudiantProfilPage() {
  const [profil, setProfil] = useState<Profil | null>(null);
  const [error, setError] = useState<string | null>(null);

  // form state
  const [current, setCurrent] = useState("");
  const [pwd, setPwd] = useState("");
  const [pwd2, setPwd2] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  useEffect(() => {
    etuApi.get<{ profil: Profil }>("/profil.php").then((r) => setProfil(r.profil)).catch((e) => setError(e.message));
  }, []);

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setFormError(null); setSuccess(null);
    // On compare les valeurs brutes (pas de trim : un mdp peut contenir des espaces)
    if (pwd.length === 0) { setFormError("Le nouveau mot de passe est requis."); return; }
    if (pwd !== pwd2) {
      setFormError(
        `Les deux mots de passe ne correspondent pas (saisi : ${pwd.length} caractères, confirmation : ${pwd2.length} caractères). Astuce : si votre navigateur a auto-rempli un champ, effacez-le et retapez les deux.`
      );
      return;
    }
    setSubmitting(true);
    try {
      // On envoie aussi password2 pour que la validation serveur soit la source de vérité.
      await etuApi.post("/change-password.php", { current, password: pwd, password2: pwd2 });
      setSuccess("Mot de passe mis à jour. Les autres sessions ont été déconnectées.");
      setCurrent(""); setPwd(""); setPwd2("");
    } catch (err) {
      setFormError(err instanceof Error ? err.message : "Erreur");
    } finally { setSubmitting(false); }
  };

  return (
    <div>
      <h1 className="font-display text-3xl text-cream mb-2">Mon profil</h1>
      <p className="text-sm text-muted-foreground mb-8">Tes informations personnelles et la sécurité de ton compte.</p>

      {error && <div className="mb-6 px-4 py-3 rounded-sm bg-destructive/10 border border-destructive/30 text-sm text-destructive">{error}</div>}

      <div className="bg-card border border-border/40 rounded-md p-6 mb-6">
        <h2 className="font-display text-lg text-cream mb-4">Identité</h2>
        {profil ? (
          <dl className="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3 text-sm">
            <Row label="Numéro étudiant" value={profil.numero_etudiant} mono />
            <Row label="Civilité" value={profil.civilite} />
            <Row label="Prénom · Nom" value={`${profil.prenom} ${profil.nom}`} />
            <Row label="Date de naissance" value={profil.date_naissance ? formatDate(profil.date_naissance) : null} />
            <Row label="Nationalité" value={profil.nationalite} />
            <Row label="E-mail" value={profil.email} />
            <Row label="Téléphone" value={profil.telephone} />
            <Row label="Dernière connexion" value={formatDateTime(profil.derniere_connexion)} />
          </dl>
        ) : <p className="text-sm text-muted-foreground">Chargement…</p>}
        <p className="mt-4 text-xs text-muted-foreground">
          Pour modifier ces informations, contacte <a className="text-blue hover:underline" href="mailto:admission@ipec.school">admission@ipec.school</a>.
        </p>
      </div>

      <div className="bg-card border border-border/40 rounded-md p-6">
        <h2 className="font-display text-lg text-cream mb-4">Changer mon mot de passe</h2>
        {formError && <div className="mb-4 px-3 py-2 rounded-sm bg-destructive/10 border border-destructive/30 text-sm text-destructive">{formError}</div>}
        {success && <div className="mb-4 px-3 py-2 rounded-sm bg-green-500/10 border border-green-500/30 text-sm text-green-400">{success}</div>}
        <form onSubmit={onSubmit} className="space-y-4 max-w-md">
          <Field id="current" label="Mot de passe actuel" type="password" autoComplete="current-password" value={current} onChange={setCurrent} />
          <Field id="password" label="Nouveau mot de passe" type="password" autoComplete="new-password" value={pwd} onChange={setPwd} hint="10 caractères min. avec majuscule, minuscule et chiffre." />
          <Field id="password2" label="Confirmer" type="password" autoComplete="new-password" value={pwd2} onChange={setPwd2} />
          <button type="submit" disabled={submitting}
            className="px-4 py-2 rounded-sm bg-gradient-blue text-ink text-sm font-medium hover:opacity-90 disabled:opacity-50">
            {submitting ? "Mise à jour…" : "Mettre à jour"}
          </button>
        </form>
      </div>
    </div>
  );
}

function Row({ label, value, mono }: { label: string; value: string | null | undefined; mono?: boolean }) {
  return (
    <div>
      <dt className="text-xs uppercase tracking-wider text-muted-foreground">{label}</dt>
      <dd className={`text-cream ${mono ? "font-mono text-xs" : ""}`}>{value || "—"}</dd>
    </div>
  );
}
function Field({ id, label, type, autoComplete, value, onChange, hint }: {
  id: string; label: string; type: string; autoComplete?: string;
  value: string; onChange: (v: string) => void; hint?: string;
}) {
  return (
    <div>
      <label htmlFor={id} className="block text-xs uppercase tracking-wider text-muted-foreground mb-1.5 flex items-center justify-between gap-2">
        <span>{label}</span>
        {type === "password" && value.length > 0 && (
          <span className="text-[10px] normal-case tracking-normal text-muted-foreground/70">{value.length} car.</span>
        )}
      </label>
      <input
        id={id}
        name={id}
        type={type}
        autoComplete={autoComplete}
        autoCapitalize="off"
        autoCorrect="off"
        spellCheck={false}
        required
        value={value}
        onChange={(e) => onChange(e.target.value)}
        className="w-full px-3 py-2.5 bg-input/40 border border-border rounded-sm text-cream focus:outline-none focus:ring-2 focus:ring-ring"
      />
      {hint && <p className="mt-1 text-xs text-muted-foreground">{hint}</p>}
    </div>
  );
}
