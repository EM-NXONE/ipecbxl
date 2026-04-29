import { useState } from "react";
import { CheckCircle2, Copy, KeyRound, Mail, RefreshCw, UserPlus, XCircle } from "lucide-react";
import { adminApi } from "@/lib/api";

export interface AdminActionResult {
  message?: string;
  default_password?: string | null;
}

export function adminActionMessage(result: AdminActionResult): string {
  return [
    result.message || "Action effectuée.",
    result.default_password ? `Mot de passe : ${result.default_password}` : "",
  ].filter(Boolean).join(" — ");
}

interface AdminCandidatureActionsProps {
  id: number | string;
  paid: boolean | number;
  hasEtudiant: boolean;
  compact?: boolean;
  /**
   * "all" (défaut) — toutes les actions
   * "payment" — uniquement marquer payé / annuler paiement
   * "general" — tout sauf le paiement (renvoi mail, création/sync étudiant, reset mdp)
   */
  scope?: "all" | "payment" | "general";
  onDone?: (result: AdminActionResult, action: string) => void;
  onError?: (message: string, action: string) => void;
}

const MOYENS = [
  { value: "virement", label: "Virement bancaire" },
  { value: "carte", label: "Carte bancaire" },
  { value: "especes", label: "Espèces" },
  { value: "cheque", label: "Chèque" },
  { value: "autre", label: "Autre" },
];

