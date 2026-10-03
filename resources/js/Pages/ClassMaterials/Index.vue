<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import MaterialViewer from '@/Components/MaterialViewer.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    classSession: Object,
    materials: Array,
    limits: Object,
});

const page = usePage();
const isTeacher = computed(() => page.props.auth?.roles?.includes('profesor'));

const types = [
    { value: 'youtube', label: 'YouTube', icon: '▶' },
    { value: 'enlace', label: 'Enlace', icon: '🔗' },
    { value: 'pdf', label: 'PDF', icon: '📄' },
    { value: 'imagen', label: 'Imagen', icon: '🖼' },
];

const form = useForm({
    type: 'youtube',
    title: '',
    url: '',
    file: null,
    description: '',
});

const isFileType = computed(() => ['pdf', 'imagen'].includes(form.type));
const maxKb = computed(() => (form.type === 'pdf' ? props.limits.pdf_kb : props.limits.image_kb));
const accept = computed(() => (form.type === 'pdf' ? 'application/pdf,.pdf' : 'image/jpeg,image/png,image/webp'));
const fileError = ref('');
const dragging = ref(false);

function youtubeId(url) {
    const match = (url ?? '').match(/(?:youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/);
    return match ? match[1] : null;
}

const previewId = computed(() => (form.type === 'youtube' ? youtubeId(form.url) : null));

function chooseType(type) {
    form.type = type;
    form.file = null;
    form.url = '';
    fileError.value = '';
    form.clearErrors();
}

function setFile(file) {
    fileError.value = '';

    if (!file) {
        form.file = null;
        return;
    }

    if (file.size > maxKb.value * 1024) {
        fileError.value = `El archivo pesa ${(file.size / 1024 / 1024).toFixed(1)} MB y el máximo es ${(maxKb.value / 1024).toFixed(1)} MB. Comprímelo o compártelo como enlace de Google Drive.`;
        form.file = null;
        return;
    }

    form.file = file;
    if (!form.title) {
        form.title = file.name.replace(/\.[^.]+$/, '');
    }
}

function onDrop(event) {
    dragging.value = false;
    setFile(event.dataTransfer.files[0] ?? null);
}

function submit() {
    form.post(route('class-materials.store', props.classSession.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            const type = form.type;
            form.reset();
            form.type = type;
        },
    });
}

function destroy(material) {
    if (confirm(`¿Eliminar "${material.title}"? Los estudiantes dejarán de verlo.`)) {
        router.delete(route('class-materials.destroy', material.id), { preserveScroll: true });
    }
}

function dayLabel(date) {
    return new Intl.DateTimeFormat('es-CO', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date(`${date}T00:00:00`));
}
</script>

<template>
    <Head title="Material de la clase" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Material de la clase
            </h2>
        </template>

        <div class="py-8 sm:py-12">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="page.props.flash?.success" class="rounded-xl bg-green-50 p-4 text-sm text-green-700">
                    {{ page.props.flash.success }}
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6">
                    <p class="text-sm font-medium text-gray-900">{{ classSession.level }}</p>
                    <p class="text-sm text-gray-600 first-letter:uppercase">
                        {{ dayLabel(classSession.date) }}, {{ classSession.start_time }} a {{ classSession.end_time }} · {{ classSession.classroom }}
                    </p>
                    <p class="mt-2 text-sm text-gray-600">
                        Solo los estudiantes que quedaron <span class="font-medium text-gray-900">presentes</span> en la asistencia de esta clase
                        verán este material ({{ classSession.present_count }} actualmente).
                    </p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6">
                    <h3 class="text-sm font-semibold text-gray-900">Agregar material</h3>

                    <div class="mt-4 grid grid-cols-4 gap-2">
                        <button
                            v-for="type in types"
                            :key="type.value"
                            type="button"
                            class="flex flex-col items-center gap-1 rounded-xl border px-2 py-3 text-sm"
                            :class="form.type === type.value ? 'border-indigo-600 bg-indigo-50 font-semibold text-indigo-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                            @click="chooseType(type.value)"
                        >
                            <span class="text-lg" aria-hidden="true">{{ type.icon }}</span>
                            {{ type.label }}
                        </button>
                    </div>

                    <form class="mt-5 space-y-4" @submit.prevent="submit">
                        <div v-if="!isFileType">
                            <InputLabel for="url" :value="form.type === 'youtube' ? 'Enlace del video de YouTube' : 'Enlace (URL)'" />
                            <TextInput id="url" type="url" v-model="form.url" class="mt-1 block w-full" placeholder="https://..." required />
                            <InputError class="mt-2" :message="form.errors.url" />
                            <div v-if="previewId" class="mt-3 aspect-video w-full overflow-hidden rounded-xl bg-black">
                                <iframe :src="`https://www.youtube-nocookie.com/embed/${previewId}`" title="Vista previa" class="h-full w-full" allowfullscreen />
                            </div>
                        </div>

                        <div v-else>
                            <label
                                class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-4 py-8 text-center text-sm"
                                :class="dragging ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 hover:bg-gray-50'"
                                @dragover.prevent="dragging = true"
                                @dragleave.prevent="dragging = false"
                                @drop.prevent="onDrop"
                            >
                                <span v-if="form.file" class="font-medium text-gray-900">{{ form.file.name }}</span>
                                <span v-else class="text-gray-600">Arrastra el archivo aquí o <span class="font-medium text-indigo-600">elígelo</span></span>
                                <span class="mt-1 text-xs text-gray-500">
                                    {{ form.type === 'pdf' ? 'PDF' : 'JPG, PNG o WEBP' }} · máximo {{ (maxKb / 1024).toFixed(1).replace('.0', '') }} MB
                                </span>
                                <input type="file" :accept="accept" class="hidden" @change="setFile($event.target.files[0] ?? null)" />
                            </label>
                            <p v-if="fileError" class="mt-2 text-sm text-red-600">{{ fileError }}</p>
                            <InputError class="mt-2" :message="form.errors.file" />
                            <div v-if="form.progress" class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full bg-indigo-600 transition-all" :style="{ width: `${form.progress.percentage}%` }" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="title" value="Título" />
                            <TextInput id="title" v-model="form.title" class="mt-1 block w-full" placeholder="Ej.: Presentación del tema, video de repaso" required />
                            <InputError class="mt-2" :message="form.errors.title" />
                        </div>

                        <div>
                            <InputLabel for="description" value="Descripción (opcional)" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>

                        <PrimaryButton :disabled="form.processing || (isFileType && !form.file)">Agregar material</PrimaryButton>
                    </form>
                </div>

                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                    <h3 class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-900">Material compartido</h3>
                    <ul class="divide-y divide-gray-100">
                        <li v-for="material in materials" :key="material.id" class="space-y-3 px-6 py-4">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900">{{ material.title }}</p>
                                    <p v-if="material.description" class="mt-1 text-sm text-gray-600">{{ material.description }}</p>
                                </div>
                                <button type="button" class="shrink-0 text-sm text-red-600 hover:text-red-900" @click="destroy(material)">
                                    Eliminar
                                </button>
                            </div>
                            <MaterialViewer :material="material" />
                        </li>
                        <li v-if="materials.length === 0" class="px-6 py-4 text-center text-sm text-gray-500">
                            Todavía no has compartido material en esta clase.
                        </li>
                    </ul>
                </div>

                <Link v-if="isTeacher" :href="route('dashboard')" class="inline-block text-sm text-gray-600 hover:text-gray-900">
                    Volver al panel
                </Link>
                <Link v-else :href="route('class-sessions.index')" class="inline-block text-sm text-gray-600 hover:text-gray-900">
                    Volver al calendario
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
