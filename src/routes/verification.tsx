import { createFileRoute } from "@tanstack/react-router";
import { useState } from "react";
import { ShieldCheck, ShieldAlert, Search, Loader2 } from "lucide-react";
import { getRecaptchaToken } from "@/lib/recaptcha";

export const Route = createFileRoute("/verification")({
  head: () => ({
    meta: [
      { title: "IPEC | Vérification" },
      {
        name: "description",
        content:
          "Vérifiez l'authenticité d'un document officiel émis par l'IPEC (candidature, confirmation, facture) à l'aide de son numéro de référence.",
      },
      { name: "robots", content: "noindex, nofollow" },
      { property: "og:title", content: "Vérification d'authenticité — IPEC" },
      {
        property: "og:description",
        content:
          "Outil de vérification réservé aux autorités, employeurs et établissements partenaires.",
      },
      { property: "og:url", content: "https://ipec.school/verification" },
    ],
    links: [{ rel: "canonical", href: "https://ipec.school/verification" }],
  }),
  component: VerificationPage,
});

const VERIFY_URL = "https://ipec.school/verify.php";

type VerifyResult = {
  valid: boolean;
  error?: string;
  reference?: string;
  document_type?: "candidature" | "facture" | "recu";
  document_label?: string;
  candidat?: string;
  programme?: string;
  programme_code?: string;
  annee?: string;
  specialisation?: string | null;
  annee_academique?: string;
  rentree?: string;
  date_creation?: string;
};

/**
 * Format attendu : IPEC-{4 lettres}-{4 chiffres}-{6 hex}
 * Le préfixe "IPEC-" est figé, on n'ajoute que les tirets pendant la frappe.
 */
const REF_PREFIX = "IPEC-";

function formatReference(raw: string): string {
  // Ne garde que les caractères alphanumériques, peu importe l'état du préfixe
  let body = raw.toUpperCase().replace(/[^A-Z0-9]/g, "");
  // Retire toutes les occurrences de "IPEC" en tête (gère "IPEC", "IPECIPEC", "IPE", etc.)
  while (body.startsWith("IPEC")) body = body.slice(4);

  // Segment 1 : 4 lettres (type de document)
  const kindMatch = body.match(/^[A-Z]{0,4}/);
  const kind = kindMatch ? kindMatch[0] : "";
  let after = body.slice(kind.length);

  // Si l'utilisateur a tapé un chiffre alors que le segment lettres n'est pas plein,
  // on ignore ce qui reste (sécurité), on prend juste 4 chiffres pour l'année
  const yearRaw = after.replace(/[^0-9]/g, "").slice(0, 4);
  // recompose après-année à partir des chiffres restants + lettres restantes (hex)
  const consumedYear = (() => {
    let n = 0, idx = 0;
    while (idx < after.length && n < yearRaw.length) {
      if (/[0-9]/.test(after[idx])) n++;
      idx++;
    }
    return idx;
  })();
  const afterYear = after.slice(consumedYear).replace(/[^0-9A-F]/g, "");
  const suffix = afterYear.slice(0, 6);

  // Reconstruction avec tirets (segment vide → on n'ajoute pas le tiret suivant)
  let out = REF_PREFIX + kind;
  if (kind.length === 4 && (yearRaw.length > 0 || suffix.length > 0)) {
    out += "-" + yearRaw;
  }
  if (yearRaw.length === 4 && suffix.length > 0) {
    out += "-" + suffix;
  }
  return out;
}

