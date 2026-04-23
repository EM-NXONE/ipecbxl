import { createFileRoute, Link } from "@tanstack/react-router";
import { FileText, CreditCard, Mail, Calendar, ArrowRight, CheckCircle2, AlertTriangle, CalendarDays, GraduationCap } from "lucide-react";
import {
  getNextSeptemberRentree,
  getNextFebruaryRentree,
  getUpcomingAcademicYearLabel,
  formatRentreeDate,
} from "@/lib/academic-dates";

export const Route = createFileRoute("/admissions")({
  head: () => ({
    meta: [
      { title: "Admissions — Candidater à un institut privé en Belgique · IPEC Bruxelles" },
      { name: "description", content: "Admissions ouvertes à l'IPEC, institut privé en Belgique. Frais de dossier 400 €, scolarité PAA 4 900 €/an, PEA 5 900 €/an. Étudier à Bruxelles : candidatures belges et internationales." },
      { name: "keywords", content: "admissions Bruxelles, admissions Belgique, candidature école privée Bruxelles, candidature école privée Belgique, inscription université privée Bruxelles, inscription université privée Belgique, institut privé Bruxelles admissions, institut privé Belgique admissions, frais scolarité Bruxelles, étudier à Bruxelles, étudier en Belgique, admission BAC+3 Bruxelles, admission BAC+5 Bruxelles, IPEC admissions" },
      { property: "og:title", content: "Admissions IPEC — Institut privé Belgique · Bruxelles" },
      { property: "og:description", content: "Process simple, frais transparents, accueil des étudiants belges et internationaux dans une école supérieure privée à Bruxelles." },
      { property: "og:url", content: "https://ipec.school/admissions" },
      { property: "og:image", content: "https://ipec.school/apple-touch-icon.png" },
      { name: "twitter:title", content: "Admissions — IPEC Bruxelles" },
      { name: "twitter:description", content: "Candidater à un institut privé en Belgique : process clair, frais transparents." },
      { name: "twitter:image", content: "https://ipec.school/apple-touch-icon.png" },
    ],
    links: [{ rel: "canonical", href: "https://ipec.school/admissions" }],
  }),
  component: Admissions,
});

const steps = [
  { n: "01", icon: FileText, t: "Candidature en ligne", d: "Remplissez votre dossier en ligne, joignez vos pièces justificatives et réglez les frais de dossier." },
  { n: "02", icon: Mail, t: "Entretien en visio", d: "Échange en visioconférence avec notre équipe pédagogique pour préciser votre projet." },
  { n: "03", icon: Calendar, t: "Réponse sous 7 jours", d: "Après réception des frais de dossier, vous recevez la décision d'admission par e-mail." },
  { n: "04", icon: CreditCard, t: "Confirmation d'inscription", d: "Versement de la première tranche des frais de scolarité." },
];

