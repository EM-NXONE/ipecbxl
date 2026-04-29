// @lovable.dev/vite-tanstack-config already includes the following — do NOT add them manually
// or the app will break with duplicate plugins:
//   - tanstackStart, viteReact, tailwindcss, tsConfigPaths, cloudflare (build-only),
//     componentTagger (dev-only), VITE_* env injection, @ path alias, React/TanStack dedupe,
//     error logger plugins, and sandbox detection (port/host/strictPort).
// You can pass additional config via defineConfig({ vite: { ... } }) if needed.
import { defineConfig } from "@lovable.dev/vite-tanstack-config";

// =====================================================================
// Builds statiques pour n0c (Apache/PHP, sans Node.js).
// =====================================================================
//
// Trois cibles au total :
//
//   STATIC_BUILD=site  npm run build  → site public      (ipec.school)
//   STATIC_BUILD=admin npm run build  → portail admin    (admin.ipec.school)
//   STATIC_BUILD=etu   npm run build  → portail étudiant (lms.ipec.school)
//
// Les pages publiques sont prerendées en HTML par TanStack. Les pages
// authentifiées sont des SPA (servies via fallback .htaccess → index.html).
// =====================================================================

const TARGET = process.env.STATIC_BUILD;

// Routes publiques du site (prerender complet)
const SITE_ROUTES = [
  "/",
  "/admissions",
  "/cgu",
  "/cgv",
  "/confidentialite",
  "/contact",
  "/cookies",
  "/inscription",
  "/international",
  "/mentions-legales",
  "/programmes",
  "/verification",
  "/vie-etudiante",
];

// Pages publiques de l'espace admin à prerender (login uniquement,
// le reste est rendu côté client après auth).
const ADMIN_PUBLIC_ROUTES = ["/admin/login"];

// Pages publiques de l'espace étudiant à prerender.
const ETU_PUBLIC_ROUTES = [
  "/etudiant/login",
  "/etudiant/mot-de-passe-oublie",
];

function makeStaticConfig(routes: string[]) {
  return {
    cloudflare: false,
    tanstackStart: {
      prerender: {
        enabled: true,
        crawlLinks: false,
        retryCount: 2,
      },
      pages: routes.map((path) => ({
        path,
        prerender: { enabled: true },
      })),
    },
  };
}

let extra = {};
if (TARGET === "site" || process.env.STATIC_BUILD === "1") {
  // "1" conservé pour compat avec l'ancien script de build.
  extra = makeStaticConfig(SITE_ROUTES);
} else if (TARGET === "admin") {
  extra = makeStaticConfig(ADMIN_PUBLIC_ROUTES);
} else if (TARGET === "etu") {
  extra = makeStaticConfig(ETU_PUBLIC_ROUTES);
}

export default defineConfig(extra);
