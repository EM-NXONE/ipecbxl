import { useState } from "react";
import { CheckCircle2, Mail, RefreshCw, UserPlus, XCircle } from "lucide-react";
import { adminApi } from "@/lib/api";

export interface AdminActionResult {
  message?: string;
  activation_url?: string | null;
}

export function adminActionMessage(result: AdminActionResult): string {
  return [
    result.message || "Action effectuée.",
    result.activation_url ? `Lien d'activation : ${result.activation_url}` : "",
  ].filter(Boolean).join(" ");
}

interface AdminCandidatureActionsProps {
  id: number | string;
  paid: boolean | number;
  hasEtudiant: boolean;
  compact?: boolean;
  onDone?: (result: AdminActionResult, action: string) => void;
  onError?: (message: string, action: string) => void;
}

export function AdminCandidatureActions({
  id,
  paid,
  hasEtudiant,
  compact = false,
  onDone,
  onError,
}: AdminCandidatureActionsProps) {
  const [busy, setBusy] = useState<string | null>(null);
  const isPaid = Boolean(Number(paid));

  const runAction = async (action: string) => {
    setBusy(action);
    try {
      const result = await adminApi.post<AdminActionResult>("/candidature-action.php", {
        id: Number(id),
        action,
      });
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

  return (
    <div className="flex flex-wrap items-center gap-2">
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

      <button
        type="button"
        onClick={() => runAction(isPaid ? "mark_unpaid" : "mark_paid")}
        disabled={busy !== null}
        className={buttonClass}
        title={isPaid ? "Annuler le paiement" : "Marquer comme payé"}
        aria-label={isPaid ? "Annuler le paiement" : "Marquer comme payé"}
      >
        {isPaid ? <XCircle size={compact ? 14 : 15} /> : <CheckCircle2 size={compact ? 14 : 15} />}
        {!compact && <span>{busy === "mark_paid" || busy === "mark_unpaid" ? "…" : isPaid ? "Annuler paiement" : "Marquer payé"}</span>}
      </button>

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
    </div>
  );
}