export function AdminCandidatureActions({
  id,
  paid,
  hasEtudiant,
  compact = false,
  scope = "all",
  onDone,
  onError,
}: AdminCandidatureActionsProps) {
  const [busy, setBusy] = useState<string | null>(null);
  const [showPayModal, setShowPayModal] = useState(false);
  const [lastPwd, setLastPwd] = useState<string | null>(null);
  const [moyen, setMoyen] = useState<string>("virement");
  const [datePaiement, setDatePaiement] = useState<string>(() => new Date().toISOString().slice(0, 10));
  const isPaid = Boolean(Number(paid));

  const runAction = async (action: string, body?: Record<string, unknown>) => {
    setBusy(action);
    try {
      const result = await adminApi.post<AdminActionResult>("/candidature-action.php", {
        id: Number(id),
        action,
        ...body,
      });
      setLastPwd(result.default_password || null);
      onDone?.(result, action);
    } catch (e) {
      onError?.(e instanceof Error ? e.message : "Échec de l'action.", action);
    } finally {
      setBusy(null);
    }
  };

  const buttonClass = compact
    ? "inline-flex h-8 w-8 items-center justify-center rounded-sm border border-border/40 text-muted-foreground hover:text-blue hover:border-blue/40 disabled:opacity-50"
    : "inline-flex items-center gap-2 px-3 py-2 rounded-sm border border-border/40 text-sm text-cream hover:border-blue/40 disabled:opacity-50";

  const showGeneral = scope === "all" || scope === "general";
  const showPayment = scope === "all" || scope === "payment";

  return (
    <>
      <div className="flex flex-wrap items-center gap-2">
        {showGeneral && (
          <button
            type="button"
            onClick={() => runAction("resend_email")}
            disabled={busy !== null}
            className={buttonClass}
            title="Renvoyer l'e-mail au candidat"
            aria-label="Renvoyer l'e-mail au candidat"
          >
            <Mail size={compact ? 14 : 15} />
            {!compact && <span>{busy === "resend_email" ? "…" : "Renvoyer e-mail"}</span>}
          </button>
        )}

        {showPayment && (
          <button
            type="button"
            onClick={() => {
              if (isPaid) runAction("mark_unpaid");
              else setShowPayModal(true);
            }}
            disabled={busy !== null}
            className={buttonClass}
            title={isPaid ? "Annuler le paiement" : "Marquer comme payé"}
            aria-label={isPaid ? "Annuler le paiement" : "Marquer comme payé"}
          >
            {isPaid ? <XCircle size={compact ? 14 : 15} /> : <CheckCircle2 size={compact ? 14 : 15} />}
            {!compact && <span>{busy === "mark_paid" || busy === "mark_unpaid" ? "…" : isPaid ? "Annuler paiement" : "Marquer payé"}</span>}
          </button>
        )}

        {showGeneral && (
          <button
            type="button"
            onClick={() => runAction(hasEtudiant ? "sync_documents" : "create_etudiant")}
            disabled={busy !== null}
            className={buttonClass}
            title={hasEtudiant ? "Synchroniser les documents" : "Créer le compte étudiant"}
            aria-label={hasEtudiant ? "Synchroniser les documents" : "Créer le compte étudiant"}
          >
            {hasEtudiant ? <RefreshCw size={compact ? 14 : 15} /> : <UserPlus size={compact ? 14 : 15} />}
            {!compact && <span>{busy === "create_etudiant" || busy === "sync_documents" ? "…" : hasEtudiant ? "Sync documents" : "Créer étudiant"}</span>}
          </button>
        )}

        {showGeneral && hasEtudiant && (
          <button
            type="button"
            onClick={() => {
              if (!confirm('Réinitialiser le mot de passe de cet étudiant à "Student1" ?')) return;
              runAction("reset_password_etudiant");
            }}
            disabled={busy !== null}
            className={buttonClass}
            title='Réinitialiser au mot de passe par défaut "Student1"'
            aria-label="Réinitialiser le mot de passe étudiant"
          >
            <KeyRound size={compact ? 14 : 15} />
            {!compact && <span>{busy === "reset_password_etudiant" ? "…" : "Reset mdp"}</span>}
          </button>
        )}
      </div>

      {lastPwd && !compact && (
        <div className="mt-3 flex flex-wrap items-center gap-2 rounded-sm border border-blue/30 bg-blue/5 px-3 py-2 text-xs">
          <span className="text-muted-foreground">Mot de passe à communiquer :</span>
          <code className="min-w-0 flex-1 break-all text-blue">{lastPwd}</code>
          <button
            type="button"
            onClick={() => navigator.clipboard?.writeText(lastPwd)}
            className="inline-flex items-center gap-1 rounded-sm border border-border/40 px-2 py-1 text-cream hover:border-blue/40"
          >
            <Copy size={12} /> Copier
          </button>
        </div>
      )}

      {showPayModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" onClick={() => setShowPayModal(false)}>
          <div className="bg-card border border-border/40 rounded-md p-6 w-full max-w-sm" onClick={(e) => e.stopPropagation()}>
            <h3 className="font-display text-lg text-cream mb-4">Marquer la facture comme payée</h3>
            <div className="space-y-4">
              <div>
                <label className="block text-xs uppercase tracking-wider text-muted-foreground mb-1.5">Moyen de paiement</label>
                <select
                  value={moyen}
                  onChange={(e) => setMoyen(e.target.value)}
                  className="w-full px-3 py-2 bg-input/40 border border-border rounded-sm text-cream text-sm"
                >
                  {MOYENS.map((m) => <option key={m.value} value={m.value}>{m.label}</option>)}
                </select>
              </div>
              <div>
                <label className="block text-xs uppercase tracking-wider text-muted-foreground mb-1.5">Date du paiement</label>
                <input
                  type="date"
                  value={datePaiement}
                  onChange={(e) => setDatePaiement(e.target.value)}
                  className="w-full px-3 py-2 bg-input/40 border border-border rounded-sm text-cream text-sm"
                />
              </div>
              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setShowPayModal(false)}
                  className="px-3 py-2 rounded-sm border border-border/40 text-sm text-muted-foreground hover:text-cream"
                >
                  Annuler
                </button>
                <button
                  type="button"
                  disabled={busy === "mark_paid"}
                  onClick={async () => {
                    await runAction("mark_paid", { moyen_paiement: moyen, date_paiement: datePaiement });
                    setShowPayModal(false);
                  }}
                  className="px-3 py-2 rounded-sm bg-gradient-blue text-ink text-sm font-medium hover:opacity-90 disabled:opacity-50"
                >
                  {busy === "mark_paid" ? "Enregistrement…" : "Confirmer le paiement"}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
