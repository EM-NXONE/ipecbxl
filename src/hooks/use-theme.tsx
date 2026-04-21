import { useEffect, useState } from "react";

type Theme = "light" | "dark";

const STORAGE_KEY = "ipec-theme";

/**
 * Read the current theme from the DOM (set early by the no-flash script in __root.tsx).
 * Falls back to "dark" during SSR.
 */
function readThemeFromDOM(): Theme {
  if (typeof document === "undefined") return "dark";
  return document.documentElement.classList.contains("light") ? "light" : "dark";
}

export function useTheme() {
  // Initialize lazily from the DOM so we never override what the no-flash
  // script already applied. During SSR this returns "dark" (the default).
  const [theme, setThemeState] = useState<Theme>(readThemeFromDOM);

  // Re-sync once on mount in case React hydrated with the SSR default ("dark")
  // but the DOM was already flipped to "light" by the no-flash script.
  useEffect(() => {
    const domTheme = readThemeFromDOM();
    if (domTheme !== theme) setThemeState(domTheme);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const setTheme = (next: Theme) => {
    setThemeState(next);
    if (typeof document !== "undefined") {
      const root = document.documentElement;
      if (next === "light") root.classList.add("light");
      else root.classList.remove("light");
      try {
        window.localStorage.setItem(STORAGE_KEY, next);
      } catch {
        // ignore
      }
    }
  };

  const toggle = () => setTheme(theme === "dark" ? "light" : "dark");

  return { theme, setTheme, toggle };
}
