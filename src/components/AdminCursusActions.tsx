/**
 * Carton « Évolution du cursus » sur la fiche candidature/étudiant admin.
 * 4 actions : passer à l'année suivante, redoubler, diplômer, marquer inactif/actif.
 */
import { useState } from "react";
import { GraduationCap, Repeat, Award, UserX, UserCheck } from "lucide-react";
import { adminApi } from "@/lib/api";
import { ACADEMIC_YEAR_LABEL, RENTREE_PRINCIPALE_LABEL, RENTREE_DECALEE_LABEL } from "@/lib/academic-dates";

export interface CursusDescriptor {
  current_step: string | null;
  current_label: string | null;
  next_step: string | null;
  next_label: string | null;
  can_promote: boolean;
  can_redouble: boolean;
  can_diplomer: boolean;
  annee_academique_courante?: string | null;
  rentree_courante?: string | null;
}

export interface CursusHistoryRow {
  id: number;
  reference: string;
  statut: string;
  programme: string | null;
  annee: string | null;
  annee_academique: string | null;
  rentree: string | null;
  type_inscription: "initiale" | "passage" | "redoublement";
  parent_candidature_id: number | null;
  created_at: string;
}

interface Props {
  latestCandidatureId: number;
  cursus: CursusDescriptor;
  history: CursusHistoryRow[];
  etudiantCategorie: string | null;
  motifInactif: string | null;
  onDone: (msg: string) => void;
  onError: (msg: string) => void;
}

