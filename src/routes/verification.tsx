import { createFileRoute } from "@tanstack/react-router";
import { useState } from "react";
import { ShieldCheck, ShieldAlert, Search, Loader2 } from "lucide-react";
import { getRecaptchaToken } from "@/lib/recaptcha";

export const Route = createFileRoute("/verification")({
  head: () => ({
    meta: [
      { title: "IPEC | Vérification de document" },
      {
        name: "description",
        content:
          "Vérifiez l'authenticité d'un document officiel émis par l'IPEC (candidature, confirmation, facture) à l'aide de son numéro de référence.",
      },
      { name: "robots", content: "index, follow" },
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
  document_type?: "candidature" | "facture";
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

function VerificationPage() {
  const [reference, setReference] = useState("");
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<VerifyResult | null>(null);

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (loading) return;
    const ref = reference.trim().toUpperCase();
    if (!ref) return;

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
              IPEC-AAAA-XXXXXX
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
                  onChange={(e) => setReference(e.target.value)}
                  placeholder="IPEC-CAND-2026-A1B2C3"
                  maxLength={32}
                  autoComplete="off"
                  className="flex-1 bg-card border border-border/60 px-4 py-3 rounded-sm text-cream uppercase tracking-wider focus:border-blue focus:outline-none transition-colors"
                />
                <button
                  type="submit"
                  disabled={loading || reference.trim() === ""}
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
                    Pour des raisons de protection des données (RGPD), le nom de famille
                    est partiellement masqué. Pour toute demande d'authentification plus
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
            <h2 className="font-display text-lg text-cream mb-3">À l'attention des autorités</h2>
            <p className="text-sm text-muted-foreground leading-relaxed">
              Cet outil est mis à disposition des établissements, employeurs, ambassades et
              autorités administratives souhaitant vérifier l'authenticité d'un document émis
              par l'IPEC. La référence figure dans le pied de page de chaque document officiel.
              Aucune information personnelle complète n'est divulguée.
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
