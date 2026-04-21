import { useSyncExternalStore, useCallback } from "react";

type Theme = "light" | "dark";

const STORAGE_KEY = "ipec-theme";

/**
 * The theme lives in a single source of truth outside React: the `.light` class
 * on <html>. The no-flash script in __root.tsx sets this class from localStorage
 * before React hydrates, which prevents the initial flash.
 *
 * We use useSyncExternalStore so that:
 *   1. SSR always returns the same snapshot ("dark") — no hydration mismatch
 *      warnings (the <html> already has suppressHydrationWarning).
 *   2. On the client, the snapshot is read directly from the DOM — so React's
 *      state can never drift from the actual applied class.
 *   3. Any external mutation of the class (HMR reload, devtools, other tabs via
 *      storage event) re-renders consumers automatically.
 */

function getSnapshot(): Theme {
  return document.documentElement.classList.contains("light") ? "light" : "dark";
}

function getServerSnapshot(): Theme {
  return "dark";
}

function subscribe(onChange: () => void): () => void {
  // React to writes from other tabs/windows
  const onStorage = (e: StorageEvent) => {
    if (e.key === STORAGE_KEY) onChange();
  };
  // React to direct DOM mutations (e.g. HMR, devtools, our own setTheme)
  const observer = new MutationObserver(onChange);
  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ["class"],
  });
  window.addEventListener("storage", onStorage);
  return () => {
    observer.disconnect();
    window.removeEventListener("storage", onStorage);
  };
}

function applyTheme(next: Theme) {
  const root = document.documentElement;
  if (next === "light") root.classList.add("light");
  else root.classList.remove("light");
  try {
    window.localStorage.setItem(STORAGE_KEY, next);
  } catch {
    // ignore
  }
}

export function useTheme() {
  const theme = useSyncExternalStore(subscribe, getSnapshot, getServerSnapshot);

  const setTheme = useCallback((next: Theme) => {
    applyTheme(next);
  }, []);

  const toggle = useCallback(() => {
    applyTheme(getSnapshot() === "dark" ? "light" : "dark");
  }, []);

  return { theme, setTheme, toggle };
}
