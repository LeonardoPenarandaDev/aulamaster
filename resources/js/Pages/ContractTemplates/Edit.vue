<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';

const props = defineProps({
    template: Object,
    types: Object,
    variables: Object,
    sampleValues: Object,
    canEdit: Boolean,
});

const page = usePage();
const isNew = !props.template;
const bodyInput = ref(null);

const form = useForm({
    name: props.template?.name ?? '',
    type: props.template?.type ?? 'matricula',
    body: props.template?.body ?? '',
    acceptance_mode: props.template?.acceptance_mode ?? 'obligatorio',
    scope: props.template?.scope ?? 'matricula',
    requires_guardian: props.template?.requires_guardian ?? false,
});

const selectClasses = 'mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-50';

const variableGroups = computed(() => {
    const groups = { Estudiante: [], Acudiente: [], Firmante: [], Matrícula: [], Institución: [], Otros: [] };

    Object.entries(props.variables).forEach(([key, label]) => {
        const group = key.startsWith('alumno.') ? 'Estudiante'
            : key.startsWith('acudiente.') ? 'Acudiente'
            : key.startsWith('firmante.') ? 'Firmante'
            : key.startsWith('institucion.') ? 'Institución'
            : ['curso', 'nivel', 'precio_base', 'precio_final', 'intensidad_horaria', 'fecha_inicio', 'fecha_fin_estimada'].includes(key) ? 'Matrícula'
            : 'Otros';
        groups[group].push({ key, label });
    });

    return Object.entries(groups).filter(([, items]) => items.length);
});

async function insertVariable(key) {
    const textarea = bodyInput.value;
    const token = `{{${key}}}`;
    const start = textarea?.selectionStart ?? form.body.length;
    const end = textarea?.selectionEnd ?? form.body.length;

    form.body = form.body.slice(0, start) + token + form.body.slice(end);

    await nextTick();
    textarea?.focus();
    textarea?.setSelectionRange(start + token.length, start + token.length);
}

