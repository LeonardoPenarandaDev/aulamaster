<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { computed } from 'vue';

const props = defineProps({
    form: Object,
    documentTypes: Object,
    // Errores ya resueltos por campo; por defecto, los del propio formulario.
    errors: { type: Object, default: null },
});

const fieldErrors = computed(() => props.errors ?? props.form.errors ?? {});

const isMinor = computed(() => {
    if (!props.form.birth_date) {
        return false;
    }

    const birth = new Date(`${props.form.birth_date}T00:00:00`);
    const adulthood = new Date(birth);
    adulthood.setFullYear(birth.getFullYear() + 18);

    return adulthood > new Date();
});

const selectClasses = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
</script>

<template>
    <div class="border-t border-gray-200 pt-6">
        <div class="flex flex-wrap items-center gap-2">
            <h3 class="text-sm font-medium text-gray-900">Acudiente</h3>
            <span v-if="isMinor" class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">Menor de edad</span>
        </div>
        <p class="mt-1 text-sm text-gray-500">
            <template v-if="isMinor">
                El estudiante es menor de edad: los contratos los firma solo el acudiente. Su nombre y correo son necesarios para enviarlos.
            </template>
            <template v-else>
                Solo es necesario si el estudiante es menor de edad.
            </template>
        </p>

        <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <InputLabel for="guardian_name" value="Nombre del acudiente" />
                <TextInput id="guardian_name" v-model="form.guardian_name" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="fieldErrors.guardian_name" />
            </div>

            <div>
                <InputLabel for="guardian_relationship" value="Parentesco" />
                <TextInput id="guardian_relationship" v-model="form.guardian_relationship" class="mt-1 block w-full" placeholder="Madre, padre, tío…" />
                <InputError class="mt-2" :message="fieldErrors.guardian_relationship" />
            </div>

            <div>
                <InputLabel for="guardian_document_type" value="Tipo de documento" />
                <select id="guardian_document_type" v-model="form.guardian_document_type" :class="selectClasses">
                    <option :value="null">Sin especificar</option>
                    <option v-for="(label, value) in documentTypes" :key="value" :value="value">{{ label }}</option>
                </select>
                <InputError class="mt-2" :message="fieldErrors.guardian_document_type" />
            </div>

            <div>
                <InputLabel for="guardian_document" value="Documento del acudiente" />
                <TextInput id="guardian_document" v-model="form.guardian_document" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="fieldErrors.guardian_document" />
            </div>

            <div>
                <InputLabel for="guardian_email" value="Correo del acudiente" />
                <TextInput id="guardian_email" type="email" v-model="form.guardian_email" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="fieldErrors.guardian_email" />
            </div>

            <div>
                <InputLabel for="guardian_phone" value="Teléfono del acudiente" />
                <TextInput id="guardian_phone" type="tel" v-model="form.guardian_phone" class="mt-1 block w-full" placeholder="+57 300 123 4567" />
                <InputError class="mt-2" :message="fieldErrors.guardian_phone" />
            </div>
        </div>
    </div>
</template>
