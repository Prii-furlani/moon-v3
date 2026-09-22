import { useEffect, useRef } from 'react';

type UseIdleTimerProps = {
  timeout?: number;
  onIdle: () => void;
};

export function useIdleTimer({ timeout = 3600000, onIdle }: UseIdleTimerProps) {
  const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    const handleActivity = () => {
      if (timerRef.current) clearTimeout(timerRef.current);
      timerRef.current = setTimeout(onIdle, timeout);
    };

    // Initial set
    handleActivity();

    // Listeners
    const events = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll'];
    events.forEach((event) => {
      window.addEventListener(event, handleActivity, { passive: true });
    });

    return () => {
      if (timerRef.current) clearTimeout(timerRef.current);
      events.forEach((event) => {
        window.removeEventListener(event, handleActivity);
      });
    };
  }, [timeout, onIdle]);
}
