import { createFileRoute, Link } from "@tanstack/react-router";
import { Briefcase, Megaphone, Globe, LineChart, ArrowRight } from "lucide-react";

export const Route = createFileRoute("/specialisations")({
  head: () => ({
    meta: [
      { title: "Spécialisations — Management, Marketing, Relations Internationales, Finance" },
      { name: "description", content: "Quatre spécialisations à l'IPEC : Management, Marketing, Relations Internationales, Économie & Finance. Choisissez votre voie au moment opportun." },
      { property: "og:title", content: "Spécialisations IPEC — quatre voies, une exigence" },
      { property: "og:description", content: "Management, Marketing, Relations Internationales, Économie & Finance : explorez nos quatre spécialisations." },
    ],
  }),
  component: Specs,
});

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

function Specs() {
  return (
    <>
      <section className="py-20 lg:py-32 border-b border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-6">— Spécialisations</div>
          <h1 className="font-display md:text-7xl text-cream leading-[1] max-w-4xl text-balance text-5xl">
            Quatre voies pour <em className="text-gradient-blue not-italic">tracer</em> la vôtre.
          </h1>
          <p className="mt-8 max-w-2xl text-muted-foreground leading-relaxed text-base">
            Chaque spécialisation s'appuie sur le tronc commun de votre cursus.
            Vous choisissez quand vous êtes prêt·e, pas quand on vous y oblige.
          </p>
        </div>
      </section>

      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-10 space-y-px bg-border/40">
          {specs.map((s, i) => (
            <div key={s.n} className="bg-background p-10 lg:p-16 grid lg:grid-cols-12 gap-10 hover:bg-card/50 transition-colors group">
              <div className="lg:col-span-1">
                <div className="font-display text-2xl text-blue">{s.n}</div>
              </div>
              <div className="lg:col-span-5">
                <s.icon className="text-blue mb-6" size={36} strokeWidth={1.5} />
                <h2 className="font-display text-4xl md:text-5xl text-cream mb-4 group-hover:text-gradient-blue transition-colors">
                  {s.t}
                </h2>
                <p className="text-muted-foreground leading-relaxed text-base">{s.desc}</p>
              </div>
              <div className="lg:col-span-5 lg:col-start-8">
                <div className="text-xs uppercase tracking-widest text-blue mb-6">— Compétences clés</div>
                <ul className="space-y-3">
                  {s.skills.map((sk) => (
                    <li key={sk} className="flex items-center gap-3 text-cream">
                      <div className="w-1 h-1 rounded-full bg-blue" />
                      <span className="font-display text-lg">{sk}</span>
                    </li>
                  ))}
                </ul>
              </div>
            </div>
          ))}
        </div>
      </section>

      <section className="py-20 border-t border-border/30">
        <div className="mx-auto max-w-5xl px-6 lg:px-10 text-center">
          <h2 className="font-display text-4xl md:text-5xl text-cream text-balance">
            Pas encore décidé·e ? <em className="text-gradient-blue not-italic">C'est normal.</em>
          </h2>
          <p className="mt-6 text-muted-foreground max-w-2xl mx-auto">
            À l'IPEC, vous explorez avant de choisir. Notre tronc commun vous permet
            de découvrir chaque discipline avant de vous engager.
          </p>
          <Link to="/programmes" className="mt-10 inline-flex items-center gap-2 px-8 py-4 rounded-sm bg-gradient-blue text-ink font-medium shadow-blue hover:opacity-90 transition-opacity">
            Voir nos programmes <ArrowRight size={18} />
          </Link>
        </div>
      </section>
    </>
  );
}
