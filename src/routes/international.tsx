import { createFileRoute, Link } from "@tanstack/react-router";
import {
  FileCheck,
  Globe2,
  Stamp,
  Wallet,
  Languages,
  ClipboardList,
  CalendarClock,
  AlertTriangle,
  ArrowRight,
  CheckCircle2,
  Info,
  ScrollText,
} from "lucide-react";
import brusselsImg from "@/assets/brussels.jpg";

export const Route = createFileRoute("/international")({
  head: () => ({
    meta: [
      { title: "Étudiants internationaux — IPEC Bruxelles" },
      { name: "description", content: "Informations destinées aux candidats résidant hors Union européenne : documents délivrés par l'IPEC et références officielles pour le visa long séjour." },
      { property: "og:title", content: "Étudiants internationaux — IPEC Bruxelles" },
      { property: "og:description", content: "Informations à destination des candidats hors UE : périmètre de l'IPEC et renvois aux autorités compétentes." },
      { property: "og:image", content: brusselsImg },
    ],
  }),
  component: International,
});

// Périmètre IPEC vs périmètre candidat — affiché clairement.
const ipecScope = [
  "Délivrance de l'attestation d'inscription au format officiel exigé par l'Office des étrangers",
  "Communication des dates de rentrée et calendrier académique",
  "Mise à disposition des documents administratifs liés à votre scolarité",
];

const candidateScope = [
  "Constitution du dossier de visa long séjour (visa D) auprès du poste diplomatique belge",
  "Justification des moyens de subsistance suffisants",
  "Demande d'équivalence de diplôme, le cas échéant",
  "Souscription d'une assurance maladie couvrant le séjour en Belgique",
  "Déclaration à la commune de résidence dans les 8 jours ouvrables suivant l'arrivée",
];

