import { createFileRoute, Link } from "@tanstack/react-router";
import heroImg from "@/assets/hero-building.jpg";
import brusselsImg from "@/assets/brussels.jpg";
import { ArrowRight, GraduationCap, Globe2, Compass, CalendarDays, Briefcase, Megaphone, Globe, LineChart } from "lucide-react";
import { LogoIpec } from "@/components/LogoIpec";

export const Route = createFileRoute("/")({
  head: () => ({
    meta: [
      { title: "IPEC Bruxelles — École supérieure de commerce" },
      { name: "description", content: "L'IPEC forme la nouvelle génération de leaders en business, marketing, relations internationales, économie et finance, au cœur de Bruxelles." },
      { property: "og:title", content: "IPEC Bruxelles — Institut Privé des Études Commerciales" },
      { property: "og:description", content: "Une école nouvelle génération au cœur de Bruxelles. Programmes PAA et PEA, spécialisation progressive, ouverture internationale." },
    ],
  }),
  component: Home,
});

function Home() {
  return (
    <>
      {/* HERO */}
      <section className="relative overflow-hidden">
        <div className="absolute inset-0 -z-10">
          <img
            src={heroImg}
            alt="Bâtiment IPEC à Bruxelles"
            className="w-full h-full object-cover opacity-30"
            width={1920}
            height={1280}
          />
          <div className="absolute inset-0 bg-gradient-to-b from-background/40 via-background/70 to-background" />
        </div>

        <div className="mx-auto max-w-7xl px-6 lg:px-10 pt-20 pb-32 lg:pt-32 lg:pb-48">
          <h1 className="font-display md:text-7xl lg:text-8xl text-cream leading-[0.95] max-w-5xl text-balance animate-fade-up text-5xl">
            Penser, <em className="text-gradient-blue not-italic">entreprendre</em>, diriger.
          </h1>

          <p className="mt-8 max-w-2xl text-muted-foreground leading-relaxed animate-fade-up text-base">
            L'Institut Privé des Études Commerciales forme
            une nouvelle génération de professionnels du business, à Bruxelles,
            capitale politique et économique de l'Europe.
          </p>

          <div className="mt-12 flex flex-wrap gap-4 animate-fade-up">
            <Link
              to="/programmes"
              className="group inline-flex items-center gap-2 px-7 py-4 rounded-sm bg-gradient-blue text-ink font-medium shadow-blue hover:opacity-90 transition-opacity"
            >
              Découvrir nos programmes
              <ArrowRight size={18} className="group-hover:translate-x-1 transition-transform" />
            </Link>
            <Link
              to="/admissions"
              className="inline-flex items-center gap-2 px-7 py-4 rounded-sm border border-blue/40 text-cream hover:bg-blue/10 transition-colors"
            >
              S'inscrire à l'IPEC
            </Link>
          </div>

          {/* Key numbers */}
          <div className="mt-24 grid grid-cols-2 md:grid-cols-4 gap-8 max-w-4xl border-t border-border/40 pt-12">
            {[
              { v: "5", l: "années d'études" },
              { v: "4", l: "spécialisations" },
              { v: "1", l: "campus à Bruxelles" },
              { v: "∞", l: "perspectives" },
            ].map((s) => (
              <div key={s.l}>
                <div className="font-display text-4xl md:text-5xl text-gradient-blue">{s.v}</div>
                <div className="text-xs uppercase tracking-widest text-muted-foreground mt-2">{s.l}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* MANIFESTO */}
      <section className="py-24 lg:py-40 border-t border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10 grid lg:grid-cols-12 gap-12">
          <div className="lg:col-span-5">
            <div className="text-xs uppercase tracking-[0.3em] text-blue mb-6">— Notre vision</div>
            <h2 className="font-display text-4xl md:text-5xl text-cream leading-tight text-balance">
              Une école pensée pour <em className="text-gradient-blue not-italic">le monde réel</em>.
            </h2>
          </div>
          <div className="lg:col-span-6 lg:col-start-7 space-y-6 text-muted-foreground leading-relaxed">
            <p className="text-base">
              À l'IPEC, nous croyons qu'un parcours d'études doit garder ses portes ouvertes
              le plus longtemps possible. C'est pourquoi notre tronc commun est généreux :
              vous explorez avant de choisir.
            </p>
            <p className="text-base">
              La spécialisation se fait <span className="text-cream">après avoir exploré</span> l'ensemble
              des disciplines qui composent le monde de l'entrepreneuriat et du business.
              Vous ne choisissez pas votre voie au hasard : vous la construisez, étape après étape.
            </p>
            <p className="text-base">
              Concrètement, le choix de la spécialisation intervient en <span className="text-cream">3ᵉ année du PAA</span> pour
              les étudiant·e·s qui suivent l'intégralité du cursus, ou dès la <span className="text-cream">première année du PEA</span> pour
              celles et ceux qui y entrent directement.
            </p>
          </div>
        </div>

        {/* LOGO — Rose des vents */}
        <div className="mx-auto max-w-7xl px-6 lg:px-10 mt-24 lg:mt-32">
          <div className="relative rounded-sm border border-border/60 bg-card/40 overflow-hidden">
            {/* Ambient glow */}
            <div className="absolute -top-40 -left-40 w-[500px] h-[500px] rounded-full bg-blue/10 blur-3xl pointer-events-none" />
            <div className="absolute -bottom-40 -right-40 w-96 h-96 rounded-full bg-blue/5 blur-3xl pointer-events-none" />

            <div className="relative grid lg:grid-cols-12 gap-10 lg:gap-14 p-8 sm:p-10 lg:p-14">
              {/* LOGO COLUMN */}
              <div className="lg:col-span-5 flex flex-col items-start">
                <div className="text-xs uppercase tracking-[0.3em] text-blue mb-8">— Notre emblème</div>

                <div className="relative w-full flex items-center justify-center py-8 sm:py-10 lg:py-12 rounded-sm border border-border/40 bg-background/30">
                  {/* Concentric decorative rings */}
                  <div className="absolute w-[280px] h-[280px] sm:w-[320px] sm:h-[320px] rounded-full border border-blue/10 pointer-events-none" />
                  <div className="absolute w-[220px] h-[220px] sm:w-[260px] sm:h-[260px] rounded-full border border-blue/15 pointer-events-none" />
                  {/* Halo */}
                  <div className="absolute w-[200px] h-[200px] rounded-full bg-gradient-blue opacity-25 blur-2xl pointer-events-none" />
                  {/* Logo */}
                  <LogoIpec
                    size={220}
                    className="text-blue relative w-[180px] h-[180px] sm:w-[220px] sm:h-[220px] lg:w-[240px] lg:h-[240px]"
                  />
                </div>

              </div>

              {/* CONTENT COLUMN */}
              <div className="lg:col-span-7 space-y-6">
                <h3 className="font-display text-3xl sm:text-4xl lg:text-5xl text-cream leading-tight text-balance">
                  Quatre vents, <em className="text-gradient-blue not-italic">un seul cap</em> : le vôtre.
                </h3>

                <p className="text-muted-foreground leading-relaxed text-base sm:text-lg">
                  Inspiré de la <span className="text-cream">rose des vents</span>, instrument
                  millénaire des navigateurs et symbole universel de guidance, notre emblème
                  incarne la mission de l'IPEC : accompagner chaque étudiant·e dans la construction
                  d'une trajectoire claire au sein d'un monde économique en mouvement permanent.
                </p>

                <p className="text-muted-foreground leading-relaxed text-base">
                  Ses <span className="text-cream">quatre branches cardinales</span> représentent
                  les disciplines fondatrices du business et de l'entrepreneuriat contemporain —
                  les quatre piliers sur lesquels se construit toute carrière d'envergure :
                </p>

                <ul className="grid sm:grid-cols-2 gap-3 pt-1">
                  {[
                    "Management",
                    "Marketing",
                    "Relations Internationales",
                    "Économie & Finance",
                  ].map((label, i) => (
                    <li
                      key={label}
                      className="group flex items-center gap-4 px-4 py-4 rounded-sm border border-border/40 bg-background/40 hover:border-blue/40 hover:bg-background/60 transition-colors"
                    >
                      <div className="font-display text-xs tracking-[0.2em] text-blue/60 group-hover:text-blue transition-colors">
                        0{i + 1}
                      </div>
                      <div className="w-px h-6 bg-blue/20" />
                      <span className="text-cream text-sm sm:text-base leading-tight">{label}</span>
                    </li>
                  ))}
                </ul>

                <p className="text-muted-foreground leading-relaxed text-base pt-4 border-t border-border/40">
                  En son cœur, le sigle <span className="text-cream">« IPEC »</span> forme l'axe
                  central de la rose, l'ancrage immuable d'où s'élancent les quatre vents. Plus
                  qu'un acronyme, une promesse : celle d'une école qui place l'étudiant·e au
                  centre de sa propre trajectoire et lui donne les repères pour tracer, avec
                  exigence et liberté, la route de son ambition.
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* PROGRAMS PREVIEW */}
      <section className="py-24 lg:py-32 bg-surface border-y border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-16">
            <div>
              <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Nos cursus</div>
              <h2 className="font-display text-4xl md:text-5xl text-cream max-w-2xl text-balance">
                Deux programmes, <em className="text-gradient-blue not-italic">un parcours complet</em>.
              </h2>
            </div>
          </div>

          <div className="grid md:grid-cols-2 gap-6">
            {[
              {
                code: "PAA",
                title: "Programme en Administration des Affaires",
                level: "BAC+1 à BAC+3",
                duration: "3 années",
                prereq: "Être titulaire du CESS / BAC ou équivalent",
                desc: "Le socle complet pour comprendre l'entreprise et le monde des affaires. Tronc commun et spécialisation progressive.",
                icon: GraduationCap,
              },
              {
                code: "PEA",
                title: "Programme Exécutif Avancé",
                level: "BAC+4 à BAC+5",
                duration: "2 années",
                prereq: "Être titulaire d'un BAC+3 (180 crédits) ou équivalent",
                desc: "Approfondissement stratégique pour celles et ceux qui visent les responsabilités de direction et l'expertise de pointe.",
                icon: Compass,
              },
            ].map((p) => (
              <Link
                key={p.code}
                to="/programmes"
                className="group relative p-10 rounded-sm border border-border/60 bg-card/50 hover:border-blue/60 hover:bg-card transition-all overflow-hidden"
              >
                <div className="absolute -top-20 -right-20 w-64 h-64 rounded-full bg-blue/10 blur-3xl group-hover:bg-blue/20 transition-colors" />
                <div className="relative">
                  <div className="flex items-center justify-between mb-8">
                    <div className="font-display text-6xl text-gradient-blue">{p.code}</div>
                    <p.icon className="text-blue/60" size={32} />
                  </div>
                  <h3 className="font-display text-2xl text-cream mb-2">{p.title}</h3>
                  <div className="text-sm text-blue mb-6">{p.level} · {p.duration}</div>
                  <p className="text-muted-foreground leading-relaxed mb-6 text-base">{p.desc}</p>
                  <div className="pt-5 border-t border-border/40">
                    <div className="text-[10px] uppercase tracking-[0.25em] text-blue/70 mb-2">Prérequis</div>
                    <div className="text-cream/90 text-base">{p.prereq}</div>
                  </div>
                  <div className="flex items-center justify-end mt-6">
                    <ArrowRight className="text-blue group-hover:translate-x-1 transition-transform" />
                  </div>
                </div>
              </Link>
            ))}
          </div>
        </div>
      </section>

      {/* SPECIALIZATIONS */}
      <section className="py-24 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Spécialisations</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream max-w-3xl mb-16 text-balance">
            Quatre voies pour <em className="text-gradient-blue not-italic">construire</em> votre carrière.
          </h2>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-px bg-border/40">
            {[
              {
                n: "01",
                t: "Management",
                icon: Briefcase,
                d: "Diriger des équipes, structurer des organisations, conduire le changement avec méthode et vision.",
                skills: ["Stratégie", "Leadership", "RH"],
              },
              {
                n: "02",
                t: "Marketing",
                icon: Megaphone,
                d: "Comprendre les marchés, construire des marques fortes et créer un lien durable avec les publics.",
                skills: ["Brand", "Digital", "Études de marché"],
              },
              {
                n: "03",
                t: "Relations Internationales",
                icon: Globe,
                d: "Naviguer la complexité géopolitique, comprendre les institutions et négocier à l'international.",
                skills: ["Diplomatie", "Géopolitique", "Négociation"],
              },
              {
                n: "04",
                t: "Économie & Finance",
                icon: LineChart,
                d: "Décrypter les flux économiques, gérer le risque et maîtriser la finance d'entreprise et de marché.",
                skills: ["Macroéconomie", "Finance", "Analyse de données"],
              },
            ].map((s) => (
              <div key={s.n} className="bg-background p-8 lg:p-10 hover:bg-card transition-colors group flex flex-col">
                <div className="flex items-center justify-between mb-10">
                  <s.icon className="text-blue" size={26} strokeWidth={1.5} />
                  <span className="font-display text-sm text-blue/70">{s.n}</span>
                </div>
                <h3 className="font-display text-2xl text-cream mb-3 group-hover:text-gradient-blue transition-colors">{s.t}</h3>
                <p className="text-sm text-muted-foreground leading-relaxed mb-6">{s.d}</p>
                <ul className="mt-auto pt-5 border-t border-border/40 space-y-2">
                  {s.skills.map((sk) => (
                    <li key={sk} className="flex items-center gap-2.5 text-cream/90 text-xs">
                      <div className="w-1 h-1 rounded-full bg-blue shrink-0" />
                      <span>{sk}</span>
                    </li>
                  ))}
                </ul>
              </div>
            ))}
          </div>

          <div className="mt-12 text-center">
            <Link to="/programmes" className="inline-flex items-center gap-2 text-blue hover:underline">
              Explorer les spécialisations <ArrowRight size={16} />
            </Link>
          </div>
        </div>
      </section>

      {/* RENTRÉES — info pratique juste après l'offre académique */}
      <section className="py-24 lg:py-32 bg-surface border-y border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Prochaines rentrées</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream max-w-2xl mb-16 text-balance">
            Deux dates pour <em className="text-gradient-blue not-italic">nous rejoindre</em>.
          </h2>

          <div className="grid md:grid-cols-2 gap-6">
            <div className="group relative p-10 rounded-sm border border-border/60 bg-card/50 hover:border-blue/60 hover:bg-card transition-all overflow-hidden">
              <div className="absolute -top-20 -right-20 w-64 h-64 rounded-full bg-blue/10 blur-3xl group-hover:bg-blue/20 transition-colors" />
              <div className="relative">
                <div className="flex items-center justify-between mb-8">
                  <CalendarDays className="text-blue" size={28} strokeWidth={1.5} />
                  <span className="font-display text-sm text-blue/70 uppercase tracking-widest">01</span>
                </div>
                <div className="text-blue uppercase tracking-widest text-xs mb-3">Rentrée principale</div>
                <h3 className="font-display text-3xl text-cream leading-snug mb-5">
                  Le deuxième lundi de <em className="text-gradient-blue not-italic">septembre</em>
                </h3>
                <p className="text-sm text-muted-foreground leading-relaxed mb-8">
                  La grande rentrée académique : démarrage du cursus complet,
                  semaine d'intégration et lancement officiel des enseignements
                  pour l'année. C'est le moment où l'ensemble des promotions se
                  retrouvent sur le campus, où les nouveaux étudiants découvrent
                  l'équipe pédagogique, leurs futurs camarades et le rythme
                  de travail qui rythmera leur parcours à l'IPEC.
                </p>
                <ul className="space-y-2.5 pt-6 border-t border-border/40">
                  {[
                    "Ouverte à toutes les années (PAA1 à PEA2)",
                    "Semaine d'accueil et d'intégration",
                    "Calendrier académique complet",
                  ].map((item) => (
                    <li key={item} className="flex items-center gap-3 text-cream/90 text-sm">
                      <div className="w-1 h-1 rounded-full bg-blue shrink-0" />
                      <span>{item}</span>
                    </li>
                  ))}
                </ul>
              </div>
            </div>

            <div className="group relative p-10 rounded-sm border border-border/60 bg-card/50 hover:border-blue/60 hover:bg-card transition-all overflow-hidden">
              <div className="absolute -top-20 -right-20 w-64 h-64 rounded-full bg-blue/10 blur-3xl group-hover:bg-blue/20 transition-colors" />
              <div className="relative">
                <div className="flex items-center justify-between mb-8">
                  <CalendarDays className="text-blue" size={28} strokeWidth={1.5} />
                  <span className="font-display text-sm text-blue/70 uppercase tracking-widest">02</span>
                </div>
                <div className="text-blue uppercase tracking-widest text-xs mb-3">Rentrée décalée</div>
                <h3 className="font-display text-3xl text-cream leading-snug mb-5">
                  Le premier lundi de <em className="text-gradient-blue not-italic">février</em>
                </h3>
                <p className="text-sm text-muted-foreground leading-relaxed mb-8">
                  Une seconde porte d'entrée, pensée pour celles et ceux qui souhaitent
                  rejoindre l'IPEC en cours d'année académique : réorientation
                  ou démarrage différé. C'est aussi
                  l'opportunité de <span className="text-cream">valider une année complète
                  en format intensif</span>, sur un rythme condensé et exigeant, pour
                  rattraper le calendrier classique sans perdre une année.
                </p>
                <ul className="space-y-2.5 pt-6 border-t border-border/40">
                  {[
                    "Idéale pour une réorientation",
                    "Année validée en format intensif",
                    "Accompagnement personnalisé à l'arrivée",
                  ].map((item) => (
                    <li key={item} className="flex items-center gap-3 text-cream/90 text-sm">
                      <div className="w-1 h-1 rounded-full bg-blue shrink-0" />
                      <span>{item}</span>
                    </li>
                  ))}
                </ul>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* BRUSSELS — ouverture vers le contexte / international */}
      <section className="relative py-24 lg:py-40 overflow-hidden">
        <div className="absolute inset-0 -z-10">
          <img src={brusselsImg} alt="Bruxelles la nuit" className="w-full h-full object-cover opacity-25" loading="lazy" width={1600} height={1000} />
          <div className="absolute inset-0 bg-gradient-to-r from-background via-background/80 to-transparent" />
        </div>
        <div className="mx-auto max-w-7xl px-6 lg:px-10 grid lg:grid-cols-2 gap-16">
          <div>
            <div className="flex items-center gap-2 text-blue mb-6">
              <Globe2 size={16} />
              <span className="text-xs uppercase tracking-[0.3em]">Bruxelles · Belgique</span>
            </div>
            <h2 className="font-display text-4xl md:text-5xl text-cream leading-tight mb-8 text-balance">
              Étudier dans la capitale de l'Europe.
            </h2>
            <div className="space-y-5 text-muted-foreground leading-relaxed">
              <p>
                Bruxelles est souvent décrite comme la
                <span className="text-cream"> ville la plus internationale et cosmopolite d'Europe</span>.
                Capitale politique de l'Union, siège de ses grandes institutions,
                elle est aussi un carrefour culturel, un hub d'affaires et un
                point de rencontre des diplomaties du monde entier.
              </p>
              <p>
                Y étudier, c'est grandir dans un écosystème vivant et pluriel,
                où chaque rue, chaque rencontre prolonge la salle de classe —
                et où l'on apprend autant des cours que de la ville elle-même.
              </p>
            </div>
            <Link to="/international" className="mt-10 inline-flex items-center gap-2 px-7 py-4 rounded-sm border border-blue/40 text-cream hover:bg-blue/10 transition-colors">
              Étudiants internationaux
              <ArrowRight size={16} />
            </Link>
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="py-24 lg:py-32">
        <div className="mx-auto max-w-5xl px-6 lg:px-10 text-center">
          <h2 className="font-display text-4xl md:text-6xl text-cream leading-tight text-balance">
            Prêt·e à <em className="text-gradient-blue not-italic">commencer</em> ?
          </h2>
          <p className="mt-6 text-muted-foreground max-w-2xl mx-auto text-base">
            Les inscriptions sont ouvertes. Notre équipe vous accompagne à chaque étape.
          </p>
          <div className="mt-10 flex flex-wrap justify-center gap-4">
            <Link to="/admissions" className="px-8 py-4 rounded-sm bg-gradient-blue text-ink font-medium shadow-blue hover:opacity-90 transition-opacity">
              S'inscrire à l'IPEC
            </Link>
            <Link to="/contact" className="px-8 py-4 rounded-sm border border-blue/40 text-cream hover:bg-blue/10 transition-colors">
              Nous contacter
            </Link>
          </div>
        </div>
      </section>
    </>
  );
}
