import { createFileRoute } from "@tanstack/react-router";

export const Route = createFileRoute("/cgv")({
  head: () => ({
    meta: [
      { title: "Conditions générales de vente — IPEC Bruxelles" },
      { name: "description", content: "Conditions générales de vente applicables aux frais de scolarité de l'IPEC." },
      { property: "og:title", content: "CGV — IPEC Bruxelles" },
      { property: "og:description", content: "Modalités contractuelles applicables aux inscriptions et frais de scolarité." },
    ],
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
              Les frais de dossier s'élèvent à <strong className="text-cream">300 €</strong>, réglés au
              dépôt de la candidature. Ils couvrent l'instruction administrative et pédagogique du
              dossier et restent acquis à l'IPEC quelle que soit la décision d'admission.
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">3. Frais de scolarité</h2>
            <ul className="space-y-2 list-disc list-inside">
              <li>PAA — Administration des Affaires : <strong className="text-cream">4 900 € / an</strong></li>
              <li>PEA — Programme Exécutif Avancé : <strong className="text-cream">5 900 € / an</strong></li>
            </ul>
            <p className="mt-3">
              Une première tranche de <strong className="text-cream">3 000 €</strong> est due à la
              confirmation d'inscription. Pour les candidats sollicitant un visa d'études, ce montant
              est intégralement déduit des droits de scolarité annuels.
            </p>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">4. Modalités de paiement</h2>
            <ul className="space-y-2 list-disc list-inside">
              <li>Paiement annuel intégral à l'inscription (escompte de 3 %)</li>
              <li>Paiement en deux fois : 50 % en septembre, 50 % en février</li>
              <li>Mensualisation sur 10 mensualités, sans frais</li>
              <li>Moyens acceptés : virement SEPA, carte de crédit, Bancontact</li>
            </ul>
          </section>

          <section>
            <h2 className="font-display text-2xl text-cream mb-4">5. Rétractation</h2>
            <p>
              Conformément au Code de droit économique belge, l'étudiant dispose d'un délai de
              <strong className="text-cream"> 14 jours</strong> à compter de la confirmation de son
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
            <h2 className="font-display text-2xl text-cream mb-4">8. Droit applicable</h2>
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
