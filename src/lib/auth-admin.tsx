/**
 * Contexte d'auth pour l'espace administrateur.
 *
 * Source de vérité : cookie httpOnly géré par PHP. On ne stocke RIEN
 * dans localStorage côté React. `useAdminAuth()` interroge `/me` au montage
 * et expose `user`, `loading`, `login()`, `logout()`.
 */
import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from "react";
import { adminApi, ApiError } from "./api";

export interface AdminUser {
  username: string;
}

interface AdminAuthContextValue {
  user: AdminUser | null;
  loading: boolean;
  login: (username: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  refresh: () => Promise<void>;
}

const AdminAuthContext = createContext<AdminAuthContextValue | null>(null);

export function AdminAuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<AdminUser | null>(null);
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(async () => {
    try {
      const data = await adminApi.get<{ user: AdminUser }>("/me.php");
      setUser(data.user);
    } catch (err) {
      if (err instanceof ApiError && err.status === 401) {
        setUser(null);
      } else {
        // Erreur réseau : on conserve l'état précédent
        console.error("[admin auth] refresh failed", err);
      }
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    refresh();
  }, [refresh]);

  const login = useCallback(async (username: string, password: string) => {
    const data = await adminApi.post<{ user: AdminUser }>("/login.php", { username, password });
    setUser(data.user);
  }, []);

  const logout = useCallback(async () => {
    try {
      await adminApi.post("/logout.php");
    } finally {
      setUser(null);
    }
  }, []);

  return (
    <AdminAuthContext.Provider value={{ user, loading, login, logout, refresh }}>
      {children}
    </AdminAuthContext.Provider>
  );
}

export function useAdminAuth(): AdminAuthContextValue {
  const ctx = useContext(AdminAuthContext);
  if (!ctx) throw new Error("useAdminAuth must be used inside AdminAuthProvider");
  return ctx;
}
