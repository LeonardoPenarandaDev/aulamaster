<script setup>
import { onBeforeUnmount, ref } from 'vue';

/**
 * Foto del documento de identidad: con la cámara web o subiendo un archivo
 * (parte 6.7 del plan de mejoras).
 */
const model = defineModel({ type: [File, null], default: null });

const props = defineProps({
    label: { type: String, required: true },
    maxKb: { type: Number, default: 5120 },
});

const preview = ref(null);
const video = ref(null);
const cameraOn = ref(false);
const error = ref('');
let stream = null;

function setFile(file) {
    error.value = '';

    if (file && file.size > props.maxKb * 1024) {
        error.value = `La foto pesa más de ${Math.round(props.maxKb / 1024)} MB.`;
        return;
    }

    model.value = file;
    preview.value = file ? URL.createObjectURL(file) : null;
}

async function startCamera() {
    error.value = '';

    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment', width: { ideal: 1920 } } });
        cameraOn.value = true;
        await new Promise((resolve) => requestAnimationFrame(resolve));
        video.value.srcObject = stream;
    } catch {
        error.value = 'No se pudo abrir la cámara. Sube la foto como archivo.';
    }
}

function stopCamera() {
    stream?.getTracks().forEach((track) => track.stop());
    stream = null;
    cameraOn.value = false;
}

function capture() {
    const canvas = document.createElement('canvas');
    canvas.width = video.value.videoWidth;
    canvas.height = video.value.videoHeight;
    canvas.getContext('2d').drawImage(video.value, 0, 0);

    canvas.toBlob((blob) => {
        setFile(new File([blob], 'documento.jpg', { type: 'image/jpeg' }));
        stopCamera();
    }, 'image/jpeg', 0.9);
}

onBeforeUnmount(stopCamera);
</script>

<template>
    <div>
        <p class="text-sm font-medium text-gray-700">{{ label }}</p>

        <div v-if="cameraOn" class="mt-2 space-y-2">
            <video ref="video" autoplay playsinline class="w-full max-w-sm rounded-md bg-black" />
            <div class="flex gap-2">
                <button type="button" class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700" @click="capture">Tomar foto</button>
                <button type="button" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50" @click="stopCamera">Cancelar</button>
            </div>
        </div>

        <div v-else class="mt-2 flex flex-wrap items-center gap-3">
            <img v-if="preview" :src="preview" alt="" class="h-20 w-32 rounded-md border border-gray-200 object-cover" />
            <button type="button" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50" @click="startCamera">
                {{ preview ? 'Repetir con la cámara' : 'Usar la cámara' }}
            </button>
            <label class="cursor-pointer rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">
                Subir archivo
                <input type="file" accept="image/jpeg,image/png,image/webp" capture="environment" class="hidden" @change="setFile($event.target.files[0] ?? null)" />
            </label>
            <button v-if="preview" type="button" class="text-sm text-gray-500 underline" @click="setFile(null)">Quitar</button>
        </div>

        <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
