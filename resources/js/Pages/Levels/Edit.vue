<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import LevelColorPicker from '@/Components/LevelColorPicker.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    level: Object,
    courses: Array,
    levelOptions: Array,
    topics: { type: Array, default: () => [] },
    topicSessionCounts: { type: Object, default: () => ({}) },
});

const page = usePage();

const topicForm = useForm({ name: '' });
const editingTopicId = ref(null);
const editingTopicName = ref('');

function addTopic() {
    topicForm.post(route('level-topics.store', props.level.id), {
        preserveScroll: true,
        onSuccess: () => topicForm.reset(),
    });
}

function startEditingTopic(topic) {
    editingTopicId.value = topic.id;
    editingTopicName.value = topic.name;
}

function saveTopic(topic) {
    router.put(route('level-topics.update', [props.level.id, topic.id]), { name: editingTopicName.value }, {
        preserveScroll: true,
        onSuccess: () => (editingTopicId.value = null),
    });
}

function moveTopic(topic, direction) {
    router.post(route('level-topics.move', [props.level.id, topic.id]), { direction }, { preserveScroll: true });
}

function deleteTopic(topic) {
    const used = props.topicSessionCounts[topic.id] ?? 0;
    const warning = used > 0 ? ` ${used} clase(s) lo tienen asignado y quedarán sin tema.` : '';

    if (confirm(`¿Eliminar el tema «${topic.name}»?${warning}`)) {
        router.delete(route('level-topics.destroy', [props.level.id, topic.id]), { preserveScroll: true });
    }
}

const form = useForm({
    course_id: props.level.course_id,
    name: props.level.name,
    class_group: props.level.class_group ?? '',
    color: props.level.color,
    next_level_id: props.level.next_level_id,
    code: props.level.code,
    duration_months: props.level.duration_months,
    weekly_hours: props.level.weekly_hours,
    monthly_hours: props.level.monthly_hours,
    required_hours: props.level.required_hours,
    minimum_grade: props.level.minimum_grade,
    price: props.level.price,
    monthly_fee: props.level.monthly_fee,
    apply_monthly_fee_to_active: false,
    start_date: props.level.start_date,
    end_date: props.level.end_date,
    status: props.level.status,
});

const monthlyFeeChanged = computed(() => Number(form.monthly_fee || 0) !== Number(props.level.monthly_fee || 0));

const intensitySuffix = /[\s\-–]*(semi[\s\-]*intensivo|intensivo|standard|est[aá]ndar)\s*$/iu;

const suggestedClassGroup = computed(() => form.name.replace(intensitySuffix, '').trim());

const classGroupOptions = computed(() => [
    ...new Set(
        props.levelOptions
            .filter((option) => option.course_id === Number(form.course_id) && option.class_group)
            .map((option) => option.class_group),
    ),
]);

const levelsSharingClasses = computed(() => {
    const group = (form.class_group || suggestedClassGroup.value).trim().toLowerCase();

    return props.levelOptions
        .filter((option) => option.course_id === Number(form.course_id) && option.id !== props.level.id && option.class_group?.toLowerCase() === group)
        .map((option) => option.name);
});

const nextLevelOptions = computed(() =>
    props.levelOptions.filter((option) => option.course_id === Number(form.course_id) && option.id !== props.level.id),
);

function submit() {
    form.put(route('levels.update', props.level.id));
}
</script>

