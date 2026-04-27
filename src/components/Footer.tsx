import { Link, useLocation } from "@tanstack/react-router";
import { LogoIpec } from "@/components/LogoIpec";

const NAV_LINKS = [
  { to: "/programmes", label: "Programmes" },
  { to: "/admissions", label: "Admissions" },
  { to: "/international", label: "International" },
  { to: "/vie-etudiante", label: "Vie étudiante" },
  { to: "/inscription", label: "Inscription" },
  { to: "/contact", label: "Contact" },
] as const;

export function Footer() {
  const { pathname } = useLocation();
  return (
    <footer className="border-t border-border/40 bg-surface mt-32">
      <div className="mx-auto max-w-7xl px-6 lg:px-10 py-16 grid gap-12 md:grid-cols-4">
        <div className="md:col-span-2">
          <div className="flex items-center gap-3 mb-4">
            <LogoIpec size={40} className="text-blue shrink-0" />
            <div>
              <div className="font-display text-xl text-cream">IPEC</div>
              <div className="text-[10px] uppercase tracking-[0.2em] text-muted-foreground">
                Institut Privé des Études Commerciales
              </div>
            </div>
          </div>
          <p className="text-sm text-muted-foreground max-w-md leading-relaxed">
            Une nouvelle génération d'étudiants formés au cœur de Bruxelles,
            capitale européenne des affaires et de la diplomatie.
          </p>
        </div>

        <div>
          <h4 className="text-cream text-sm uppercase tracking-widest mb-4 font-body font-medium">
            Navigation
          </h4>
          <ul className="space-y-2 text-sm text-muted-foreground">
            <li><Link to="/programmes" className="hover:text-blue">Programmes</Link></li>
            <li><Link to="/admissions" className="hover:text-blue">Admissions</Link></li>
            <li><Link to="/international" className="hover:text-blue">International</Link></li>
            <li><Link to="/vie-etudiante" className="hover:text-blue">Vie étudiante</Link></li>
            <li><Link to="/inscription" className="hover:text-blue">Inscription</Link></li>
            <li><Link to="/contact" className="hover:text-blue">Contact</Link></li>
          </ul>
        </div>

        <div>
          <h4 className="text-cream text-sm uppercase tracking-widest mb-4 font-body font-medium">
            Contact
          </h4>
          <ul className="space-y-2 text-sm text-muted-foreground">
            <li>Chaussée d'Alsemberg 897</li>
            <li>1180 Uccle, Belgique</li>
            <li>contact@ipec.school</li>
            <li>+32 2 000 00 00</li>
          </ul>
        </div>
      </div>
      <div className="border-t border-border/40">
        <div className="mx-auto max-w-7xl px-6 lg:px-10 py-6 flex flex-col gap-3 text-xs text-muted-foreground">
          <div className="flex flex-col md:flex-row justify-between gap-3">
            <p>© {new Date().getFullYear()} IPEC — Institut Privé des Études Commerciales ASBL</p>
            <nav className="flex flex-wrap gap-x-4 gap-y-1">
              <Link to="/mentions-legales" className="hover:text-blue">Mentions légales</Link>
              <span aria-hidden="true">·</span>
              <Link to="/cgu" className="hover:text-blue">CGU</Link>
              <span aria-hidden="true">·</span>
              <Link to="/cgv" className="hover:text-blue">CGV</Link>
              <span aria-hidden="true">·</span>
              <Link to="/confidentialite" className="hover:text-blue">Confidentialité</Link>
              <span aria-hidden="true">·</span>
              <Link to="/cookies" className="hover:text-blue">Cookies</Link>
            </nav>
          </div>
          <p className="text-[11px] italic opacity-80">
            Établissement, formations et diplômes non reconnus par la Communauté française de Belgique.
          </p>
        </div>
      </div>
    </footer>
  );
}