function Admissions() {
  const septembreRentree = getNextSeptemberRentree();
  const fevrierRentree = getNextFebruaryRentree();
  const academicYear = getUpcomingAcademicYearLabel();
  return (
    <>
      {/* HERO */}
      <section className="py-20 lg:py-32 border-b border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-6">— Admissions</div>
          <h1 className="font-display md:text-7xl text-cream leading-[1] max-w-4xl text-balance text-5xl">
            Rejoignez la <em className="text-gradient-blue not-italic">prochaine</em> promotion.
          </h1>
          <p className="mt-8 max-w-2xl text-muted-foreground leading-relaxed text-base">
            Les inscriptions sont ouvertes pour toutes les années de cursus.
            Notre équipe vous accompagne à chaque étape.
          </p>
        </div>
      </section>

      {/* 1. CONDITIONS — Suis-je éligible ? */}
      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Conditions d'admission</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-2xl text-balance">
            Une porte d'entrée à <em className="text-gradient-blue not-italic">chaque niveau</em>.
          </h2>

          <div className="grid md:grid-cols-2 gap-6">
            {[
              {
                year: "1ʳᵉ année",
                title: "Entrée post-secondaire",
                desc: "Titulaires du CESS (ou équivalent) ou en dernière année de secondaire : candidatez à tout moment, votre inscription sera, dans le second cas, définitivement validée à l'obtention de votre diplôme.",
              },
              {
                year: "2ᵉ année",
                title: "Admission Bac+1",
                desc: "L'intégration en deuxième année est ouverte aux étudiants ayant validé une première année d'études supérieures, soit 60 crédits.",
              },
              {
                year: "3ᵉ année",
                title: "Admission Bac+2",
                desc: "Tout candidat justifiant d'un Bac+2 et de 120 crédits peut postuler en troisième année.",
              },
              {
                year: "4ᵉ et 5ᵉ année",
                title: "Admission directe en PEA",
                desc: "Les titulaires d'un Bac+3 (180 crédits) peuvent intégrer la première année du PEA ; ceux justifiant d'un Bac+4 (240 crédits) accèdent à la deuxième année. La spécialisation est choisie dès l'admission.",
              },
            ].map((c) => (
              <div key={c.year} className="p-10 rounded-sm border border-border/60 bg-card/50">
                <GraduationCap className="text-blue mb-6" size={28} strokeWidth={1.5} />
                <div className="text-xs text-blue uppercase tracking-widest mb-3">{c.year}</div>
                <h3 className="font-display text-xl text-cream mb-4">{c.title}</h3>
                <p className="text-sm text-muted-foreground leading-relaxed">{c.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* 2. PROCESS — Comment je candidate ? */}
      <section className="py-20 lg:py-32 bg-surface border-y border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Process</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-2xl text-balance">
            Quatre étapes pour rejoindre l'IPEC.
          </h2>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-px bg-border/40">
            {steps.map((s) => (
              <div key={s.n} className="bg-background p-8 hover:bg-card transition-colors">
                <s.icon className="text-blue mb-6" size={28} strokeWidth={1.5} />
                <div className="text-xs text-blue uppercase tracking-widest mb-3">Étape {s.n}</div>
                <h3 className="font-display text-xl text-cream mb-3">{s.t}</h3>
                <p className="text-sm text-muted-foreground leading-relaxed">{s.d}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* 3. MODALITÉS PRATIQUES — Pièces & calendrier */}
      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Constituer son dossier</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-2xl text-balance">
            Pièces à fournir et calendrier.
          </h2>

          <div className="grid lg:grid-cols-2 gap-6 mb-6">
            {/* Pièces à fournir */}
            <div className="p-10 rounded-sm border border-border/60 bg-card/50">
              <FileText className="text-blue mb-6" size={28} strokeWidth={1.5} />
              <h3 className="font-display text-2xl text-cream mb-6">Pièces à fournir</h3>
              <ul className="space-y-3 text-sm text-muted-foreground">
                {[
                  "Copie de votre pièce d'identité ou passeport",
                  "Diplômes et relevés de notes des trois dernières années",
                  "CV à jour et lettre de motivation",
                  "Photo d'identité récente au format numérique",
                  "Justificatif de niveau de français (facultatif)",
                ].map((item) => (
                  <li key={item} className="flex gap-3">
                    <CheckCircle2 size={16} className="text-blue shrink-0 mt-0.5" />
                    <span>{item}</span>
                  </li>
                ))}
              </ul>
            </div>

            {/* Calendrier — empilé verticalement à droite */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-6">
              <div className="p-8 rounded-sm border border-border/60 bg-card/50">
                <CalendarDays className="text-blue mb-5" size={28} strokeWidth={1.5} />
                <div className="text-blue uppercase tracking-widest text-xs mb-2">Rentrée principale · {academicYear}</div>
                <h3 className="font-display text-xl text-cream leading-snug">
                  {formatRentreeDate(septembreRentree)}
                </h3>
                <p className="text-sm text-muted-foreground mt-3 leading-relaxed">
                  Démarrage du cursus complet pour l'année académique {academicYear}.
                </p>
              </div>

              <div className="p-8 rounded-sm border border-border/60 bg-card/50">
                <CalendarDays className="text-blue mb-5" size={28} strokeWidth={1.5} />
                <div className="text-blue uppercase tracking-widest text-xs mb-2">Rentrée décalée</div>
                <h3 className="font-display text-xl text-cream leading-snug">
                  {formatRentreeDate(fevrierRentree)}
                </h3>
                <p className="text-sm text-muted-foreground mt-3 leading-relaxed">
                  Pour intégrer l'IPEC en cours d'année.
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>


      {/* 4. TARIFS & PAIEMENT */}
      <section className="py-20 lg:py-32 bg-surface border-y border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Frais de scolarité</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-2xl text-balance">
            Une grille claire, sans surprise.
          </h2>

          <div className="grid md:grid-cols-2 gap-6 mb-6">
            <div className="p-10 rounded-sm border border-border/60 bg-card/50">
              <div className="font-display text-5xl text-gradient-blue mb-2">PAA</div>
              <div className="text-sm text-blue uppercase tracking-widest mb-8">BAC+1 à BAC+3</div>
              <div className="font-display text-6xl text-cream">4 900 <span className="text-2xl text-muted-foreground">€/an</span></div>
              <p className="text-sm text-muted-foreground mt-6">Programme en Administration des Affaires</p>
            </div>
            <div className="p-10 rounded-sm border border-border/60 bg-card/50">
              <div className="font-display text-5xl text-gradient-blue mb-2">PEA</div>
              <div className="text-sm text-blue uppercase tracking-widest mb-8">BAC+4 et BAC+5</div>
              <div className="font-display text-6xl text-cream">5 900 <span className="text-2xl text-muted-foreground">€/an</span></div>
              <p className="text-sm text-muted-foreground mt-6">Programme Exécutif Avancé</p>
            </div>
          </div>

          <div className="grid md:grid-cols-2 gap-6 mb-6">
            <div className="p-8 rounded-sm border border-blue/40 bg-blue/5">
              <div className="text-xs uppercase tracking-widest text-blue mb-2">Frais de dossier</div>
              <div className="font-display text-3xl text-cream mb-3">400 €</div>
              <p className="text-sm text-muted-foreground leading-relaxed">
                Réglés au dépôt de la candidature, ils couvrent l'instruction administrative
                et pédagogique de votre dossier.
              </p>
            </div>
            <div className="p-8 rounded-sm border border-blue/40 bg-blue/5">
              <div className="text-xs uppercase tracking-widest text-blue mb-2">Règlement de la scolarité</div>
              <div className="font-display text-3xl text-cream mb-3">3 000 € + solde</div>
              <p className="text-sm text-muted-foreground leading-relaxed">
                Première tranche de 3 000 € à la confirmation d'inscription, puis solde
                (1 900 € en PAA, 2 900 € en PEA). Un plan de paiement échelonné peut être
                convenu avec le service administratif.
              </p>
            </div>
          </div>

          {/* Modalités de paiement */}
          <div className="p-10 rounded-sm border border-border/60 bg-card/50">
            <CreditCard className="text-blue mb-6" size={28} strokeWidth={1.5} />
            <h3 className="font-display text-2xl text-cream mb-3">Modalités de paiement</h3>
            <p className="text-sm text-muted-foreground leading-relaxed mb-8 max-w-3xl">
              Les frais de scolarité sont réglés en deux tranches. Un échéancier
              personnalisé peut être convenu, sur demande, avec le service administratif.
            </p>

            <div className="grid md:grid-cols-2 gap-4 mb-8">
              <div className="p-6 rounded-sm border border-blue/30 bg-blue/5">
                <div className="text-xs uppercase tracking-widest text-blue mb-2">1ʳᵉ tranche</div>
                <div className="font-display text-xl text-cream mb-2">3 000 € à l'inscription</div>
                <p className="text-sm text-muted-foreground leading-relaxed">
                  Versée à la confirmation d'inscription, elle valide définitivement votre place.
                </p>
              </div>
              <div className="p-6 rounded-sm border border-blue/30 bg-blue/5">
                <div className="text-xs uppercase tracking-widest text-blue mb-2">2ᵉ tranche — solde</div>
                <div className="font-display text-xl text-cream mb-2">1 900 € (PAA) · 2 900 € (PEA)</div>
                <p className="text-sm text-muted-foreground leading-relaxed">
                  Étudiants en présentiel : avant le début effectif des cours.
                  Étudiants internationaux : à l'arrivée à l'institut.
                </p>
              </div>
            </div>

            <div className="grid md:grid-cols-2 gap-6">
              <div>
                <div className="text-xs uppercase tracking-widest text-blue mb-3">Plan de paiement</div>
                <p className="text-sm text-muted-foreground leading-relaxed">
                  L'octroi d'un échéancier relève de la seule appréciation du service
                  administratif de l'IPEC, sans obligation de motivation. Toute demande
                  est examinée au cas par cas et n'ouvre aucun droit acquis ni recours.
                </p>
              </div>
              <div>
                <div className="text-xs uppercase tracking-widest text-blue mb-3">Moyens acceptés</div>
                <ul className="grid grid-cols-2 gap-2 text-sm text-muted-foreground">
                  <li className="flex gap-2"><CheckCircle2 size={16} className="text-blue shrink-0 mt-0.5" /><span>Virement SEPA</span></li>
                  <li className="flex gap-2"><CheckCircle2 size={16} className="text-blue shrink-0 mt-0.5" /><span>Bancontact</span></li>
                  <li className="flex gap-2"><CheckCircle2 size={16} className="text-blue shrink-0 mt-0.5" /><span>Carte de crédit</span></li>
                  <li className="flex gap-2"><CheckCircle2 size={16} className="text-blue shrink-0 mt-0.5" /><span>Espèces<span className="opacity-60"> (Limites légales)</span></span></li>
                </ul>
              </div>
            </div>
          </div>

          <div className="mt-10 p-8 rounded-sm border border-blue/30 bg-blue/5">
            <div className="flex items-start gap-4">
              <CheckCircle2 className="text-blue shrink-0 mt-1" size={24} strokeWidth={1.5} />
              <div>
                <div className="text-xs uppercase tracking-[0.25em] text-blue mb-2">Tout inclus</div>
                <h3 className="font-display text-xl text-cream mb-3">Aucun frais en cours d'année</h3>
                <p className="text-sm text-muted-foreground leading-relaxed">
                  Les droits de scolarité couvrent l'ensemble des activités pédagogiques
                  de l'année académique : cours, syllabi, séminaires, conférences et
                  visites extérieures auprès des institutions européennes.
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* 5. AVIS HORS UE */}
      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="p-10 rounded-sm border border-blue/30 bg-blue/5 flex flex-col md:flex-row gap-6">
            <AlertTriangle className="text-blue shrink-0" size={32} strokeWidth={1.5} />
            <div>
              <div className="text-xs uppercase tracking-[0.25em] text-blue mb-3">Avis aux candidats résidents hors U.E.</div>
              <h2 className="font-display text-2xl md:text-3xl text-cream mb-4">Justificatifs financiers et visa</h2>
              <p className="text-sm md:text-base text-muted-foreground leading-relaxed mb-4">
                Les candidats résidents hors Union européenne doivent fournir leurs propres
                justificatifs d'autosuffisance financière auprès des autorités belges.
                L'IPEC ne propose pas de blocage de fonds sur ses comptes. Les candidatures
                hors UE sont en nombre limité chaque année : nous vous recommandons d'anticiper
                vos démarches consulaires dès l'admission confirmée.
              </p>

              <div className="rounded-sm border border-blue/30 bg-background/40 p-5 mb-4">
                <div className="text-xs uppercase tracking-[0.25em] text-blue mb-2">Attestation de virement irrévocable (AVI)</div>
                <p className="text-sm text-muted-foreground leading-relaxed mb-3">
                  Actuellement, l'Office des étrangers (OE) n'accepte que les attestations
                  de virement irrévocable (AVI) établies par les sociétés{" "}
                  <a
                    href="https://www.studely.com/fr/caution-bancaire-etudiante"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-cream underline decoration-blue/50 underline-offset-2 hover:decoration-blue"
                  >
                    Studely
                  </a>{" "}
                  et{" "}
                  <a
                    href="https://www.ready-study-go.com/"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-cream underline decoration-blue/50 underline-offset-2 hover:decoration-blue"
                  >
                    Ready Study Go International
                  </a>.
                  L'OE n'est toutefois pas lié contractuellement à ces sociétés et ne peut
                  pas être tenu pour responsable en cas de manquement à leurs obligations
                  vis-à-vis de l'étudiant.
                </p>
                <a
                  href="https://dofi.ibz.be/fr/themes/ressortissants-dun-pays-tiers/etudes/favoris/moyens-de-subsistance-suffisants"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="inline-flex items-center gap-2 text-sm text-blue hover:underline"
                >
                  Voir la page officielle de l'Office des étrangers <ArrowRight size={14} />
                </a>
              </div>

              <Link to="/international" className="inline-flex items-center gap-2 text-sm text-blue hover:underline">
                Page Étudiants internationaux <ArrowRight size={14} />
              </Link>
            </div>
          </div>
        </div>
      </section>

      {/* CTA final retiré : la page entière EST l'invitation à candidater. */}
    </>
  );
}
