import { createFileRoute } from "@tanstack/react-router";
import { Briefcase, Megaphone, Globe, LineChart } from "lucide-react";

export const Route = createFileRoute("/programmes")({
  head: () => ({
    meta: [
      { title: "Programmes & spécialisations — IPEC Bruxelles" },
      { name: "description", content: "PAA (BAC+1 à BAC+3), PEA (BAC+4 et BAC+5) et quatre spécialisations : Management, Marketing, Relations Internationales, Économie & Finance." },
      { property: "og:title", content: "Programmes & spécialisations — IPEC Bruxelles" },
      { property: "og:description", content: "Cinq années pour construire votre carrière, quatre voies pour tracer la vôtre." },
    ],
  }),
  component: Programmes,
});

const paaYears = [
  {
    code: "PAA1",
    title: "Première année",
    desc: "Découverte des grands fondamentaux : économie, gestion, droit, comptabilité, communication. Tronc commun intégral.",
  },
  {
    code: "PAA2",
    title: "Deuxième année",
    desc: "Approfondissement transversal : stratégie, marketing, finance, management. Premiers projets en équipe et stage exploratoire.",
  },
  {
    code: "PAA3",
    title: "Troisième année",
    desc: "Choix de la spécialisation et premier stage long. Projet de fin de cursus et orientation vers le PEA ou la vie active.",
  },
];

const peaYears = [
  {
    code: "PEA1",
    title: "Première année exécutive",
    desc: "Expertise avancée dans la spécialisation choisie. Études de cas, immersion en entreprise, séminaires internationaux.",
  },
  {
    code: "PEA2",
    title: "Deuxième année exécutive",
    desc: "Mémoire de recherche appliquée, stage de fin d'études et préparation à l'entrée dans la vie professionnelle de haut niveau.",
  },
];

const specs = [
  {
    n: "01",
    t: "Management",
    icon: Briefcase,
    desc: "Diriger des équipes, structurer des organisations, conduire le changement.",
    skills: ["Stratégie d'entreprise", "Leadership", "Conduite du changement", "Ressources humaines"],
  },
  {
    n: "02",
    t: "Marketing",
    icon: Megaphone,
    desc: "Comprendre les marchés, construire des marques, créer du lien avec les publics.",
    skills: ["Stratégie de marque", "Marketing digital", "Études de marché", "Communication"],
  },
  {
    n: "03",
    t: "Relations Internationales",
    icon: Globe,
    desc: "Naviguer la complexité géopolitique, comprendre les institutions, négocier à l'international.",
    skills: ["Diplomatie économique", "Géopolitique", "Droit international", "Négociation"],
  },
  {
    n: "04",
    t: "Économie & Finance",
    icon: LineChart,
    desc: "Décrypter les flux économiques, gérer le risque, maîtriser la finance d'entreprise et de marché.",
    skills: ["Macroéconomie", "Finance d'entreprise", "Marchés financiers", "Analyse de données"],
  },
];

