import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';

type TrapMode = 'guest' | 'auth';

type SessionTrapOptions = {
    mode: TrapMode;
    intervalMs?: number;
    redirectIfAuthenticated?: string;
    redirectIfGuest?: string;
};

const STORAGE_KEY = 'ehris:auth:signal';

async function fetchAuthStatus(): Promise<boolean | null> {
    try {
        const res = await fetch('/auth/status', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
            },
        });

        if (!res.ok) return null;
        const data = (await res.json()) as { authenticated?: boolean };
        return typeof data.authenticated === 'boolean' ? data.authenticated : null;
    } catch {
        return null;
    }
}

export function signalAuthChange() {
    try {
        localStorage.setItem(STORAGE_KEY, String(Date.now()));
    } catch {
        // ignore (private mode / storage disabled)
    }
}

export function useSessionTrap(options: SessionTrapOptions) {
    const intervalMs = options.intervalMs ?? 3000;

    let timer: number | null = null;
    let lastDecision: 'redirected' | 'none' = 'none';

    const checkAndRedirect = async () => {
        const authenticated = await fetchAuthStatus();
        if (authenticated === null) return;

        if (options.mode === 'guest' && authenticated) {
            if (lastDecision !== 'redirected') {
                lastDecision = 'redirected';
                router.visit(options.redirectIfAuthenticated ?? '/dashboard', {
                    replace: true,
                });
            }
            return;
        }

        if (options.mode === 'auth' && !authenticated) {
            if (lastDecision !== 'redirected') {
                lastDecision = 'redirected';
                router.visit(options.redirectIfGuest ?? '/login', {
                    replace: true,
                });
            }
            return;
        }

        lastDecision = 'none';
    };

    const onStorage = (e: StorageEvent) => {
        if (e.key === STORAGE_KEY) {
            void checkAndRedirect();
        }
    };

    const onVisibility = () => {
        if (document.visibilityState === 'visible') {
            void checkAndRedirect();
        }
    };

    onMounted(() => {
        // Run once ASAP, then keep polling.
        void checkAndRedirect();
        timer = window.setInterval(() => void checkAndRedirect(), intervalMs);

        window.addEventListener('storage', onStorage);
        document.addEventListener('visibilitychange', onVisibility);
    });

    onBeforeUnmount(() => {
        if (timer) window.clearInterval(timer);
        window.removeEventListener('storage', onStorage);
        document.removeEventListener('visibilitychange', onVisibility);
    });
}

