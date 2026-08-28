<script setup>
import { computed } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    icon: { type: String, required: true },
    sublabel: { type: String, default: null },
    color: { type: String, default: 'indigo' },
});

const colors = {
    indigo: 'bg-indigo-50 text-indigo-600',
    violet: 'bg-violet-50 text-violet-600',
    sky: 'bg-sky-50 text-sky-600',
    emerald: 'bg-emerald-50 text-emerald-600',
    amber: 'bg-amber-50 text-amber-600',
    rose: 'bg-rose-50 text-rose-600',
};

const colorClasses = computed(() => colors[props.color] ?? colors.indigo);
</script>

<template>
    <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm transition hover:shadow-md">
        <div class="flex items-center gap-4">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg" :class="colorClasses">
                <Icon :name="icon" class="h-6 w-6" />
            </div>
            <div class="min-w-0">
                <p class="truncate text-xs font-medium text-gray-500">{{ label }}</p>
                <p class="mt-0.5 text-2xl font-bold tabular-nums text-gray-900">{{ value }}</p>
                <p v-if="sublabel" class="mt-0.5 truncate text-xs text-gray-400">{{ sublabel }}</p>
            </div>
        </div>
    </div>
</template>
