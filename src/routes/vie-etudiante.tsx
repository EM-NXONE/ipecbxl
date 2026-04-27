import { createFileRoute } from "@tanstack/react-router";
import { Sparkles, Bus, ArrowRight } from "lucide-react";

export const Route = createFileRoute("/vie-etudiante")({
  head: () => ({
    meta: [
      { title: "IPEC | Vie étudiante" },
      { name: "description", content: "Avantages du statut étudiant IPEC à Bruxelles : réductions UNiDAYS, tarif préférentiel STIB-MIVB et autres bénéfices liés à la vie étudiante en Belgique." },
      { name: "keywords", content: "vie étudiante Bruxelles, avantages étudiants Belgique, UNiDAYS Bruxelles, tarif étudiant STIB, abonnement STIB étudiant, réductions étudiantes, IPEC vie étudiante" },
      { property: "og:title", content: "Vie étudiante — IPEC Bruxelles" },
      { property: "og:description", content: "Réductions UNiDAYS, tarif préférentiel STIB-MIVB et avantages quotidiens du statut étudiant IPEC." },
      { property: "og:url", content: "https://ipec.school/vie-etudiante" },
    ],
    links: [{ rel: "canonical", href: "https://ipec.school/vie-etudiante" }],
  }),
  component: VieEtudiante,
});

