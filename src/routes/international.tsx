import { createFileRoute, Link } from "@tanstack/react-router";
import {
  Plane,
  FileCheck,
  Globe2,
  Users,
  Stamp,
  Wallet,
  HeartPulse,
  Languages,
  Home,
  ClipboardList,
  CalendarClock,
  AlertTriangle,
  ArrowRight,
  CheckCircle2,
} from "lucide-react";
import brusselsImg from "@/assets/brussels.jpg";

export const Route = createFileRoute("/international")({
  head: () => ({
    meta: [
      { title: "Étudiants internationaux — IPEC Bruxelles" },
      { name: "description", content: "Démarches visa D, documents requis, moyens de subsistance et accompagnement à l'arrivée. L'IPEC accueille les étudiants hors UE à Bruxelles." },
      { property: "og:title", content: "Étudiants internationaux à l'IPEC Bruxelles" },
      { property: "og:description", content: "Étudier à Bruxelles depuis l'étranger : visa D, AVI, équivalence de diplôme et accompagnement." },
      { property: "og:image", content: brusselsImg },
    ],
  }),
  component: International,
});

const timeline = [
  {
    n: "01",
    icon: ClipboardList,
    t: "Candidature à l'IPEC",
    d: "Déposez votre dossier en ligne. Après admission et règlement de la première tranche, l'IPEC vous délivre l'attestation d'inscription au format officiel exigé par les autorités belges.",
  },
  {
    n: "02",
    icon: Wallet,
    t: "Justificatifs financiers (AVI)",
    d: "Constituez votre attestation de virement irrévocable auprès de Studely ou Ready Study Go International — seules sociétés actuellement acceptées par l'Office des étrangers.",
  },
  {
    n: "03",
    icon: FileCheck,
    t: "Équivalence de diplôme",
    d: "Facultative dans le cas d'un institut privé comme l'IPEC. Pour intégrer une 1ʳᵉ année de cycle dans un établissement public, l'équivalence du diplôme secondaire se demande auprès du service compétent de la Fédération Wallonie-Bruxelles.",
  },
  {
    n: "04",
    icon: Stamp,
    t: "Demande de visa D",
    d: "Introduisez votre demande de visa long séjour en personne auprès du poste diplomatique belge (ou via VFS Global / TLS Contact). Délai de traitement : jusqu'à 90 jours.",
  },
  {
    n: "05",
    icon: Plane,
    t: "Arrivée à Bruxelles",
    d: "Après votre arrivée, déclarez-vous à la commune de résidence dans les 8 jours ouvrables pour obtenir votre titre de séjour étudiant.",
  },
];

const documents = [
  "Passeport en cours de validité (copie complète)",
  "Attestation d'inscription IPEC (modèle officiel AM 28 mars 2022)",
  "Preuve de moyens de subsistance suffisants (AVI, bourse ou prise en charge)",
  "Preuve d'assurance maladie couvrant l'ensemble des risques en Belgique",
  "Certificat médical type, conforme à l'annexe de la loi du 15 décembre 1980",
  "Extrait de casier judiciaire de moins de 6 mois (si majeur)",
  "Autorisation parentale (si mineur)",
  "Preuve du paiement de la redevance, si vous y êtes soumis",
  "Traduction jurée vers FR/NL/EN/DE pour tout document rédigé dans une autre langue",
];

