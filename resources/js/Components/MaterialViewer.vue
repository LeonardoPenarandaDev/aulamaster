<script setup>
import { ref } from 'vue';

/**
 * Muestra un material según su tipo (parte 12 del plan de mejoras): el video
 * se reproduce en la página con youtube-nocookie.com, el PDF en un visor y
 * la imagen se amplía al hacer clic.
 */
defineProps({
    material: { type: Object, required: true },
});

const showPdf = ref(false);
const zoomed = ref(false);

function size(bytes) {
    if (!bytes) {
        return '';
    }
    return bytes >= 1024 * 1024 ? `${(bytes / 1024 / 1024).toFixed(1)} MB` : `${Math.round(bytes / 1024)} KB`;
}
</script>

<template>
    <div>
        <div v-if="material.type === 'youtube' && material.youtube_id" class="aspect-video w-full overflow-hidden rounded-xl bg-black">
            <iframe
                :src="`https://www.youtube-nocookie.com/embed/${material.youtube_id}`"
                :title="material.title"
                class="h-full w-full"
                loading="lazy"
                allow="accelerometer; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
            />
        </div>

        <template v-else-if="material.type === 'pdf'">
            <div class="flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 px-4 py-3">
                <span class="rounded-md bg-red-50 px-2 py-1 text-xs font-bold text-red-700">PDF</span>
                <span class="min-w-0 flex-1 truncate text-sm text-gray-700">{{ material.file_name }} <span class="text-gray-400">{{ size(material.file_size) }}</span></span>
                <button type="button" class="text-sm font-medium text-indigo-600 hover:text-indigo-800" @click="showPdf = !showPdf">
                    {{ showPdf ? 'Ocultar' : 'Ver aquí' }}
                </button>
                <a :href="material.file_url" target="_blank" rel="noopener" class="text-sm text-gray-600 hover:text-gray-900">Abrir</a>
            </div>
            <iframe v-if="showPdf" :src="material.file_url" :title="material.title" class="mt-2 h-[70vh] w-full rounded-xl border border-gray-200" />
        </template>

        <template v-else-if="material.type === 'imagen'">
            <button type="button" class="block overflow-hidden rounded-xl border border-gray-200" @click="zoomed = true">
                <img :src="material.file_url" :alt="material.title" loading="lazy" class="max-h-64 w-auto object-contain" />
            </button>
            <div v-if="zoomed" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" @click="zoomed = false">
                <img :src="material.file_url" :alt="material.title" class="max-h-full max-w-full rounded-lg" />
            </div>
        </template>

        <a
            v-else
            :href="material.url"
            target="_blank"
            rel="noopener noreferrer"
            class="break-all text-sm font-medium text-indigo-600 hover:text-indigo-800"
        >
            {{ material.url }}
        </a>
    </div>
</template>
