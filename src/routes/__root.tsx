import { Outlet, Link, createRootRoute, HeadContent, Scripts } from "@tanstack/react-router";

import appCss from "../styles.css?url";
import { Header } from "@/components/Header";
import { Footer } from "@/components/Footer";

function NotFoundComponent() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-background px-4">
      <div className="max-w-md text-center">
        <h1 className="text-7xl font-display text-gradient-blue">404</h1>
        <h2 className="mt-4 text-xl font-display text-cream">Page introuvable</h2>
        <p className="mt-2 text-sm text-muted-foreground">
          Cette page n'existe pas ou a été déplacée.
        </p>
        <div className="mt-6">
          <Link
            to="/"
            className="inline-flex items-center justify-center rounded-sm bg-gradient-blue px-6 py-3 text-sm font-medium text-ink hover:opacity-90 transition-opacity"
          >
            Retour à l'accueil
          </Link>
        </div>
      </div>
    </div>
  );
}

const ORG_JSONLD = JSON.stringify({
  "@context": "https://schema.org",
  "@type": "CollegeOrUniversity",
  "name": "IPEC — Institut Privé des Études Commerciales",
  "alternateName": ["IPEC Bruxelles", "Institut Privé des Études Commerciales"],
  "url": "https://ipec.school",
  "logo": "https://ipec.school/android-chrome-512x512.png",
  "image": "https://ipec.school/apple-touch-icon.png",
  "description": "École supérieure de commerce privée à Bruxelles, Belgique. Programmes en management, marketing, relations internationales, économie et finance.",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Chaussée d'Alsemberg 897",
    "postalCode": "1180",
    "addressLocality": "Uccle",
    "addressRegion": "Bruxelles-Capitale",
    "addressCountry": "BE"
  },
  "email": "contact@ipec.school",
  "areaServed": "BE",
  "sameAs": []
});

export const Route = createRootRoute({
  head: () => ({
    meta: [
      { charSet: "utf-8" },
      { name: "viewport", content: "width=device-width, initial-scale=1" },
      { name: "author", content: "IPEC Bruxelles" },
      { name: "robots", content: "index, follow, max-image-preview:large, max-snippet:-1" },
      { name: "theme-color", content: "#0a1628" },
      { name: "format-detection", content: "telephone=no" },
      { name: "geo.region", content: "BE-BRU" },
      { name: "geo.placename", content: "Bruxelles" },
      { property: "og:type", content: "website" },
      { property: "og:site_name", content: "IPEC Bruxelles" },
      { property: "og:locale", content: "fr_BE" },
      { name: "twitter:card", content: "summary_large_image" },
    ],
    links: [
      { rel: "stylesheet", href: appCss },
      { rel: "icon", type: "image/svg+xml", href: "/favicon.svg" },
      { rel: "icon", type: "image/x-icon", href: "/favicon.ico" },
      { rel: "icon", type: "image/png", sizes: "32x32", href: "/favicon-32x32.png" },
      { rel: "icon", type: "image/png", sizes: "16x16", href: "/favicon-16x16.png" },
      { rel: "apple-touch-icon", sizes: "180x180", href: "/apple-touch-icon.png" },
      { rel: "manifest", href: "/site.webmanifest" },
    ],
    scripts: [
      { type: "application/ld+json", children: ORG_JSONLD },
    ],
  }),
  shellComponent: RootShell,
  component: RootComponent,
  notFoundComponent: NotFoundComponent,
});

// Inline script that runs BEFORE React hydrates: reads the persisted theme
// from localStorage and applies the `.light` class to <html> immediately.
// This prevents the dark→light flash (FOUC) on page load.
const NO_FLASH_THEME_SCRIPT = `(function(){try{var t=localStorage.getItem('ipec-theme');if(t==='light'){document.documentElement.classList.add('light');}}catch(e){}})();`;

function RootShell({ children }: { children: React.ReactNode }) {
  return (
    <html lang="fr" suppressHydrationWarning>
      <head>
        <HeadContent />
        <script dangerouslySetInnerHTML={{ __html: NO_FLASH_THEME_SCRIPT }} />
      </head>
      <body>
        {children}
        <Scripts />
      </body>
    </html>
  );
}

function RootComponent() {
  return (
    <div className="min-h-screen flex flex-col">
      <Header />
      <main className="flex-1 pt-20">
        <Outlet />
      </main>
      <Footer />
    </div>
  );
}
