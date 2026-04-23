import { createFileRoute, Link, useNavigate } from "@tanstack/react-router";
import { Send, FileText, Mail, CheckCircle2, ArrowRight } from "lucide-react";
import { useEffect, useRef, useState } from "react";
import {
  formatRentreeDate,
  getNextSeptemberRentree,
  getNextFebruaryRentree,
  getUpcomingAcademicYearLabel,
} from "@/lib/academic-dates";

export const Route = createFileRoute("/inscription")({
  head: () => ({
    meta: [
      { title: "IPEC | Inscription" },
      { name: "description", content: "Déposez votre dossier d'inscription à l'IPEC, université privée en Belgique. Formulaire en ligne pour les programmes PAA et PEA. Réponse sous 7 jours." },
      { name: "keywords", content: "inscription école Bruxelles, inscription école Belgique, formulaire inscription Bruxelles, formulaire inscription Belgique, candidater institut privé Bruxelles, candidater institut privé Belgique, inscription université privée Bruxelles, inscription université privée Belgique, école de commerce Bruxelles inscription, IPEC inscription, étudier à Bruxelles, étudier en Belgique" },
      { property: "og:title", content: "Inscription — IPEC Bruxelles · Institut privé en Belgique" },
      { property: "og:description", content: "Formulaire d'inscription en ligne pour intégrer une école supérieure privée à Bruxelles. Réponse sous 7 jours." },
      { property: "og:url", content: "https://ipec.school/inscription" },
      { property: "og:image", content: "https://ipec.school/apple-touch-icon.png" },
      { name: "twitter:title", content: "Inscription — IPEC Bruxelles" },
      { name: "twitter:description", content: "Candidater en ligne à l'IPEC, institut privé en Belgique." },
      { name: "twitter:image", content: "https://ipec.school/apple-touch-icon.png" },
    ],
    links: [{ rel: "canonical", href: "https://ipec.school/inscription" }],
  }),
  component: Inscription,
});

type Programme = "PAA" | "PEA";

const yearsByProgramme: Record<Programme, { value: string; label: string }[]> = {
  PAA: [
    { value: "1", label: "1ʳᵉ année" },
    { value: "2", label: "2ᵉ année (Bac+1)" },
    { value: "3", label: "3ᵉ année (Bac+2)" },
  ],
  PEA: [
    { value: "4", label: "1ʳᵉ année — PEA1 (Bac+3)" },
    { value: "5", label: "2ᵉ année — PEA2 (Bac+4)" },
  ],
};

