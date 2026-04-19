import { createFileRoute } from "@tanstack/react-router";
import { Send } from "lucide-react";
import { useState } from "react";

export const Route = createFileRoute("/contact")({
  head: () => ({
    meta: [
      { title: "Contact — IPEC Bruxelles" },
      { name: "description", content: "Contactez l'IPEC à Bruxelles : informations, dossier de candidature, visites du campus." },
      { property: "og:title", content: "Contact — IPEC Bruxelles" },
      { property: "og:description", content: "Une question, un projet ? Notre équipe vous répond." },
    ],
  }),
  component: Contact,
});

function Contact() {
  const [sent, setSent] = useState(false);

  return (
    <>
      <section className="py-20 lg:py-32 border-b border-border/30">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <div className="text-xs uppercase tracking-[0.3em] text-blue mb-6">— Contact</div>
          <h1 className="font-display text-5xl md:text-7xl text-cream leading-[1] max-w-4xl text-balance">
            Parlons de <em className="text-gradient-blue not-italic">votre projet</em>.
          </h1>
          <p className="mt-8 max-w-2xl text-muted-foreground leading-relaxed text-base">
            Notre équipe est à votre écoute pour vous renseigner sur nos programmes,
            constituer votre dossier ou organiser une visite.
          </p>
        </div>
      </section>

      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-3xl px-6 lg:px-10">
          {/* Form */}
          <div>
            {sent ? (
              <div className="p-10 rounded-sm border border-blue/40 bg-blue/5 text-center">
                <div className="font-display text-3xl text-gradient-blue mb-3">Merci !</div>
                <p className="text-muted-foreground">Votre message a bien été envoyé. Nous vous répondons sous 48h.</p>
              </div>
            ) : (
              <form
                className="space-y-6"
                onSubmit={(e) => { e.preventDefault(); setSent(true); }}
              >
                <div className="grid md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-xs uppercase tracking-widest text-blue mb-3">Prénom</label>
                    <input required type="text" className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors" />
                  </div>
                  <div>
                    <label className="block text-xs uppercase tracking-widest text-blue mb-3">Nom</label>
                    <input required type="text" className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors" />
                  </div>
                </div>
                <div>
                  <label className="block text-xs uppercase tracking-widest text-blue mb-3">E-mail</label>
                  <input required type="email" className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors" />
                </div>
                <div>
                  <label className="block text-xs uppercase tracking-widest text-blue mb-3">Programme d'intérêt</label>
                  <select className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors">
                    <option>PAA — Administration des Affaires</option>
                    <option>PEA — Programme Exécutif Avancé</option>
                    <option>Je ne sais pas encore</option>
                  </select>
                </div>
                <div>
                  <label className="block text-xs uppercase tracking-widest text-blue mb-3">Spécialisation souhaitée</label>
                  <select className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors">
                    <option>Management</option>
                    <option>Marketing</option>
                    <option>Relations Internationales</option>
                    <option>Économie & Finance</option>
                    <option>Je ne sais pas encore</option>
                  </select>
                  <p className="mt-2 text-xs text-muted-foreground leading-relaxed">
                    Pour le PAA, ce choix est indicatif et non définitif : la spécialisation se précise progressivement au fil du cursus.
                  </p>
                </div>
                <div>
                  <label className="block text-xs uppercase tracking-widest text-blue mb-3">Message <span className="text-muted-foreground normal-case tracking-normal">(facultatif)</span></label>
                  <textarea rows={6} className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors resize-none" />
                </div>
                <button
                  type="submit"
                  className="inline-flex items-center gap-2 px-8 py-4 rounded-sm bg-gradient-blue text-ink font-medium shadow-blue hover:opacity-90 transition-opacity"
                >
                  Envoyer le message <Send size={16} />
                </button>
              </form>
            )}
          </div>
        </div>
      </section>
    </>
  );
}
