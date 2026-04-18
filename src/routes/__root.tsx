import { Outlet, Link, createRootRoute, HeadContent, Scripts } from "@tanstack/react-router";

import appCss from "../styles.css?url";
import { Header } from "@/components/Header";
import { Footer } from "@/components/Footer";

function NotFoundComponent() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-background px-4">
      <div className="max-w-md text-center">
        <h1 className="text-7xl font-display text-gradient-gold">404</h1>
        <h2 className="mt-4 text-xl font-display text-cream">Page introuvable</h2>
        <p className="mt-2 text-sm text-muted-foreground">
          Cette page n'existe pas ou a été déplacée.
        </p>
        <div className="mt-6">
          <Link
            to="/"
            className="inline-flex items-center justify-center rounded-sm bg-gradient-gold px-6 py-3 text-sm font-medium text-ink hover:opacity-90 transition-opacity"
          >
            Retour à l'accueil
          </Link>
        </div>
      </div>
    </div>
  );
}

export const Route = createRootRoute({
  head: () => ({
    meta: [
      { charSet: "utf-8" },
      { name: "viewport", content: "width=device-width, initial-scale=1" },
      { title: "IPEC — Institut Privé des Études Commerciales · Bruxelles" },
      { name: "description", content: "École supérieure de commerce à Bruxelles. Programmes en administration des affaires et exécutif avancé. Management, Marketing, Relations Internationales, Économie & Finance." },
      { name: "author", content: "IPEC Bruxelles" },
      { property: "og:type", content: "website" },
      { name: "twitter:card", content: "summary_large_image" },
      { property: "og:title", content: "IPEC — Institut Privé des Études Commerciales · Bruxelles" },
      { name: "twitter:title", content: "IPEC — Institut Privé des Études Commerciales · Bruxelles" },
      { property: "og:description", content: "École supérieure de commerce à Bruxelles. Programmes en administration des affaires et exécutif avancé. Management, Marketing, Relations Internationales, Économie & Finance." },
      { name: "twitter:description", content: "École supérieure de commerce à Bruxelles. Programmes en administration des affaires et exécutif avancé. Management, Marketing, Relations Internationales, Économie & Finance." },
      { property: "og:image", content: "https://pub-bb2e103a32db4e198524a2e9ed8f35b4.r2.dev/0e932aee-86c9-4425-a47f-13ef7c0151b8/id-preview-fd44bf7a--e680d373-9824-4b72-b3de-ec8be69b1869.lovable.app-1776547649654.png" },
      { name: "twitter:image", content: "https://pub-bb2e103a32db4e198524a2e9ed8f35b4.r2.dev/0e932aee-86c9-4425-a47f-13ef7c0151b8/id-preview-fd44bf7a--e680d373-9824-4b72-b3de-ec8be69b1869.lovable.app-1776547649654.png" },
    ],
    links: [
      { rel: "stylesheet", href: appCss },
    ],
  }),
  shellComponent: RootShell,
  component: RootComponent,
  notFoundComponent: NotFoundComponent,
});

function RootShell({ children }: { children: React.ReactNode }) {
  return (
    <html lang="fr">
      <head>
        <HeadContent />
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
