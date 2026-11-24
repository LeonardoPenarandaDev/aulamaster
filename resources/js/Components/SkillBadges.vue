<script setup>
/**
 * Habilidades con las que se trabaja un tema (Listening, Speaking, Reading,
 * Writing). Acepta las claves ("listening") o los nombres ("Listening").
 */
defineProps({
    skills: { type: Array, default: () => [] },
});

const colors = {
    listening: 'bg-sky-100 text-sky-800',
    speaking: 'bg-violet-100 text-violet-800',
    reading: 'bg-emerald-100 text-emerald-800',
    writing: 'bg-amber-100 text-amber-800',
};

function label(skill) {
    return skill.charAt(0).toUpperCase() + skill.slice(1);
}
</script>

<template>
    <span v-if="skills.length" class="inline-flex flex-wrap gap-1 align-middle">
        <span
            v-for="skill in skills"
            :key="skill"
            class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
            :class="colors[skill.toLowerCase()] ?? 'bg-slate-100 text-slate-700'"
        >
            {{ label(skill) }}
        </span>
    </span>
</template>
