// @lovable.dev/vite-tanstack-config already includes the following — do NOT add them manually
// or the app will break with duplicate plugins:
//   - tanstackStart, viteReact, tailwindcss, tsConfigPaths, cloudflare (build-only),
//     componentTagger (dev-only), VITE_* env injection, @ path alias, React/TanStack dedupe,
//     error logger plugins, and sandbox detection (port/host/strictPort).
// You can pass additional config via defineConfig({ vite: { ... } }) if needed.
import { defineConfig } from "@lovable.dev/vite-tanstack-config";

// =====================================================================
// Build statique pour n0c (hébergement Apache/PHP, sans Node.js).
// =====================================================================
// Quand STATIC_BUILD=1, on désactive le preset Cloudflare et on active
// le prerendering : chaque route listée ci-dessous est rendue en HTML
// au build → n0c sert ces .html directement.
//
// Build local pour n0c :   STATIC_BUILD=1 npm run build
// Build standard Lovable : npm run build
// =====================================================================

const IS_STATIC_BUILD = process.env.STATIC_BUILD === "1";

const PRERENDER_ROUTES = [
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
  "/vie-etudiante",
];

export default defineConfig(
  IS_STATIC_BUILD
    ? {
        // Désactive Cloudflare → preset node-server par défaut → produit
        // dist/server/server.js que le prerender sait charger.
        cloudflare: false,
        tanstackStart: {
          prerender: {
            enabled: true,
            crawlLinks: true,
            retryCount: 2,
          },
          pages: PRERENDER_ROUTES.map((path) => ({
            path,
            prerender: { enabled: true },
          })),
        },
      }
    : {},
);
