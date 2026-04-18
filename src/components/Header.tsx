import { Link } from "@tanstack/react-router";
import { useState } from "react";
import { Menu, X } from "lucide-react";
import { LogoIpec } from "./LogoIpec";

const links = [
  { to: "/", label: "Accueil" },
  { to: "/programmes", label: "Programmes" },
  { to: "/specialisations", label: "Spécialisations" },
  { to: "/admissions", label: "Admissions" },
  { to: "/international", label: "International" },
  { to: "/contact", label: "Contact" },
] as const;

export function Header() {
  const [open, setOpen] = useState(false);

  return (
    <header className="fixed top-0 left-0 right-0 z-50 backdrop-blur-xl bg-background/70 border-b border-border/40">
      <div className="mx-auto max-w-7xl px-6 lg:px-10 h-20 flex items-center justify-between">
        <Link to="/" className="flex items-center gap-3 group">
          <LogoIpec size={40} className="text-gold shrink-0" />
          <div className="leading-tight">
            <div className="font-display text-xl text-cream tracking-tight">IPEC</div>
            <div className="text-[10px] uppercase tracking-[0.2em] text-muted-foreground">INSTITUT PRIVÉ DES ÉTUDES COMMERCIALES</div>
          </div>
        </Link>

        <nav className="hidden lg:flex items-center gap-1">
          {links.map((l) => (
            <Link
              key={l.to}
              to={l.to}
              className="px-4 py-2 text-sm text-muted-foreground hover:text-gold transition-colors"
              activeProps={{ className: "px-4 py-2 text-sm text-gold" }}
            >
              {l.label}
            </Link>
          ))}
        </nav>

        <Link
          to="/admissions"
          className="hidden lg:inline-flex items-center px-5 py-2.5 rounded-sm bg-gradient-gold text-ink text-sm font-medium hover:opacity-90 transition-opacity shadow-gold"
        >
          S'inscrire
        </Link>

        <button
          className="lg:hidden text-cream"
          onClick={() => setOpen(!open)}
          aria-label="Menu"
        >
          {open ? <X size={24} /> : <Menu size={24} />}
        </button>
      </div>

      {open && (
        <div className="lg:hidden border-t border-border/40 bg-background/95 backdrop-blur-xl">
          <nav className="flex flex-col p-6 gap-2">
            {links.map((l) => (
              <Link
                key={l.to}
                to={l.to}
                onClick={() => setOpen(false)}
                className="py-3 text-base text-muted-foreground hover:text-gold border-b border-border/30"
              >
                {l.label}
              </Link>
            ))}
          </nav>
        </div>
      )}
    </header>
  );
}
