// @lovable.dev/vite-tanstack-config already includes the following — do NOT add them manually
// or the app will break with duplicate plugins:
//   - tanstackStart, viteReact, tailwindcss, tsConfigPaths, cloudflare (build-only),
//     componentTagger (dev-only), VITE_* env injection, @ path alias, React/TanStack dedupe,
//     error logger plugins, and sandbox detection (port/host/strictPort).
// You can pass additional config via defineConfig({ vite: { ... } }) if needed.
import { defineConfig } from "@lovable.dev/vite-tanstack-config";

// Liste de toutes les routes statiques à prérendre en HTML.
// Ajoutez ici toute nouvelle route créée dans src/routes/.
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
];

export default defineConfig({
  tanstackStart: {
    // Génère un .html pré-rendu pour chaque route → déployable sur n'importe
    // quel hébergeur statique (n0c, Apache, Nginx) sans Node.js côté serveur.
    prerender: {
      enabled: true,
      crawlLinks: true,
      retryCount: 2,
    },
    pages: PRERENDER_ROUTES.map((path) => ({ path, prerender: { enabled: true } })),
  },
});