function International() {
  return (
    <>
      {/* HERO */}
      <section className="relative py-20 lg:py-32 overflow-hidden border-b border-border/30">
        <div className="absolute inset-0 -z-10">
          <img src={brusselsImg} alt="Bruxelles" className="w-full h-full object-cover opacity-30" width={1600} height={1000} />
          <div className="absolute inset-0 bg-gradient-to-b from-background/60 via-background/80 to-background" />
        </div>
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="flex items-center gap-2 text-blue mb-6">
            <Globe2 size={16} />
            <span className="text-xs uppercase tracking-[0.3em]">International</span>
          </div>
          <h1 className="font-display md:text-7xl text-cream leading-[1] max-w-4xl text-balance text-5xl">
            Étudier à Bruxelles, <em className="text-gradient-blue not-italic">depuis le monde entier</em>.
          </h1>
          <p className="mt-8 max-w-2xl text-muted-foreground leading-relaxed text-base">
            L'IPEC accueille les étudiants venus de l'étranger. Ce guide rassemble
            les démarches officielles à anticiper pour rejoindre l'IPEC dans
            les meilleures conditions.
          </p>
        </div>
      </section>

      {/* 1. PARCOURS — Timeline */}
      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Votre parcours</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-2xl text-balance">
            Cinq étapes, de la candidature à l'arrivée.
          </h2>

          <div className="grid md:grid-cols-2 lg:grid-cols-5 gap-px bg-border/40">
            {timeline.map((s) => (
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

      {/* 2. DOCUMENTS À FOURNIR */}
      <section className="py-20 lg:py-32 bg-surface border-y border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Demande de visa D</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-2xl text-balance">
            Le dossier à constituer pour le consulat.
          </h2>

          <div className="grid lg:grid-cols-2 gap-6">
            <div className="p-10 rounded-sm border border-border/60 bg-card/50">
              <FileCheck className="text-blue mb-6" size={28} strokeWidth={1.5} />
              <h3 className="font-display text-2xl text-cream mb-6">Pièces exigées par l'Office des étrangers</h3>
              <ul className="space-y-3 text-sm text-muted-foreground">
                {documents.map((item) => (
                  <li key={item} className="flex gap-3">
                    <CheckCircle2 size={16} className="text-blue shrink-0 mt-0.5" />
                    <span>{item}</span>
                  </li>
                ))}
              </ul>
              <a
                href="https://dofi.ibz.be/fr/themes/ressortissants-dun-pays-tiers/etudes/1ere-autorisation-de-sejour-demande-de-visa-d"
                target="_blank"
                rel="noopener noreferrer"
                className="mt-8 inline-flex items-center gap-2 text-sm text-blue hover:underline"
              >
                Voir la procédure officielle (visa D) <ArrowRight size={14} />
              </a>
            </div>

            <div className="space-y-6">
              <div className="p-8 rounded-sm border border-border/60 bg-card/50">
                <CalendarClock className="text-blue mb-6" size={28} strokeWidth={1.5} />
                <h3 className="font-display text-xl text-cream mb-3">Délai de traitement</h3>
                <p className="text-sm text-muted-foreground leading-relaxed">
                  Les autorités belges disposent de <span className="text-cream">90 jours</span> à
                  compter de l'accusé de réception pour statuer. Introduisez votre demande
                  au plus tard 90 jours avant la rentrée.
                </p>
              </div>

              <div className="p-8 rounded-sm border border-border/60 bg-card/50">
                <Languages className="text-blue mb-6" size={28} strokeWidth={1.5} />
                <h3 className="font-display text-xl text-cream mb-3">Traductions et légalisations</h3>
                <p className="text-sm text-muted-foreground leading-relaxed">
                  Tout document rédigé dans une autre langue que le français, le
                  néerlandais, l'allemand ou l'anglais doit être accompagné d'une
                  <span className="text-cream"> traduction jurée</span>. Diplômes et actes
                  d'état civil peuvent également nécessiter une légalisation ou apostille.
                </p>
              </div>

              <div className="p-8 rounded-sm border border-border/60 bg-card/50">
                <HeartPulse className="text-blue mb-6" size={28} strokeWidth={1.5} />
                <h3 className="font-display text-xl text-cream mb-3">Assurance maladie</h3>
                <p className="text-sm text-muted-foreground leading-relaxed">
                  Une couverture santé complète sur le territoire belge est obligatoire
                  pour la durée du séjour. Sur place, l'inscription à une mutuelle
                  belge est généralement requise dès l'obtention du titre de séjour.
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* 3. JUSTIFICATIFS FINANCIERS — AVI */}
      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Moyens de subsistance</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-2xl text-balance">
            L'attestation de virement irrévocable (AVI).
          </h2>

          <div className="grid lg:grid-cols-3 gap-6">
            <div className="lg:col-span-2 p-10 rounded-sm border border-blue/30 bg-blue/5">
              <Wallet className="text-blue mb-6" size={28} strokeWidth={1.5} />
              <h3 className="font-display text-2xl text-cream mb-4">Le principe</h3>
              <p className="text-sm md:text-base text-muted-foreground leading-relaxed mb-4">
                Vous devez prouver disposer de moyens d'existence suffisants pour
                la durée de votre séjour, soit{" "}
                <span className="text-cream">1 062 € net par mois</span> (montant
                indexé pour l'année académique 2026-2027), généralement justifié
                par le dépôt de douze mensualités sur un compte bloqué.
              </p>
              <p className="text-sm md:text-base text-muted-foreground leading-relaxed">
                L'IPEC ne propose pas de blocage de fonds sur ses comptes.
                L'Office des étrangers n'accepte aujourd'hui que les AVI émises
                par deux sociétés agréées :
              </p>

              <div className="mt-6 grid sm:grid-cols-2 gap-4">
                <a
                  href="https://www.studely.com/fr/caution-bancaire-etudiante"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="group p-5 rounded-sm border border-border/60 bg-background/40 hover:border-blue/60 transition-colors"
                >
                  <div className="text-xs uppercase tracking-widest text-blue mb-2">Société agréée</div>
                  <div className="font-display text-xl text-cream mb-1">Studely</div>
                  <div className="text-xs text-muted-foreground inline-flex items-center gap-1">
                    studely.com
                    <ArrowRight size={12} className="group-hover:translate-x-0.5 transition-transform" />
                  </div>
                </a>
                <a
                  href="https://www.ready-study-go.com/"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="group p-5 rounded-sm border border-border/60 bg-background/40 hover:border-blue/60 transition-colors"
                >
                  <div className="text-xs uppercase tracking-widest text-blue mb-2">Société agréée</div>
                  <div className="font-display text-xl text-cream mb-1">Ready Study Go International</div>
                  <div className="text-xs text-muted-foreground inline-flex items-center gap-1">
                    ready-study-go.com
                    <ArrowRight size={12} className="group-hover:translate-x-0.5 transition-transform" />
                  </div>
                </a>
              </div>

              <a
                href="https://dofi.ibz.be/fr/themes/ressortissants-dun-pays-tiers/etudes/favoris/moyens-de-subsistance-suffisants"
                target="_blank"
                rel="noopener noreferrer"
                className="mt-8 inline-flex items-center gap-2 text-sm text-blue hover:underline"
              >
                Page officielle Office des étrangers <ArrowRight size={14} />
              </a>
            </div>

            <div className="p-8 rounded-sm border border-border/60 bg-card/50">
              <AlertTriangle className="text-blue mb-6" size={28} strokeWidth={1.5} />
              <h3 className="font-display text-xl text-cream mb-3">Alternatives admises</h3>
              <ul className="space-y-3 text-sm text-muted-foreground">
                <li className="flex gap-3">
                  <CheckCircle2 size={14} className="text-blue shrink-0 mt-0.5" />
                  <span>Attestation de bourse (organisation internationale, autorité publique, université)</span>
                </li>
                <li className="flex gap-3">
                  <CheckCircle2 size={14} className="text-blue shrink-0 mt-0.5" />
                  <span>Engagement de prise en charge — annexe 32 signée par un garant solvable</span>
                </li>
                <li className="flex gap-3">
                  <CheckCircle2 size={14} className="text-blue shrink-0 mt-0.5" />
                  <span>Compte bloqué ouvert par un établissement d'enseignement supérieur belge</span>
                </li>
              </ul>
              <p className="mt-6 text-xs text-muted-foreground leading-relaxed">
                L'examen est individuel : prêts, indemnités et revenus d'activité
                limitée peuvent également être pris en compte.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* 4. ÉQUIVALENCE & ACCOMPAGNEMENT */}
      <section className="py-20 lg:py-32 bg-surface border-y border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Préparer son entrée</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-2xl text-balance">
            Équivalence, logement, accompagnement.
          </h2>

          <div className="grid md:grid-cols-3 gap-6">
            <div className="p-10 rounded-sm border border-border/60 bg-card/50">
              <FileCheck className="text-blue mb-6" size={28} strokeWidth={1.5} />
              <h3 className="font-display text-xl text-cream mb-4">Équivalence de diplôme</h3>
              <p className="text-sm text-muted-foreground leading-relaxed mb-5">
                <span className="text-cream">Facultative pour intégrer l'IPEC</span>, qui est
                un institut privé. Elle reste requise pour une entrée en 1ʳᵉ année dans un
                établissement public : la demande se fait auprès de la Fédération
                Wallonie-Bruxelles et peut durer plusieurs mois — anticipez.
              </p>
              <a
                href="http://www.equivalences.cfwb.be/"
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-2 text-sm text-blue hover:underline"
              >
                equivalences.cfwb.be <ArrowRight size={14} />
              </a>
            </div>

            <div className="p-10 rounded-sm border border-border/60 bg-card/50">
              <Home className="text-blue mb-6" size={28} strokeWidth={1.5} />
              <h3 className="font-display text-xl text-cream mb-4">Logement à Bruxelles</h3>
              <p className="text-sm text-muted-foreground leading-relaxed">
                Kots étudiants, colocations, résidences : nous orientons vers les
                ressources locales fiables. Comptez 500 à 800 € par mois pour une
                chambre meublée selon le quartier. Anticipez votre recherche dès
                la confirmation d'admission.
              </p>
            </div>

            <div className="p-10 rounded-sm border border-border/60 bg-card/50">
              <Users className="text-blue mb-6" size={28} strokeWidth={1.5} />
              <h3 className="font-display text-xl text-cream mb-4">Accompagnement IPEC</h3>
              <p className="text-sm text-muted-foreground leading-relaxed">
                Notre équipe pédagogique vous remet les documents officiels,
                répond à vos questions sur le visa et vous oriente sur les
                démarches communales à votre arrivée. Un seul interlocuteur,
                tout au long du parcours.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* 5. POURQUOI BRUXELLES */}
      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Pourquoi Bruxelles</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream max-w-3xl mb-16 text-balance">
            La ville la plus internationale d'Europe.
          </h2>

          <div className="grid md:grid-cols-3 gap-px bg-border/40">
            {[
              { t: "Cœur européen", d: "Siège de la Commission européenne, du Parlement européen, de l'OTAN." },
              { t: "Multilingue", d: "Français, néerlandais, anglais : Bruxelles vit en plusieurs langues au quotidien." },
              { t: "À taille humaine", d: "Une capitale qui se traverse à pied, où les étudiants font partie intégrante du quotidien et trouvent vite leurs repères." },
            ].map((c) => (
              <div key={c.t} className="bg-background p-10">
                <Users className="text-blue mb-6" size={28} strokeWidth={1.5} />
                <h3 className="font-display text-2xl text-cream mb-3">{c.t}</h3>
                <p className="text-sm text-muted-foreground leading-relaxed">{c.d}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* 6. RAPPEL */}
      <section className="pb-20 lg:pb-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="p-10 rounded-sm border border-blue/30 bg-blue/5 flex flex-col md:flex-row gap-6">
            <AlertTriangle className="text-blue shrink-0" size={32} strokeWidth={1.5} />
            <div>
              <div className="text-xs uppercase tracking-[0.25em] text-blue mb-3">Bon à savoir</div>
              <h2 className="font-display text-2xl md:text-3xl text-cream mb-4">Anticipez vos démarches</h2>
              <p className="text-sm md:text-base text-muted-foreground leading-relaxed mb-4">
                Les places hors Union européenne sont en nombre limité chaque
                année. Dès que votre admission est confirmée, lancez en parallèle
                la constitution de votre AVI, la demande d'équivalence et la prise
                de rendez-vous au consulat. Ces informations ont une valeur
                indicative : référez-vous toujours aux sources officielles
                (Office des étrangers, ambassade compétente).
              </p>
              <Link to="/admissions" className="inline-flex items-center gap-2 text-sm text-blue hover:underline">
                Conditions d'admission et tarifs <ArrowRight size={14} />
              </Link>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
