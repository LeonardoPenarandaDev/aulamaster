<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    classSession: Object,
    materials: Array,
});

const page = usePage();
const isTeacher = computed(() => page.props.auth?.roles?.includes('profesor'));

const form = useForm({
    title: '',
    url: '',
    description: '',
});

function submit() {
    form.post(route('class-materials.store', props.classSession.id), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
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

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Material de la clase
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <div v-if="page.props.flash?.success" class="rounded-md bg-green-50 p-4 text-sm text-green-700">
                    {{ page.props.flash.success }}
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-900">{{ classSession.level }}</p>
                    <p class="text-sm text-gray-600 first-letter:uppercase">
                        {{ dayLabel(classSession.date) }}, {{ classSession.start_time }} a {{ classSession.end_time }} · {{ classSession.classroom }}
                    </p>
                    <p class="mt-2 text-sm text-gray-600">
                        Solo los estudiantes que quedaron <span class="font-medium text-gray-900">presentes</span> en la asistencia de esta clase
                        verán este material ({{ classSession.present_count }} actualmente).
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-gray-900">Agregar enlace</h3>
                    <form class="mt-4 space-y-4" @submit.prevent="submit">
                        <div>
                            <InputLabel for="title" value="Título" />
                            <TextInput id="title" v-model="form.title" class="mt-1 block w-full" placeholder="Ej.: Presentación del tema, video de repaso" required />
                            <InputError class="mt-2" :message="form.errors.title" />
                        </div>

                        <div>
                            <InputLabel for="url" value="Enlace (URL)" />
                            <TextInput id="url" type="url" v-model="form.url" class="mt-1 block w-full" placeholder="https://..." required />
                            <InputError class="mt-2" :message="form.errors.url" />
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

                        <PrimaryButton :disabled="form.processing">Agregar material</PrimaryButton>
                    </form>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <h3 class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-900">Material compartido</h3>
                    <ul class="divide-y divide-gray-100">
                        <li v-for="material in materials" :key="material.id" class="flex items-start justify-between gap-4 px-6 py-4">
                            <div class="min-w-0">
                                <a :href="material.url" target="_blank" rel="noopener noreferrer" class="font-medium text-indigo-600 hover:text-indigo-800">
                                    {{ material.title }}
                                </a>
                                <p class="truncate text-xs text-gray-500">{{ material.url }}</p>
                                <p v-if="material.description" class="mt-1 text-sm text-gray-600">{{ material.description }}</p>
                            </div>
                            <button type="button" class="shrink-0 text-sm text-red-600 hover:text-red-900" @click="destroy(material)">
                                Eliminar
                            </button>
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
    </AuthenticatedLayout>
</template>
