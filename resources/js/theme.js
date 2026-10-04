import { computed, ref } from 'vue';

/**
 * Tema claro, oscuro o el del sistema. Se guarda en el navegador y se
 * aplica con la clase "dark" en <html> (app.blade.php lo aplica antes de
 * pintar la página para que no parpadee).
 */
const STORAGE_KEY = 'theme';
const media = typeof window !== 'undefined' ? window.matchMedia('(prefers-color-scheme: dark)') : null;

function stored() {
    try {
        return localStorage.getItem(STORAGE_KEY) ?? 'system';
    } catch {
        return 'system';
    }
}

const theme = ref(stored());
const systemDark = ref(media?.matches ?? false);
const isDark = computed(() => theme.value === 'dark' || (theme.value === 'system' && systemDark.value));

function apply() {
    document.documentElement.classList.toggle('dark', isDark.value);
}

media?.addEventListener('change', (event) => {
    systemDark.value = event.matches;
    apply();
});

export function setTheme(value) {
    theme.value = value;

    try {
        localStorage.setItem(STORAGE_KEY, value);
    } catch {
        // Sin almacenamiento local: el tema dura solo esta visita.
    }

    apply();
}

export function useTheme() {
    return { theme, isDark, setTheme };
}
