import { Link } from "@tanstack/react-router";
import { LogoIpec } from "@/components/LogoIpec";

export function Footer() {
  return (
    <footer className="border-t border-border/40 bg-ink/60 mt-32">
      <div className="mx-auto max-w-7xl px-6 lg:px-10 py-16 grid gap-12 md:grid-cols-4">
        <div className="md:col-span-2">
          <div className="flex items-center gap-3 mb-4">
            <LogoIpec size={40} className="text-gold shrink-0" />
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
            <li><Link to="/programmes" className="hover:text-gold">Programmes</Link></li>
            <li><Link to="/specialisations" className="hover:text-gold">Spécialisations</Link></li>
            <li><Link to="/admissions" className="hover:text-gold">Admissions</Link></li>
            <li><Link to="/international" className="hover:text-gold">International</Link></li>
          </ul>
        </div>

        <div>
          <h4 className="text-cream text-sm uppercase tracking-widest mb-4 font-body font-medium">
            Contact
          </h4>
          <ul className="space-y-2 text-sm text-muted-foreground">
            <li>Bruxelles, Belgique</li>
            <li>contact@ipec-bruxelles.be</li>
            <li>+32 2 000 00 00</li>
          </ul>
        </div>
      </div>
      <div className="border-t border-border/40">
        <div className="mx-auto max-w-7xl px-6 lg:px-10 py-6 flex flex-col md:flex-row justify-between gap-2 text-xs text-muted-foreground">
          <p>© {new Date().getFullYear()} IPEC — Institut Privé des Études Commerciales</p>
          <p>Bruxelles · Belgique</p>
        </div>
      </div>
    </footer>
  );
}
