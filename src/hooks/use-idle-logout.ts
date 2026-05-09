/**
 * Déclenche `onIdle` après `timeoutMs` sans activité utilisateur réelle
 * (mousemove, mousedown, keydown, touchstart, scroll, wheel).
 *
 * Le timer ne se réarme pas sur les rafraîchissements automatiques
 * (polling /me.php) — uniquement sur de vraies interactions.
 */
import { useEffect, useRef } from "react";

const EVENTS = ["mousemove", "mousedown", "keydown", "touchstart", "scroll", "wheel"] as const;

export function useIdleLogout(enabled: boolean, timeoutMs: number, onIdle: () => void) {
  const onIdleRef = useRef(onIdle);
  onIdleRef.current = onIdle;

  useEffect(() => {
    if (!enabled) return;
    let timer: number | undefined;
    const reset = () => {
      if (timer) window.clearTimeout(timer);
      timer = window.setTimeout(() => onIdleRef.current(), timeoutMs);
    };
    reset();
    EVENTS.forEach((ev) => window.addEventListener(ev, reset, { passive: true }));
    return () => {
      if (timer) window.clearTimeout(timer);
      EVENTS.forEach((ev) => window.removeEventListener(ev, reset));
    };
  }, [enabled, timeoutMs]);
}
