import defaultTheme from 'tailwindcss/defaultTheme';
import colors from 'tailwindcss/colors';
import forms from '@tailwindcss/forms';
import plugin from 'tailwindcss/plugin';

/**
 * Paleta de la aplicación. Los colores de la institución se configuran en
 * Sistema → Configuración y llegan como variables CSS (--c-primary-600…),
 * generadas en InstitutionSetting::paletteCss():
 * - "indigo" (las clases que ya usan las pantallas) es el color principal.
 * - "accent" es el color de acento (degradados y detalles).
 * Los grises son zinc.
 */
const SHADES = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];
const brandColor = (name) => Object.fromEntries(SHADES.map((shade) => [shade, `rgb(var(--c-${name}-${shade}) / <alpha-value>)`]));
const brandVar = (name, shade, alpha = 1) => `rgb(var(--c-${name}-${shade}) / ${alpha})`;
const neutral = colors.zinc;

/**
 * Modo oscuro (`class` en <html>). En vez de agregar `dark:` en cada
 * pantalla, este plugin genera la versión oscura de los colores que usan
 * las pantallas: fondos, textos, bordes y los avisos de color.
 */
const darkTheme = plugin(({ addBase }) => {
    const escape = (className) => className.replace(/\//g, '\\/').replace(/:/g, '\\:').replace(/\[/g, '\\[').replace(/\]/g, '\\]');
    const rules = {};
    const add = (classNames, declarations, suffix = '') => {
        const selector = classNames.map((className) => `.dark .${escape(className)}${suffix}`).join(',\n');
        rules[selector] = { ...(rules[selector] ?? {}), ...declarations };
    };
    const rgba = (hex, alpha) => {
        const value = parseInt(hex.slice(1), 16);
        return `rgb(${(value >> 16) & 255} ${(value >> 8) & 255} ${value & 255} / ${alpha})`;
    };
    const grays = ['gray', 'slate'];
    const both = (shades, prefix, opacity = '') => grays.flatMap((gray) => shades.map((shade) => `${prefix}-${gray}-${shade}${opacity}`));

    // Superficies
    add(['bg-white'], { backgroundColor: neutral[900] });
    add(['bg-white/80', 'bg-white/90', 'bg-white/60'], { backgroundColor: rgba(neutral[900], 0.8) });
    add([...both([50], 'bg'), ...both([50], 'bg', '/80'), ...both([50], 'bg', '/60'), ...both([50], 'bg', '/40')], { backgroundColor: 'rgb(255 255 255 / 0.03)' });
    add(both([100], 'bg'), { backgroundColor: 'rgb(255 255 255 / 0.06)' });
    add(both([200], 'bg'), { backgroundColor: 'rgb(255 255 255 / 0.1)' });
    add(['bg-slate-900/40', 'bg-slate-900/50'], { backgroundColor: 'rgb(0 0 0 / 0.6)' });

    // Textos
    add([...both([900, 800], 'text')], { color: neutral[100] });
    add(both([700], 'text'), { color: neutral[300] });
    add(both([600, 500], 'text'), { color: neutral[400] });
    add(both([400], 'text'), { color: neutral[500] });

    // Bordes y separadores
    const borderShades = [100, 200];
    add([...both(borderShades, 'border'), ...both([200], 'border', '/80'), ...both([200], 'border', '/70'), ...both([200], 'border', '/60')], { borderColor: neutral[800] });
    add(both([300], 'border'), { borderColor: neutral[700] });
    grays.forEach((gray) => [100, 200].forEach((shade) => add([`divide-${gray}-${shade}`], { borderColor: neutral[800] }, ' > :not([hidden]) ~ :not([hidden])')));

    // Estados al pasar el mouse
    add([...both([50, 100], 'hover:bg'), ...both([50], 'hover:bg', '/70')], { backgroundColor: 'rgb(255 255 255 / 0.06)' }, ':hover');
    add(both([200], 'hover:bg'), { backgroundColor: 'rgb(255 255 255 / 0.1)' }, ':hover');
    add([...both([900, 800, 700], 'hover:text')], { color: neutral[100] }, ':hover');
    add(both([400], 'hover:border'), { borderColor: neutral[600] }, ':hover');

    // Colores de avisos y etiquetas
    ['red', 'rose', 'amber', 'yellow', 'orange', 'emerald', 'green', 'teal', 'sky', 'blue', 'indigo', 'accent', 'violet', 'purple', 'pink', 'fuchsia'].forEach((name) => {
        // Los colores de la institución vienen de variables CSS.
        const brand = { indigo: 'primary', accent: 'accent' }[name];
        const tone = (shade, alpha) => (brand ? brandVar(brand, shade, alpha) : rgba(colors[name][shade], alpha));

        add([`bg-${name}-50`], { backgroundColor: tone(500, 0.1) });
        add([`bg-${name}-50/40`], { backgroundColor: tone(500, 0.08) });
        add([`bg-${name}-100`], { backgroundColor: tone(500, 0.18) });
        add([`bg-${name}-200`], { backgroundColor: tone(500, 0.25) });
        add([`hover:bg-${name}-50`, `hover:bg-${name}-100`, `hover:bg-${name}-200`], { backgroundColor: tone(500, 0.22) }, ':hover');
        add([`text-${name}-600`], { color: tone(400, 1) });
        add([`text-${name}-700`, `text-${name}-800`, `text-${name}-900`], { color: tone(300, 1) });
        add([`hover:text-${name}-700`, `hover:text-${name}-800`, `hover:text-${name}-900`], { color: tone(200, 1) }, ':hover');
        add([`border-${name}-100`, `border-${name}-200`], { borderColor: tone(500, 0.3) });
        add([`divide-${name}-100`], { borderColor: tone(500, 0.2) }, ' > :not([hidden]) ~ :not([hidden])');
    });

    // Elementos con el color pastel de un nivel: el texto se mantiene oscuro.
    rules['.dark .on-level-color, .dark .on-level-color *'] = { color: `${neutral[900]} !important` };

    // Formularios
    rules['.dark input:not([type=checkbox]):not([type=radio]):not([type=color]):not([type=file]), .dark select, .dark textarea'] = {
        backgroundColor: neutral[900],
        borderColor: neutral[700],
        color: neutral[100],
        colorScheme: 'dark',
    };
    rules['.dark input[type=checkbox], .dark input[type=radio]'] = { backgroundColor: neutral[800], borderColor: neutral[600] };
    rules['.dark input:checked'] = { backgroundColor: 'currentColor' };
    rules['.dark input:focus, .dark select:focus, .dark textarea:focus'] = { borderColor: brandVar('primary', 500) };
    rules['.dark input::placeholder, .dark textarea::placeholder'] = { color: neutral[500] };
    rules['.dark input:disabled, .dark select:disabled, .dark textarea:disabled'] = { backgroundColor: neutral[800] };

    rules['.dark body'] = { backgroundColor: neutral[950], color: neutral[100], colorScheme: 'dark' };
    rules['.dark .shadow-sm, .dark .shadow, .dark .shadow-lg, .dark .shadow-xl'] = { '--tw-shadow-color': 'rgb(0 0 0 / 0.4)' };

    addBase(rules);
});

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                indigo: brandColor('primary'),
                accent: brandColor('accent'),
                gray: neutral,
                slate: neutral,
            },
        },
    },

    plugins: [forms, darkTheme],
};
