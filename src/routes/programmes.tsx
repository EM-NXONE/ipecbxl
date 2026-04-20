import { createFileRoute, Link } from "@tanstack/react-router";
import { Check, ArrowRight } from "lucide-react";

export const Route = createFileRoute("/programmes")({
  head: () => ({
    meta: [
      { title: "Programmes PAA et PEA — IPEC Bruxelles" },
      { name: "description", content: "Découvrez les programmes PAA (BAC+1 à BAC+3) et PEA (BAC+4 et BAC+5) de l'IPEC. Tronc commun généreux et spécialisation progressive." },
      { property: "og:title", content: "Programmes PAA et PEA — IPEC Bruxelles" },
      { property: "og:description", content: "Cinq années pour construire votre carrière. PAA pour les fondamentaux, PEA pour l'expertise avancée." },
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

function Programmes() {
  return (
    <>
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

      {/* Approach */}
      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-10 grid lg:grid-cols-2 gap-16">
          <div>
            <div className="text-xs uppercase tracking-[0.3em] text-blue mb-6">— Notre approche</div>
            <h2 className="font-display text-4xl md:text-5xl text-cream text-balance">
              Tronc commun généreux, spécialisation progressive.
            </h2>
          </div>
          <ul className="space-y-5">
            {[
              "Vous explorez chaque discipline avant de choisir votre voie",
              "La spécialisation intervient le plus tard possible dans le cursus",
              "Promotions à taille humaine, suivi individualisé",
              "Pédagogie ancrée dans le réel : projets, cas, immersions",
              "Passerelles fluides entre les spécialisations",
            ].map((p) => (
              <li key={p} className="flex items-start gap-4">
                <div className="mt-1 w-6 h-6 rounded-full bg-blue/15 flex items-center justify-center flex-shrink-0">
                  <Check size={14} className="text-blue" />
                </div>
                <span className="text-muted-foreground leading-relaxed">{p}</span>
              </li>
            ))}
          </ul>
        </div>
      </section>

      <section className="py-20 border-t border-border/30">
        <div className="mx-auto max-w-5xl px-6 lg:px-10 text-center">
          <h2 className="font-display text-4xl md:text-5xl text-cream text-balance">
            Construisez votre parcours.
          </h2>
          <Link to="/admissions" className="mt-10 inline-flex items-center gap-2 px-8 py-4 rounded-sm bg-gradient-blue text-ink font-medium shadow-blue hover:opacity-90 transition-opacity">
            Candidater <ArrowRight size={18} />
          </Link>
        </div>
      </section>
    </>
  );
}
