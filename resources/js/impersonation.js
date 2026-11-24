import { router } from '@inertiajs/vue3';

/**
 * Vista del estudiante en una sola pestaña (ImpersonateStudentInTab en el
 * servidor). La llave llega en ?_as= al abrir la pestaña y se guarda en
 * sessionStorage, que es propio de cada pestaña: así las demás pestañas
 * siguen siendo del administrador.
 */
const STORAGE_KEY = 'impersonation-token';
const QUERY_KEY = '_as';

function readToken() {
    try {
        const fromUrl = new URLSearchParams(window.location.search).get(QUERY_KEY);
        if (fromUrl) {
            sessionStorage.setItem(STORAGE_KEY, fromUrl);
        }

        return sessionStorage.getItem(STORAGE_KEY);
    } catch {
        return new URLSearchParams(window.location.search).get(QUERY_KEY);
    }
}

function withToken(href, token) {
    const url = new URL(href, window.location.origin);
    if (url.origin === window.location.origin && !url.searchParams.has(QUERY_KEY)) {
        url.searchParams.set(QUERY_KEY, token);
    }

    return url;
}

export function setupImpersonation() {
    const token = readToken();
    if (!token) {
        return;
    }

    // Peticiones de Inertia (navegación, formularios, recargas parciales).
    router.on('before', (event) => {
        event.detail.visit.headers = { ...event.detail.visit.headers, 'X-Impersonation': token };
    });

    // Si se recarga la página (F5), la dirección conserva la llave.
    router.on('navigate', () => {
        const url = withToken(window.location.href, token);
        if (url.href !== window.location.href) {
            window.history.replaceState(window.history.state, '', url.href);
        }
    });

    // Enlaces normales (PDF, certificados, pago en línea) y pestañas nuevas.
    document.addEventListener('click', (event) => {
        const link = event.target.closest?.('a[href]');
        if (!link || link.getAttribute('href').startsWith('#') || link.href.startsWith('mailto:') || link.href.startsWith('tel:')) {
            return;
        }

        const url = withToken(link.href, token);
        if (url.origin === window.location.origin) {
            link.href = url.href;
        }
    }, true);
}

/**
 * Cierra la vista del estudiante: borra la llave y cierra la pestaña (o, si
 * el navegador no deja cerrarla, vuelve a la vista del administrador).
 */
export function closeImpersonation() {
    try {
        sessionStorage.removeItem(STORAGE_KEY);
    } catch {
        // Sin sessionStorage la llave solo vive en la dirección.
    }

    window.close();
    window.location.href = route('dashboard');
}