function Programmes() {
  return (
    <>
      {/* HERO */}
      <section className="py-20 lg:py-32 border-b border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-6">— Nos programmes</div>
          <h1 className="font-display text-5xl md:text-7xl text-cream leading-[1] max-w-4xl text-balance">
            Du fondamental à <em className="text-gradient-blue not-italic">l'expertise</em>.
          </h1>
          <p className="mt-8 max-w-2xl text-muted-foreground leading-relaxed text-base">
            Notre cursus complet se déploie sur cinq années, de l'initiation aux fondamentaux
            jusqu'à l'expertise avancée. Choisissez votre voie à votre rythme.
          </p>
        </div>
      </section>

      {/* PAA */}
      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="grid lg:grid-cols-12 gap-10 mb-16">
            <div className="lg:col-span-5">
              <div className="font-display text-7xl md:text-8xl text-gradient-blue leading-none">PAA</div>
              <div className="text-sm text-blue mt-2 uppercase tracking-widest">BAC+1 à BAC+3 · 3 ans</div>
            </div>
            <div className="lg:col-span-6 lg:col-start-7">
              <h2 className="font-display text-3xl md:text-4xl text-cream mb-6">
                Programme en Administration des Affaires
              </h2>
              <p className="text-muted-foreground leading-relaxed mb-8">
                Trois années pour acquérir une vision complète du monde des affaires.
                Le PAA combine un tronc commun généreux et une montée en compétence
                progressive, pour vous permettre de choisir votre spécialisation au
                meilleur moment.
              </p>
              <div className="inline-flex items-baseline gap-3 px-5 py-3 rounded-sm bg-card border border-border/60">
                <span className="text-xs uppercase tracking-widest text-muted-foreground">Frais de scolarité</span>
                <span className="font-display text-2xl text-gradient-blue">4 900 €</span>
                <span className="text-xs text-muted-foreground">/ an</span>
              </div>
            </div>
          </div>

          <div className="grid md:grid-cols-3 gap-px bg-border/40">
            {paaYears.map((y) => (
              <div key={y.code} className="bg-background p-8 hover:bg-card transition-colors">
                <div className="text-blue text-xs uppercase tracking-widest mb-4">{y.code}</div>
                <h3 className="font-display text-2xl text-cream mb-4">{y.title}</h3>
                <p className="text-sm text-muted-foreground leading-relaxed">{y.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* PEA */}
      <section className="py-20 lg:py-32 bg-surface border-y border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="grid lg:grid-cols-12 gap-10 mb-16">
            <div className="lg:col-span-5">
              <div className="font-display text-7xl md:text-8xl text-gradient-blue leading-none">PEA</div>
              <div className="text-sm text-blue mt-2 uppercase tracking-widest">BAC+4 et BAC+5 · 2 ans</div>
            </div>
            <div className="lg:col-span-6 lg:col-start-7">
              <h2 className="font-display text-3xl md:text-4xl text-cream mb-6">
                Programme Exécutif Avancé
              </h2>
              <p className="text-muted-foreground leading-relaxed mb-8">
                Deux années d'approfondissement pour celles et ceux qui visent
                des fonctions de direction, l'expertise de pointe ou la création
                d'entreprise. Études de cas réels, immersion et accompagnement
                personnalisé.
              </p>
              <div className="inline-flex items-baseline gap-3 px-5 py-3 rounded-sm bg-card border border-border/60">
                <span className="text-xs uppercase tracking-widest text-muted-foreground">Frais de scolarité</span>
                <span className="font-display text-2xl text-gradient-blue">5 900 €</span>
                <span className="text-xs text-muted-foreground">/ an</span>
              </div>
            </div>
          </div>

          <div className="grid md:grid-cols-2 gap-px bg-border/40">
            {peaYears.map((y) => (
              <div key={y.code} className="bg-background p-8 hover:bg-card transition-colors">
                <div className="text-blue text-xs uppercase tracking-widest mb-4">{y.code}</div>
                <h3 className="font-display text-2xl text-cream mb-4">{y.title}</h3>
                <p className="text-sm text-muted-foreground leading-relaxed">{y.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* MOMENT DU CHOIX */}
      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Moment du choix</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-2xl text-balance">
            Deux portes d'entrée vers votre <em className="text-gradient-blue not-italic">spécialisation</em>.
          </h2>

          <div className="grid md:grid-cols-2 gap-px bg-border/40">
            <div className="bg-background p-10 hover:bg-card transition-colors">
              <div className="text-xs uppercase tracking-widest text-blue mb-3">— Parcours intégral</div>
              <h3 className="font-display text-2xl text-cream mb-4">Choix en PAA3</h3>
              <p className="text-sm text-muted-foreground leading-relaxed">
                Les étudiant·e·s qui suivent l'intégralité du cursus choisissent leur
                spécialisation en troisième année du PAA, après avoir exploré l'ensemble
                des disciplines. Ils la poursuivent ensuite tout au long du PEA.
              </p>
            </div>
            <div className="bg-background p-10 hover:bg-card transition-colors">
              <div className="text-xs uppercase tracking-widest text-blue mb-3">— Entrée directe en PEA</div>
              <h3 className="font-display text-2xl text-cream mb-4">Choix dès l'admission</h3>
              <p className="text-sm text-muted-foreground leading-relaxed">
                Les candidat·e·s admis·es directement en PEA déterminent leur spécialisation
                dès l'entrée dans le programme. Une réorientation en cours d'année peut être
                envisagée à titre exceptionnel, sur demande motivée et à l'issue d'un entretien
                avec notre équipe pédagogique évaluant la cohérence du projet académique et
                professionnel. Elle demeure soumise à l'appréciation de l'institution.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* SPÉCIALISATIONS */}
      <section id="quatre-voies" className="py-20 lg:py-32 bg-surface border-y border-border/30 scroll-mt-24">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Les quatre voies</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-2xl text-balance">
            Quatre voies pour <em className="text-gradient-blue not-italic">tracer</em> la vôtre.
          </h2>

          <div className="grid md:grid-cols-2 gap-px bg-border/40">
            {specs.map((s) => (
              <div
                key={s.n}
                className="bg-background p-10 hover:bg-card transition-colors"
              >
                <div className="flex items-center justify-between mb-6">
                  <s.icon className="text-blue" size={28} strokeWidth={1.5} />
                  <div className="font-display text-3xl text-gradient-blue">{s.n}</div>
                </div>
                <h3 className="font-display text-2xl text-cream mb-4">{s.t}</h3>
                <p className="text-sm text-muted-foreground leading-relaxed mb-8">{s.desc}</p>

                <div className="text-xs uppercase tracking-widest text-blue mb-4">— Compétences clés</div>
                <ul className="space-y-2.5">
                  {s.skills.map((sk) => (
                    <li key={sk} className="flex items-center gap-3 text-cream">
                      <div className="w-1 h-1 rounded-full bg-blue shrink-0" />
                      <span className="font-body text-sm">{sk}</span>
                    </li>
                  ))}
                </ul>
              </div>
            ))}
          </div>
        </div>
      </section>
    </>
  );
}
