<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    items: Array,
    automaticChecks: Object,
});

const page = usePage();
const selectClasses = 'mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500';

const form = useForm({ name: '', description: '', automatic_check: null });

const editing = ref(null);
const editForm = useForm({ name: '', description: '', automatic_check: null, is_active: true });

function addItem() {
    form.post(route('checklist-items.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function startEditing(item) {
    editing.value = item.id;
    editForm.clearErrors();
    editForm.name = item.name;
    editForm.description = item.description ?? '';
    editForm.automatic_check = item.automatic_check;
    editForm.is_active = item.is_active;
}

function saveItem(item) {
    editForm.put(route('checklist-items.update', item.id), {
        preserveScroll: true,
        onSuccess: () => (editing.value = null),
    });
}

function toggleActive(item) {
    router.put(route('checklist-items.update', item.id), {
        name: item.name,
        description: item.description,
        automatic_check: item.automatic_check,
        is_active: !item.is_active,
    }, { preserveScroll: true });
}

function moveItem(item, direction) {
    router.post(route('checklist-items.move', item.id), { direction }, { preserveScroll: true });
}

function deleteItem(item) {
    const warning = item.marks_count > 0 ? ` Se borrarán las ${item.marks_count} marca(s) que tiene en los estudiantes; si quieres conservarlas, desactívalo.` : '';

    if (confirm(`¿Eliminar el requisito «${item.name}»?${warning}`)) {
        router.delete(route('checklist-items.destroy', item.id), { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Checklist del alumno" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Checklist del alumno</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div
                    v-if="page.props.flash?.success"
                    class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-700"
                >
                    {{ page.props.flash.success }}
                </div>

                <form class="rounded-2xl border border-slate-200/80 bg-white p-6" @submit.prevent="addItem">
                    <h3 class="text-base font-semibold text-gray-900">Nuevo requisito</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Lo que cada estudiante debe tener para quedar al día, por ejemplo "Kit de inicio entregado".
                        Los <strong>manuales</strong> los marca el personal en la ficha del estudiante; los <strong>automáticos</strong> los revisa la plataforma.
                    </p>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="item_name" value="Requisito" />
                            <TextInput id="item_name" v-model="form.name" class="mt-1 block w-full" placeholder="Ej: Kit de inicio entregado" />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="item_check" value="Cómo se cumple" />
                            <select id="item_check" v-model="form.automatic_check" :class="selectClasses">
                                <option :value="null">Manual: lo marca el personal</option>
                                <option v-for="(label, key) in automaticChecks" :key="key" :value="key">Automático: {{ label }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.automatic_check" />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel for="item_description" value="Descripción (opcional)" />
                            <TextInput id="item_description" v-model="form.description" class="mt-1 block w-full" placeholder="Ej: libro, carné y camiseta" />
                        </div>
                    </div>
                    <PrimaryButton class="mt-4" :disabled="form.processing || !form.name.trim()">Agregar requisito</PrimaryButton>
                </form>

                <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white">
                    <header class="border-b border-slate-100 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">Requisitos</h3>
                        <p class="mt-1 text-sm text-gray-500">Se muestran en este orden en la ficha de cada estudiante.</p>
                    </header>

                    <ol v-if="items.length" class="divide-y divide-slate-100">
                        <li v-for="(item, index) in items" :key="item.id" class="px-6 py-4" :class="{ 'opacity-60': !item.is_active }">
                            <form v-if="editing === item.id" class="grid grid-cols-1 gap-3 sm:grid-cols-2" @submit.prevent="saveItem(item)">
                                <TextInput v-model="editForm.name" class="block w-full" autofocus />
                                <select v-model="editForm.automatic_check" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm shadow-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option :value="null">Manual: lo marca el personal</option>
                                    <option v-for="(label, key) in automaticChecks" :key="key" :value="key">Automático: {{ label }}</option>
                                </select>
                                <TextInput v-model="editForm.description" class="block w-full sm:col-span-2" placeholder="Descripción (opcional)" />
                                <div class="flex gap-2 sm:col-span-2">
                                    <PrimaryButton :disabled="editForm.processing">Guardar</PrimaryButton>
                                    <SecondaryButton type="button" @click="editing = null">Cancelar</SecondaryButton>
                                </div>
                                <InputError class="sm:col-span-2" :message="editForm.errors.name || editForm.errors.automatic_check" />
                            </form>

                            <div v-else class="flex flex-wrap items-center gap-3">
                                <span class="w-6 text-sm font-semibold tabular-nums text-gray-400">{{ index + 1 }}.</span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ item.name }}
                                        <span v-if="!item.is_active" class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">Inactivo</span>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <template v-if="item.automatic_check">Automático: {{ automaticChecks[item.automatic_check] }}</template>
                                        <template v-else>Manual</template>
                                        <template v-if="item.description"> · {{ item.description }}</template>
                                    </p>
                                </div>
                                <div class="flex items-center gap-1 text-sm">
                                    <button type="button" class="rounded-md px-2 py-1 text-gray-500 hover:bg-slate-100 disabled:opacity-30" :disabled="index === 0" title="Subir" @click="moveItem(item, 'up')">↑</button>
                                    <button type="button" class="rounded-md px-2 py-1 text-gray-500 hover:bg-slate-100 disabled:opacity-30" :disabled="index === items.length - 1" title="Bajar" @click="moveItem(item, 'down')">↓</button>
                                    <button type="button" class="rounded-md px-2 py-1 font-medium text-indigo-600 hover:bg-indigo-50" @click="startEditing(item)">Editar</button>
                                    <button type="button" class="rounded-md px-2 py-1 font-medium text-slate-600 hover:bg-slate-100" @click="toggleActive(item)">{{ item.is_active ? 'Desactivar' : 'Activar' }}</button>
                                    <button type="button" class="rounded-md px-2 py-1 font-medium text-rose-600 hover:bg-rose-50" @click="deleteItem(item)">Eliminar</button>
                                </div>
                            </div>
                        </li>
                    </ol>
                    <p v-else class="px-6 py-4 text-sm text-gray-500">Todavía no hay requisitos. Agrega el primero arriba.</p>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
