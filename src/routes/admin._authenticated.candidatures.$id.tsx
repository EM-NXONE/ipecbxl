/**
 * /admin/candidatures/$id — détail + actions (mark_paid, resend_email, create_etudiant, sync_documents).
 */
import { createFileRoute, Link } from "@tanstack/react-router";
import { useCallback, useEffect, useState } from "react";
import { ArrowLeft, RefreshCw, Download } from "lucide-react";
import { AdminCandidatureActions, adminActionMessage } from "@/components/AdminCandidatureActions";
import { adminApi, adminUrl } from "@/lib/api";
import { formatDate, formatDateTime } from "@/lib/format";
import { StatusBadge } from "./admin._authenticated.index";

export const Route = createFileRoute("/admin/_authenticated/candidatures/$id")({
  component: AdminCandidatureDetailPage,
  head: () => ({ meta: [{ title: "IPEC | Détail candidature" }] }),
});

interface Detail {
  candidature: Record<string, unknown> & {
    id: number; reference: string; statut: string; prenom: string; nom: string;
    email: string; date_naissance: string | null; programme: string | null;
    annee: string | null; annee_academique: string | null; nationalite: string | null;
    telephone: string | null; civilite: string | null; rue: string | null; numero: string | null;
    ville: string | null; code_postal: string | null; pays_residence: string | null;
    specialisation: string | null; rentree: string | null; message: string | null;
    facture_numero: string | null; facture_payee: number | boolean;
    facture_payee_at: string | null; facture_payee_par: string | null;
    moyen_paiement: string | null; etudiant_id: number | null;
    ip: string | null; user_agent: string | null; updated_at: string | null; created_at: string;
  };
  etudiant: { id: number; numero_etudiant: string; prenom: string; nom: string; email: string; active: number } | null;
  homonyme: { id: number; numero_etudiant: string; prenom: string; nom: string; date_naissance: string } | null;
  historique: { id: number; action: string; detail: string | null; admin_user: string | null; created_at: string }[];
  statuts: Record<string, string>;
}

