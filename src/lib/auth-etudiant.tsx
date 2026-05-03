/**
 * Contexte d'auth pour l'espace étudiant.
 * Cookie httpOnly géré par PHP. /me.php → 200 user / 401 si non connecté.
 */
import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from "react";
import { etuApi, ApiError } from "./api";

export type EtudiantCategorie = "candidat" | "preadmis" | "etudiant";

export interface EtudiantUser {
  id: number;
  email: string;
  prenom: string;
  nom: string;
  civilite?: string;
  numero_etudiant?: string;
  categorie?: EtudiantCategorie;
}

interface LoginPayload {
  numero_etudiant: string;
  password: string;
}

interface EtudiantAuthContextValue {
  user: EtudiantUser | null;
  loading: boolean;
  login: (payload: LoginPayload) => Promise<void>;
  logout: () => Promise<void>;
  refresh: () => Promise<void>;
}

const EtudiantAuthContext = createContext<EtudiantAuthContextValue | null>(null);

export function EtudiantAuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<EtudiantUser | null>(null);
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(async () => {
    try {
      const data = await etuApi.get<{ user: EtudiantUser }>("/me.php");
      setUser(data.user);
    } catch (err) {
      if (err instanceof ApiError && err.status === 401) {
        setUser(null);
      } else {
        console.error("[etu auth] refresh failed", err);
      }
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    refresh();
  }, [refresh]);

  const login = useCallback(async (payload: LoginPayload) => {
    const data = await etuApi.post<{ user: EtudiantUser }>("/login.php", payload);
    setUser(data.user);
  }, []);

  const logout = useCallback(async () => {
    try {
      await etuApi.post("/logout.php");
    } finally {
      setUser(null);
    }
  }, []);

  return (
    <EtudiantAuthContext.Provider value={{ user, loading, login, logout, refresh }}>
      {children}
    </EtudiantAuthContext.Provider>
  );
}

export function useEtudiantAuth(): EtudiantAuthContextValue {
  const ctx = useContext(EtudiantAuthContext);
  if (!ctx) throw new Error("useEtudiantAuth must be used inside EtudiantAuthProvider");
  return ctx;
}
