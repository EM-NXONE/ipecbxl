import { createFileRoute, Link, useNavigate } from "@tanstack/react-router";
import { Send, Mail, MapPin, Phone, Clock, ArrowRight } from "lucide-react";
import { useEffect, useRef, useState } from "react";

export const Route = createFileRoute("/contact")({
  head: () => ({
    meta: [
      { title: "IPEC | Contact" },
      { name: "description", content: "Contactez l'IPEC, école supérieure de commerce privée à Bruxelles : informations, dossier de candidature, visites du campus en Belgique." },
      { name: "keywords", content: "contact IPEC, contact école privée Bruxelles, contact école privée Belgique, institut privé Bruxelles adresse, institut privé Belgique adresse, université privée Bruxelles contact, visite campus Bruxelles, école de commerce Bruxelles contact" },
      { property: "og:title", content: "Contact — IPEC Bruxelles" },
      { property: "og:description", content: "Une question, un projet ? L'équipe de l'institut privé IPEC à Bruxelles vous répond." },
      { property: "og:url", content: "https://ipec.school/contact" },
      { property: "og:image", content: "https://ipec.school/apple-touch-icon.png" },
      { name: "twitter:title", content: "Contact — IPEC Bruxelles" },
      { name: "twitter:description", content: "Contactez l'IPEC, institut privé en Belgique." },
      { name: "twitter:image", content: "https://ipec.school/apple-touch-icon.png" },
    ],
    links: [{ rel: "canonical", href: "https://ipec.school/contact" }],
  }),
  component: Contact,
});

const MAILER_URL = "https://ipec.school/mailer.php";

function Contact() {
  const [sent, setSent] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const confirmationRef = useRef<HTMLDivElement | null>(null);
  const navigate = useNavigate();

  useEffect(() => {
    if (sent && confirmationRef.current) {
      confirmationRef.current.scrollIntoView({ behavior: "smooth", block: "center" });
      confirmationRef.current.focus();
      const timer = setTimeout(() => {
        navigate({ to: "/" });
      }, 4000);
      return () => clearTimeout(timer);
    }
  }, [sent, navigate]);

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (submitting) return;
    setErrorMsg(null);
    setSubmitting(true);

    const fd = new FormData(e.currentTarget);
    const payload = {
      type: "contact",
      prenom: String(fd.get("prenom") ?? ""),
      nom: String(fd.get("nom") ?? ""),
      email: String(fd.get("email") ?? ""),
      sujet: String(fd.get("sujet") ?? ""),
      message: String(fd.get("message") ?? ""),
      website: String(fd.get("website") ?? ""), // honeypot
    };

    try {
      const res = await fetch(MAILER_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        setErrorMsg(data?.error ?? "Une erreur est survenue. Réessayez.");
        return;
      }
      setSent(true);
    } catch {
      setErrorMsg("Impossible d'envoyer le message. Vérifiez votre connexion.");
    } finally {
      setSubmitting(false);
    }
  };

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
                  <div className="text-cream leading-relaxed">Chaussée d'Alsemberg 897<br />1180 Uccle, Belgique</div>
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
              <div
                ref={confirmationRef}
                tabIndex={-1}
                role="status"
                aria-live="polite"
                className="p-10 rounded-sm border border-blue/40 bg-blue/5 text-center scroll-mt-24 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue/60"
              >
                <div className="font-display text-3xl text-gradient-blue mb-3">Message bien reçu</div>
                <p className="text-muted-foreground leading-relaxed mb-3">
                  Merci pour votre message. Vous allez recevoir un e-mail de confirmation et notre équipe revient vers vous rapidement.
                </p>
                <p className="text-xs uppercase tracking-[0.25em] text-blue">
                  Redirection vers l'accueil…
                </p>
              </div>
            ) : (
              <form className="space-y-6" onSubmit={handleSubmit} noValidate>
                <div className="p-5 rounded-sm border border-blue/30 bg-blue/5">
                  <p className="text-sm text-muted-foreground leading-relaxed">
                    Vous souhaitez déposer un dossier de candidature ?{" "}
                    <Link to="/inscription" className="inline-flex items-center gap-1 text-blue hover:underline">
                      Accéder au formulaire d'inscription <ArrowRight size={14} />
                    </Link>
                  </p>
                </div>

                {/* Honeypot anti-bot — caché aux humains */}
                <input
                  type="text"
                  name="website"
                  tabIndex={-1}
                  autoComplete="off"
                  aria-hidden="true"
                  style={{
                    position: "absolute",
                    left: "-9999px",
                    width: 1,
                    height: 1,
                    opacity: 0,
                    pointerEvents: "none",
                  }}
                />

                <div className="grid md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-xs uppercase tracking-widest text-blue mb-3">Prénom</label>
                    <input required name="prenom" type="text" maxLength={100} className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors" />
                  </div>
                  <div>
                    <label className="block text-xs uppercase tracking-widest text-blue mb-3">Nom</label>
                    <input required name="nom" type="text" maxLength={100} className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors" />
                  </div>
                </div>
                <div>
                  <label className="block text-xs uppercase tracking-widest text-blue mb-3">E-mail</label>
                  <input required name="email" type="email" maxLength={255} className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors" />
                </div>
                <div>
                  <label className="block text-xs uppercase tracking-widest text-blue mb-3">Sujet</label>
                  <input required name="sujet" type="text" maxLength={150} className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors" />
                </div>
                <div>
                  <label className="block text-xs uppercase tracking-widest text-blue mb-3">Message</label>
                  <textarea required name="message" rows={6} maxLength={2000} className="w-full bg-card border border-border/60 px-4 py-3 rounded-sm text-cream focus:border-blue focus:outline-none transition-colors resize-none" />
                </div>

                {errorMsg && (
                  <div
                    role="alert"
                    className="p-4 rounded-sm border border-destructive/40 bg-destructive/5 text-sm text-destructive"
                  >
                    {errorMsg}
                  </div>
                )}

                <button
                  type="submit"
                  disabled={submitting}
                  aria-busy={submitting}
                  className="inline-flex items-center gap-2 px-8 py-4 rounded-sm bg-gradient-blue text-ink font-medium shadow-blue hover:opacity-90 transition-opacity disabled:opacity-60 disabled:cursor-not-allowed"
                >
                  {submitting ? "Envoi en cours…" : "Envoyer le message"}
                  {!submitting && <Send size={16} />}
                </button>
              </form>
            )}
          </div>
        </div>
      </section>
    </>
  );
}
