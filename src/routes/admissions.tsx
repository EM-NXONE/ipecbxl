import { createFileRoute, Link } from "@tanstack/react-router";
import { FileText, CreditCard, Mail, Calendar, ArrowRight } from "lucide-react";

export const Route = createFileRoute("/admissions")({
  head: () => ({
    meta: [
      { title: "Admissions — Candidater à l'IPEC Bruxelles" },
      { name: "description", content: "Inscriptions ouvertes pour la rentrée. Frais de dossier 500 €, frais de scolarité PAA 5 900 €/an, PEA 6 900 €/an. Étudiants belges et internationaux." },
      { property: "og:title", content: "Admissions — IPEC Bruxelles" },
      { property: "og:description", content: "Candidatez à l'IPEC : process simple, frais transparents, accueil des étudiants internationaux." },
    ],
  }),
  component: Admissions,
});

const steps = [
  { n: "01", icon: FileText, t: "Candidature en ligne", d: "Remplissez votre dossier en ligne et joignez vos pièces justificatives." },
  { n: "02", icon: Mail, t: "Entretien personnel", d: "Échange avec notre équipe pédagogique pour préciser votre projet." },
  { n: "03", icon: Calendar, t: "Réponse sous 7 jours", d: "Vous recevez la décision d'admission rapidement, par e-mail." },
  { n: "04", icon: CreditCard, t: "Confirmation d'inscription", d: "Versement des frais de dossier (500 €, une seule fois) et du premier acompte." },
];

function Admissions() {
  return (
    <>
      <section className="py-20 lg:py-32 border-b border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-gold mb-6">— Admissions</div>
          <h1 className="font-display md:text-7xl text-cream leading-[1] max-w-4xl text-balance text-5xl">
            Rejoignez la <em className="text-gradient-gold not-italic">prochaine</em> promotion.
          </h1>
          <p className="mt-8 max-w-2xl text-muted-foreground leading-relaxed text-base">
            Les inscriptions sont ouvertes pour toutes les années de cursus.
            Notre équipe vous accompagne à chaque étape.
          </p>
        </div>
      </section>

      {/* PRICES */}
      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-gold mb-4">— Frais de scolarité</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-2xl text-balance">
            Une grille claire, sans surprise.
          </h2>

          <div className="grid md:grid-cols-2 gap-6 mb-6">
            <div className="p-10 rounded-sm border border-border/60 bg-card/50">
              <div className="font-display text-5xl text-gradient-gold mb-2">PAA</div>
              <div className="text-sm text-gold uppercase tracking-widest mb-8">BAC+1 à BAC+3</div>
              <div className="font-display text-6xl text-cream">5 900 <span className="text-2xl text-muted-foreground">€/an</span></div>
              <p className="text-sm text-muted-foreground mt-6">Programme en Administration des Affaires</p>
            </div>
            <div className="p-10 rounded-sm border border-border/60 bg-card/50">
              <div className="font-display text-5xl text-gradient-gold mb-2">PEA</div>
              <div className="text-sm text-gold uppercase tracking-widest mb-8">BAC+4 et BAC+5</div>
              <div className="font-display text-6xl text-cream">6 900 <span className="text-2xl text-muted-foreground">€/an</span></div>
              <p className="text-sm text-muted-foreground mt-6">Programme Exécutif Avancé</p>
            </div>
          </div>

          <div className="p-8 rounded-sm border border-gold/40 bg-gold/5 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
              <div className="text-xs uppercase tracking-widest text-gold mb-2">Frais de dossier</div>
              <div className="font-display text-3xl text-cream">500 € · une seule fois</div>
            </div>
            <p className="text-sm text-muted-foreground max-w-md">
              Versés une seule fois lors de l'inscription, peu importe l'année du cursus
              dans laquelle vous entrez à l'IPEC.
            </p>
          </div>
        </div>
      </section>

      {/* STEPS */}
      <section className="py-20 lg:py-32 bg-surface border-y border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-gold mb-4">— Process</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-2xl text-balance">
            Quatre étapes pour rejoindre l'IPEC.
          </h2>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-px bg-border/40">
            {steps.map((s) => (
              <div key={s.n} className="bg-background p-8 hover:bg-card transition-colors">
                <s.icon className="text-gold mb-6" size={28} strokeWidth={1.5} />
                <div className="text-xs text-gold uppercase tracking-widest mb-3">Étape {s.n}</div>
                <h3 className="font-display text-xl text-cream mb-3">{s.t}</h3>
                <p className="text-sm text-muted-foreground leading-relaxed">{s.d}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-5xl px-6 lg:px-10 text-center">
          <h2 className="font-display text-4xl md:text-5xl text-cream text-balance">
            Prêt·e à candidater ?
          </h2>
          <p className="mt-6 text-muted-foreground max-w-2xl mx-auto">
            Contactez-nous pour recevoir votre dossier de candidature ou organiser
            un entretien.
          </p>
          <div className="mt-10 flex flex-wrap justify-center gap-4">
            <Link to="/contact" className="inline-flex items-center gap-2 px-8 py-4 rounded-sm bg-gradient-gold text-ink font-medium shadow-gold hover:opacity-90 transition-opacity">
              Demander mon dossier <ArrowRight size={18} />
            </Link>
            <Link to="/international" className="inline-flex items-center gap-2 px-8 py-4 rounded-sm border border-gold/40 text-cream hover:bg-gold/10 transition-colors">
              Étudiants internationaux
            </Link>
          </div>
        </div>
      </section>
    </>
  );
}
