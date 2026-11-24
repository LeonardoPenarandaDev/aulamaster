<script setup>
import SkillBadges from '@/Components/SkillBadges.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    groups: Array,
    topics: Array,
    skills: { type: Object, default: () => ({}) },
    filters: Object,
});

const page = usePage();

const selectClasses = 'mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500';

const groupFilter = ref(props.filters.group ?? '');

const form = useForm({
    group_key: props.filters.group ?? props.groups[0]?.key ?? '',
    name: '',
    skills: [],
    teacher_notes: '',
});

const editing = ref(null);
const editForm = useForm({ name: '', group_key: '', skills: [], teacher_notes: '' });

/**
 * Niveles globales con sus temas en orden (los que no tienen temas
 * también se muestran, para ver qué falta cargar).
 */
const visibleGroups = computed(() =>
    props.groups
        .filter((group) => !groupFilter.value || group.key === groupFilter.value)
        .map((group) => ({ ...group, topics: props.topics.filter((topic) => topic.group_key === group.key) })),
);

function groupLabel(group) {
    return `${group.group}${group.course ? ` · ${group.course}` : ''}`;
}

function applyFilter() {
    if (groupFilter.value) {
        form.group_key = groupFilter.value;
    }

    router.get(route('level-topics.index'), { group: groupFilter.value || undefined }, { preserveState: true, preserveScroll: true, replace: true });
}

function addTopic() {
    form.post(route('level-topics.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('name', 'skills', 'teacher_notes'),
    });
}

function startEditing(topic) {
    editing.value = topic.id;
    editForm.clearErrors();
    editForm.name = topic.name;
    editForm.group_key = topic.group_key;
    editForm.skills = [...topic.skills];
    editForm.teacher_notes = topic.teacher_notes ?? '';
}

function saveTopic(topic) {
    editForm.put(route('level-topics.update', topic.id), {
        preserveScroll: true,
        onSuccess: () => (editing.value = null),
    });
}

function moveTopic(topic, direction) {
    router.post(route('level-topics.move', topic.id), { direction }, { preserveScroll: true });
}

