<script setup>
import Icon from '@/Components/Icon.vue';
import { onMounted, ref } from 'vue';

/**
 * Campo de contraseña con el botón del "ojito" para mostrar u ocultar lo
 * escrito. Las clases (ancho, márgenes) van al contenedor; el resto de
 * atributos (id, autocomplete, required…) van al input.
 */
defineOptions({ inheritAttrs: false });

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);
const visible = ref(false);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <div class="relative" :class="$attrs.class" :style="$attrs.style">
        <input
            v-bind="{ ...$attrs, class: undefined, style: undefined }"
            ref="input"
            v-model="model"
            :type="visible ? 'text' : 'password'"
            class="block w-full rounded-xl border-slate-200 pr-11 text-sm shadow-sm shadow-slate-100 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-slate-50"
        />
        <button
            type="button"
            class="absolute inset-y-0 right-0 flex w-10 items-center justify-center rounded-r-xl text-slate-400 hover:text-slate-700 focus:text-slate-700 focus:outline-none"
            :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
            :title="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
            :aria-pressed="visible"
            @click="visible = !visible"
        >
            <Icon :name="visible ? 'eye-off' : 'eye'" class="h-5 w-5" />
        </button>
    </div>
</template>
