import { createFileRoute, Link } from "@tanstack/react-router";
import { Plane, FileCheck, Globe2, Users } from "lucide-react";
import brusselsImg from "@/assets/brussels.jpg";

export const Route = createFileRoute("/international")({
  head: () => ({
    meta: [
      { title: "Étudiants internationaux — IPEC Bruxelles" },
      { name: "description", content: "L'IPEC accueille les étudiants étrangers à Bruxelles. Inscription possible avec demande de visa, accompagnement personnalisé." },
      { property: "og:title", content: "Étudiants internationaux à l'IPEC Bruxelles" },
      { property: "og:description", content: "Étudier à Bruxelles depuis l'étranger : visa, démarches et accompagnement." },
      { property: "og:image", content: brusselsImg },
    ],
  }),
  component: International,
});

function International() {
  return (
    <>
      <section className="relative py-20 lg:py-32 overflow-hidden border-b border-border/30">
        <div className="absolute inset-0 -z-10">
          <img src={brusselsImg} alt="Bruxelles" className="w-full h-full object-cover opacity-30" width={1600} height={1000} />
          <div className="absolute inset-0 bg-gradient-to-b from-background/60 via-background/80 to-background" />
        </div>
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="flex items-center gap-2 text-gold mb-6">
            <Globe2 size={16} />
            <span className="text-xs uppercase tracking-[0.3em]">International</span>
          </div>
          <h1 className="font-display md:text-7xl text-cream leading-[1] max-w-4xl text-balance text-5xl">
            Étudier à Bruxelles, <em className="text-gradient-gold not-italic">depuis le monde entier</em>.
          </h1>
          <p className="mt-8 max-w-2xl text-muted-foreground leading-relaxed text-base">
            L'IPEC accueille avec joie les étudiants venus de l'étranger.
            Quel que soit votre pays d'origine, votre candidature est la bienvenue.
          </p>
        </div>
      </section>

      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-10 grid lg:grid-cols-2 gap-12">
          <div className="p-10 rounded-sm border border-border/60 bg-card/50">
            <Plane className="text-gold mb-6" size={32} strokeWidth={1.5} />
            <h2 className="font-display text-3xl text-cream mb-4">Demande de visa</h2>
            <p className="text-muted-foreground leading-relaxed text-base">
              Si votre nationalité requiert un visa pour étudier en Belgique, votre
              inscription à l'IPEC est tout à fait possible. Notre équipe vous remet
              les documents nécessaires (attestation d'inscription, pré-inscription,
              etc.) pour soutenir votre demande auprès des autorités belges.
            </p>
          </div>
          <div className="p-10 rounded-sm border border-border/60 bg-card/50">
            <FileCheck className="text-gold mb-6" size={32} strokeWidth={1.5} />
            <h2 className="font-display text-3xl text-cream mb-4">Accompagnement</h2>
            <p className="text-muted-foreground leading-relaxed text-base">
              Nous vous guidons à chaque étape : constitution du dossier, traduction
              et légalisation des diplômes, conseils sur le logement à Bruxelles,
              démarches administratives à votre arrivée.
            </p>
          </div>
        </div>
      </section>

      <section className="py-20 lg:py-32 bg-ink/40 border-y border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-gold mb-4">— Pourquoi Bruxelles</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream max-w-3xl mb-16 text-balance">
            La ville la plus internationale d'Europe.
          </h2>

          <div className="grid md:grid-cols-3 gap-px bg-border/40">
            {[
              { t: "Cœur européen", d: "Siège de la Commission européenne, du Parlement européen, de l'OTAN." },
              { t: "Multilingue", d: "Français, néerlandais, anglais : Bruxelles vit en plusieurs langues au quotidien." },
              { t: "À taille humaine", d: "Une grande capitale qui se traverse à pied. Étudiants accueillis avec chaleur." },
            ].map((c) => (
              <div key={c.t} className="bg-background p-10">
                <Users className="text-gold mb-6" size={28} strokeWidth={1.5} />
                <h3 className="font-display text-2xl text-cream mb-3">{c.t}</h3>
                <p className="text-sm text-muted-foreground leading-relaxed">{c.d}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-5xl px-6 lg:px-10 text-center">
          <h2 className="font-display text-4xl md:text-5xl text-cream text-balance">
            Votre dossier, où que vous soyez.
          </h2>
          <p className="mt-6 text-muted-foreground max-w-2xl mx-auto">
            Contactez-nous pour démarrer votre candidature internationale.
          </p>
          <Link to="/contact" className="mt-10 inline-flex items-center gap-2 px-8 py-4 rounded-sm bg-gradient-gold text-ink font-medium shadow-gold hover:opacity-90 transition-opacity">
            Nous contacter
          </Link>
        </div>
      </section>
    </>
  );
}