function deleteTopic(topic) {
    const warning = topic.sessions_count > 0 ? ` ${topic.sessions_count} clase(s) lo tienen asignado y quedarán sin tema.` : '';

    if (confirm(`¿Eliminar el tema «${topic.name}»?${warning}`)) {
        router.delete(route('level-topics.destroy', topic.id), { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Temas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Temas</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div
                    v-if="page.props.flash?.success"
                    class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-700"
                >
                    {{ page.props.flash.success }}
                </div>

                <div v-if="groups.length === 0" class="rounded-2xl border border-slate-200/80 bg-white p-6 text-sm text-gray-600">
                    Todavía no hay niveles. Crea los niveles primero y luego agrega sus temas aquí.
                </div>

                <template v-else>
                    <form class="rounded-2xl border border-slate-200/80 bg-white p-6" @submit.prevent="addTopic">
                        <h3 class="text-base font-semibold text-gray-900">Nuevo tema</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Elige el nivel en el que se verá. El tema queda disponible para todas sus intensidades
                            (Standard, Semi-Intensivo e Intensivo) y se elige al programar cada clase.
                        </p>

                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)_auto] sm:items-end">
                            <div>
                                <InputLabel for="group_key" value="Nivel" />
                                <select id="group_key" v-model="form.group_key" :class="selectClasses">
                                    <option v-for="group in groups" :key="group.key" :value="group.key">{{ groupLabel(group) }}</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel for="topic_name" value="Tema" />
                                <TextInput id="topic_name" v-model="form.name" class="mt-1 block w-full" placeholder="Ej: Verb to be" />
                            </div>
                            <PrimaryButton :disabled="form.processing || !form.name.trim()">Agregar</PrimaryButton>
                        </div>

                        <fieldset class="mt-4">
                            <legend class="text-sm font-medium text-gray-700">Cómo se va a enseñar</legend>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <label
                                    v-for="(label, key) in skills"
                                    :key="key"
                                    class="flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-1.5 text-sm"
                                    :class="form.skills.includes(key) ? 'border-indigo-300 bg-indigo-50 text-indigo-800' : 'border-slate-200 text-gray-700 hover:bg-slate-50'"
                                >
                                    <input v-model="form.skills" type="checkbox" :value="key" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                    {{ label }}
                                </label>
                            </div>
                        </fieldset>

                        <div class="mt-4">
                            <InputLabel for="teacher_notes" value="Observaciones para el docente" />
                            <textarea
                                id="teacher_notes"
                                v-model="form.teacher_notes"
                                rows="3"
                                maxlength="2000"
                                :class="selectClasses"
                                placeholder="Ej: empezar con un video corto y trabajar el diálogo en parejas"
                            />
                            <p class="mt-1 text-xs text-gray-500">Solo las ven el docente y el personal; los estudiantes no.</p>
                        </div>
                        <InputError class="mt-2" :message="form.errors.name || form.errors.group_key || form.errors.skills || form.errors.teacher_notes" />
                    </form>

                    <div class="flex flex-wrap items-center gap-3">
                        <label for="group_filter" class="text-sm font-medium text-gray-700">Ver nivel:</label>
                        <select id="group_filter" v-model="groupFilter" class="rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500" @change="applyFilter">
                            <option value="">Todos los niveles</option>
                            <option v-for="group in groups" :key="group.key" :value="group.key">{{ groupLabel(group) }}</option>
                        </select>
                    </div>

                    <section
                        v-for="group in visibleGroups"
                        :key="group.key"
                        class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white"
                    >
                        <header class="border-b border-slate-100 px-6 py-4">
                            <h3 class="text-base font-semibold text-gray-900">
                                {{ group.group }}
                                <span v-if="group.course" class="ml-1 text-xs font-normal uppercase tracking-wider text-gray-400">{{ group.course }}</span>
                            </h3>
                            <p class="mt-1 flex flex-wrap gap-1.5 text-xs text-gray-500">
                                Lo ven:
                                <span v-for="levelName in group.levels" :key="levelName" class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-700">{{ levelName }}</span>
                            </p>
                        </header>

                        <ol v-if="group.topics.length" class="divide-y divide-slate-100">
                            <li v-for="(topic, index) in group.topics" :key="topic.id" class="flex flex-wrap items-center gap-3 px-6 py-3">
                                <span class="w-6 text-sm font-semibold tabular-nums text-gray-400">{{ index + 1 }}.</span>

                                <form v-if="editing === topic.id" class="flex flex-1 flex-wrap items-start gap-2" @submit.prevent="saveTopic(topic)">
                                    <TextInput v-model="editForm.name" class="block min-w-48 flex-1" autofocus />
                                    <select v-model="editForm.group_key" class="rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                        <option v-for="option in groups" :key="option.key" :value="option.key">{{ groupLabel(option) }}</option>
                                    </select>
                                    <div class="flex w-full flex-wrap gap-3">
                                        <label v-for="(label, key) in skills" :key="key" class="flex items-center gap-1.5 text-sm text-gray-700">
                                            <input v-model="editForm.skills" type="checkbox" :value="key" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                            {{ label }}
                                        </label>
                                    </div>
                                    <textarea
                                        v-model="editForm.teacher_notes"
                                        rows="3"
                                        maxlength="2000"
                                        class="block w-full rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Observaciones para el docente"
                                    />
                                    <PrimaryButton :disabled="editForm.processing">Guardar</PrimaryButton>
                                    <SecondaryButton type="button" @click="editing = null">Cancelar</SecondaryButton>
                                    <InputError class="w-full" :message="editForm.errors.name || editForm.errors.group_key || editForm.errors.skills || editForm.errors.teacher_notes" />
                                </form>

                                <template v-else>
                                    <div class="min-w-0 flex-1 text-sm text-gray-900">
                                        <p>
                                            {{ topic.name }}
                                            <SkillBadges :skills="topic.skills" class="ml-1" />
                                            <span v-if="topic.sessions_count" class="ml-1 text-xs text-gray-400">· {{ topic.sessions_count }} clase(s)</span>
                                        </p>
                                        <p v-if="topic.teacher_notes" class="mt-1 whitespace-pre-line text-xs text-amber-800">📝 {{ topic.teacher_notes }}</p>
                                    </div>
                                    <div class="flex items-center gap-1 text-sm">
                                        <button type="button" class="rounded-md px-2 py-1 text-gray-500 hover:bg-slate-100 disabled:opacity-30" :disabled="index === 0" title="Subir" @click="moveTopic(topic, 'up')">↑</button>
                                        <button type="button" class="rounded-md px-2 py-1 text-gray-500 hover:bg-slate-100 disabled:opacity-30" :disabled="index === group.topics.length - 1" title="Bajar" @click="moveTopic(topic, 'down')">↓</button>
                                        <button type="button" class="rounded-md px-2 py-1 font-medium text-indigo-600 hover:bg-indigo-50" @click="startEditing(topic)">Editar</button>
                                        <button type="button" class="rounded-md px-2 py-1 font-medium text-rose-600 hover:bg-rose-50" @click="deleteTopic(topic)">Eliminar</button>
                                    </div>
                                </template>
                            </li>
                        </ol>
                        <p v-else class="px-6 py-4 text-sm text-gray-500">Este nivel todavía no tiene temas.</p>
                    </section>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
