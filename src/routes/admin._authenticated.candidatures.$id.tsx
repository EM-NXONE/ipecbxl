/**
 * /admin/candidatures/$id — détail + actions (mark_paid, resend_email, create_etudiant, sync_documents).
 */
import { createFileRoute, Link } from "@tanstack/react-router";
import { useCallback, useEffect, useState } from "react";
import { ArrowLeft, RefreshCw, Download, KeyRound, Ban, CheckCircle2 } from "lucide-react";
import { AdminCandidatureActions, adminActionMessage } from "@/components/AdminCandidatureActions";
import { adminApi, adminUrl } from "@/lib/api";
import { formatDate, formatDateTime } from "@/lib/format";
import { StatusBadge } from "./admin._authenticated.index";

export const Route = createFileRoute("/admin/_authenticated/candidatures/$id")({
  component: AdminCandidatureDetailPage,
  head: () => ({ meta: [{ title: "IPEC | Détail candidature" }] }),
});

interface FactureRow {
  id: number;
  numero: string;
  type: "frais_dossier" | "scolarite" | string;
  libelle: string | null;
  description: string | null;
  montant_ttc_cents: number;
  tva_taux: number | string | null;
  devise: string | null;
  date_emission: string | null;
  date_echeance: string | null;
  statut_paiement: "en_attente" | "payee" | string;
  paye_at: string | null;
  moyen_paiement: string | null;
  paye_par_admin: string | null;
  reference_paiement: string | null;
}

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
  etudiant: { id: number; numero_etudiant: string; prenom: string; nom: string; email: string; active: number; statut: string } | null;
  homonyme: { id: number; numero_etudiant: string; prenom: string; nom: string; date_naissance: string } | null;
  factures: FactureRow[];
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

          <FacturesCard
            factures={data.factures || []}
            onDone={(m) => { setMsg(m); reload(); }}
            onError={setError}
          />

          <details className="bg-card border border-border/40 rounded-md group">
            <summary className="cursor-pointer list-none px-5 py-4 flex items-center justify-between hover:bg-secondary/20 select-none">
              <span className="font-display text-base text-cream">Historique</span>
              <span className="text-xs text-muted-foreground">
                {data.historique.length} entrée{data.historique.length > 1 ? "s" : ""}
                <span className="ml-2 text-blue group-open:hidden">Afficher ▾</span>
                <span className="ml-2 text-blue hidden group-open:inline">Masquer ▴</span>
              </span>
            </summary>
            <div className="px-5 pb-5 pt-0 border-t border-border/30">
              {data.historique.length === 0 ? (
                <p className="text-sm text-muted-foreground pt-4">Aucune action enregistrée.</p>
              ) : (
                <ul className="space-y-2 text-sm pt-4">
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
            </div>
          </details>
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
            <div className="mt-4 pt-4 border-t border-border/40">
              <a
                href={adminUrl(`/candidature-pdf.php?id=${id}`)}
                target="_blank" rel="noreferrer"
                className="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-sm border border-border/40 text-sm text-cream hover:border-blue/40"
              >
                <Download size={14} /> Télécharger PDF candidature
              </a>
            </div>
          </Card>

          {/* Le détail / actions des factures (frais + scolarité) sont gérés
              dans le carton « Factures » de la colonne principale. */}

          <Card title="Compte étudiant">
            {data.etudiant ? (() => {
              const etu = data.etudiant!;
              const statut = etu.statut || "actif";
              const statutLabel: Record<string, { label: string; tone: string }> = {
                actif:    { label: "● Compte actif",     tone: "text-emerald-400" },
                suspendu: { label: "● Compte suspendu",  tone: "text-amber-400" },
                archive:  { label: "● Compte archivé",   tone: "text-muted-foreground" },
              };
              const s = statutLabel[statut] ?? statutLabel.actif;
              return (
                <div className="space-y-3">
                  <div className="text-sm">
                    <div className="text-cream">{etu.prenom} {etu.nom}</div>
                    <div className="text-xs text-muted-foreground font-mono">n° {etu.numero_etudiant}</div>
                  </div>
                  <div className="text-xs space-y-1">
                    <div className={s.tone}>{s.label}</div>
                    {!etu.active && <div className="text-amber-400">● Sans mot de passe</div>}
                  </div>

                  <div className="pt-3 border-t border-border/40 flex flex-col gap-2">
                    <button onClick={() => runAction("sync_documents")} disabled={busy !== null}
                      className="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-sm border border-border/40 text-xs text-cream hover:border-blue/40 disabled:opacity-50">
                      <RefreshCw size={12} /> {busy === "sync_documents" ? "…" : "Synchroniser documents"}
                    </button>
                    <button
                      onClick={() => {
                        if (!confirm('Réinitialiser le mot de passe de cet étudiant à "Student1" ?')) return;
                        runAction("reset_password_etudiant");
                      }}
                      disabled={busy !== null}
                      className="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-sm border border-border/40 text-xs text-cream hover:border-blue/40 disabled:opacity-50">
                      <KeyRound size={12} /> {busy === "reset_password_etudiant" ? "…" : "Réinitialiser le mot de passe"}
                    </button>

                    {statut === "actif" ? (
                      <button
                        onClick={() => {
                          if (!confirm("Suspendre ce compte étudiant ? L'étudiant ne pourra plus se connecter et toutes ses sessions seront fermées.")) return;
                          runAction("set_statut_etudiant", { statut: "suspendu" });
                        }}
                        disabled={busy !== null}
                        className="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-sm border border-amber-500/40 text-xs text-amber-300 hover:bg-amber-500/10 disabled:opacity-50">
                        <Ban size={12} /> {busy === "set_statut_etudiant" ? "…" : "Suspendre le compte"}
                      </button>
                    ) : (
                      <button
                        onClick={() => runAction("set_statut_etudiant", { statut: "actif" })}
                        disabled={busy !== null}
                        className="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-sm border border-emerald-500/40 text-xs text-emerald-300 hover:bg-emerald-500/10 disabled:opacity-50">
                        <CheckCircle2 size={12} /> {busy === "set_statut_etudiant" ? "…" : "Réactiver le compte"}
                      </button>
                    )}
                  </div>
                </div>
              );
            })() : data.homonyme ? (
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
              scope="email"
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

const TYPE_LABELS: Record<string, string> = {
  frais_dossier: "Frais de dossier",
  scolarite: "Scolarité",
};

function formatMontantCents(cents: number, devise: string | null = "EUR"): string {
  const v = (cents || 0) / 100;
  return v.toLocaleString("fr-BE", { style: "currency", currency: devise || "EUR" });
}

function FacturesCard({
  factures,
  onDone,
  onError,
}: {
  factures: FactureRow[];
  onDone: (msg: string) => void;
  onError: (msg: string) => void;
}) {
  const [busy, setBusy] = useState<number | null>(null);
  const [showPay, setShowPay] = useState<{ id: number; edit: boolean } | null>(null);
  const [moyen, setMoyen] = useState("virement");
  const [datePaiement, setDatePaiement] = useState<string>(() => new Date().toISOString().slice(0, 10));

  const openPay = (f: FactureRow, edit: boolean) => {
    setMoyen(edit && f.moyen_paiement ? f.moyen_paiement : "virement");
    setDatePaiement(
      edit && f.paye_at ? f.paye_at.slice(0, 10) : new Date().toISOString().slice(0, 10),
    );
    setShowPay({ id: f.id, edit });
  };

  const runAction = async (id: number, action: string, body?: Record<string, unknown>) => {
    setBusy(id);
    try {
      const res = await adminApi.post<{ message?: string }>("/facture-action.php", {
        id, action, ...body,
      });
      onDone(res.message || "Action effectuée.");
      setShowPay(null);
    } catch (e) {
      onError(e instanceof Error ? e.message : "Échec de l'action.");
    } finally {
      setBusy(null);
    }
  };

  if (!factures.length) {
    return (
      <Card title="Factures">
        <p className="text-sm text-muted-foreground">
          Aucune facture pour ce candidat. Les factures de scolarité sont générées
          automatiquement lorsque la candidature est validée et que les frais de
          dossier sont marqués payés.
        </p>
      </Card>
    );
  }

  return (
    <Card title={`Factures (${factures.length})`}>
      <ul className="divide-y divide-border/20 -my-2">
        {factures.map((f) => {
          const paid = f.statut_paiement === "payee";
          return (
            <li key={f.id} className="py-3">
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="min-w-0">
                  <div className="flex flex-wrap items-center gap-2 mb-1">
                    <span className="inline-block px-2 py-0.5 text-[10px] uppercase tracking-wider rounded-sm border border-border/40 text-muted-foreground">
                      {TYPE_LABELS[f.type] ?? f.type}
                    </span>
                    {paid ? (
                      <span className="inline-block px-2 py-0.5 text-[10px] uppercase tracking-wider rounded-sm border bg-emerald-500/10 text-emerald-400 border-emerald-500/30">
                        ✓ Payée
                      </span>
                    ) : (
                      <span className="inline-block px-2 py-0.5 text-[10px] uppercase tracking-wider rounded-sm border bg-amber-500/10 text-amber-300 border-amber-500/30">
                        En attente
                      </span>
                    )}
                  </div>
                  <div className="text-sm text-cream font-medium truncate">
                    {f.libelle || "—"}
                  </div>
                  <div className="text-xs text-muted-foreground font-mono break-all">
                    {f.numero}
                  </div>
                  <div className="text-xs text-muted-foreground mt-1 space-x-3">
                    {f.date_echeance && <span>Échéance : {formatDate(f.date_echeance)}</span>}
                    {paid && f.paye_at && <span>Payée le {formatDate(f.paye_at)}</span>}
                    {paid && f.moyen_paiement && <span>· {moyenLabel(f.moyen_paiement)}</span>}
                  </div>
                </div>
                <div className="text-right shrink-0">
                  <div className="text-cream font-medium">
                    {formatMontantCents(f.montant_ttc_cents, f.devise)}
                  </div>
                  <div className="text-[10px] text-muted-foreground uppercase tracking-wider">TTC</div>
                </div>
              </div>

              <div className="mt-3 flex flex-wrap gap-2">
                <a
                  href={adminUrl(`/facture-pdf.php?id=${f.id}&kind=facture`)}
                  target="_blank" rel="noreferrer"
                  className="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-sm border border-border/40 text-xs text-cream hover:border-blue/40"
                >
                  <Download size={12} /> Facture PDF
                </a>
                {paid && (
                  <a
                    href={adminUrl(`/facture-pdf.php?id=${f.id}&kind=recu`)}
                    target="_blank" rel="noreferrer"
                    className="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-sm border border-emerald-500/40 text-xs text-emerald-300 hover:border-emerald-500/70"
                  >
                    <Download size={12} /> Reçu
                  </a>
                )}
                {!paid ? (
                  <button
                    onClick={() => openPay(f, false)}
                    disabled={busy === f.id}
                    className="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-sm border border-blue/40 text-xs text-blue hover:bg-blue/10 disabled:opacity-50"
                  >
                    <CheckCircle2 size={12} /> Marquer payée
                  </button>
                ) : (
                  <>
                    <button
                      onClick={() => openPay(f, true)}
                      disabled={busy === f.id}
                      className="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-sm border border-border/40 text-xs text-cream hover:border-blue/40 disabled:opacity-50"
                    >
                      Éditer
                    </button>
                    <button
                      onClick={() => {
                        if (!confirm("Annuler ce paiement ? La facture repassera en attente.")) return;
                        runAction(f.id, "mark_unpaid");
                      }}
                      disabled={busy === f.id}
                      className="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-sm border border-amber-500/40 text-xs text-amber-300 hover:bg-amber-500/10 disabled:opacity-50"
                    >
                      Annuler paiement
                    </button>
                  </>
                )}
              </div>
            </li>
          );
        })}
      </ul>

      {showPay && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
          onClick={() => setShowPay(null)}
        >
          <div
            className="bg-card border border-border/40 rounded-md p-6 w-full max-w-sm"
            onClick={(e) => e.stopPropagation()}
          >
            <h3 className="font-display text-lg text-cream mb-4">
              {showPay.edit ? "Modifier le paiement" : "Marquer la facture comme payée"}
            </h3>
            <div className="space-y-4">
              <div>
                <label className="block text-xs uppercase tracking-wider text-muted-foreground mb-1.5">
                  Moyen de paiement
                </label>
                <select
                  value={moyen}
                  onChange={(e) => setMoyen(e.target.value)}
                  className="w-full px-3 py-2 bg-input/40 border border-border rounded-sm text-cream text-sm"
                >
                  <option value="virement">Virement bancaire</option>
                  <option value="carte">Carte bancaire</option>
                  <option value="especes">Espèces</option>
                  <option value="cheque">Chèque</option>
                  <option value="autre">Autre</option>
                </select>
              </div>
              <div>
                <label className="block text-xs uppercase tracking-wider text-muted-foreground mb-1.5">
                  Date du paiement
                </label>
                <input
                  type="date"
                  value={datePaiement}
                  onChange={(e) => setDatePaiement(e.target.value)}
                  className="w-full px-3 py-2 bg-input/40 border border-border rounded-sm text-cream text-sm"
                />
              </div>
              <div className="flex justify-end gap-2 pt-2">
                <button
                  onClick={() => setShowPay(null)}
                  className="px-3 py-2 rounded-sm border border-border/40 text-sm text-muted-foreground hover:text-cream"
                >
                  Annuler
                </button>
                <button
                  disabled={busy === showPay.id}
                  onClick={() => runAction(showPay.id, "mark_paid", {
                    moyen_paiement: moyen,
                    date_paiement: datePaiement,
                  })}
                  className="px-3 py-2 rounded-sm bg-gradient-blue text-ink text-sm font-medium hover:opacity-90 disabled:opacity-50"
                >
                  {busy === showPay.id ? "…" : "Confirmer"}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </Card>
  );
}
