import { createFileRoute } from "@tanstack/react-router";

export const Route = createFileRoute("/cgv")({
  head: () => ({
    meta: [
      { title: "IPEC | CGV" },
      { name: "description", content: "Conditions générales de vente applicables aux frais de scolarité de l'IPEC, institut privé en Belgique." },
      { name: "robots", content: "noindex, follow" },
      { property: "og:title", content: "CGV — IPEC Bruxelles" },
      { property: "og:description", content: "Modalités contractuelles applicables aux inscriptions et frais de scolarité." },
      { property: "og:url", content: "https://ipec.school/cgv" },
    ],
    links: [{ rel: "canonical", href: "https://ipec.school/cgv" }],
  }),
  component: CGV,
});

function CGV() {
  return (
    <article className="py-20 lg:py-32">
      <div className="mx-auto max-w-3xl px-6 lg:px-10">
        <div className="text-xs uppercase tracking-[0.3em] text-blue mb-6">— Légal</div>
        <h1 className="font-display text-5xl md:text-7xl text-cream leading-[1] mb-12 text-balance">
          Conditions générales de <em className="text-gradient-blue not-italic">vente</em>
        </h1>

        <div className="space-y-10 text-sm text-muted-foreground leading-relaxed">
          <section>
            <h2 className="font-display text-2xl text-cream mb-4">1. Champ d'application</h2>
            <p>
              Les présentes conditions générales de vente (CGV) régissent les relations contractuelles
              entre l'Institut Privé des Études Commerciales (IPEC) et toute personne physique ou
              morale procédant à une inscription à l'un des programmes proposés par l'établissement.
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">2. Frais de dossier</h2>
            <p>
              Les frais de dossier s'élèvent à 400 €, réglés au
              dépôt de la candidature. Ils couvrent l'instruction administrative et pédagogique du
              dossier et restent acquis à l'IPEC quelle que soit la décision d'admission.
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">3. Frais de scolarité</h2>
            <ul className="space-y-2 list-disc list-inside">
              <li>PAA — Administration des Affaires : 4 900 € / an</li>
              <li>PEA — Programme Exécutif Avancé : 5 900 € / an</li>
            </ul>
            <p className="mt-3">
              Une première tranche de 3 000 € est due à la confirmation d'inscription.
              Le solde — soit 1 900 € en PAA et 2 900 € en PEA — est exigible avant le
              début effectif des cours pour les étudiants en présentiel ; pour les
              étudiants internationaux soumis à une procédure de visa, ce solde est dû
              à l'arrivée à l'institut. Les sommes versées sont intégralement déduites
              des droits de scolarité annuels.
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">4. Modalités de paiement</h2>
            <p className="mb-3">
              Les droits de scolarité sont réglés en deux tranches :
            </p>
            <ul className="space-y-2 list-disc list-inside">
              <li>1ʳᵉ tranche de 3 000 € à la confirmation d'inscription</li>
              <li>
                2ᵉ tranche — solde (1 900 € en PAA, 2 900 € en PEA) : avant le début
                effectif des cours pour les étudiants en présentiel ; à l'arrivée à
                l'institut pour les étudiants internationaux soumis à une procédure de visa
              </li>
            </ul>
            <div className="mt-6 p-5 rounded-sm border border-blue/30 bg-blue/5">
              <div className="text-xs uppercase tracking-[0.25em] text-blue mb-3">Plan de paiement échelonné</div>
              <p className="text-cream leading-relaxed mb-3">
                L'octroi d'un plan de paiement échelonné relève de la seule appréciation
                discrétionnaire du service administratif de l'IPEC. Toute demande est
                examinée au cas par cas et peut être acceptée, refusée ou révoquée sans
                que l'IPEC soit tenue de motiver sa décision.
              </p>
              <p className="text-muted-foreground leading-relaxed">
                Aucune demande ne confère de droit acquis et aucun recours, amiable ou
                contentieux, ne peut être fondé sur un éventuel refus. Lorsqu'il est
                accordé, l'aménagement doit faire l'objet d'un accord écrit préalable à
                la rentrée académique ; tout défaut ou retard de paiement à l'une des
                échéances convenues entraîne de plein droit la déchéance du terme et
                l'exigibilité immédiate du solde.
              </p>
            </div>
            <p className="mt-4">
              Moyens de paiement acceptés : virement SEPA, Bancontact, carte de crédit
              et espèces (dans les limites prévues par la législation belge en vigueur).
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">5. Rétractation</h2>
            <p>
              Conformément au Code de droit économique belge, l'étudiant dispose d'un délai de
              14 jours à compter de la confirmation de son
              inscription pour exercer son droit de rétractation, sauf renoncement exprès dans le
              cas où la formation a déjà débuté à sa demande.
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">6. Désistement et remboursement</h2>
            <p>
              En cas de désistement après la rentrée académique, les sommes versées au titre de la
              scolarité ne sont pas remboursables, sauf cas de force majeure dûment justifié.
              Les frais de dossier ne sont en aucun cas remboursables.
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">7. Retard de paiement</h2>
            <p>
              Tout retard de paiement peut entraîner la suspension de l'accès aux activités
              pédagogiques jusqu'à régularisation, sans préjudice de poursuites éventuelles.
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">8. Conditions particulières d'admission</h2>
            <p className="mb-4">
              Le candidat-étudiant à l'IPEC reconnaît avoir été informé des conditions particulières
              suivantes, qui font partie intégrante de son engagement contractuel au moment de
              l'inscription :
            </p>
            <ol className="space-y-4 list-decimal list-outside ml-5">
              <li>
                Dès réception du dossier complet et du paiement des frais de dossier
                (400 € pour l'année académique en cours),
                l'IPEC délivre une attestation de préadmission dans un délai raisonnable.
                Les frais de dossier ne sont en aucun cas remboursables.
              </li>
              <li>
                Formation et diplôme non reconnus par la
                Communauté française de Belgique (article 14/4, §2, du décret du 7 novembre 2013).
              </li>
              <li>
                L'IPEC ne se porte en aucun cas garant des étudiants. L'IPEC ne propose aucun stage
                ou logement en Belgique. Ces dimensions sont entièrement à la charge du candidat,
                qui est tenu de faire les démarches nécessaires.
              </li>
              <li>
                En cas de préadmission, celle-ci ne devient définitive qu'à partir de la réception
                du paiement de la première tranche des frais de scolarité (3 000€), qui doit être versée sur le compte de l'IPEC.
              </li>
              <li>
                Les étudiants établis dans un État ne faisant pas partie de l'Union européenne ne
                recevront les documents nécessaires à leur demande de visa étudiant qu'une fois le
                paiement de cette première tranche réceptionné par l'IPEC. Ces mêmes
                étudiants seront tenus de suivre leur formation en visioconférence (online) durant
                toute la procédure d'obtention d'un visa en Belgique, jusqu'à l'obtention effective
                dudit visa.
              </li>
              <li>
                En cas de refus de visa étudiant par les autorités belges, les étudiants visés au
                point précédent seront libres d'exercer toute voie de recours disponible, à leur
                propre discrétion. Le cas échéant, si la date de rentrée académique est échue, les
                étudiants obtiendront une dérogation leur permettant de participer à la prochaine
                rentrée décalée.
              </li>
              <li>
                Dans le cas où ces étudiants ne souhaitent pas introduire de recours contre une
                décision de refus de visa étudiant, ou dans le cas où le recours introduit n'a pas
                abouti sur une décision d'obtention de visa, leur inscription au programme en
                présentiel sera automatiquement remplacée par une inscription au programme online
                correspondant. La première tranche des droits d'inscription versée sera de plein
                droit utilisée pour le paiement des droits d'inscription audit programme online.
              </li>
              <li>
                Le candidat reconnaît avoir été informé que le paiement des droits d'inscription
                visés ci-dessus entraîne donc une inscription définitive à l'IPEC. Seules les
                modalités de suivi du programme (en présentiel à Bruxelles ou online) seront
                déterminées en fonction de l'obtention d'un visa étudiant.
              </li>
            </ol>
            <p className="mt-6 p-4 rounded-sm border border-blue/30 bg-blue/5 text-cream">
              L'acceptation de ces conditions particulières est requise lors de la soumission du
              formulaire d'inscription en ligne. Cette validation électronique constitue une
              signature électronique au sens du Règlement eIDAS (UE) n° 910/2014 et engage
              le candidat au même titre qu'une signature manuscrite. L'horodatage et l'adresse
              IP de la soumission sont conservés à des fins probatoires.
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">10. Droit applicable</h2>
            <p>
              Les présentes CGV sont régies par le droit belge. Tout litige sera porté devant les
              tribunaux compétents de Bruxelles.
            </p>
          </section>
        </div>
      </div>
    </article>
  );
}
