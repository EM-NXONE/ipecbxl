import { createFileRoute } from "@tanstack/react-router";

export const Route = createFileRoute("/cookies")({
  head: () => ({
    meta: [
      { title: "IPEC | Cookies" },
      { name: "description", content: "Gestion des cookies sur le site de l'IPEC, institut privé en Belgique." },
      { name: "robots", content: "noindex, follow" },
      { property: "og:title", content: "Cookies — IPEC Bruxelles" },
      { property: "og:description", content: "Comment l'IPEC utilise les cookies sur son site internet." },
      { property: "og:url", content: "https://ipec.school/cookies" },
    ],
    links: [{ rel: "canonical", href: "https://ipec.school/cookies" }],
  }),
  component: Cookies,
});

function Cookies() {
  return (
    <article className="py-20 lg:py-32">
      <div className="mx-auto max-w-3xl px-6 lg:px-10">
        <div className="text-xs uppercase tracking-[0.3em] text-blue mb-6">— Légal</div>
        <h1 className="font-display text-5xl md:text-7xl text-cream leading-[1] mb-12 text-balance">
          Politique <em className="text-gradient-blue not-italic">cookies</em>
        </h1>

        <div className="space-y-10 text-sm text-muted-foreground leading-relaxed">
          <section>
            <h2 className="font-display text-2xl text-cream mb-4">1. Qu'est-ce qu'un cookie ?</h2>
            <p>
              Un cookie est un petit fichier texte déposé sur votre terminal lors de la consultation
              d'un site internet. Il permet au site de mémoriser des informations relatives à votre
              navigation pour une durée déterminée.
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">2. Cookies utilisés</h2>
            <p>Le site de l'IPEC utilise les catégories de cookies suivantes :</p>
            <ul className="mt-3 space-y-2 list-disc list-inside">
              <li>
                Cookies strictement nécessaires — indispensables au fonctionnement du site
                (préférences d'affichage, thème clair/sombre).
              </li>
              <li>
                Cookies de mesure d'audience anonymisée — permettent de comprendre l'usage du
                site afin de l'améliorer, sans identifier personnellement les visiteurs.
              </li>
              <li>
                Cookies tiers Google reCAPTCHA — déposés par Google sur les pages comportant un
                formulaire (contact, inscription, vérification de documents) afin de distinguer
                les utilisateurs humains des robots. Ces cookies sont strictement nécessaires à
                la sécurité du site et ne servent ni au profilage publicitaire, ni à la mesure
                d'audience. Voir la{" "}
                <a href="/confidentialite" className="text-blue hover:underline">politique de confidentialité</a>{" "}
                pour le détail des données transmises à Google.
              </li>
            </ul>
            <p className="mt-3">
              Le site n'utilise <strong className="text-cream">aucun cookie publicitaire</strong> ni
              de profilage à des fins commerciales.
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">3. Gestion des cookies</h2>
            <p>
              Vous pouvez à tout moment paramétrer votre navigateur pour accepter ou refuser
              tout ou partie des cookies. La configuration de chaque navigateur est différente :
              consultez la rubrique « aide » de votre navigateur pour connaître la marche à suivre.
            </p>
            <p className="mt-3">
              Le refus des cookies strictement nécessaires peut empêcher certaines fonctionnalités
              du site de fonctionner correctement.
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">4. Durée de conservation</h2>
            <p>
              Les cookies sont conservés pour une durée maximale de 13 mois, conformément aux
              recommandations des autorités de protection des données.
            </p>
          </section>
        </div>
      </div>
    </article>
  );
}