<template>
    <Head title="Editar nivel" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Editar nivel
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="course_id" value="Curso" />
                                <select id="course_id" v-model="form.course_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.course_id" />
                            </div>

                            <div>
                                <InputLabel for="status" value="Estado" />
                                <select id="status" v-model="form.status" class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.status" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="name" value="Nombre" />
                                <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required autofocus />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div>
                                <InputLabel for="code" value="Código" />
                                <TextInput id="code" v-model="form.code" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.code" />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Color del nivel" />
                            <p class="mt-1 text-xs text-gray-500">Se usa como fondo del portal de los estudiantes de este nivel.</p>
                            <LevelColorPicker v-model="form.color" class="mt-2" />
                            <InputError class="mt-2" :message="form.errors.color" />
                        </div>

                        <div>
                            <InputLabel for="next_level_id" value="Nivel siguiente" />
                            <select id="next_level_id" v-model="form.next_level_id" class="mt-1 block w-full max-w-md rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                <option :value="null">Ninguno (último nivel de la ruta)</option>
                                <option v-for="option in nextLevelOptions" :key="option.id" :value="option.id">{{ option.name }} ({{ option.code }})</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Al aprobar este nivel, el estudiante queda matriculado automáticamente en el siguiente como pendiente.</p>
                            <InputError class="mt-2" :message="form.errors.next_level_id" />
                        </div>

                        <div>
                            <InputLabel for="class_group" value="Comparte clases con (grupo)" />
                            <TextInput
                                id="class_group"
                                v-model="form.class_group"
                                list="class_group_options"
                                class="mt-1 block w-full max-w-md"
                                :placeholder="suggestedClassGroup || 'Ej: Elementary 1'"
                                autocomplete="off"
                            />
                            <datalist id="class_group_options">
                                <option v-for="group in classGroupOptions" :key="group" :value="group" />
                            </datalist>
                            <p class="mt-1 text-xs text-gray-500">
                                Los niveles del mismo curso con el mismo grupo ven y comparten las mismas clases del calendario
                                (p. ej. Elementary 1 Standard, Semi-Intensivo e Intensivo); cada estudiante toma las horas de su intensidad.
                                Si lo dejas vacío, se toma del nombre sin la intensidad.
                            </p>
                            <p v-if="levelsSharingClasses.length" class="mt-1 text-xs font-medium text-indigo-700">
                                Comparte clases con: {{ levelsSharingClasses.join(', ') }}
                            </p>
                            <InputError class="mt-2" :message="form.errors.class_group" />
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                            <div>
                                <InputLabel for="duration_months" value="Duración (meses)" />
                                <TextInput id="duration_months" type="number" v-model="form.duration_months" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.duration_months" />
                            </div>

                            <div>
                                <InputLabel for="weekly_hours" value="Horas semanales" />
                                <TextInput id="weekly_hours" type="number" step="0.5" v-model="form.weekly_hours" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.weekly_hours" />
                            </div>

                            <div>
                                <InputLabel for="monthly_hours" value="Horas mensuales" />
                                <TextInput id="monthly_hours" type="number" step="0.5" v-model="form.monthly_hours" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.monthly_hours" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="required_hours" value="Horas totales requeridas" />
                                <TextInput id="required_hours" type="number" step="0.5" v-model="form.required_hours" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.required_hours" />
                            </div>

                            <div>
                                <InputLabel for="minimum_grade" value="Nota mínima" />
                                <TextInput id="minimum_grade" type="number" step="0.1" v-model="form.minimum_grade" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.minimum_grade" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="price" value="Precio de la matrícula" />
                                <TextInput id="price" type="number" step="0.01" v-model="form.price" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.price" />
                            </div>

                            <div>
                                <InputLabel for="monthly_fee" value="Mensualidad (opcional)" />
                                <TextInput id="monthly_fee" type="number" step="1000" min="0" v-model="form.monthly_fee" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.monthly_fee" />
                                <p class="mt-1 text-xs text-gray-500">Valor de referencia; se puede ajustar en cada matrícula. Vacío: el nivel no cobra mensualidad.</p>
                            </div>
                        </div>

                        <label
                            v-if="monthlyFeeChanged"
                            class="-mt-2 flex items-start gap-2 rounded-md bg-amber-50 p-3 text-sm text-amber-800"
                        >
                            <input v-model="form.apply_monthly_fee_to_active" type="checkbox" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            Cambiaste la mensualidad. ¿Aplicar el nuevo valor a las matrículas activas de este nivel desde el próximo mes?
                            (Las que tienen un valor ajustado a mano no cambian.)
                        </label>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="start_date" value="Fecha de inicio" />
                                <TextInput id="start_date" type="date" v-model="form.start_date" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.start_date" />
                            </div>

                            <div>
                                <InputLabel for="end_date" value="Fecha de finalización" />
                                <TextInput id="end_date" type="date" v-model="form.end_date" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.end_date" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Guardar</PrimaryButton>
                            <Link :href="route('levels.index')">
                                <SecondaryButton type="button">Cancelar</SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </div>

                <div class="mt-6 rounded-2xl border border-slate-200/80 bg-white p-6">
                    <h3 class="text-base font-semibold text-gray-900">Temas de {{ level.class_group || level.name }}</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Los comparten todas las intensidades de este nivel
                        <template v-if="levelsSharingClasses.length">({{ [level.name, ...levelsSharingClasses].join(', ') }})</template>.
                        Al programar una clase eliges cuál de estos temas se verá.
                    </p>

                    <div
                        v-if="page.props.flash?.success"
                        class="mt-4 rounded-xl border border-emerald-100 bg-emerald-50 p-3 text-sm text-emerald-700"
                    >
                        {{ page.props.flash.success }}
                    </div>

                    <ol v-if="topics.length" class="mt-4 divide-y divide-slate-100 rounded-xl border border-slate-200">
                        <li v-for="(topic, index) in topics" :key="topic.id" class="flex flex-wrap items-center gap-3 px-4 py-3">
                            <span class="w-6 text-sm font-semibold tabular-nums text-gray-400">{{ index + 1 }}.</span>

                            <form v-if="editingTopicId === topic.id" class="flex flex-1 gap-2" @submit.prevent="saveTopic(topic)">
                                <TextInput v-model="editingTopicName" class="block w-full" autofocus />
                                <PrimaryButton>Guardar</PrimaryButton>
                                <SecondaryButton type="button" @click="editingTopicId = null">Cancelar</SecondaryButton>
                            </form>

                            <template v-else>
                                <span class="flex-1 text-sm text-gray-900">
                                    {{ topic.name }}
                                    <span v-if="topicSessionCounts[topic.id]" class="ml-1 text-xs text-gray-400">· {{ topicSessionCounts[topic.id] }} clase(s)</span>
                                </span>
                                <div class="flex items-center gap-1 text-sm">
                                    <button type="button" class="rounded-md px-2 py-1 text-gray-500 hover:bg-slate-100 disabled:opacity-30" :disabled="index === 0" title="Subir" @click="moveTopic(topic, 'up')">↑</button>
                                    <button type="button" class="rounded-md px-2 py-1 text-gray-500 hover:bg-slate-100 disabled:opacity-30" :disabled="index === topics.length - 1" title="Bajar" @click="moveTopic(topic, 'down')">↓</button>
                                    <button type="button" class="rounded-md px-2 py-1 font-medium text-indigo-600 hover:bg-indigo-50" @click="startEditingTopic(topic)">Editar</button>
                                    <button type="button" class="rounded-md px-2 py-1 font-medium text-rose-600 hover:bg-rose-50" @click="deleteTopic(topic)">Eliminar</button>
                                </div>
                            </template>
                        </li>
                    </ol>
                    <p v-else class="mt-4 text-sm text-gray-500">Todavía no hay temas para este nivel.</p>

                    <form class="mt-4 flex gap-2" @submit.prevent="addTopic">
                        <TextInput v-model="topicForm.name" class="block w-full" placeholder="Nuevo tema, ej: Verb to be" />
                        <PrimaryButton :disabled="topicForm.processing || !topicForm.name.trim()">Agregar</PrimaryButton>
                    </form>
                    <InputError class="mt-2" :message="topicForm.errors.name" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
