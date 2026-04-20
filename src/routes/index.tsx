import { createFileRoute, Link } from "@tanstack/react-router";
import heroImg from "@/assets/hero-building.jpg";
import brusselsImg from "@/assets/brussels.jpg";
import { ArrowRight, GraduationCap, Globe2, Compass, CalendarDays, ClipboardCheck } from "lucide-react";

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
              Candidater
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
              La spécialisation se fait <span className="text-cream">le plus tard possible</span>,
              quand vos goûts, vos forces et vos ambitions se sont vraiment révélés. Vous ne
              choisissez pas votre voie à 18 ans : vous la construisez.
            </p>
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
                Choisissez votre voie, <em className="text-gradient-blue not-italic">à votre rythme</em>.
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
                price: "5 900 €",
                desc: "Le socle complet pour comprendre l'entreprise et le monde des affaires. Tronc commun et spécialisation progressive.",
                icon: GraduationCap,
              },
              {
                code: "PEA",
                title: "Programme Exécutif Avancé",
                level: "BAC+4 à BAC+5",
                duration: "2 années",
                price: "6 900 €",
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
                  <p className="text-muted-foreground leading-relaxed mb-8 text-base">{p.desc}</p>
                  <div className="flex items-center justify-end pt-6 border-t border-border/40">
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
              { n: "01", t: "Management", d: "Diriger les équipes et les organisations." },
              { n: "02", t: "Marketing", d: "Comprendre, attirer et fidéliser les marchés." },
              { n: "03", t: "Relations Internationales", d: "Naviguer la complexité géopolitique et diplomatique." },
              { n: "04", t: "Économie & Finance", d: "Maîtriser les équilibres et flux du monde économique." },
            ].map((s) => (
              <div key={s.n} className="bg-background p-8 lg:p-10 hover:bg-card transition-colors group">
                <div className="font-display text-sm text-blue mb-12">{s.n}</div>
                <h3 className="font-display text-2xl text-cream mb-3 group-hover:text-gradient-blue transition-colors">{s.t}</h3>
                <p className="text-sm text-muted-foreground leading-relaxed">{s.d}</p>
              </div>
            ))}
          </div>

          <div className="mt-12 text-center">
            <Link to="/specialisations" className="inline-flex items-center gap-2 text-blue hover:underline">
              Explorer les spécialisations <ArrowRight size={16} />
            </Link>
          </div>
        </div>
      </section>

      {/* MODALITIES — Inspired by IPHE */}
      <section className="py-24 lg:py-32 bg-surface border-y border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Modalités</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream max-w-3xl mb-16 text-balance">
            Trois manières de <em className="text-gradient-blue not-italic">nous rejoindre</em>.
          </h2>

          <div className="grid md:grid-cols-2 gap-6">
            {[
              {
                icon: ClipboardCheck,
                tag: "Inscriptions ouvertes",
                title: "Candidater",
                desc: "Les inscriptions pour la rentrée académique sont ouvertes. Constituez votre dossier en ligne et recevez une réponse sous 7 jours.",
                cta: "Déposer ma candidature",
                to: "/admissions" as const,
              },
              {
                icon: CalendarDays,
                tag: "Rentrée décalée",
                title: "Rejoindre en cours d'année",
                desc: "Vous avez manqué la rentrée d'octobre ? Notre rentrée décalée vous permet d'intégrer l'IPEC en cours d'année académique.",
                cta: "En savoir plus",
                to: "/admissions" as const,
              },
            ].map((m) => (
              <Link
                key={m.title}
                to={m.to}
                className="group relative p-8 lg:p-10 rounded-sm border border-border/60 bg-card/50 hover:border-blue/60 hover:bg-card transition-all flex flex-col"
              >
                <m.icon className="text-blue mb-8" size={32} strokeWidth={1.5} />
                <div className="text-xs uppercase tracking-[0.25em] text-blue mb-3">{m.tag}</div>
                <h3 className="font-display text-2xl text-cream mb-4">{m.title}</h3>
                <p className="text-sm text-muted-foreground leading-relaxed mb-8 flex-1">{m.desc}</p>
                <div className="inline-flex items-center gap-2 text-sm text-blue pt-4 border-t border-border/40">
                  {m.cta}
                  <ArrowRight size={14} className="group-hover:translate-x-1 transition-transform" />
                </div>
              </Link>
            ))}
          </div>
        </div>
      </section>

      {/* BRUSSELS */}
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
                Bruxelles est l'une des villes les plus internationales du monde :
                siège des institutions européennes, carrefour culturel, hub d'affaires,
                point de rencontre des diplomaties.
              </p>
              <p>
                Étudier ici, c'est se former dans un écosystème vivant, multiculturel,
                où l'on apprend autant en classe qu'en dehors.
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
              Candidater à l'IPEC
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
