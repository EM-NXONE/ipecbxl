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
      <section className="pt-10 pb-8 lg:pt-14 lg:pb-10 border-b border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-6">— Spécialisations</div>
          <h1 className="font-display text-cream leading-[1.05] max-w-4xl text-balance">
            Quatre voies pour <em className="text-gradient-blue not-italic">tracer</em> la vôtre.
          </h1>
          <p className="mt-6 max-w-2xl text-muted-foreground leading-relaxed text-base">
            Chaque spécialisation s'appuie sur le tronc commun de votre cursus.
            Vous choisissez quand vous êtes prêt·e, pas quand on vous y oblige.
          </p>
        </div>
      </section>

      {/* MOMENT DU CHOIX */}
      <section className="py-8 lg:py-12 border-b border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="grid lg:grid-cols-2 gap-px bg-border/40 border border-border/40">
            <div className="bg-background p-8 lg:p-10">
              <div className="text-xs uppercase tracking-[0.25em] text-blue mb-4">— Parcours intégral</div>
              <h2 className="font-display text-cream mb-3">Choix en PAA3</h2>
              <p className="text-muted-foreground leading-relaxed text-base">
                Les étudiant·e·s qui suivent l'intégralité du cursus choisissent leur
                spécialisation en troisième année du PAA, après avoir exploré l'ensemble
                des disciplines. Ils la poursuivent ensuite tout au long du PEA.
              </p>
            </div>
            <div className="bg-background p-8 lg:p-10">
              <div className="text-xs uppercase tracking-[0.25em] text-blue mb-4">— Entrée directe en PEA</div>
              <h2 className="font-display text-cream mb-3">Choix dès l'admission</h2>
              <p className="text-muted-foreground leading-relaxed text-base">
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
      <section className="py-8 lg:py-12">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="space-y-px bg-border/40 border border-border/40">
            {specs.map((s) => (
              <div
                key={s.n}
                className="bg-muted p-8 lg:p-10 grid lg:grid-cols-12 gap-8 hover:bg-card/50 transition-colors group"
              >
                {/* Numéro + icône */}
                <div className="lg:col-span-3 flex lg:flex-col items-center lg:items-start gap-4">
                  <div className="font-display text-3xl text-blue">{s.n}</div>
                  <s.icon className="text-blue" size={28} strokeWidth={1.5} />
                </div>

                {/* Titre + description */}
                <div className="lg:col-span-5">
                  <h2 className="font-display text-cream mb-3 group-hover:text-gradient-blue transition-colors">
                    {s.t}
                  </h2>
                  <p className="text-muted-foreground leading-relaxed text-base">{s.desc}</p>
                </div>

                {/* Compétences */}
                <div className="lg:col-span-4">
                  <div className="text-xs uppercase tracking-widest text-blue mb-4">— Compétences clés</div>
                  <ul className="space-y-2.5">
                    {s.skills.map((sk) => (
                      <li key={sk} className="flex items-center gap-3 text-cream">
                        <div className="w-1 h-1 rounded-full bg-blue shrink-0" />
                        <span className="font-body text-base">{sk}</span>
                      </li>
                    ))}
                  </ul>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>
    </>
  );
}