const documents = [
  "Passeport en cours de validité",
  "Attestation d'inscription dans un établissement d'enseignement supérieur belge",
  "Preuve de moyens de subsistance suffisants",
  "Preuve d'assurance maladie couvrant l'ensemble des risques en Belgique",
  "Certificat médical type, conforme à l'annexe de la loi du 15 décembre 1980",
  "Extrait de casier judiciaire de moins de 6 mois (si majeur)",
  "Autorisation parentale (si mineur)",
  "Preuve du paiement de la redevance, si applicable",
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
            Candidats <em className="text-gradient-blue not-italic">hors Union européenne</em>.
          </h1>
          <p className="mt-8 max-w-2xl text-muted-foreground leading-relaxed text-base">
            Cette page récapitule, à titre informatif, le cadre réglementaire
            applicable aux candidats résidant hors Union européenne. Les démarches
            de séjour relèvent exclusivement des autorités belges compétentes.
          </p>
        </div>
      </section>

      {/* 1. PÉRIMÈTRE — Ce que fait l'IPEC / Ce qui relève du candidat */}
      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Périmètre des responsabilités</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-3xl text-balance">
            Une répartition claire des rôles.
          </h2>

          <div className="grid md:grid-cols-2 gap-6">
            <div className="p-10 rounded-sm border border-border/60 bg-card/50">
              <ScrollText className="text-blue mb-6" size={28} strokeWidth={1.5} />
              <div className="text-xs text-blue uppercase tracking-widest mb-3">Le rôle de l'IPEC</div>
              <h3 className="font-display text-2xl text-cream mb-6">Documents académiques</h3>
              <ul className="space-y-3 text-sm text-muted-foreground">
                {ipecScope.map((item) => (
                  <li key={item} className="flex gap-3">
                    <CheckCircle2 size={16} className="text-blue shrink-0 mt-0.5" />
                    <span>{item}</span>
                  </li>
                ))}
              </ul>
              <p className="mt-6 text-xs text-muted-foreground leading-relaxed">
                L'IPEC n'intervient ni dans l'instruction du visa, ni dans le
                blocage des fonds, ni auprès des autorités consulaires.
              </p>
            </div>

            <div className="p-10 rounded-sm border border-border/60 bg-card/50">
              <ClipboardList className="text-blue mb-6" size={28} strokeWidth={1.5} />
              <div className="text-xs text-blue uppercase tracking-widest mb-3">À la charge du candidat</div>
              <h3 className="font-display text-2xl text-cream mb-6">Démarches personnelles</h3>
              <ul className="space-y-3 text-sm text-muted-foreground">
                {candidateScope.map((item) => (
                  <li key={item} className="flex gap-3">
                    <CheckCircle2 size={16} className="text-blue shrink-0 mt-0.5" />
                    <span>{item}</span>
                  </li>
                ))}
              </ul>
              <p className="mt-6 text-xs text-muted-foreground leading-relaxed">
                Le candidat est seul responsable de la constitution, de la
                véracité et du suivi de son dossier auprès des autorités belges.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* 2. CADRE LÉGAL — Visa D */}
      <section className="py-20 lg:py-32 bg-surface border-y border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Cadre réglementaire</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-3xl text-balance">
            Visa long séjour (visa D).
          </h2>

          <div className="grid lg:grid-cols-2 gap-6">
            <div className="p-10 rounded-sm border border-border/60 bg-card/50">
              <FileCheck className="text-blue mb-6" size={28} strokeWidth={1.5} />
              <h3 className="font-display text-2xl text-cream mb-6">Pièces listées par l'Office des étrangers</h3>
              <ul className="space-y-3 text-sm text-muted-foreground">
                {documents.map((item) => (
                  <li key={item} className="flex gap-3">
                    <CheckCircle2 size={16} className="text-blue shrink-0 mt-0.5" />
                    <span>{item}</span>
                  </li>
                ))}
              </ul>
              <p className="mt-6 text-xs text-muted-foreground leading-relaxed">
                Liste indicative susceptible d'évoluer. Seules font foi les
                informations publiées par l'Office des étrangers et le poste
                diplomatique compétent.
              </p>
              <a
                href="https://dofi.ibz.be/fr/themes/ressortissants-dun-pays-tiers/etudes/1ere-autorisation-de-sejour-demande-de-visa-d"
                target="_blank"
                rel="noopener noreferrer"
                className="mt-8 inline-flex items-center gap-2 text-sm text-blue hover:underline"
              >
                Procédure officielle — Office des étrangers <ArrowRight size={14} />
              </a>
            </div>

            <div className="space-y-6">
              <div className="p-8 rounded-sm border border-border/60 bg-card/50">
                <CalendarClock className="text-blue mb-6" size={28} strokeWidth={1.5} />
                <h3 className="font-display text-xl text-cream mb-3">Délai légal d'instruction</h3>
                <p className="text-sm text-muted-foreground leading-relaxed">
                  Les autorités belges disposent de <span className="text-cream">90 jours</span> à
                  compter de l'accusé de réception pour statuer. Ce délai n'est
                  ni garanti, ni opposable à l'IPEC.
                </p>
              </div>

              <div className="p-8 rounded-sm border border-border/60 bg-card/50">
                <Languages className="text-blue mb-6" size={28} strokeWidth={1.5} />
                <h3 className="font-display text-xl text-cream mb-3">Traduction et légalisation</h3>
                <p className="text-sm text-muted-foreground leading-relaxed">
                  Tout document rédigé dans une autre langue que le français,
                  le néerlandais, l'allemand ou l'anglais doit faire l'objet
                  d'une <span className="text-cream">traduction jurée</span>.
                  Une légalisation ou apostille peut également être exigée.
                </p>
              </div>

              <div className="p-8 rounded-sm border border-border/60 bg-card/50">
                <Stamp className="text-blue mb-6" size={28} strokeWidth={1.5} />
                <h3 className="font-display text-xl text-cream mb-3">Dépôt de la demande</h3>
                <p className="text-sm text-muted-foreground leading-relaxed">
                  La demande de visa D est introduite en personne auprès du
                  poste diplomatique belge compétent dans le pays de résidence,
                  ou via son prestataire mandaté (VFS Global, TLS Contact).
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* 3. MOYENS DE SUBSISTANCE — strictement informatif */}
      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Moyens de subsistance</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-3xl text-balance">
            Une exigence légale, hors du périmètre de l'IPEC.
          </h2>

          <div className="grid lg:grid-cols-3 gap-6">
            <div className="lg:col-span-2 p-10 rounded-sm border border-border/60 bg-card/50">
              <Wallet className="text-blue mb-6" size={28} strokeWidth={1.5} />
              <h3 className="font-display text-2xl text-cream mb-4">Le cadre fixé par les autorités belges</h3>
              <p className="text-sm md:text-base text-muted-foreground leading-relaxed mb-4">
                La réglementation belge impose au candidat de prouver disposer
                de moyens d'existence suffisants pour la durée de son séjour.
                Le montant de référence — indexé chaque année — ainsi que les
                modalités de justification sont fixés par l'Office des étrangers.
              </p>
              <p className="text-sm md:text-base text-muted-foreground leading-relaxed">
                <span className="text-cream">L'IPEC ne propose pas de blocage de fonds</span>,
                ne perçoit aucune somme en lieu et place du candidat et n'a
                aucun lien contractuel avec les sociétés de cautionnement.
                À ce jour, l'Office des étrangers indique n'accepter que les
                attestations émises par les sociétés ci-dessous, sans toutefois
                pouvoir être tenu pour responsable d'un éventuel manquement de
                celles-ci à leurs obligations vis-à-vis du candidat.
              </p>

              <div className="mt-6 grid sm:grid-cols-2 gap-4">
                <a
                  href="https://www.studely.com/fr/caution-bancaire-etudiante"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="group p-5 rounded-sm border border-border/60 bg-background/40 hover:border-blue/60 transition-colors"
                >
                  <div className="text-xs uppercase tracking-widest text-blue mb-2">Société tierce</div>
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
                  <div className="text-xs uppercase tracking-widest text-blue mb-2">Société tierce</div>
                  <div className="font-display text-xl text-cream mb-1">Ready Study Go International</div>
                  <div className="text-xs text-muted-foreground inline-flex items-center gap-1">
                    ready-study-go.com
                    <ArrowRight size={12} className="group-hover:translate-x-0.5 transition-transform" />
                  </div>
                </a>
              </div>

              <p className="mt-6 text-xs text-muted-foreground leading-relaxed">
                Mentions fournies à titre purement informatif, conformément aux
                indications publiées par l'Office des étrangers. L'IPEC ne
                recommande, ne mandate, ni ne perçoit aucune commission de la
                part de ces sociétés.
              </p>

              <a
                href="https://dofi.ibz.be/fr/themes/ressortissants-dun-pays-tiers/etudes/favoris/moyens-de-subsistance-suffisants"
                target="_blank"
                rel="noopener noreferrer"
                className="mt-8 inline-flex items-center gap-2 text-sm text-blue hover:underline"
              >
                Page officielle — moyens de subsistance <ArrowRight size={14} />
              </a>
            </div>

            <div className="p-8 rounded-sm border border-border/60 bg-card/50">
              <Info className="text-blue mb-6" size={28} strokeWidth={1.5} />
              <h3 className="font-display text-xl text-cream mb-3">Autres modes de preuve admis</h3>
              <ul className="space-y-3 text-sm text-muted-foreground">
                <li className="flex gap-3">
                  <CheckCircle2 size={14} className="text-blue shrink-0 mt-0.5" />
                  <span>Attestation de bourse délivrée par une organisation internationale, une autorité publique ou une université</span>
                </li>
                <li className="flex gap-3">
                  <CheckCircle2 size={14} className="text-blue shrink-0 mt-0.5" />
                  <span>Engagement de prise en charge — annexe 32 signée par un garant solvable</span>
                </li>
              </ul>
              <p className="mt-6 text-xs text-muted-foreground leading-relaxed">
                L'examen relève exclusivement de l'Office des étrangers, qui
                apprécie chaque dossier de manière individuelle.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* 4. ÉQUIVALENCE — facultative pour l'IPEC */}
      <section className="py-20 lg:py-32 bg-surface border-y border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Équivalence de diplôme</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-3xl text-balance">
            Une procédure distincte de l'admission à l'IPEC.
          </h2>

          <div className="p-10 rounded-sm border border-border/60 bg-card/50 max-w-3xl">
            <FileCheck className="text-blue mb-6" size={28} strokeWidth={1.5} />
            <p className="text-sm md:text-base text-muted-foreground leading-relaxed mb-4">
              <span className="text-cream">L'IPEC étant un établissement d'enseignement privé</span>,
              l'équivalence du diplôme secondaire n'est pas requise pour s'y
              inscrire. Elle reste obligatoire pour toute inscription en 1ʳᵉ
              année dans un établissement d'enseignement supérieur reconnu et
              financé par la Fédération Wallonie-Bruxelles.
            </p>
            <p className="text-sm md:text-base text-muted-foreground leading-relaxed mb-4">
              La procédure relève exclusivement du service des équivalences
              de la Fédération Wallonie-Bruxelles. Les délais d'instruction
              sont fixés par ce service.
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
        </div>
      </section>

      {/* 5. AVERTISSEMENT */}
      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="p-10 rounded-sm border border-blue/30 bg-blue/5 flex flex-col md:flex-row gap-6">
            <AlertTriangle className="text-blue shrink-0" size={32} strokeWidth={1.5} />
            <div>
              <div className="text-xs uppercase tracking-[0.25em] text-blue mb-3">Avertissement</div>
              <h2 className="font-display text-2xl md:text-3xl text-cream mb-4">
                Information non contractuelle
              </h2>
              <p className="text-sm md:text-base text-muted-foreground leading-relaxed mb-3">
                Les éléments présentés sur cette page sont fournis à titre
                purement indicatif et ne sauraient se substituer aux
                informations officielles publiées par l'Office des étrangers,
                la Fédération Wallonie-Bruxelles et le poste diplomatique belge
                compétent dans le pays de résidence du candidat.
              </p>
              <p className="text-sm md:text-base text-muted-foreground leading-relaxed mb-4">
                L'IPEC ne se substitue à aucun moment aux autorités belges et
                ne peut être tenu responsable des décisions rendues par
                celles-ci. Les places disponibles pour les candidats hors
                Union européenne sont en nombre limité chaque année académique.
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