function escapeHtml(text) {
    return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

const previewHtml = computed(() => {
    const rendered = form.body.replace(/\{\{\s*([a-z_.]+)\s*\}\}/g, (match, key) => {
        if (!(key in props.sampleValues)) {
            return match;
        }
        return props.sampleValues[key] || `[${props.variables[key] ?? key}]`;
    });

    return rendered
        .trim()
        .split(/\n{2,}/)
        .filter((paragraph) => paragraph.trim())
        .map((paragraph) => `<p>${escapeHtml(paragraph.trim()).replace(/\n/g, '<br>')}</p>`)
        .join('');
});

const unknownVariables = computed(() => {
    const found = [...form.body.matchAll(/\{\{\s*([a-z_.]+)\s*\}\}/g)].map((match) => match[1]);
    return [...new Set(found.filter((key) => !(key in props.variables)))];
});

function submit() {
    if (isNew) {
        form.post(route('contract-templates.store'));
    } else {
        form.put(route('contract-templates.update', props.template.id), { preserveScroll: true });
    }
}

function publish() {
    const message = props.template.version > 1
        ? `¿Publicar la versión ${props.template.version}? La versión anterior se archivará y los contratos nuevos usarán esta.`
        : '¿Publicar esta plantilla? Desde ahora se asignará a las matrículas y ya no podrá editarse.';

    if (form.isDirty) {
        alert('Guarda los cambios antes de publicar.');
        return;
    }

    if (confirm(message)) {
        router.post(route('contract-templates.publish', props.template.id));
    }
}

function createVersion() {
    router.post(route('contract-templates.new-version', props.template.id));
}

function archive() {
    if (confirm('¿Archivar esta plantilla? Dejará de asignarse a matrículas nuevas; lo ya firmado no cambia.')) {
        router.post(route('contract-templates.archive', props.template.id));
    }
}

function destroy() {
    if (confirm('¿Eliminar este borrador?')) {
        router.delete(route('contract-templates.destroy', props.template.id));
    }
}
</script>

<template>
    <Head :title="isNew ? 'Nueva plantilla' : template.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ isNew ? 'Nueva plantilla de contrato' : template.name }}
                </h2>
                <span v-if="!isNew" class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">
                    v{{ template.version }} · {{ template.status }}
                </span>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="page.props.flash?.success" class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-700">
                    {{ page.props.flash.success }}
                </div>

                <div v-if="!canEdit" class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800">
                    <span>Esta versión está {{ template.status }} y no se puede editar. Para cambiarla, crea una versión nueva.</span>
                    <div class="flex gap-2">
                        <PrimaryButton type="button" @click="createVersion">Crear nueva versión</PrimaryButton>
                        <SecondaryButton v-if="template.status === 'publicado'" type="button" @click="archive">Archivar</SecondaryButton>
                    </div>
                </div>

                <form class="grid grid-cols-1 gap-6 xl:grid-cols-2" @submit.prevent="submit">
                    <div class="space-y-6 rounded-2xl border border-slate-200/80 bg-white p-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="name" value="Nombre" />
                                <TextInput id="name" v-model="form.name" class="mt-1 block w-full" :disabled="!canEdit" required />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div>
                                <InputLabel for="type" value="Tipo" />
                                <select id="type" v-model="form.type" :class="selectClasses" :disabled="!canEdit">
                                    <option v-for="(label, value) in types" :key="value" :value="value">{{ label }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.type" />
                            </div>

                            <div>
                                <InputLabel for="acceptance_mode" value="Aceptación" />
                                <select id="acceptance_mode" v-model="form.acceptance_mode" :class="selectClasses" :disabled="!canEdit">
                                    <option value="obligatorio">Obligatorio: debe firmarse para activar la matrícula</option>
                                    <option value="opcional">Opcional: el firmante elige Sí o No</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.acceptance_mode" />
                            </div>

                            <div>
                                <InputLabel for="scope" value="Alcance" />
                                <select id="scope" v-model="form.scope" :class="selectClasses" :disabled="!canEdit">
                                    <option value="matricula">Por matrícula: se firma en cada matrícula</option>
                                    <option value="alumno">Por alumno: se firma una sola vez</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.scope" />
                            </div>
                        </div>

                        <label class="flex items-start gap-2 text-sm text-gray-700">
                            <input v-model="form.requires_guardian" type="checkbox" :disabled="!canEdit" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            Solo para estudiantes menores de edad (lo firma el acudiente)
                        </label>

                        <div>
                            <InputLabel for="body" value="Texto del contrato" />
                            <p class="mt-1 text-xs text-gray-500">
                                Deja una línea en blanco entre párrafos. Usa los botones para insertar datos del estudiante o de la matrícula.
                            </p>

                            <div v-if="canEdit" class="mt-3 space-y-2">
                                <div v-for="[group, items] in variableGroups" :key="group" class="flex flex-wrap items-center gap-1">
                                    <span class="w-24 shrink-0 text-xs font-medium text-gray-500">{{ group }}</span>
                                    <button
                                        v-for="variable in items"
                                        :key="variable.key"
                                        type="button"
                                        class="rounded-md border border-indigo-200 bg-indigo-50 px-2 py-0.5 font-mono text-xs text-indigo-700 hover:bg-indigo-100"
                                        :title="variable.label"
                                        @click="insertVariable(variable.key)"
                                    >
                                        {{ variable.key }}
                                    </button>
                                </div>
                            </div>

                            <textarea
                                id="body"
                                ref="bodyInput"
                                v-model="form.body"
                                rows="22"
                                :disabled="!canEdit"
                                class="mt-3 block w-full rounded-xl border-slate-200 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-50"
                                required
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.body" />
                            <p v-if="unknownVariables.length" class="mt-2 text-xs text-amber-700">
                                Variables desconocidas (no se reemplazarán): {{ unknownVariables.join(', ') }}
                            </p>
                        </div>

                        <div v-if="canEdit" class="flex flex-wrap items-center gap-3">
                            <PrimaryButton :disabled="form.processing">{{ isNew ? 'Guardar borrador' : 'Guardar cambios' }}</PrimaryButton>
                            <SecondaryButton v-if="!isNew" type="button" @click="publish">Publicar</SecondaryButton>
                            <Link :href="route('contract-templates.index')" class="text-sm text-gray-600 hover:text-gray-900">Volver</Link>
                            <DangerButton v-if="!isNew" type="button" class="ml-auto" @click="destroy">Eliminar borrador</DangerButton>
                        </div>
                        <Link v-else :href="route('contract-templates.index')" class="text-sm text-gray-600 hover:text-gray-900">Volver</Link>
                    </div>

                    <div class="rounded-2xl border border-slate-200/80 bg-white p-6">
                        <h3 class="text-sm font-medium text-gray-900">Vista previa</h3>
                        <p class="mt-1 text-xs text-gray-500">Con datos de ejemplo (una estudiante menor de edad y su acudiente).</p>
                        <article
                            class="prose prose-sm mt-4 max-w-none space-y-3 text-sm leading-relaxed text-gray-800"
                            v-html="previewHtml || '<p class=&quot;text-gray-400&quot;>El texto aparecerá aquí.</p>'"
                        />
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
