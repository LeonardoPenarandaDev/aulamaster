/**
 * Estilos del menú lateral del personal (Sistema → Apariencia). Los usan
 * AuthenticatedLayout y la vista previa de la pantalla de Apariencia.
 * "indigo" es el color principal de la institución.
 */
export const sidebarThemes = {
    color: {
        aside: 'border-transparent bg-gradient-to-b from-indigo-700 via-indigo-800 to-indigo-950 text-white',
        divider: 'border-white/10',
        name: 'text-white',
        groupLabel: 'text-white/50',
        item: 'text-white/75 hover:bg-white/10 hover:text-white',
        itemActive: 'bg-white/15 text-white shadow-sm shadow-black/10',
        icon: 'text-white/60 group-hover:text-white',
        iconActive: 'text-accent-300',
        profileHover: 'hover:bg-white/10',
        profileName: 'text-white',
        profileRole: 'text-white/60',
        closeButton: 'text-white/70 hover:bg-white/10',
    },
    oscuro: {
        aside: 'border-zinc-800 bg-zinc-950 text-zinc-100',
        divider: 'border-white/10',
        name: 'text-white',
        groupLabel: 'text-zinc-500',
        item: 'text-zinc-400 hover:bg-white/5 hover:text-white',
        itemActive: 'bg-white/10 text-white',
        icon: 'text-zinc-500 group-hover:text-zinc-200',
        iconActive: 'text-indigo-400',
        profileHover: 'hover:bg-white/5',
        profileName: 'text-white',
        profileRole: 'text-zinc-500',
        closeButton: 'text-zinc-400 hover:bg-white/10',
    },
    claro: {
        aside: 'border-slate-200 bg-white',
        divider: 'border-slate-100',
        name: 'text-slate-900',
        groupLabel: 'text-slate-400',
        item: 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
        itemActive: 'bg-indigo-50 text-indigo-700',
        icon: 'text-slate-400 group-hover:text-slate-600',
        iconActive: 'text-indigo-600',
        profileHover: 'hover:bg-slate-100',
        profileName: 'text-slate-900',
        profileRole: 'text-slate-500',
        closeButton: 'text-slate-500 hover:bg-slate-100',
    },
};

export function sidebarTheme(style) {
    return sidebarThemes[style] ?? sidebarThemes.color;
}