export function AdminCursusActions({
  latestCandidatureId, cursus, history, etudiantCategorie, motifInactif,
  onDone, onError,
}: Props) {
  const [busy, setBusy] = useState<string | null>(null);
  const [annee, setAnnee] = useState(ACADEMIC_YEAR_LABEL);
  const [rentree, setRentree] = useState(RENTREE_PRINCIPALE_LABEL);

  const isInactif  = etudiantCategorie === "inactif";
  const isDiplome  = etudiantCategorie === "diplome";

  const run = async (action: string, body: Record<string, unknown> = {}) => {
    setBusy(action);
    try {
      const res = await adminApi.post<{ message?: string }>("/cursus-action.php", {
        candidature_id: latestCandidatureId,
        action,
        ...body,
      });
      onDone(res.message || "Action effectuée.");
    } catch (e) {
      onError(e instanceof Error ? e.message : "Échec de l'action cursus.");
    } finally {
      setBusy(null);
    }
  };

  const passer = () => {
    if (!cursus.can_promote) return;
    if (!confirm(
      `Faire passer cet étudiant de ${cursus.current_label} à ${cursus.next_label}\n` +
      `pour l'année académique ${annee} ?\n\n` +
      `Une nouvelle candidature et 3 factures de scolarité seront générées.\n` +
      `L'étudiant reste « étudiant » ; une nouvelle attestation d'inscription définitive\n` +
      `sera générée après paiement de la 1ʳᵉ tranche de la nouvelle année.`
    )) return;
    run("passer_annee", { annee_academique: annee, rentree });
  };

  const redoubler = () => {
    if (!cursus.can_redouble) return;
    if (!confirm(
      `Faire redoubler cet étudiant en ${cursus.current_label}\n` +
      `pour l'année académique ${annee} ?\n\n` +
      `Une nouvelle candidature et 3 factures de scolarité seront générées.`
    )) return;
    run("redoubler", { annee_academique: annee, rentree });
  };

  const diplomer = () => {
    if (!cursus.can_diplomer) return;
    if (!confirm("Confirmer la diplomation de cet étudiant ? Une attestation de réussite sera générée dans son espace.")) return;
    run("diplomer");
  };

  const inactiver = () => {
    const motif = prompt("Motif (abandon, exclusion, etc.) — obligatoire :", "");
    if (!motif || !motif.trim()) return;
    run("set_inactif", { motif: motif.trim() });
  };

  const reactiver = () => {
    if (!confirm("Réactiver ce compte étudiant ?")) return;
    run("set_actif");
  };

  return (
    <div className="space-y-4">
      <div className="text-xs text-muted-foreground">
        <div>
          <span className="text-cream">Étape actuelle :</span>{" "}
          <strong className="text-blue">{cursus.current_label || "—"}</strong>
        </div>
        {cursus.next_label && (
          <div>
            <span className="text-cream">Prochaine étape :</span>{" "}
            <strong className="text-blue">{cursus.next_label}</strong>
          </div>
        )}
        {isDiplome && <div className="text-emerald-400 mt-1">● Étudiant diplômé</div>}
        {isInactif && (
          <div className="text-amber-400 mt-1">
            ● Inactif{motifInactif ? ` — ${motifInactif}` : ""}
          </div>
        )}
      </div>

      {!isDiplome && !isInactif && (
        <>
          <div className="space-y-2 pt-3 border-t border-border/40">
            <label className="block text-[10px] uppercase tracking-wider text-muted-foreground">
              Année académique cible
            </label>
            <input
              value={annee}
              onChange={(e) => setAnnee(e.target.value)}
              placeholder="AAAA-AAAA"
              className="w-full bg-secondary/30 border border-border/40 rounded-sm px-2 py-1.5 text-sm font-mono"
            />
            <label className="block text-[10px] uppercase tracking-wider text-muted-foreground pt-1">
              Rentrée
            </label>
            <select
              value={rentree}
              onChange={(e) => setRentree(e.target.value)}
              className="w-full bg-secondary/30 border border-border/40 rounded-sm px-2 py-1.5 text-sm"
            >
              <option value={RENTREE_PRINCIPALE_LABEL}>{RENTREE_PRINCIPALE_LABEL}</option>
              <option value={RENTREE_DECALEE_LABEL}>{RENTREE_DECALEE_LABEL}</option>
            </select>
          </div>

          <div className="flex flex-col gap-2">
            <button
              onClick={passer}
              disabled={!cursus.can_promote || busy !== null}
              title={!cursus.can_promote ? "Déjà à la dernière année du cursus" : ""}
              className="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-sm bg-gradient-blue text-ink text-sm font-medium hover:opacity-90 disabled:opacity-40">
              <GraduationCap size={14} />
              {busy === "passer_annee" ? "…" : `Passer à ${cursus.next_label || "—"}`}
            </button>
            <button
              onClick={redoubler}
              disabled={!cursus.can_redouble || busy !== null}
              className="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-sm border border-amber-500/40 text-xs text-amber-300 hover:bg-amber-500/10 disabled:opacity-40">
              <Repeat size={12} />
              {busy === "redoubler" ? "…" : `Faire redoubler en ${cursus.current_label || "—"}`}
            </button>
            <button
              onClick={diplomer}
              disabled={!cursus.can_diplomer || busy !== null}
              title={!cursus.can_diplomer ? "Possible uniquement depuis PEA-2" : ""}
              className="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-sm border border-emerald-500/40 text-xs text-emerald-300 hover:bg-emerald-500/10 disabled:opacity-40">
              <Award size={12} />
              {busy === "diplomer" ? "…" : "Diplômer (fin de cursus)"}
            </button>
          </div>
        </>
      )}

      <div className="pt-3 border-t border-border/40">
        {isInactif ? (
          <button
            onClick={reactiver}
            disabled={busy !== null}
            className="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-sm border border-emerald-500/40 text-xs text-emerald-300 hover:bg-emerald-500/10 disabled:opacity-40">
            <UserCheck size={12} /> {busy === "set_actif" ? "…" : "Réactiver le compte"}
          </button>
        ) : (
          <button
            onClick={inactiver}
            disabled={busy !== null || isDiplome}
            className="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-sm border border-destructive/40 text-xs text-destructive hover:bg-destructive/10 disabled:opacity-40">
            <UserX size={12} /> {busy === "set_inactif" ? "…" : "Marquer inactif (abandon/exclusion)"}
          </button>
        )}
      </div>

      {history.length > 1 && (
        <details className="pt-3 border-t border-border/40">
          <summary className="cursor-pointer text-xs text-muted-foreground hover:text-blue">
            Historique cursus ({history.length} candidature{history.length > 1 ? "s" : ""})
          </summary>
          <ul className="mt-2 space-y-1.5 text-xs">
            {history.map((h) => (
              <li key={h.id} className="flex flex-wrap gap-2 border-b border-border/20 pb-1.5 last:border-b-0">
                <span className="font-mono text-blue">{h.reference}</span>
                <span className="text-cream">{h.programme} {h.annee}</span>
                {h.annee_academique && <span className="text-muted-foreground">· {h.annee_academique}</span>}
                <span className="ml-auto text-muted-foreground">{h.type_inscription}</span>
              </li>
            ))}
          </ul>
        </details>
      )}
    </div>
  );
}
