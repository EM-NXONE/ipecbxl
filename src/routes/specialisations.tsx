import { createFileRoute } from "@tanstack/react-router";
import { Briefcase, Megaphone, Globe, LineChart } from "lucide-react";

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
      {/* HERO */}
      <section className="py-20 lg:py-32 border-b border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-6">— Spécialisations</div>
          <h1 className="font-display text-5xl md:text-7xl text-cream leading-[1] max-w-4xl text-balance">
            Quatre voies pour <em className="text-gradient-blue not-italic">tracer</em> la vôtre.
          </h1>
          <p className="mt-8 max-w-2xl text-muted-foreground leading-relaxed text-base">
            Chaque spécialisation s'appuie sur le tronc commun de votre cursus.
            Vous choisissez quand vous êtes prêt·e, pas quand on vous y oblige.
          </p>
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

      {/* LISTE DES SPÉCIALISATIONS */}
      <section className="py-20 lg:py-32 bg-surface border-y border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Les quatre voies</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-2xl text-balance">
            Une exigence commune, quatre <em className="text-gradient-blue not-italic">expertises</em>.
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
