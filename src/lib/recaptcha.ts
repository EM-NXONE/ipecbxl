/**
 * Helper Google reCAPTCHA v3 (invisible, score-based).
 * - La clé site est publique (VITE_RECAPTCHA_SITE_KEY).
 * - Le script est chargé une seule fois (idempotent), à la 1ʳᵉ utilisation
 *   plutôt qu'au boot global → meilleur LCP et moins d'appels Google.
 * - getRecaptchaToken("contact") renvoie un token court à envoyer au backend
 *   qui le vérifie via siteverify + applique un seuil de score.
 */

declare global {
  interface Window {
    grecaptcha?: {
      ready: (cb: () => void) => void;
      execute: (siteKey: string, opts: { action: string }) => Promise<string>;
    };
  }
}

const SITE_KEY = import.meta.env.VITE_RECAPTCHA_SITE_KEY as string | undefined;
let scriptLoadingPromise: Promise<void> | null = null;

function loadScript(): Promise<void> {
  if (typeof window === "undefined") return Promise.resolve();
  if (window.grecaptcha) return Promise.resolve();
  if (scriptLoadingPromise) return scriptLoadingPromise;

  scriptLoadingPromise = new Promise((resolve, reject) => {
    const existing = document.querySelector<HTMLScriptElement>(
      'script[data-recaptcha="v3"]',
    );
    if (existing) {
      existing.addEventListener("load", () => resolve());
      existing.addEventListener("error", () => reject(new Error("recaptcha load failed")));
      return;
    }
    const s = document.createElement("script");
    s.src = `https://www.google.com/recaptcha/api.js?render=${encodeURIComponent(SITE_KEY ?? "")}`;
    s.async = true;
    s.defer = true;
    s.dataset.recaptcha = "v3";
    s.onload = () => resolve();
    s.onerror = () => reject(new Error("recaptcha load failed"));
    document.head.appendChild(s);
  });
  return scriptLoadingPromise;
}

/**
 * Génère un token reCAPTCHA v3 pour une action donnée.
 * Renvoie une chaîne vide si la clé n'est pas configurée
 * (le backend décidera alors d'accepter ou non — utile en dev).
 */
export async function getRecaptchaToken(action: string): Promise<string> {
  if (!SITE_KEY) {
    if (import.meta.env.DEV) {
      console.warn("[recaptcha] VITE_RECAPTCHA_SITE_KEY manquante — token vide");
    }
    return "";
  }
  await loadScript();
  return new Promise<string>((resolve, reject) => {
    if (!window.grecaptcha) return reject(new Error("grecaptcha unavailable"));
    window.grecaptcha.ready(() => {
      window
        .grecaptcha!.execute(SITE_KEY, { action })
        .then(resolve)
        .catch(reject);
    });
  });
}

export const RECAPTCHA_ENABLED = Boolean(SITE_KEY);
