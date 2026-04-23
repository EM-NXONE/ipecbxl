import { createFileRoute } from "@tanstack/react-router";

export const Route = createFileRoute("/confidentialite")({
  head: () => ({
    meta: [
      { title: "IPEC | Confidentialité" },
      { name: "description", content: "Politique de protection des données personnelles de l'IPEC, conforme au RGPD. Institut privé en Belgique." },
      { name: "robots", content: "noindex, follow" },
      { property: "og:title", content: "Confidentialité — IPEC Bruxelles" },
      { property: "og:description", content: "Comment l'IPEC collecte, utilise et protège vos données personnelles." },
      { property: "og:url", content: "https://ipec.school/confidentialite" },
    ],
    links: [{ rel: "canonical", href: "https://ipec.school/confidentialite" }],
  }),
  component: Confidentialite,
});

function Confidentialite() {
  return (
    <article className="py-20 lg:py-32">
      <div className="mx-auto max-w-3xl px-6 lg:px-10">
        <div className="text-xs uppercase tracking-[0.3em] text-blue mb-6">— Légal</div>
        <h1 className="font-display text-5xl md:text-7xl text-cream leading-[1] mb-12 text-balance">
          Politique de <em className="text-gradient-blue not-italic">confidentialité</em>
        </h1>

        <div className="space-y-10 text-sm text-muted-foreground leading-relaxed">
          <section>
            <h2 className="font-display text-2xl text-cream mb-4">1. Responsable du traitement</h2>
            <p>
              Le responsable du traitement des données personnelles collectées via ce site est
              l'<strong className="text-cream">Institut Privé des Études Commerciales (IPEC)</strong>,
              dont les coordonnées figurent dans les{" "}
              <a href="/mentions-legales" className="text-blue hover:underline">mentions légales</a>.
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">2. Données collectées</h2>
            <p>L'IPEC collecte les données suivantes via ses formulaires :</p>
            <ul className="mt-3 space-y-2 list-disc list-inside">
              <li>Identité : civilité, nom, prénom, date de naissance, nationalité</li>
              <li>Coordonnées : adresse postale, e-mail, téléphone, pays de résidence</li>
              <li>Données académiques : programme, année et spécialisation visés</li>
              <li>Tout contenu librement transmis dans le champ « Message »</li>
            </ul>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">3. Finalités</h2>
            <p>Les données sont traitées pour les finalités suivantes :</p>
            <ul className="mt-3 space-y-2 list-disc list-inside">
              <li>Instruction des candidatures et gestion des inscriptions ;</li>
              <li>Réponse aux demandes de contact et d'information ;</li>
              <li>Suivi pédagogique et administratif des étudiants ;</li>
              <li>Communication institutionnelle relative à l'IPEC.</li>
            </ul>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">4. Base légale</h2>
            <p>
              Les traitements reposent sur le consentement de la personne concernée, l'exécution
              de mesures précontractuelles ou contractuelles, ainsi que sur l'intérêt légitime
              de l'IPEC à organiser son activité d'enseignement.
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">5. Destinataires</h2>
            <p>
              Les données sont destinées exclusivement aux services internes de l'IPEC habilités
              à les traiter. Elles ne sont ni vendues, ni cédées à des tiers à des fins commerciales.
              Certains prestataires techniques (hébergement, messagerie) peuvent y accéder dans le
              cadre strict de leur mission, sous engagement de confidentialité.
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">6. Durée de conservation</h2>
            <ul className="space-y-2 list-disc list-inside">
              <li>Candidatures non retenues : 12 mois après la décision ;</li>
              <li>Dossiers d'étudiants inscrits : durée du cursus + 10 ans ;</li>
              <li>Demandes de contact : 24 mois maximum.</li>
            </ul>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">7. Vos droits</h2>
            <p>
              Conformément au Règlement (UE) 2016/679 (RGPD), vous disposez d'un droit d'accès,
              de rectification, d'effacement, de limitation, d'opposition et de portabilité des
              données qui vous concernent. Vous pouvez exercer ces droits en écrivant à{" "}
              <a href="mailto:contact@ipec.school" className="text-blue hover:underline">contact@ipec.school</a>.
            </p>
            <p className="mt-3">
              Vous avez également le droit d'introduire une réclamation auprès de l'Autorité de
              protection des données belge ({" "}
              <a href="https://www.autoriteprotectiondonnees.be/" target="_blank" rel="noopener noreferrer" className="text-blue hover:underline">
                autoriteprotectiondonnees.be
              </a>{" "}).
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">8. Cookies</h2>
            <p>
              Le site utilise des cookies strictement nécessaires à son fonctionnement ainsi que
              des cookies de mesure d'audience anonymisée. Aucun cookie publicitaire n'est déposé
              sans votre consentement.
            </p>
          </section>
        </div>
      </div>
    </article>
  );
}