function AdminCandidatureDetailPage() {
  const { id } = Route.useParams();
  const [data, setData] = useState<Detail | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState<string | null>(null);
  const [msg, setMsg] = useState<string | null>(null);

  const reload = useCallback(() => {
    adminApi.get<Detail>(`/candidature.php?id=${id}`).then(setData).catch((e) => setError(e.message));
  }, [id]);

  useEffect(() => { reload(); }, [reload]);

  const runAction = async (action: string, body?: Record<string, unknown>) => {
    setBusy(action);
    setMsg(null);
    setError(null);
    try {
      const res = await adminApi.post<{ message?: string; default_password?: string | null }>("/candidature-action.php", { id: Number(id), action, ...body });
      setMsg(adminActionMessage(res));
      reload();
    } catch (e) {
      setError(e instanceof Error ? e.message : "Échec de l'action.");
    } finally {
      setBusy(null);
    }
  };

  if (error && !data) {
    return (
      <div>
        <BackLink />
        <div className="bg-destructive/10 border border-destructive/30 rounded-sm px-4 py-3 text-sm text-destructive">{error}</div>
      </div>
    );
  }
  if (!data) return <div><BackLink /><p className="text-muted-foreground text-sm">Chargement…</p></div>;

  const c = data.candidature;
  const paid = Boolean(Number(c.facture_payee));
  const address = [
    [c.rue, c.numero].filter(Boolean).join(" "),
    [c.code_postal, c.ville].filter(Boolean).join(" "),
    c.pays_residence,
  ].filter(Boolean).join(", ");

  return (
    <div>
      <BackLink />

      {/* Header : identité + statut + repères temporels */}
      <header className="mb-6">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div className="min-w-0">
            <div className="flex flex-wrap items-center gap-3 mb-1">
              <h1 className="font-display text-3xl text-cream truncate">{c.prenom} {c.nom}</h1>
              <StatusBadge value={c.statut} />
              {paid
                ? <span className="inline-block px-2 py-0.5 text-[10px] uppercase tracking-wider rounded-sm border bg-emerald-500/10 text-emerald-400 border-emerald-500/30">Frais payés</span>
                : <span className="inline-block px-2 py-0.5 text-[10px] uppercase tracking-wider rounded-sm border bg-amber-500/10 text-amber-300 border-amber-500/30">Frais en attente</span>}
            </div>
            <p className="text-sm text-muted-foreground font-mono">
              {c.reference} · reçue le {formatDateTime(c.created_at)}
            </p>
          </div>
          <div className="flex flex-wrap gap-2 shrink-0">
            <a
              href={`mailto:${c.email}`}
              className="inline-flex items-center gap-2 px-3 py-2 rounded-sm border border-border/40 text-sm text-cream hover:border-blue/40"
            >
              Écrire au candidat
            </a>
            <a
              href={adminUrl(`/candidature-pdf.php?id=${id}`)}
              target="_blank" rel="noreferrer"
              className="inline-flex items-center gap-2 px-3 py-2 rounded-sm border border-border/40 text-sm text-cream hover:border-blue/40"
            >
              <Download size={14} /> PDF candidature
            </a>
          </div>
        </div>
      </header>

      {msg && <div className="mb-4 px-4 py-3 rounded-sm bg-emerald-500/10 border border-emerald-500/30 text-sm text-emerald-400">{msg}</div>}
      {error && <div className="mb-4 px-4 py-3 rounded-sm bg-destructive/10 border border-destructive/30 text-sm text-destructive">{error}</div>}

      {/* Layout 2 colonnes : contenu détaillé à gauche, panneau opérationnel à droite */}
      <div className="grid lg:grid-cols-[minmax(0,1fr)_360px] gap-6 items-start">

        {/* ====================== COLONNE PRINCIPALE ====================== */}
        <div className="space-y-6 min-w-0">

          <Card title="Coordonnées du candidat">
            <Field label="Civilité" value={c.civilite || "—"} />
            <Field label="Email" value={<a href={`mailto:${c.email}`} className="text-blue hover:underline break-all">{c.email}</a>} />
            <Field label="Téléphone" value={c.telephone || "—"} />
            <Field label="Date de naissance" value={formatDate(c.date_naissance)} />
            <Field label="Nationalité" value={c.nationalite || "—"} />
            <Field label="Adresse" value={address || "—"} />
          </Card>

          <Card title="Dossier académique">
            <Field label="Cursus" value={c.programme || "—"} />
            <Field label="Année" value={c.annee || "—"} />
            <Field label="Spécialisation" value={c.specialisation || "—"} />
            <Field label="Rentrée" value={c.rentree || "—"} />
            <Field label="Année académique" value={c.annee_academique || "—"} />
          </Card>

          {c.message && (
            <Card title="Message du candidat">
              <p className="text-sm whitespace-pre-wrap text-muted-foreground">{c.message as string}</p>
            </Card>
          )}

          <Card title="Historique">
            {data.historique.length === 0 ? (
              <p className="text-sm text-muted-foreground">Aucune action enregistrée.</p>
            ) : (
              <ul className="space-y-2 text-sm">
                {data.historique.map((h) => (
                  <li key={h.id} className="flex flex-wrap gap-2 border-b border-border/20 pb-2 last:border-b-0 last:pb-0">
                    <span className="font-mono text-xs text-blue">{h.action}</span>
                    {h.detail && <span className="text-muted-foreground">{h.detail}</span>}
                    <span className="text-muted-foreground text-xs ml-auto">
                      {h.admin_user || "—"} · {formatDateTime(h.created_at)}
                    </span>
                  </li>
                ))}
              </ul>
            )}
          </Card>

          <Card title="Traçabilité">
            <Field label="ID interne" value={c.id} />
            <Field label="Référence" value={<span className="font-mono text-blue">{c.reference}</span>} />
            <Field label="Créée le" value={formatDateTime(c.created_at)} />
            <Field label="Modifiée le" value={c.updated_at ? formatDateTime(c.updated_at) : "—"} />
            <Field label="IP" value={c.ip || "—"} />
            <Field label="Navigateur" value={<span className="break-all text-xs">{c.user_agent || "—"}</span>} />
          </Card>
        </div>

        {/* ====================== PANNEAU OPÉRATIONNEL ====================== */}
        <aside className="space-y-6 lg:sticky lg:top-4">

          <Card title="Statut de la candidature">
            <div className="flex flex-wrap gap-2">
              {Object.entries(data.statuts).map(([k, label]) => (
                <button key={k}
                  disabled={c.statut === k || busy === `statut:${k}`}
                  onClick={() => runAction("change_statut", { statut: k })}
                  className={`px-3 py-1.5 rounded-sm border text-xs ${c.statut === k ? "border-blue bg-blue/10 text-blue" : "border-border/40 text-cream hover:border-blue/40"} disabled:opacity-50`}>
                  {label}
                </button>
              ))}
            </div>
          </Card>

          <Card title="Frais de dossier">
            <dl className="space-y-1.5 text-sm mb-4">
              <div className="flex gap-2">
                <dt className="text-muted-foreground w-28 shrink-0">Statut</dt>
                <dd>
                  {paid
                    ? <span className="text-emerald-400">✓ Payés</span>
                    : <span className="text-amber-400">En attente</span>}
                </dd>
              </div>
              {c.facture_numero && (
                <div className="flex gap-2">
                  <dt className="text-muted-foreground w-28 shrink-0">N° facture</dt>
                  <dd className="font-mono text-xs text-cream break-all">{c.facture_numero}</dd>
                </div>
              )}
              {paid && c.facture_payee_at && (
                <div className="flex gap-2">
                  <dt className="text-muted-foreground w-28 shrink-0">Payé le</dt>
                  <dd className="text-cream">{formatDateTime(c.facture_payee_at)}</dd>
                </div>
              )}
              {paid && c.moyen_paiement && (
                <div className="flex gap-2">
                  <dt className="text-muted-foreground w-28 shrink-0">Moyen</dt>
                  <dd className="text-cream">{moyenLabel(c.moyen_paiement)}</dd>
                </div>
              )}
              {paid && c.facture_payee_par && (
                <div className="flex gap-2">
                  <dt className="text-muted-foreground w-28 shrink-0">Validé par</dt>
                  <dd className="text-cream">{c.facture_payee_par}</dd>
                </div>
              )}
              <div className="flex gap-2">
                <dt className="text-muted-foreground w-28 shrink-0">Montant</dt>
                <dd className="text-cream">400,00 €</dd>
              </div>
            </dl>

            <AdminCandidatureActions
              id={id}
              paid={paid}
              hasEtudiant={Boolean(data.etudiant || c.etudiant_id)}
              scope="payment"
              currentMoyen={c.moyen_paiement}
              currentDate={c.facture_payee_at}
              onDone={(res) => { setMsg(adminActionMessage(res)); reload(); }}
              onError={setError}
            />

            <div className="mt-4 pt-4 border-t border-border/40 flex flex-col gap-2">
              <a
                href={adminUrl(`/candidature-pdf.php?id=${id}&kind=facture`)}
                target="_blank" rel="noreferrer"
                className="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-sm border border-border/40 text-sm text-cream hover:border-blue/40"
              >
                <Download size={14} /> Facture PDF
              </a>
              {paid && (
                <a
                  href={adminUrl(`/candidature-pdf.php?id=${id}&kind=recu`)}
                  target="_blank" rel="noreferrer"
                  className="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-sm border border-emerald-500/40 text-sm text-emerald-300 hover:border-emerald-500/70"
                >
                  <Download size={14} /> Reçu de paiement
                </a>
              )}
            </div>
          </Card>

          <Card title="Compte étudiant">
            {data.etudiant ? (
              <div className="space-y-2">
                <div className="text-sm">
                  <div className="text-cream">{data.etudiant.prenom} {data.etudiant.nom}</div>
                  <div className="text-xs text-muted-foreground font-mono">n° {data.etudiant.numero_etudiant}</div>
                </div>
                <div className="text-xs">
                  {data.etudiant.active
                    ? <span className="text-emerald-400">● Compte actif</span>
                    : <span className="text-amber-400">● Compte sans mot de passe</span>}
                </div>
                <button onClick={() => runAction("sync_documents")} disabled={busy === "sync_documents"}
                  className="w-full mt-2 inline-flex items-center justify-center gap-2 px-3 py-1.5 rounded-sm border border-border/40 text-xs text-cream hover:border-blue/40 disabled:opacity-50">
                  <RefreshCw size={12} /> {busy === "sync_documents" ? "…" : "Synchroniser documents"}
                </button>
              </div>
            ) : data.homonyme ? (
              <div className="space-y-2">
                <p className="text-sm text-amber-400">⚠ Identité déjà connue :</p>
                <div className="text-sm text-cream">
                  {data.homonyme.prenom} {data.homonyme.nom}
                  <div className="text-xs text-muted-foreground">n° {data.homonyme.numero_etudiant} · né le {formatDate(data.homonyme.date_naissance)}</div>
                </div>
                <button onClick={() => runAction("create_etudiant", { link_to: data.homonyme!.id })} disabled={busy === "create_etudiant"}
                  className="w-full inline-flex items-center justify-center gap-2 px-3 py-1.5 rounded-sm border border-blue/40 text-xs text-blue hover:bg-blue/10 disabled:opacity-50">
                  Lier au compte existant
                </button>
              </div>
            ) : (
              <div className="space-y-3">
                <p className="text-sm text-muted-foreground">Aucun compte étudiant.</p>
                <button onClick={() => runAction("create_etudiant")} disabled={busy === "create_etudiant"}
                  className="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-sm bg-gradient-blue text-ink text-sm font-medium hover:opacity-90 disabled:opacity-50">
                  {busy === "create_etudiant" ? "…" : "Créer le compte étudiant"}
                </button>
              </div>
            )}
          </Card>

          <Card title="Autres actions">
            <AdminCandidatureActions
              id={id}
              paid={paid}
              hasEtudiant={Boolean(data.etudiant || c.etudiant_id)}
              scope="general"
              onDone={(res) => { setMsg(adminActionMessage(res)); reload(); }}
              onError={setError}
            />
          </Card>
        </aside>
      </div>
    </div>
  );
}

function BackLink() {
  return (
    <Link to="/admin/candidatures" className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-blue mb-4">
      <ArrowLeft size={14} /> Retour à la liste
    </Link>
  );
}
function Card({ title, children, className = "" }: { title: string; children: React.ReactNode; className?: string }) {
  return (
    <section className={`bg-card border border-border/40 rounded-md p-6 ${className}`}>
      <h2 className="font-display text-lg text-cream mb-4">{title}</h2>
      {children}
    </section>
  );
}
function Field({ label, value }: { label: string; value: React.ReactNode }) {
  return (
    <div className="grid grid-cols-[140px_1fr] gap-3 py-1.5 border-b border-border/10 last:border-b-0 text-sm">
      <dt className="text-muted-foreground text-xs uppercase tracking-wider pt-0.5">{label}</dt>
      <dd className="text-cream">{value}</dd>
    </div>
  );
}

const MOYEN_LABELS: Record<string, string> = {
  virement: "Virement bancaire",
  carte: "Carte bancaire",
  especes: "Espèces",
  cheque: "Chèque",
  autre: "Autre",
};
function moyenLabel(v: string | null | undefined): string {
  if (!v) return "—";
  return MOYEN_LABELS[v] ?? v;
}
