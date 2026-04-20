import { createFileRoute, Link } from "@tanstack/react-router";
import { FileText, CreditCard, Mail, Calendar, ArrowRight, CheckCircle2, AlertTriangle, DoorOpen, CalendarDays } from "lucide-react";

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
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-6">— Admissions</div>
          <h1 className="font-display md:text-7xl text-cream leading-[1] max-w-4xl text-balance text-5xl">
            Rejoignez la <em className="text-gradient-blue not-italic">prochaine</em> promotion.
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
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Frais de scolarité</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-2xl text-balance">
            Une grille claire, sans surprise.
          </h2>

          <div className="grid md:grid-cols-2 gap-6 mb-6">
            <div className="p-10 rounded-sm border border-border/60 bg-card/50">
              <div className="font-display text-5xl text-gradient-blue mb-2">PAA</div>
              <div className="text-sm text-blue uppercase tracking-widest mb-8">BAC+1 à BAC+3</div>
              <div className="font-display text-6xl text-cream">5 900 <span className="text-2xl text-muted-foreground">€/an</span></div>
              <p className="text-sm text-muted-foreground mt-6">Programme en Administration des Affaires</p>
            </div>
            <div className="p-10 rounded-sm border border-border/60 bg-card/50">
              <div className="font-display text-5xl text-gradient-blue mb-2">PEA</div>
              <div className="text-sm text-blue uppercase tracking-widest mb-8">BAC+4 et BAC+5</div>
              <div className="font-display text-6xl text-cream">6 900 <span className="text-2xl text-muted-foreground">€/an</span></div>
              <p className="text-sm text-muted-foreground mt-6">Programme Exécutif Avancé</p>
            </div>
          </div>

          <div className="p-8 rounded-sm border border-blue/40 bg-blue/5 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
              <div className="text-xs uppercase tracking-widest text-blue mb-2">Frais de dossier</div>
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
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Process</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-2xl text-balance">
            Quatre étapes pour rejoindre l'IPEC.
          </h2>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-px bg-border/40">
            {steps.map((s) => (
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

      {/* MODALITIES */}
      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Modalités</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-2xl text-balance">
            Tout ce qu'il faut savoir avant de candidater.
          </h2>

          <div className="grid lg:grid-cols-2 gap-6">
            {/* Pièces à fournir */}
            <div className="p-10 rounded-sm border border-border/60 bg-card/50">
              <FileText className="text-blue mb-6" size={28} strokeWidth={1.5} />
              <h3 className="font-display text-2xl text-cream mb-6">Pièces à fournir</h3>
              <ul className="space-y-3 text-sm text-muted-foreground">
                {[
                  "Copie de votre pièce d'identité ou passeport",
                  "Diplômes et relevés de notes des trois dernières années",
                  "CV à jour et lettre de motivation",
                  "Photo d'identité récente au format numérique",
                  "Justificatif de niveau de français (CECRL B2 minimum pour les non-francophones)",
                ].map((item) => (
                  <li key={item} className="flex gap-3">
                    <CheckCircle2 size={16} className="text-blue shrink-0 mt-0.5" />
                    <span>{item}</span>
                  </li>
                ))}
              </ul>
            </div>

            {/* Calendrier */}
            <div className="p-10 rounded-sm border border-border/60 bg-card/50">
              <CalendarDays className="text-blue mb-6" size={28} strokeWidth={1.5} />
              <h3 className="font-display text-2xl text-cream mb-6">Calendrier académique</h3>
              <ul className="space-y-5 text-sm">
                <li>
                  <div className="text-blue uppercase tracking-widest text-xs mb-1">Rentrée principale</div>
                  <div className="text-cream">Début octobre · candidatures ouvertes dès janvier</div>
                </li>
                <li>
                  <div className="text-blue uppercase tracking-widest text-xs mb-1">Rentrée décalée</div>
                  <div className="text-cream">Début février · pour intégrer l'IPEC en cours d'année</div>
                </li>
                <li>
                  <div className="text-blue uppercase tracking-widest text-xs mb-1">Délai de réponse</div>
                  <div className="text-cream">7 jours après réception du dossier complet</div>
                </li>
              </ul>
            </div>

            {/* Portes ouvertes */}
            <div className="p-10 rounded-sm border border-border/60 bg-card/50">
              <DoorOpen className="text-blue mb-6" size={28} strokeWidth={1.5} />
              <h3 className="font-display text-2xl text-cream mb-6">Journées portes ouvertes</h3>
              <p className="text-sm text-muted-foreground leading-relaxed mb-6">
                Découvrez nos locaux au cœur de Bruxelles, échangez avec nos enseignants
                et étudiants, et assistez à des cours de démonstration. Sessions
                organisées sur place ou en visioconférence.
              </p>
              <Link to="/contact" className="inline-flex items-center gap-2 text-sm text-blue hover:underline">
                Demander le calendrier des sessions <ArrowRight size={14} />
              </Link>
            </div>

            {/* Paiement */}
            <div className="p-10 rounded-sm border border-border/60 bg-card/50">
              <CreditCard className="text-blue mb-6" size={28} strokeWidth={1.5} />
              <h3 className="font-display text-2xl text-cream mb-6">Modalités de paiement</h3>
              <ul className="space-y-3 text-sm text-muted-foreground">
                <li className="flex gap-3">
                  <CheckCircle2 size={16} className="text-blue shrink-0 mt-0.5" />
                  <span>Paiement annuel intégral à l'inscription (escompte de 3%)</span>
                </li>
                <li className="flex gap-3">
                  <CheckCircle2 size={16} className="text-blue shrink-0 mt-0.5" />
                  <span>Paiement en deux fois : 50% en septembre, 50% en février</span>
                </li>
                <li className="flex gap-3">
                  <CheckCircle2 size={16} className="text-blue shrink-0 mt-0.5" />
                  <span>Paiement mensualisé sur 10 mensualités (sans frais)</span>
                </li>
                <li className="flex gap-3">
                  <CheckCircle2 size={16} className="text-blue shrink-0 mt-0.5" />
                  <span>Virement bancaire SEPA, carte de crédit, Bancontact</span>
                </li>
              </ul>
            </div>
          </div>

          {/* Notice résidents hors UE */}
          <div className="mt-6 p-8 rounded-sm border border-blue/30 bg-blue/5 flex flex-col md:flex-row gap-6">
            <AlertTriangle className="text-blue shrink-0" size={28} strokeWidth={1.5} />
            <div>
              <div className="text-xs uppercase tracking-[0.25em] text-blue mb-2">Avis aux candidats résidents hors U.E.</div>
              <h3 className="font-display text-xl text-cream mb-3">Justificatifs financiers et visa</h3>
              <p className="text-sm text-muted-foreground leading-relaxed">
                Les candidats résidents hors Union européenne doivent fournir leurs propres
                justificatifs d'autosuffisance financière auprès des autorités belges.
                L'IPEC ne propose pas de blocage de fonds sur ses comptes. Les candidatures
                hors UE sont en nombre limité chaque année : nous vous recommandons d'anticiper
                vos démarches consulaires dès l'admission confirmée.
              </p>
              <Link to="/international" className="mt-4 inline-flex items-center gap-2 text-sm text-blue hover:underline">
                Page Étudiants internationaux <ArrowRight size={14} />
              </Link>
            </div>
          </div>
        </div>
      </section>

      <section className="py-20 lg:py-32 bg-surface border-y border-border/30">
        <div className="mx-auto max-w-5xl px-6 lg:px-10 text-center">
          <h2 className="font-display text-4xl md:text-5xl text-cream text-balance">
            Prêt·e à candidater ?
          </h2>
          <p className="mt-6 text-muted-foreground max-w-2xl mx-auto">
            Contactez-nous pour recevoir votre dossier de candidature ou organiser
            un entretien.
          </p>
          <div className="mt-10 flex flex-wrap justify-center gap-4">
            <Link to="/contact" className="inline-flex items-center gap-2 px-8 py-4 rounded-sm bg-gradient-blue text-ink font-medium shadow-blue hover:opacity-90 transition-opacity">
              Demander mon dossier <ArrowRight size={18} />
            </Link>
            <Link to="/international" className="inline-flex items-center gap-2 px-8 py-4 rounded-sm border border-blue/40 text-cream hover:bg-blue/10 transition-colors">
              Étudiants internationaux
            </Link>
          </div>
        </div>
      </section>
    </>
  );
}
