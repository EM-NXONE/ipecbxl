import { createFileRoute, Link } from "@tanstack/react-router";
import { FileText, CreditCard, Mail, Calendar, ArrowRight, CheckCircle2, AlertTriangle, CalendarDays, GraduationCap } from "lucide-react";

export const Route = createFileRoute("/admissions")({
  head: () => ({
    meta: [
      { title: "Admissions — Candidater à l'IPEC Bruxelles" },
      { name: "description", content: "Inscriptions ouvertes pour la rentrée. Frais de dossier 300 €, scolarité PAA 4 900 €/an, PEA 5 900 €/an. Étudiants belges et internationaux." },
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
  { n: "04", icon: CreditCard, t: "Confirmation d'inscription", d: "Versement des frais de dossier (300 €) et de la première tranche de scolarité." },
];

function Admissions() {
  return (
    <>
      {/* HERO */}
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

      {/* 1. PROCESS — Vue d'ensemble */}
      <section className="py-20 lg:py-32">
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

      {/* 2. CONDITIONS — Qui peut entrer */}
      <section className="py-20 lg:py-32 bg-surface border-y border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Conditions d'admission</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-2xl text-balance">
            Une porte d'entrée à <em className="text-gradient-blue not-italic">chaque niveau</em>.
          </h2>

          <div className="grid md:grid-cols-3 gap-6">
            {[
              {
                year: "1ʳᵉ année",
                title: "Entrée post-secondaire",
                desc: "Vous êtes en dernière année de secondaire ou déjà titulaire du CESS (ou équivalent) ? Vous pouvez déposer votre candidature à tout moment de l'année. Votre inscription sera définitivement validée à l'obtention de votre diplôme.",
              },
              {
                year: "2ᵉ année",
                title: "Admission Bac+1",
                desc: "L'intégration en deuxième année est ouverte aux étudiants ayant validé une première année d'études supérieures, soit 60 crédits.",
              },
              {
                year: "3ᵉ année",
                title: "Admission Bac+2",
                desc: "Tout candidat justifiant d'un Bac+2 et de 120 crédits peut postuler en troisième année.",
              },
            ].map((c) => (
              <div key={c.year} className="p-10 rounded-sm border border-border/60 bg-card/50">
                <GraduationCap className="text-blue mb-6" size={28} strokeWidth={1.5} />
                <div className="text-xs text-blue uppercase tracking-widest mb-3">{c.year}</div>
                <h3 className="font-display text-xl text-cream mb-4">{c.title}</h3>
                <p className="text-sm text-muted-foreground leading-relaxed">{c.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* 3. MODALITÉS PRATIQUES — Pièces & calendrier */}
      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Constituer son dossier</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-2xl text-balance">
            Pièces à fournir et calendrier.
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
                  "Justificatif de niveau de français (facultatif)",
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
                  <div className="text-cream">Le deuxième lundi du mois de septembre de l'année en cours</div>
                </li>
                <li>
                  <div className="text-blue uppercase tracking-widest text-xs mb-1">Rentrée décalée</div>
                  <div className="text-cream">Le premier lundi du mois de février · pour intégrer l'IPEC en cours d'année</div>
                </li>
                <li>
                  <div className="text-blue uppercase tracking-widest text-xs mb-1">Délai de réponse</div>
                  <div className="text-cream">7 jours après réception du dossier complet</div>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      {/* 4. TARIFS & PAIEMENT */}
      <section className="py-20 lg:py-32 bg-surface border-y border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Frais de scolarité</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-2xl text-balance">
            Une grille claire, sans surprise.
          </h2>

          <div className="grid md:grid-cols-2 gap-6 mb-6">
            <div className="p-10 rounded-sm border border-border/60 bg-card/50">
              <div className="font-display text-5xl text-gradient-blue mb-2">PAA</div>
              <div className="text-sm text-blue uppercase tracking-widest mb-8">BAC+1 à BAC+3</div>
              <div className="font-display text-6xl text-cream">4 900 <span className="text-2xl text-muted-foreground">€/an</span></div>
              <p className="text-sm text-muted-foreground mt-6">Programme en Administration des Affaires</p>
            </div>
            <div className="p-10 rounded-sm border border-border/60 bg-card/50">
              <div className="font-display text-5xl text-gradient-blue mb-2">PEA</div>
              <div className="text-sm text-blue uppercase tracking-widest mb-8">BAC+4 et BAC+5</div>
              <div className="font-display text-6xl text-cream">5 900 <span className="text-2xl text-muted-foreground">€/an</span></div>
              <p className="text-sm text-muted-foreground mt-6">Programme Exécutif Avancé</p>
            </div>
          </div>

          <div className="grid md:grid-cols-2 gap-6 mb-6">
            <div className="p-8 rounded-sm border border-blue/40 bg-blue/5">
              <div className="text-xs uppercase tracking-widest text-blue mb-2">Frais de dossier</div>
              <div className="font-display text-3xl text-cream mb-3">300 €</div>
              <p className="text-sm text-muted-foreground leading-relaxed">
                Réglés au dépôt de la candidature, ils couvrent l'instruction administrative
                et pédagogique de votre dossier.
              </p>
            </div>
            <div className="p-8 rounded-sm border border-blue/40 bg-blue/5">
              <div className="text-xs uppercase tracking-widest text-blue mb-2">Première tranche à l'inscription</div>
              <div className="font-display text-3xl text-cream mb-3">3 000 €</div>
              <p className="text-sm text-muted-foreground leading-relaxed">
                Cette première tranche couvre l'accès au cursus en ligne dès la confirmation
                de votre inscription. Pour les candidats devant solliciter un visa d'études,
                ce montant est intégralement déduit des droits de scolarité annuels.
              </p>
            </div>
          </div>

          {/* Modalités de paiement */}
          <div className="p-10 rounded-sm border border-border/60 bg-card/50">
            <CreditCard className="text-blue mb-6" size={28} strokeWidth={1.5} />
            <h3 className="font-display text-2xl text-cream mb-6">Modalités de paiement</h3>
            <ul className="grid md:grid-cols-2 gap-3 text-sm text-muted-foreground">
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
                <span>Virement SEPA, carte de crédit, Bancontact</span>
              </li>
            </ul>
          </div>

          <p className="mt-10 text-sm text-muted-foreground leading-relaxed max-w-3xl">
            Les droits de scolarité couvrent l'ensemble des activités pédagogiques de
            l'année académique : cours, syllabi, séminaires, conférences et visites
            extérieures auprès des institutions européennes — sans frais supplémentaires
            en cours d'année.
          </p>
        </div>
      </section>

      {/* 5. AVIS HORS UE */}
      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="p-10 rounded-sm border border-blue/30 bg-blue/5 flex flex-col md:flex-row gap-6">
            <AlertTriangle className="text-blue shrink-0" size={32} strokeWidth={1.5} />
            <div>
              <div className="text-xs uppercase tracking-[0.25em] text-blue mb-3">Avis aux candidats résidents hors U.E.</div>
              <h2 className="font-display text-2xl md:text-3xl text-cream mb-4">Justificatifs financiers et visa</h2>
              <p className="text-sm md:text-base text-muted-foreground leading-relaxed mb-4">
                Les candidats résidents hors Union européenne doivent fournir leurs propres
                justificatifs d'autosuffisance financière auprès des autorités belges.
                L'IPEC ne propose pas de blocage de fonds sur ses comptes. Les candidatures
                hors UE sont en nombre limité chaque année : nous vous recommandons d'anticiper
                vos démarches consulaires dès l'admission confirmée.
              </p>
              <Link to="/international" className="inline-flex items-center gap-2 text-sm text-blue hover:underline">
                Page Étudiants internationaux <ArrowRight size={14} />
              </Link>
            </div>
          </div>
        </div>
      </section>

      {/* CTA final retiré : la page entière EST l'invitation à candidater. */}
    </>
  );
}
