/**
 * Layout commun aux espaces admin et étudiant.
 * - Topbar fixe (logo IPEC + titre + actions)
 * - Sidebar latérale fixe sur desktop, drawer overlay sur mobile (≤ 960 px)
 * - Reprend la charte du site public (LogoIpec, font-display, gradients, tokens)
 */
import { useEffect, useState, type ReactNode } from "react";
import { Link, useRouterState } from "@tanstack/react-router";
import { Menu, X, LogOut, Sun, Moon } from "lucide-react";
import { LogoIpec } from "./LogoIpec";
import { useTheme } from "@/hooks/use-theme";

function ThemeToggle() {
  const { theme, toggle } = useTheme();
  const isDark = theme === "dark";
  return (
    <button
      type="button"
      onClick={toggle}
      aria-label={isDark ? "Passer en thème clair" : "Passer en thème sombre"}
      title={isDark ? "Thème clair" : "Thème sombre"}
      className="inline-flex items-center justify-center h-8 w-8 rounded-sm text-muted-foreground hover:text-blue border border-border/40 hover:border-blue/40 transition-colors"
    >
      {isDark ? <Sun size={14} /> : <Moon size={14} />}
    </button>
  );
}

export interface PortalNavItem {
  to: string;
  label: string;
  icon: ReactNode;
  /** Match exact (par défaut: false → matche aussi les enfants) */
  exact?: boolean;
}

interface PortalLayoutProps {
  /** Titre court affiché à droite du logo (ex: "Administration", "Espace étudiant"). */
  brandSubtitle: string;
  /** Items de navigation latérale. */
  nav: PortalNavItem[];
  /** Nom affiché dans la topbar (utilisateur connecté). */
  userLabel?: string | null;
  /** Callback de déconnexion. */
  onLogout?: () => void | Promise<void>;
  /** Contenu de la page. */
  children: ReactNode;
}

export function PortalLayout({
  brandSubtitle,
  nav,
  userLabel,
  onLogout,
  children,
}: PortalLayoutProps) {
  const [drawerOpen, setDrawerOpen] = useState(false);
  const pathname = useRouterState({ select: (s) => s.location.pathname });

  // Ferme le drawer quand on change de route
  useEffect(() => {
    setDrawerOpen(false);
  }, [pathname]);

  // Ferme le drawer avec Escape
  useEffect(() => {
    if (!drawerOpen) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") setDrawerOpen(false);
    };
    document.addEventListener("keydown", onKey);
    return () => document.removeEventListener("keydown", onKey);
  }, [drawerOpen]);

  const isActive = (item: PortalNavItem) => {
    if (item.exact) return pathname === item.to;
    return pathname === item.to || pathname.startsWith(item.to + "/");
  };

  return (
    <div className="min-h-screen bg-background text-foreground font-body">
      {/* Topbar */}
      <header className="fixed top-0 left-0 right-0 z-40 h-16 backdrop-blur-xl bg-background/80 border-b border-border/40">
        <div className="h-full px-4 lg:px-6 flex items-center justify-between gap-4">
          <div className="flex items-center gap-3 min-w-0">
            <button
              type="button"
              className="lg:hidden p-2 -ml-2 text-cream"
              onClick={() => setDrawerOpen((v) => !v)}
              aria-label="Ouvrir le menu"
            >
              {drawerOpen ? <X size={22} /> : <Menu size={22} />}
            </button>
            <Link to="/" className="flex items-center gap-3 min-w-0">
              <LogoIpec size={36} className="text-blue shrink-0" />
              <div className="leading-tight min-w-0">
                <div className="font-display text-lg text-cream tracking-tight">IPEC</div>
                <div className="text-[10px] uppercase tracking-[0.2em] text-muted-foreground truncate">
                  {brandSubtitle}
                </div>
              </div>
            </Link>
          </div>

          <div className="flex items-center gap-3">
            {userLabel && (
              <span className="hidden sm:inline text-xs text-muted-foreground truncate max-w-[200px]">
                {userLabel}
              </span>
            )}
            <ThemeToggle />
            {onLogout && (
              <button
                type="button"
                onClick={() => onLogout()}
                className="inline-flex items-center gap-2 px-3 py-1.5 rounded-sm text-xs text-muted-foreground hover:text-blue border border-border/40 hover:border-blue/40 transition-colors"
              >
                <LogOut size={14} />
                <span className="hidden sm:inline">Déconnexion</span>
              </button>
            )}
          </div>
        </div>
      </header>

      {/* Sidebar (desktop) + Drawer (mobile) */}
      <aside
        className={[
          "fixed top-16 bottom-0 left-0 z-30 w-64 bg-card border-r border-border/40",
          "transition-transform duration-200 ease-out",
          "lg:translate-x-0",
          drawerOpen ? "translate-x-0" : "-translate-x-full",
        ].join(" ")}
      >
        <nav className="h-full overflow-y-auto p-4 flex flex-col gap-1">
          {nav.map((item) => {
            const active = isActive(item);
            return (
              <Link
                key={item.to}
                to={item.to}
                className={[
                  "flex items-center gap-3 px-3 py-2.5 rounded-sm text-sm transition-colors",
                  active
                    ? "bg-blue/10 text-blue"
                    : "text-muted-foreground hover:text-cream hover:bg-secondary/50",
                ].join(" ")}
              >
                <span className="shrink-0">{item.icon}</span>
                <span className="truncate">{item.label}</span>
              </Link>
            );
          })}
        </nav>
      </aside>

      {/* Backdrop drawer mobile */}
      {drawerOpen && (
        <div
          className="lg:hidden fixed inset-0 top-16 z-20 bg-background/60 backdrop-blur-sm"
          onClick={() => setDrawerOpen(false)}
          aria-hidden="true"
        />
      )}

      {/* Contenu */}
      <main className="pt-16 lg:pl-64">
        <div className="p-4 lg:p-8 max-w-6xl mx-auto">{children}</div>
      </main>
    </div>
  );
}

/**
 * Layout minimal pour les pages publiques des espaces (login, reset, activer…).
 * Pas de sidebar, juste la topbar et un contenu centré.
 */
export function PortalAuthShell({
  brandSubtitle,
  children,
}: {
  brandSubtitle: string;
  children: ReactNode;
}) {
  return (
    <div className="min-h-screen flex flex-col bg-background text-foreground font-body">
      <header className="h-16 backdrop-blur-xl bg-background/80 border-b border-border/40">
        <div className="h-full px-4 lg:px-6 flex items-center">
          <Link to="/" className="flex items-center gap-3">
            <LogoIpec size={36} className="text-blue shrink-0" />
            <div className="leading-tight">
              <div className="font-display text-lg text-cream tracking-tight">IPEC</div>
              <div className="text-[10px] uppercase tracking-[0.2em] text-muted-foreground">
                {brandSubtitle}
              </div>
            </div>
          </Link>
        </div>
      </header>
      <main className="flex-1 flex items-center justify-center p-4">
        <div className="w-full max-w-md">{children}</div>
      </main>
    </div>
  );
}
