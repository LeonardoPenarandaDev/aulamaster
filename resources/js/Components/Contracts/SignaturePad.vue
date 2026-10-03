<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';

/**
 * Área de firma con canvas y Pointer Events (parte 6.7 del plan de
 * mejoras): funciona con mouse, pantallas táctiles y tabletas de lápiz
 * (Wacom Intuos/One, Huion, XP-Pen), y usa la presión del lápiz para el
 * grosor del trazo. Las Wacom STU no son compatibles sin un trabajo aparte.
 */
const emit = defineEmits(['change']);

const props = defineProps({
    height: { type: Number, default: 220 },
});

const canvas = ref(null);
const isEmpty = ref(true);
let context = null;
let drawing = false;
let lastPoint = null;
let resizeObserver = null;

function setupCanvas() {
    const element = canvas.value;
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const snapshot = isEmpty.value ? null : element.toDataURL('image/png');

    element.width = element.offsetWidth * ratio;
    element.height = props.height * ratio;
    context = element.getContext('2d');
    context.scale(ratio, ratio);
    context.lineCap = 'round';
    context.lineJoin = 'round';
    context.strokeStyle = '#111827';

    if (snapshot) {
        const image = new Image();
        image.onload = () => context.drawImage(image, 0, 0, element.offsetWidth, props.height);
        image.src = snapshot;
    }
}

function pointFrom(event) {
    const rect = canvas.value.getBoundingClientRect();
    const pressure = event.pointerType === 'pen' && event.pressure > 0 ? event.pressure : 0.5;

    return { x: event.clientX - rect.left, y: event.clientY - rect.top, pressure };
}

function start(event) {
    event.preventDefault();
    canvas.value.setPointerCapture(event.pointerId);
    drawing = true;
    lastPoint = pointFrom(event);

    context.beginPath();
    context.arc(lastPoint.x, lastPoint.y, 0.8 + lastPoint.pressure * 1.6, 0, Math.PI * 2);
    context.fillStyle = '#111827';
    context.fill();
}

function move(event) {
    if (!drawing) {
        return;
    }

    event.preventDefault();
    const events = event.getCoalescedEvents ? event.getCoalescedEvents() : [event];

    for (const item of events) {
        const point = pointFrom(item);
        context.lineWidth = 1 + point.pressure * 3;
        context.beginPath();
        context.moveTo(lastPoint.x, lastPoint.y);
        context.lineTo(point.x, point.y);
        context.stroke();
        lastPoint = point;
    }

    isEmpty.value = false;
}

function end(event) {
    if (!drawing) {
        return;
    }

    drawing = false;
    canvas.value.releasePointerCapture?.(event.pointerId);
    isEmpty.value = false;
    emit('change', toDataURL());
}

function clear() {
    context.clearRect(0, 0, canvas.value.width, canvas.value.height);
    isEmpty.value = true;
    emit('change', null);
}

function toDataURL() {
    return isEmpty.value ? null : canvas.value.toDataURL('image/png');
}

onMounted(() => {
    setupCanvas();
    resizeObserver = new ResizeObserver(() => setupCanvas());
    resizeObserver.observe(canvas.value);
});

onBeforeUnmount(() => resizeObserver?.disconnect());

defineExpose({ clear, toDataURL, isEmpty });
</script>

<template>
    <div>
        <div class="relative rounded-lg border-2 border-dashed border-gray-300 bg-white">
            <canvas
                ref="canvas"
                class="block w-full cursor-crosshair touch-none select-none"
                :style="{ height: `${height}px` }"
                aria-label="Área de firma"
                @pointerdown="start"
                @pointermove="move"
                @pointerup="end"
                @pointercancel="end"
                @pointerleave="end"
            />
            <div class="pointer-events-none absolute inset-x-8 bottom-10 border-t border-gray-300" />
            <span v-if="isEmpty" class="pointer-events-none absolute inset-0 flex items-center justify-center text-sm text-gray-400">
                Firme aquí
            </span>
        </div>
        <button type="button" class="mt-2 text-sm text-gray-600 underline hover:text-gray-900" @click="clear">Borrar firma</button>
    </div>
</template>
