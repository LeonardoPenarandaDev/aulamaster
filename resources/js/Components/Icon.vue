<script setup>
defineProps({
    name: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.5"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <template v-if="name === 'users'">
            <circle cx="9" cy="8" r="2.5" />
            <path d="M4.5 19c0-2.5 2-4.5 4.5-4.5s4.5 2 4.5 4.5" />
            <circle cx="16" cy="9" r="2" />
            <path d="M14.5 19c.2-1.8 1.3-3.2 3-3.7" />
        </template>

        <template v-else-if="name === 'book'">
            <path d="M12 6c-1.5-1-3.5-1.5-5.5-1.5-1 0-2 .1-3 .4v13c1-.3 2-.4 3-.4 2 0 4 .5 5.5 1.5" />
            <path d="M12 6c1.5-1 3.5-1.5 5.5-1.5 1 0 2 .1 3 .4v13c-1-.3-2-.4-3-.4-2 0-4 .5-5.5 1.5" />
            <path d="M12 6v13.5" />
        </template>

        <template v-else-if="name === 'teacher'">
            <circle cx="12" cy="8.2" r="2.5" />
            <path d="M7 19c0-2.8 2.2-5 5-5s5 2.2 5 5" />
            <path d="M8.5 6 12 4.3 15.5 6 12 7.7 8.5 6Z" />
        </template>

        <template v-else-if="name === 'building'">
            <rect x="5" y="4" width="14" height="16" rx="1" />
            <rect x="8" y="7" width="3" height="3" />
            <rect x="13" y="7" width="3" height="3" />
            <rect x="8" y="12" width="3" height="3" />
            <rect x="13" y="12" width="3" height="3" />
            <rect x="10" y="17" width="4" height="3" />
        </template>

        <template v-else-if="name === 'calendar'">
            <rect x="4" y="5.5" width="16" height="14.5" rx="1.5" />
            <path d="M4 10h16M8 3.5v3M16 3.5v3" />
            <circle cx="12" cy="15" r="1.3" fill="currentColor" stroke="none" />
        </template>

        <template v-else-if="name === 'check-circle'">
            <circle cx="12" cy="12" r="8" />
            <path d="m8.5 12.3 2.3 2.3 4.7-4.9" />
        </template>

        <template v-else-if="name === 'clipboard'">
            <rect x="6" y="4.5" width="12" height="16" rx="1.5" />
            <rect x="9" y="3" width="6" height="3" rx="1" />
            <path d="M9 11h6M9 14.5h6M9 18h4" />
        </template>

        <template v-else-if="name === 'refresh'">
            <path d="M5 12a7 7 0 0 1 11.5-5.3" />
            <path d="M19 12a7 7 0 0 1-11.5 5.3" />
            <path d="M16.5 5v3h-3" />
            <path d="M7.5 19v-3h3" />
        </template>

        <template v-else-if="name === 'alert-triangle'">
            <path d="M12 4 3 19h18L12 4Z" stroke-linejoin="round" />
            <path d="M12 10v4M12 17h.01" />
        </template>

        <template v-else-if="name === 'coins'">
            <ellipse cx="8" cy="9" rx="4.5" ry="2.5" />
            <path d="M3.5 9v5c0 1.4 2 2.5 4.5 2.5s4.5-1.1 4.5-2.5v-5" />
            <path d="M3.5 12c0 1.4 2 2.5 4.5 2.5s4.5-1.1 4.5-2.5" />
            <ellipse cx="16" cy="14" rx="4.5" ry="2.5" />
            <path d="M11.5 14v3c0 1.4 2 2.5 4.5 2.5s4.5-1.1 4.5-2.5v-3" />
        </template>

        <template v-else-if="name === 'clock'">
            <circle cx="12" cy="12" r="8" />
            <path d="M12 7.5v5l3.2 1.8" />
        </template>

        <template v-else-if="name === 'wallet'">
            <rect x="3.5" y="6.5" width="17" height="12" rx="2" />
            <path d="M3.5 10h17" />
            <circle cx="17" cy="14.5" r="1" fill="currentColor" stroke="none" />
        </template>

        <template v-else-if="name === 'home'">
            <path d="M4 10.5 12 4l8 6.5" />
            <path d="M6 9v10.5h4.5V15h3v4.5H18V9" />
        </template>

        <template v-else-if="name === 'document'">
            <path d="M7 3.5h7l4 4V20.5H7z" />
            <path d="M14 3.5v4h4" />
            <path d="M9.5 12h6M9.5 15.5h6" />
        </template>

        <template v-else-if="name === 'user'">
            <circle cx="12" cy="8.5" r="3.5" />
            <path d="M5 20c.8-3.6 3.6-5.5 7-5.5s6.2 1.9 7 5.5" />
        </template>

        <template v-else-if="name === 'video'">
            <rect x="3.5" y="6.5" width="12" height="11" rx="2" />
            <path d="m15.5 10.5 5-3v9l-5-3" />
        </template>

        <template v-else-if="name === 'logout'">
            <path d="M14 4.5H6.5v15H14" />
            <path d="M10.5 12h10M17 8.5l3.5 3.5-3.5 3.5" />
        </template>

        <template v-else-if="name === 'folder'">
            <path d="M3.5 7a1.5 1.5 0 0 1 1.5-1.5h4l2 2h8A1.5 1.5 0 0 1 20.5 9v8.5A1.5 1.5 0 0 1 19 19H5a1.5 1.5 0 0 1-1.5-1.5z" />
        </template>

        <template v-else-if="name === 'link'">
            <path d="M10 14a4 4 0 0 0 5.66 0l3-3a4 4 0 0 0-5.66-5.66l-1 1" />
            <path d="M14 10a4 4 0 0 0-5.66 0l-3 3a4 4 0 0 0 5.66 5.66l1-1" />
        </template>

        <template v-else-if="name === 'chevron-right'">
            <path d="m9.5 6 6 6-6 6" />
        </template>
    </svg>
</template>
