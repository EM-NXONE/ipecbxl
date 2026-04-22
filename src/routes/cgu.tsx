import { createFileRoute } from "@tanstack/react-router";

export const Route = createFileRoute("/cgu")({
  head: () => ({
    meta: [
      { title: "Conditions générales d'utilisation — IPEC Bruxelles" },
      { name: "description", content: "Conditions générales d'utilisation du site IPEC." },
      { property: "og:title", content: "CGU — IPEC Bruxelles" },
      { property: "og:description", content: "Règles d'utilisation du site et des services en ligne de l'IPEC." },
    ],
  }),
  component: CGU,
});

function CGU() {
  return (
    <article className="py-20 lg:py-32">
      <div className="mx-auto max-w-3xl px-6 lg:px-10">
        <div className="text-xs uppercase tracking-[0.3em] text-blue mb-6">— Légal</div>
        <h1 className="font-display text-5xl md:text-7xl text-cream leading-[1] mb-12 text-balance">
          Conditions générales d'<em className="text-gradient-blue not-italic">utilisation</em>
        </h1>

        <div className="space-y-10 text-sm text-muted-foreground leading-relaxed">
          <section>
            <h2 className="font-display text-2xl text-cream mb-4">1. Objet</h2>
            <p>
              Les présentes conditions générales d'utilisation (CGU) ont pour objet de définir
              les modalités d'accès et d'utilisation du site internet de l'Institut Privé des
              Études Commerciales (IPEC). L'accès au site implique l'acceptation pleine et entière
              des présentes CGU.
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">2. Accès au site</h2>
            <p>
              Le site est accessible gratuitement à tout utilisateur disposant d'un accès à internet.
              L'IPEC se réserve le droit d'interrompre, de suspendre ou de modifier sans préavis
              l'accès à tout ou partie du site, notamment pour des opérations de maintenance.
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">3. Comportement des utilisateurs</h2>
            <p>L'utilisateur s'engage à :</p>
            <ul className="mt-3 space-y-2 list-disc list-inside">
              <li>ne pas perturber le fonctionnement du site ;</li>
              <li>ne pas tenter d'accéder à des espaces réservés ;</li>
              <li>ne transmettre aucun contenu illicite, diffamatoire ou contraire aux bonnes mœurs ;</li>
              <li>fournir des informations exactes lors de tout formulaire de contact ou d'inscription.</li>
            </ul>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">4. Propriété intellectuelle</h2>
            <p>
              L'ensemble des éléments accessibles sur le site (textes, images, vidéos, logos, marques,
              code source) est protégé par le droit de la propriété intellectuelle. Toute utilisation
              non expressément autorisée est constitutive de contrefaçon.
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">5. Liens hypertextes</h2>
            <p>
              Le site peut contenir des liens vers des sites tiers. L'IPEC n'exerce aucun contrôle sur
              ces sites et décline toute responsabilité quant à leur contenu.
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">6. Données personnelles</h2>
            <p>
              Le traitement des données personnelles est encadré par notre{" "}
              <a href="/confidentialite" className="text-blue hover:underline">politique de confidentialité</a>.
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">7. Droit applicable</h2>
            <p>
              Les présentes CGU sont soumises au droit belge. Tout litige relatif à leur interprétation
              ou à leur exécution relève de la compétence exclusive des tribunaux de Bruxelles.
            </p>
          </section>
        </div>
      </div>
    </article>
  );
}
