import { createFileRoute } from "@tanstack/react-router";

export const Route = createFileRoute("/mentions-legales")({
  head: () => ({
    meta: [
      { title: "IPEC | Mentions légales" },
      { name: "description", content: "Mentions légales de l'Institut Privé des Études Commerciales, en abrégé \"IPEC\", école supérieure privée à Bruxelles, Belgique." },
      { name: "robots", content: "noindex, follow" },
      { property: "og:title", content: "Mentions légales — IPEC Bruxelles" },
      { property: "og:description", content: "Informations légales relatives à l'éditeur du site IPEC." },
      { property: "og:url", content: "https://ipec.school/mentions-legales" },
    ],
    links: [{ rel: "canonical", href: "https://ipec.school/mentions-legales" }],
  }),
  component: MentionsLegales,
});

function MentionsLegales() {
  return (
    <article className="py-20 lg:py-32">
      <div className="mx-auto max-w-3xl px-6 lg:px-10">
        <div className="text-xs uppercase tracking-[0.3em] text-blue mb-6">— Légal</div>
        <h1 className="font-display text-5xl md:text-7xl text-cream leading-[1] mb-12 text-balance">
          Mentions <em className="text-gradient-blue not-italic">légales</em>
        </h1>

        <div className="space-y-10 text-sm text-muted-foreground leading-relaxed">
          <section>
            <h2 className="font-display text-2xl text-cream mb-4">Éditeur du site</h2>
            <p>
              Le présent site est édité par l'<strong className="text-cream">Institut Privé des Études Commerciales, en abrégé "IPEC"</strong>,
              établissement d'enseignement supérieur privé.
            </p>
            <ul className="mt-4 space-y-1">
              <li>Adresse : Chaussée d'Alsemberg 897, 1180 Uccle, Belgique</li>
              <li>E-mail : contact@ipec.school</li>
              <li>Téléphone : +32 2 000 00 00</li>
            </ul>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">Directeur de la publication</h2>
            <p>La direction de l'IPEC est responsable de la publication.</p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">Hébergement</h2>
            <p>
              Le site est hébergé par un prestataire technique conforme au RGPD,
              au sein de l'Espace économique européen.
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">Propriété intellectuelle</h2>
            <p>
              L'ensemble des contenus présents sur ce site (textes, images, graphismes, logo,
              icônes, structure, code source) est la propriété exclusive de l'IPEC ou de ses
              partenaires. Toute reproduction, représentation, modification ou diffusion,
              totale ou partielle, est interdite sans autorisation écrite préalable.
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">Reconnaissance académique</h2>
            <p className="italic">
              Établissement, formations et diplômes non reconnus par la Communauté française de Belgique.
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">Responsabilité</h2>
            <p>
              L'IPEC s'efforce d'assurer l'exactitude et la mise à jour des informations diffusées
              sur ce site. Toutefois, l'IPEC ne saurait être tenu responsable des erreurs, omissions
              ou résultats qui pourraient être obtenus par un usage inapproprié de ces informations.
            </p>
          </section>
        </div>
      </div>
    </article>
  );
}