function VerificationPage() {
  const [reference, setReference] = useState(REF_PREFIX);
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<VerifyResult | null>(null);

  const handleReferenceChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setReference(formatReference(e.target.value));
  };

  // Empêche l'utilisateur de placer son curseur dans le préfixe figé
  const protectPrefix = (e: React.SyntheticEvent<HTMLInputElement>) => {
    const input = e.currentTarget;
    if ((input.selectionStart ?? 0) < REF_PREFIX.length) {
      input.setSelectionRange(REF_PREFIX.length, Math.max(input.selectionEnd ?? 0, REF_PREFIX.length));
    }
  };

  // Bloque Backspace/Delete quand le curseur est dans/au bord du préfixe
  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    const input = e.currentTarget;
    const start = input.selectionStart ?? 0;
    const end = input.selectionEnd ?? 0;
    if (e.key === "Backspace" && start === end && start <= REF_PREFIX.length) {
      e.preventDefault();
    }
    if (e.key === "Delete" && start === end && start < REF_PREFIX.length) {
      e.preventDefault();
    }
  };

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (loading) return;
    const ref = reference.trim().toUpperCase();
    if (!ref || ref === REF_PREFIX) return;

    setLoading(true);
    setResult(null);

    let recaptchaToken = "";
    try {
      recaptchaToken = await getRecaptchaToken("verification");
    } catch {
      recaptchaToken = "";
    }

    try {
      const res = await fetch(`${VERIFY_URL}?reference=${encodeURIComponent(ref)}`, {
        method: "GET",
        headers: {
          Accept: "application/json",
          "X-Recaptcha-Token": recaptchaToken,
          "X-Recaptcha-Action": "verification",
        },
      });
      const data: VerifyResult = await res.json().catch(() => ({
        valid: false,
        error: "Réponse invalide du serveur.",
      }));
      setResult(data);
    } catch {
      setResult({
        valid: false,
        error: "Impossible de joindre le service de vérification. Vérifiez votre connexion.",
      });
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <section className="py-20 lg:py-32 border-b border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-6">
            — Vérification d'authenticité
          </div>
          <h1 className="font-display text-5xl md:text-7xl text-cream leading-[1] max-w-4xl text-balance">
            Vérifier un <em className="text-gradient-blue not-italic">document IPEC</em>.
          </h1>
          <p className="mt-8 max-w-2xl text-muted-foreground leading-relaxed text-base">
            Saisissez le numéro de référence indiqué en bas du document (format{" "}
            <code className="text-cream bg-card px-1.5 py-0.5 rounded text-sm">
              IPEC-XXXX-AAAA-XXXXXX
            </code>
            ) pour confirmer son authenticité auprès de nos services.
          </p>
        </div>
      </section>

      <section className="py-20 lg:py-28">
        <div className="mx-auto max-w-3xl px-6 lg:px-10">
          <form onSubmit={handleSubmit} className="space-y-6">
            <div>
              <label
                htmlFor="reference"
                className="block text-xs uppercase tracking-widest text-blue mb-3"
              >
                Numéro de référence
              </label>
              <div className="flex flex-col sm:flex-row gap-3">
                <input
                  id="reference"
                  name="reference"
                  type="text"
                  required
                  value={reference}
                  onChange={handleReferenceChange}
                  onClick={protectPrefix}
                  onKeyUp={protectPrefix}
                  onKeyDown={handleKeyDown}
                  onFocus={protectPrefix}
                  onSelect={protectPrefix}
                  placeholder="IPEC-CAND-2026-A1B2C3"
                  maxLength={REF_PREFIX.length + 4 + 1 + 4 + 1 + 6}
                  autoComplete="off"
                  inputMode="text"
                  spellCheck={false}
                  className="flex-1 bg-card border border-border/60 px-4 py-3 rounded-sm text-cream uppercase tracking-wider font-mono focus:border-blue focus:outline-none transition-colors"
                />
                <button
                  type="submit"
                  disabled={loading || reference.trim() === REF_PREFIX || reference.trim() === ""}
                  className="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-sm bg-gradient-blue text-ink font-medium shadow-blue hover:opacity-90 transition-opacity disabled:opacity-60 disabled:cursor-not-allowed"
                >
                  {loading ? (
                    <>
                      <Loader2 size={16} className="animate-spin" />
                      Vérification…
                    </>
                  ) : (
                    <>
                      <Search size={16} />
                      Vérifier
                    </>
                  )}
                </button>
              </div>
              <p className="mt-3 text-xs text-muted-foreground/80">
                Les tirets sont insérés automatiquement. Tapez simplement le type de document
                (4 lettres), l'année (4 chiffres) puis le code à 6 caractères figurant sur le document.
              </p>
            </div>
          </form>

          {result && (
            <div
              role="status"
              aria-live="polite"
              className={`mt-10 p-8 rounded-sm border ${
                result.valid
                  ? "border-blue/40 bg-blue/5"
                  : "border-destructive/40 bg-destructive/5"
              }`}
            >
              {result.valid ? (
                <>
                  <div className="flex items-center gap-3 mb-6">
                    <ShieldCheck size={28} className="text-blue shrink-0" />
                    <div>
                      <div className="font-display text-2xl text-gradient-blue">
                        Document authentique
                      </div>
                      <div className="text-xs uppercase tracking-[0.25em] text-blue mt-1">
                        Référence vérifiée auprès de l'IPEC
                      </div>
                    </div>
                  </div>

                  <dl className="grid sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
                    <Field label="Référence document" value={result.reference} mono />
                    <Field label="Type de document" value={result.document_label} />
                    <Field label="Candidat·e" value={result.candidat} />
                    <Field label="Date d'enregistrement" value={result.date_creation} />
                    <Field
                      label="Programme"
                      value={
                        result.programme
                          ? `${result.programme} (${result.programme_code})`
                          : undefined
                      }
                    />
                    <Field label="Année" value={result.annee} />
                    <Field label="Année académique" value={result.annee_academique} />
                    <Field label="Rentrée" value={result.rentree} />
                    {result.specialisation && (
                      <Field label="Spécialisation" value={result.specialisation} />
                    )}
                  </dl>

                  {result.document_type === "candidature" && (
                    <p className="mt-6 pt-6 border-t border-border/30 text-xs text-muted-foreground leading-relaxed">
                      <strong className="text-cream">Important :</strong> ce document
                      atteste uniquement de la <em>soumission</em> d'une candidature à
                      l'IPEC. Il ne constitue en aucun cas une preuve d'inscription
                      définitive ni d'admission au programme. L'admission n'est effective
                      qu'après examen du dossier complet par la commission pédagogique et
                      notification écrite officielle de l'IPEC.
                    </p>
                  )}

                  <p className="mt-4 text-xs text-muted-foreground leading-relaxed">
                    Pour des raisons de protection des données (RGPD), le prénom et le nom
                    sont partiellement masqués. Pour toute demande d'authentification plus
                    poussée par une autorité officielle, contactez{" "}
                    <a
                      href="mailto:admission@ipec.school"
                      className="text-blue hover:underline"
                    >
                      admission@ipec.school
                    </a>
                    .
                  </p>
                </>
              ) : (
                <>
                  <div className="flex items-center gap-3 mb-4">
                    <ShieldAlert size={28} className="text-destructive shrink-0" />
                    <div className="font-display text-2xl text-destructive">
                      Document non reconnu
                    </div>
                  </div>
                  <p className="text-sm text-muted-foreground leading-relaxed">
                    {result.error ??
                      "Aucune candidature ne correspond à cette référence dans nos archives."}
                  </p>
                </>
              )}
            </div>
          )}

          <div className="mt-16 p-6 rounded-sm border border-border/40 bg-card/50">
            <h2 className="font-display text-lg text-cream mb-3">À l'attention des utilisateurs légitimes</h2>
            <p className="text-sm text-muted-foreground leading-relaxed">
              Cet outil est mis à la disposition de toute personne ou organisation souhaitant
              s'assurer de l'authenticité d'un document émis par l'IPEC : candidat·e·s,
              familles, établissements scolaires, employeurs, ambassades, administrations
              publiques ou partenaires. Le numéro de référence figure dans le pied de page de
              chaque document officiel. Aucune information personnelle complète n'est divulguée.
            </p>
            <p className="text-xs text-muted-foreground/80 leading-relaxed mt-4 pt-4 border-t border-border/30">
              Protection des données : conformément au RGPD, le prénom et le nom sont partiellement
              masqué et seules les informations strictement nécessaires à l'authentification sont
              affichées. La vérification est protégée par Google reCAPTCHA contre les usages
              automatisés. Pour plus d'informations, consultez notre{" "}
              <a href="/confidentialite" className="text-blue hover:underline">politique de confidentialité</a>.
            </p>
          </div>
        </div>
      </section>
    </>
  );
}

function Field({
  label,
  value,
  mono,
}: {
  label: string;
  value?: string | null;
  mono?: boolean;
}) {
  if (!value) return null;
  return (
    <div>
      <dt className="text-xs uppercase tracking-widest text-blue mb-1">{label}</dt>
      <dd className={`text-cream ${mono ? "font-mono text-sm" : ""}`}>{value}</dd>
    </div>
  );
}