function Inscription() {
  const [sent, setSent] = useState(true); // TEMP: prévisualisation écran de confirmation
  const [submitting, setSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const [programme, setProgramme] = useState<Programme>("PAA");
  const [annee, setAnnee] = useState<string>("1");
  const [countdown, setCountdown] = useState(10);
  const [rgpdChecked, setRgpdChecked] = useState(false);
  const [conditionsChecked, setConditionsChecked] = useState(false);
  const confirmationRef = useRef<HTMLDivElement | null>(null);
  const navigate = useNavigate();

  useEffect(() => {
    if (sent && confirmationRef.current) {
      confirmationRef.current.scrollIntoView({ behavior: "smooth", block: "center" });
      confirmationRef.current.focus();
      const interval = setInterval(() => {
        setCountdown((c) => (c > 0 ? c - 1 : 0));
      }, 1000);
      const timer = setTimeout(() => {
        // navigate({ to: "/" }); // TEMP désactivé pour prévisualisation
      }, 10000);
      return () => {
        clearTimeout(timer);
        clearInterval(interval);
      };
    }
  }, [sent, navigate]);

  const years = yearsByProgramme[programme];
  const allowUndecided = programme === "PAA" && (annee === "1" || annee === "2");
  const septembreRentree = formatRentreeDate(getNextSeptemberRentree());
  const fevrierRentree = formatRentreeDate(getNextFebruaryRentree());
  const academicYearLabel = getUpcomingAcademicYearLabel();

  const handleProgrammeChange = (value: Programme) => {
    setProgramme(value);
    setAnnee(yearsByProgramme[value][0].value);
  };

  const MAILER_URL = "https://ipec.school/mailer.php";

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (submitting) return;
    setErrorMsg(null);
    setSubmitting(true);

    const fd = new FormData(e.currentTarget);
    const annee = String(fd.get("annee") ?? "");
    const anneeLabel = years.find((y) => y.value === annee)?.label ?? annee;

    const payload = {
      type: "inscription",
      civilite: String(fd.get("civilite") ?? ""),
      prenom: String(fd.get("prenom") ?? ""),
      nom: String(fd.get("nom") ?? ""),
      dateNaissance: String(fd.get("dateNaissance") ?? ""),
      nationalite: String(fd.get("nationalite") ?? ""),
      email: String(fd.get("email") ?? ""),
      telephone: String(fd.get("telephone") ?? ""),
      adresse: String(fd.get("adresse") ?? ""),
      paysResidence: String(fd.get("paysResidence") ?? ""),
      programme: String(fd.get("programme") ?? ""),
      annee: anneeLabel,
      specialisation: String(fd.get("specialisation") ?? ""),
      rentree: String(fd.get("rentree") ?? ""),
      message: String(fd.get("message") ?? ""),
      website: String(fd.get("website") ?? ""), // honeypot anti-bot
    };

    try {
      const res = await fetch(MAILER_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        setErrorMsg(data?.error ?? "Une erreur est survenue. Réessayez.");
        return;
      }
      setSent(true);
    } catch {
      setErrorMsg("Impossible d'envoyer la candidature. Vérifiez votre connexion.");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <>
      {/* HERO */}
      <section className="py-20 lg:py-32 border-b border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-6">— Inscription</div>
          <h1 className="font-display text-5xl md:text-7xl text-cream leading-[1] max-w-4xl text-balance">
            Déposez votre <em className="text-gradient-blue not-italic">candidature</em>.
          </h1>
          <p className="mt-8 max-w-2xl text-muted-foreground leading-relaxed text-base">
            Remplissez le formulaire ci-dessous pour engager votre dossier d'admission.
            Notre équipe revient vers vous sous 7 jours ouvrables pour la suite de la procédure.
          </p>
        </div>
      </section>

      {/* FORM */}
      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-10 grid lg:grid-cols-12 gap-12">
          {/* Aside : rappel du process */}
          <aside className="lg:col-span-4 space-y-8">
            <div>
              <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Étapes</div>
              <h2 className="font-display text-2xl text-cream mb-2">Comment ça se passe ?</h2>
              <p className="text-sm text-muted-foreground leading-relaxed">
                Une procédure simple, en quatre temps.
              </p>
            </div>

            <ol className="relative space-y-4 text-sm before:content-[''] before:absolute before:left-[15px] before:top-2 before:bottom-2 before:w-px before:bg-gradient-to-b before:from-blue/60 before:via-blue/30 before:to-transparent">
              {[
                { n: "01", t: "Candidature en ligne", d: "Remplissez le formulaire de candidature." },
                { n: "02", t: "Suite par e-mail", d: "Vous recevez un e-mail récapitulatif détaillant la procédure à suivre et les pièces à transmettre." },
                { n: "03", t: "Entretien en visio", d: "Échange en visioconférence avec notre équipe pédagogique pour préciser votre projet." },
                { n: "04", t: "Réponse d'admission", d: "Vous recevez la décision d'admission par e-mail, puis confirmez votre inscription." },
              ].map((s) => (
                <li key={s.n} className="relative flex gap-4 items-start">
                  <div className="relative z-10 flex items-center justify-center w-8 h-8 rounded-full border border-blue/50 bg-card text-blue font-display text-xs shrink-0 shadow-blue">
                    {s.n}
                  </div>
                  <div className="flex-1 pt-1 pb-4 border-b border-border/30 last:border-b-0">
                    <div className="text-cream mb-1 font-medium">{s.t}</div>
                    <div className="text-muted-foreground leading-relaxed text-xs">{s.d}</div>
                  </div>
                </li>
              ))}
            </ol>

            <div className="p-6 rounded-sm border border-blue/30 bg-blue/5">
              <FileText className="text-blue mb-3" size={22} strokeWidth={1.5} />
              <div className="text-xs uppercase tracking-widest text-blue mb-2">Conditions & tarifs</div>
              <p className="text-sm text-muted-foreground leading-relaxed mb-3">
                Retrouvez l'ensemble des modalités d'admission, pièces à fournir et frais de scolarité.
              </p>
              <Link to="/admissions" className="inline-flex items-center gap-2 text-sm text-blue hover:underline">
                Voir la page Admissions <ArrowRight size={14} />
              </Link>
            </div>
          </aside>

          {/* Form */}
          <div className="lg:col-span-7 lg:col-start-6">
            {sent ? (
              <div
                ref={confirmationRef}
                tabIndex={-1}
                role="status"
                aria-live="polite"
                className="p-10 rounded-sm border border-blue/40 bg-blue/5 text-center scroll-mt-24 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue/60"
              >
                <CheckCircle2 className="text-blue mx-auto mb-4" size={40} strokeWidth={1.5} />
                <div className="font-display text-3xl text-gradient-blue mb-3">Candidature enregistrée</div>
                <p className="text-muted-foreground leading-relaxed mb-3">
                  Votre demande d'admission a bien été prise en compte. Un e-mail de confirmation vous a été envoyé.
                </p>
                <p className="text-xs uppercase tracking-[0.25em] text-blue">
                  Redirection vers l'accueil dans {countdown}s…
                </p>
              </div>
            ) : (
              <form className="space-y-6" onSubmit={handleSubmit}>
                {/* Honeypot anti-bot — caché aux humains, rempli par les bots */}
                <input
                  type="text"
                  name="website"
                  tabIndex={-1}
                  autoComplete="off"
                  aria-hidden="true"
                  style={{ position: "absolute", left: "-9999px", width: "1px", height: "1px", opacity: 0 }}
                />

                <div>
                  <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Votre dossier</div>
                  <h2 className="font-display text-2xl text-cream mb-2">Renseignez vos informations</h2>
                  <p className="text-sm text-muted-foreground leading-relaxed">
                    Tous les champs marqués sont obligatoires.
                  </p>
                </div>

                {/* Identité */}
                <div>
                  <label className="block text-xs uppercase tracking-widest text-blue mb-3">Civilité</label>
                  <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    {["Mr", "Mme", "Mlle", "Mx — non binaire"].map((opt) => (
                      <label
                        key={opt}
                        className="flex items-center gap-2 px-4 py-3 rounded-sm border border-border/60 bg-card cursor-pointer hover:border-blue transition-colors text-sm text-cream has-[:checked]:border-blue has-[:checked]:bg-blue/10"
                      >
                        <input
                          type="radio"
                          name="civilite"
                          value={opt}
                          required
                          className="accent-blue shrink-0"
                        />
                        <span>{opt}</span>
                      </label>
                    ))}
                  </div>
                </div>

                <div className="grid md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-xs uppercase tracking-widest text-blue mb-3">Prénom</label>
                    <input required name="prenom" type="text" maxLength={100} className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors" />
                  </div>
                  <div>
                    <label className="block text-xs uppercase tracking-widest text-blue mb-3">Nom</label>
                    <input required name="nom" type="text" maxLength={100} className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors" />
                  </div>
                </div>

                {/* État civil */}
                <div className="grid md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-xs uppercase tracking-widest text-blue mb-3">Date de naissance</label>
                    <input
                      required
                      name="dateNaissance"
                      type="date"
                      max={new Date().toISOString().split("T")[0]}
                      className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors"
                    />
                  </div>
                  <div>
                    <label className="block text-xs uppercase tracking-widest text-blue mb-3">Nationalité</label>
                    <input required name="nationalite" type="text" maxLength={100} className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors" />
                  </div>
                </div>

                {/* Contact */}
                <div className="grid md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-xs uppercase tracking-widest text-blue mb-3">E-mail</label>
                    <input required name="email" type="email" maxLength={255} className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors" />
                  </div>
                  <div>
                    <label className="block text-xs uppercase tracking-widest text-blue mb-3">Téléphone</label>
                    <input name="telephone" type="tel" maxLength={30} className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors" />
                  </div>
                </div>

                {/* Adresse */}
                <div>
                  <label className="block text-xs uppercase tracking-widest text-blue mb-3">Adresse</label>
                  <textarea
                    required
                    name="adresse"
                    rows={2}
                    maxLength={250}
                    placeholder="Rue, numéro, code postal, ville"
                    className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors resize-none"
                  />
                </div>

                <div>
                  <label className="block text-xs uppercase tracking-widest text-blue mb-3">Pays de résidence</label>
                  <input required name="paysResidence" type="text" maxLength={100} className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors" />
                </div>


                <div className="grid md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-xs uppercase tracking-widest text-blue mb-3">Programme</label>
                    <select
                      required
                      name="programme"
                      value={programme}
                      onChange={(e) => handleProgrammeChange(e.target.value as Programme)}
                      className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors"
                    >
                      <option value="PAA">PAA — Administration des Affaires</option>
                      <option value="PEA">PEA — Programme Exécutif Avancé</option>
                    </select>
                  </div>
                  <div>
                    <label className="block text-xs uppercase tracking-widest text-blue mb-3">Année visée</label>
                    <select
                      required
                      name="annee"
                      value={annee}
                      onChange={(e) => setAnnee(e.target.value)}
                      className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors"
                    >
                      {years.map((y) => (
                        <option key={y.value} value={y.value}>{y.label}</option>
                      ))}
                    </select>
                  </div>
                </div>

                <div>
                  <label className="block text-xs uppercase tracking-widest text-blue mb-3">Spécialisation souhaitée</label>
                  <select
                    required
                    name="specialisation"
                    defaultValue=""
                    className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors"
                  >
                    <option value="" disabled>Sélectionnez une spécialisation</option>
                    <option>Management</option>
                    <option>Marketing</option>
                    <option>Relations Internationales</option>
                    <option>Économie & Finance</option>
                    {allowUndecided && <option>Je ne sais pas encore</option>}
                  </select>
                  {allowUndecided ? (
                    <p className="mt-2 text-xs text-muted-foreground leading-relaxed">
                      En 1ʳᵉ et 2ᵉ année du PAA, ce choix reste indicatif : la spécialisation se précise progressivement au fil du cursus.
                    </p>
                  ) : (
                    <p className="mt-2 text-xs text-muted-foreground leading-relaxed">
                      À ce niveau d'études, la spécialisation doit être définitivement choisie au moment de l'inscription.
                    </p>
                  )}
                </div>

                <div>
                  <label className="block text-xs uppercase tracking-widest text-blue mb-3">
                    Rentrée envisagée — Année {academicYearLabel}
                  </label>
                  <select required name="rentree" className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors">
                    <option>Rentrée principale — {septembreRentree}</option>
                    <option>Rentrée décalée — {fevrierRentree}</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs uppercase tracking-widest text-blue mb-3">
                    Message <span className="text-muted-foreground normal-case tracking-normal">(facultatif)</span>
                  </label>
                  <textarea
                    name="message"
                    rows={5}
                    maxLength={1500}
                    placeholder="Parcours, motivations, projet professionnel…"
                    className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors resize-none"
                  />
                </div>

                <div className={`flex items-start gap-3 text-sm p-4 rounded-sm border transition-colors ${rgpdChecked ? "border-success/50 bg-success/10 text-cream" : "border-blue/30 bg-blue/5 text-muted-foreground"}`}>
                  <input
                    required
                    id="rgpd"
                    type="checkbox"
                    checked={rgpdChecked}
                    onChange={(e) => setRgpdChecked(e.target.checked)}
                    className={`mt-1 ${rgpdChecked ? "accent-success" : "accent-blue"}`}
                  />
                  <label htmlFor="rgpd" className="leading-relaxed">
                    J'accepte que les informations saisies soient utilisées dans le cadre de ma candidature à l'IPEC.
                  </label>
                </div>

                <div className={`flex items-start gap-3 text-sm p-4 rounded-sm border transition-colors ${conditionsChecked ? "border-success/50 bg-success/10 text-cream" : "border-blue/30 bg-blue/5 text-muted-foreground"}`}>
                  <input
                    required
                    id="conditions"
                    type="checkbox"
                    checked={conditionsChecked}
                    onChange={(e) => setConditionsChecked(e.target.checked)}
                    className={`mt-1 ${conditionsChecked ? "accent-success" : "accent-blue"}`}
                  />
                  <label htmlFor="conditions" className="leading-relaxed">
                    J'ai lu et j'accepte les{" "}
                    <Link to="/cgv" className="text-blue hover:underline">
                      conditions particulières d'admission
                    </Link>{" "}
                    de l'IPEC.
                  </label>
                </div>

                {errorMsg && (
                  <div className="p-4 rounded-sm border border-destructive/40 bg-destructive/10 text-sm text-destructive-foreground">
                    {errorMsg}
                  </div>
                )}

                <div className="flex flex-wrap items-center gap-4 pt-2">
                  <button
                    type="submit"
                    disabled={submitting}
                    className="inline-flex items-center gap-2 px-8 py-4 rounded-sm bg-gradient-blue text-ink font-medium shadow-blue hover:opacity-90 transition-opacity disabled:opacity-60 disabled:cursor-not-allowed"
                  >
                    {submitting ? "Envoi en cours…" : "Envoyer ma candidature"} <Send size={16} />
                  </button>
                  <Link
                    to="/contact"
                    className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-blue transition-colors"
                  >
                    <Mail size={14} /> Une question avant de candidater ?
                  </Link>
                </div>
              </form>
            )}
          </div>
        </div>
      </section>
    </>
  );
}
