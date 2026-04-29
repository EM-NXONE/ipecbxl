/**
 * Client HTTP pour les API PHP des espaces admin et étudiant.
 *
 * - En production : `admin.ipec.school` et `lms.ipec.school` servent à la fois
 *   le React build et l'API PHP sous /api/* → même origine, cookies SameSite=Lax.
 * - En développement Lovable : VITE_ADMIN_API_BASE / VITE_ETU_API_BASE permettent
 *   de pointer vers une API PHP locale ou distante (CORS + cookies SameSite=None).
 */

type ApiBase = "admin" | "etudiant";

function baseUrl(kind: ApiBase): string {
  if (kind === "admin") {
    return import.meta.env.VITE_ADMIN_API_BASE || "/api";
  }
  return import.meta.env.VITE_ETU_API_BASE || "/api";
}

export class ApiError extends Error {
  status: number;
  payload?: unknown;
  constructor(message: string, status: number, payload?: unknown) {
    super(message);
    this.status = status;
    this.payload = payload;
  }
}

interface ApiOptions {
  method?: "GET" | "POST" | "PUT" | "DELETE";
  body?: unknown;
  /** Forcer un base path différent (ex: pour télécharger un PDF en absolu). */
  raw?: boolean;
}

async function apiCall<T = unknown>(
  kind: ApiBase,
  path: string,
  opts: ApiOptions = {},
): Promise<T> {
  const url = opts.raw ? path : `${baseUrl(kind)}${path}`;
  const init: RequestInit = {
    method: opts.method || "GET",
    credentials: "include",
    headers: { Accept: "application/json" },
  };
  if (opts.body !== undefined) {
    init.headers = { ...init.headers, "Content-Type": "application/json" };
    init.body = JSON.stringify(opts.body);
  }
  const res = await fetch(url, init);
  const text = await res.text();
  let data: unknown = null;
  if (text) {
    try {
      data = JSON.parse(text);
    } catch {
      data = text;
    }
  }
  if (!res.ok) {
    const msg =
      (data && typeof data === "object" && "error" in data && typeof (data as { error: unknown }).error === "string"
        ? (data as { error: string }).error
        : null) ||
      `Erreur ${res.status}`;
    throw new ApiError(msg, res.status, data);
  }
  return data as T;
}

export const adminApi = {
  get: <T = unknown>(path: string) => apiCall<T>("admin", path),
  post: <T = unknown>(path: string, body?: unknown) =>
    apiCall<T>("admin", path, { method: "POST", body }),
};

export const etuApi = {
  get: <T = unknown>(path: string) => apiCall<T>("etudiant", path),
  post: <T = unknown>(path: string, body?: unknown) =>
    apiCall<T>("etudiant", path, { method: "POST", body }),
};

/** URL absolue d'un endpoint (pour <a href> de téléchargement de PDF). */
export function etuUrl(path: string): string {
  return `${baseUrl("etudiant")}${path}`;
}
export function adminUrl(path: string): string {
  return `${baseUrl("admin")}${path}`;
}
