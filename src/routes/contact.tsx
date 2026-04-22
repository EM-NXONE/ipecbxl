import { createFileRoute, Link } from "@tanstack/react-router";
import { Send, Mail, MapPin, Phone, Clock, ArrowRight } from "lucide-react";
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
        <div className="mx-auto max-w-7xl px-6 lg:px-10 grid lg:grid-cols-12 gap-12">
          {/* Coordonnées */}
          <aside className="lg:col-span-4 space-y-8">
            <div>
              <div className="text-xs uppercase tracking-[0.3em] text-blue mb-4">— Nous joindre</div>
              <h2 className="font-display text-2xl text-cream mb-2">Bureau des admissions</h2>
              <p className="text-sm text-muted-foreground leading-relaxed">
                L'équipe vous répond du lundi au vendredi.
              </p>
            </div>

            <ul className="space-y-6 text-sm">
              <li className="flex gap-4">
                <MapPin size={20} className="text-blue shrink-0 mt-0.5" />
                <div>
                  <div className="text-xs uppercase tracking-widest text-blue mb-1">Adresse</div>
                  <div className="text-cream leading-relaxed">Bruxelles<br />Belgique</div>
                </div>
              </li>
              <li className="flex gap-4">
                <Mail size={20} className="text-blue shrink-0 mt-0.5" />
                <div>
                  <div className="text-xs uppercase tracking-widest text-blue mb-1">E-mail</div>
                  <a href="mailto:contact@ipec.school" className="text-cream hover:text-blue transition-colors">
                    contact@ipec.school
                  </a>
                </div>
              </li>
              <li className="flex gap-4">
                <Phone size={20} className="text-blue shrink-0 mt-0.5" />
                <div>
                  <div className="text-xs uppercase tracking-widest text-blue mb-1">Téléphone</div>
                  <a href="tel:+3220000000" className="text-cream hover:text-blue transition-colors">
                    +32 2 000 00 00
                  </a>
                </div>
              </li>
              <li className="flex gap-4">
                <Clock size={20} className="text-blue shrink-0 mt-0.5" />
                <div>
                  <div className="text-xs uppercase tracking-widest text-blue mb-1">Horaires</div>
                  <div className="text-cream leading-relaxed">Lun – Ven · 9h00 – 17h30</div>
                </div>
              </li>
            </ul>
          </aside>

          {/* Form */}
          <div className="lg:col-span-7 lg:col-start-6">
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
                <div className="p-5 rounded-sm border border-blue/30 bg-blue/5">
                  <p className="text-sm text-muted-foreground leading-relaxed">
                    Vous souhaitez déposer un dossier de candidature ?{" "}
                    <Link to="/inscription" className="inline-flex items-center gap-1 text-blue hover:underline">
                      Accéder au formulaire d'inscription <ArrowRight size={14} />
                    </Link>
                  </p>
                </div>

                <div className="grid md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-xs uppercase tracking-widest text-blue mb-3">Prénom</label>
                    <input required type="text" maxLength={100} className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors" />
                  </div>
                  <div>
                    <label className="block text-xs uppercase tracking-widest text-blue mb-3">Nom</label>
                    <input required type="text" maxLength={100} className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors" />
                  </div>
                </div>
                <div>
                  <label className="block text-xs uppercase tracking-widest text-blue mb-3">E-mail</label>
                  <input required type="email" maxLength={255} className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors" />
                </div>
                <div>
                  <label className="block text-xs uppercase tracking-widest text-blue mb-3">Sujet</label>
                  <input required type="text" maxLength={150} className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors" />
                </div>
                <div>
                  <label className="block text-xs uppercase tracking-widest text-blue mb-3">Message</label>
                  <textarea required rows={6} maxLength={2000} className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors resize-none" />
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
