import { createFileRoute } from "@tanstack/react-router";
import { Mail, MapPin, Phone, Send } from "lucide-react";
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
          <div className="text-xs uppercase tracking-[0.3em] text-gold mb-6">— Contact</div>
          <h1 className="font-display text-5xl md:text-7xl text-cream leading-[1] max-w-4xl text-balance">
            Parlons de <em className="text-gradient-gold not-italic">votre projet</em>.
          </h1>
          <p className="mt-8 max-w-2xl text-lg text-muted-foreground leading-relaxed">
            Notre équipe est à votre écoute pour vous renseigner sur nos programmes,
            constituer votre dossier ou organiser une visite.
          </p>
        </div>
      </section>

      <section className="py-20 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-10 grid lg:grid-cols-12 gap-16">
          {/* Form */}
          <div className="lg:col-span-7">
            {sent ? (
              <div className="p-10 rounded-sm border border-gold/40 bg-gold/5 text-center">
                <div className="font-display text-3xl text-gradient-gold mb-3">Merci !</div>
                <p className="text-muted-foreground">Votre message a bien été envoyé. Nous vous répondons sous 48h.</p>
              </div>
            ) : (
              <form
                className="space-y-6"
                onSubmit={(e) => { e.preventDefault(); setSent(true); }}
              >
                <div className="grid md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-xs uppercase tracking-widest text-gold mb-3">Prénom</label>
                    <input required type="text" className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-gold focus:outline-none transition-colors" />
                  </div>
                  <div>
                    <label className="block text-xs uppercase tracking-widest text-gold mb-3">Nom</label>
                    <input required type="text" className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-gold focus:outline-none transition-colors" />
                  </div>
                </div>
                <div>
                  <label className="block text-xs uppercase tracking-widest text-gold mb-3">E-mail</label>
                  <input required type="email" className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-gold focus:outline-none transition-colors" />
                </div>
                <div>
                  <label className="block text-xs uppercase tracking-widest text-gold mb-3">Programme d'intérêt</label>
                  <select className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-gold focus:outline-none transition-colors">
                    <option>PAA — Administration des Affaires</option>
                    <option>PEA — Programme Exécutif Avancé</option>
                    <option>Je ne sais pas encore</option>
                  </select>
                </div>
                <div>
                  <label className="block text-xs uppercase tracking-widest text-gold mb-3">Message</label>
                  <textarea required rows={6} className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-gold focus:outline-none transition-colors resize-none" />
                </div>
                <button
                  type="submit"
                  className="inline-flex items-center gap-2 px-8 py-4 rounded-sm bg-gradient-gold text-ink font-medium shadow-gold hover:opacity-90 transition-opacity"
                >
                  Envoyer le message <Send size={16} />
                </button>
              </form>
            )}
          </div>

          {/* Info */}
          <div className="lg:col-span-5 lg:col-start-9 space-y-10">
            <div>
              <div className="flex items-center gap-3 text-gold mb-3">
                <MapPin size={18} />
                <span className="text-xs uppercase tracking-widest">Adresse</span>
              </div>
              <p className="text-cream font-display text-xl">IPEC — Campus de Bruxelles</p>
              <p className="text-muted-foreground mt-1">Bruxelles, Belgique</p>
            </div>
            <div>
              <div className="flex items-center gap-3 text-gold mb-3">
                <Mail size={18} />
                <span className="text-xs uppercase tracking-widest">E-mail</span>
              </div>
              <a href="mailto:contact@ipec-bruxelles.be" className="text-cream hover:text-gold font-display text-xl">
                contact@ipec-bruxelles.be
              </a>
            </div>
            <div>
              <div className="flex items-center gap-3 text-gold mb-3">
                <Phone size={18} />
                <span className="text-xs uppercase tracking-widest">Téléphone</span>
              </div>
              <a href="tel:+3220000000" className="text-cream hover:text-gold font-display text-xl">
                +32 2 000 00 00
              </a>
            </div>
            <div className="pt-8 border-t border-border/40">
              <p className="text-sm text-muted-foreground leading-relaxed">
                L'équipe des admissions vous répond généralement sous 48h ouvrées.
              </p>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