function VieEtudiante() {
  return (
    <>
      {/* HERO */}
      <section className="py-20 lg:py-32 border-b border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-6">— Vie étudiante</div>
          <h1 className="font-display md:text-7xl text-cream leading-[1] max-w-4xl text-balance text-5xl">
            La vie <em className="text-gradient-blue not-italic">au-delà du campus</em>.
          </h1>
          <p className="mt-8 max-w-2xl text-muted-foreground leading-relaxed text-base">
            Inscrits à l'IPEC, vous bénéficiez d'une série de réductions et de tarifs préférentiels au quotidien, du shopping aux transports bruxellois, grâce à nos partenaires.
          </p>
        </div>
      </section>

      {/* AVANTAGES */}
      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Tarifs préférentiels</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-16 max-w-3xl text-balance">
            Bruxelles, version étudiante.
          </h2>

          <div className="grid md:grid-cols-2 gap-6">
            {/* UNiDAYS */}
            <div className="p-10 rounded-sm border border-border/60 bg-card/50 flex flex-col">
              <Sparkles className="text-blue mb-6" size={28} strokeWidth={1.5} />
              <div className="text-xs uppercase tracking-widest text-blue mb-3">Partenaire</div>
              <h3 className="font-display text-2xl text-cream mb-4">Réductions UNiDAYS</h3>
              <p className="text-sm text-muted-foreground leading-relaxed mb-5">
                L'IPEC est partenaire d'<span className="text-cream">UNiDAYS</span>,
                la plateforme internationale de réductions étudiantes. Une fois
                votre inscription confirmée et votre adresse e-mail
                institutionnelle activée, vous bénéficiez d'offres exclusives
                chez des centaines de marques (mode, tech, voyage, abonnements
                en ligne, livraison à domicile…).
              </p>
              <a
                href="https://www.myunidays.com/BE/fr-BE"
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-2 text-sm text-blue hover:underline mt-auto"
              >
                Découvrir les offres UNiDAYS <ArrowRight size={14} />
              </a>
            </div>

            {/* STIB */}
            <div className="p-10 rounded-sm border border-border/60 bg-card/50 flex flex-col">
              <Bus className="text-blue mb-6" size={28} strokeWidth={1.5} />
              <div className="text-xs uppercase tracking-widest text-blue mb-3">Transports bruxellois</div>
              <h3 className="font-display text-2xl text-cream mb-4">Tarif étudiant STIB-MIVB</h3>
              <p className="text-sm text-muted-foreground leading-relaxed mb-5">
                En tant qu'étudiant inscrit à l'IPEC et jusqu'à l'âge de 24 ans,
                vous avez droit au tarif préférentiel{" "}
                <span className="text-cream">STIB-MIVB</span> pour vos
                déplacements en métro, tram et bus dans toute la Région
                bruxelloise. L'institut vous délivre, sur demande, l'attestation
                d'inscription nécessaire à la souscription de l'abonnement.
              </p>
              <a
                href="https://www.stib-mivb.be/accueil/acheter/notre-catalogue-d-abonnements"
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-2 text-sm text-blue hover:underline mt-auto"
              >
                Conditions et tarifs STIB-MIVB <ArrowRight size={14} />
              </a>
            </div>
          </div>

          <p className="mt-10 text-xs text-muted-foreground leading-relaxed max-w-3xl">
            Les conditions d'octroi, plafonds tarifaires et modalités d'usage
            relèvent exclusivement des partenaires et opérateurs concernés.
            L'IPEC se limite à délivrer les justificatifs d'inscription requis
            et n'intervient pas dans la relation contractuelle entre l'étudiant
            et le partenaire.
          </p>
        </div>
      </section>

      {/* LOGEMENT */}
      <section className="py-20 lg:py-32 border-t border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Se loger à Bruxelles</div>
          <h2 className="font-display text-4xl md:text-5xl text-cream mb-6 max-w-3xl text-balance">
            Trouver un logement étudiant.
          </h2>
          <p className="max-w-3xl text-muted-foreground leading-relaxed text-base mb-16">
            À titre informatif, si vous devez vous loger durant vos études à
            Bruxelles, voici quelques plateformes utiles.
          </p>

          <div className="grid md:grid-cols-3 gap-6">
            {/* BRUKOT */}
            <div className="p-10 rounded-sm border border-border/60 bg-card/50 flex flex-col">
              <div className="text-xs uppercase tracking-widest text-blue mb-4">Brukot</div>
              <p className="text-sm text-muted-foreground leading-relaxed mb-5">
                <span className="text-cream">Brukot</span> répertorie des
                annonces de kots, studios et chambres à louer à Bruxelles,
                spécifiquement orientées vers le public étudiant. Une bonne
                porte d'entrée pour comparer les biens disponibles dans les
                différentes communes bruxelloises.
              </p>
              <a
                href="https://brukot.be/"
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-2 text-sm text-blue hover:underline mt-auto"
              >
                Consulter Brukot.be <ArrowRight size={14} />
              </a>
            </div>

            {/* IMMOWEB */}
            <div className="p-10 rounded-sm border border-border/60 bg-card/50 flex flex-col">
              <div className="text-xs uppercase tracking-widest text-blue mb-4">Immoweb</div>
              <p className="text-sm text-muted-foreground leading-relaxed mb-5">
                <span className="text-cream">Immoweb</span> est le principal
                portail immobilier de Belgique. Vous y trouvez un large choix
                d'appartements, studios et colocations à louer à Bruxelles,
                avec des filtres par budget, quartier et superficie.
              </p>
              <a
                href="https://www.immoweb.be/fr/recherche/maison-et-appartement/a-louer"
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-2 text-sm text-blue hover:underline mt-auto"
              >
                Consulter Immoweb <ArrowRight size={14} />
              </a>
            </div>

            {/* IKOT */}
            <div className="p-10 rounded-sm border border-border/60 bg-card/50 flex flex-col">
              <div className="text-xs uppercase tracking-widest text-blue mb-4">iKot</div>
              <p className="text-sm text-muted-foreground leading-relaxed mb-5">
                <span className="text-cream">iKot</span> est une plateforme
                dédiée aux logements étudiants en Belgique : kots, studios
                meublés et colocations. L'interface permet de cibler rapidement
                les biens proches de votre lieu d'études.
              </p>
              <a
                href="https://www.ikot.be/"
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-2 text-sm text-blue hover:underline mt-auto"
              >
                Consulter iKot.be <ArrowRight size={14} />
              </a>
            </div>
          </div>

          <p className="mt-10 text-xs text-muted-foreground leading-relaxed max-w-3xl">
            La recherche, la sélection et la signature du bail relèvent de la
            seule responsabilité de l'étudiant et du bailleur. L'IPEC ne se
            porte ni garant, ni intermédiaire, et n'intervient pas dans la
            relation contractuelle locative.
          </p>
        </div>
      </section>
    </>
  );
}
