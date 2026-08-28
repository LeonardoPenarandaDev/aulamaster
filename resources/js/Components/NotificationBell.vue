<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();

function markAsRead(notification) {
    if (!notification.read_at) {
        router.post(route('notifications.read', notification.id), {}, { preserveScroll: true });
    }
}

function markAllAsRead() {
    router.post(route('notifications.read-all'), {}, { preserveScroll: true });
}

function timeAgo(dateString) {
    const diffMinutes = Math.round((Date.now() - new Date(dateString).getTime()) / 60000);
    if (diffMinutes < 1) return 'ahora';
    if (diffMinutes < 60) return `hace ${diffMinutes} min`;
    const diffHours = Math.round(diffMinutes / 60);
    if (diffHours < 24) return `hace ${diffHours} h`;
    return `hace ${Math.round(diffHours / 24)} d`;
}
</script>

<template>
    <Dropdown align="right" width="96">
        <template #trigger>
            <button
                type="button"
                class="relative inline-flex items-center rounded-md border border-transparent bg-white p-2 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
            >
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                    />
                </svg>
                <span
                    v-if="page.props.notifications.unreadCount > 0"
                    class="absolute -right-1 -top-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-xs font-medium text-white"
                >
                    {{ page.props.notifications.unreadCount > 9 ? '9+' : page.props.notifications.unreadCount }}
                </span>
            </button>
        </template>

        <template #content>
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-2">
                <span class="text-sm font-medium text-gray-700">Notificaciones</span>
                <button
                    v-if="page.props.notifications.unreadCount > 0"
                    type="button"
                    class="text-xs text-indigo-600 hover:text-indigo-900"
                    @click="markAllAsRead"
                >
                    Marcar todas como leídas
                </button>
            </div>

            <div class="max-h-96 overflow-y-auto">
                <button
                    v-for="notification in page.props.notifications.recent"
                    :key="notification.id"
                    type="button"
                    class="block w-full border-b border-gray-50 px-4 py-3 text-left hover:bg-gray-50"
                    :class="!notification.read_at ? 'bg-indigo-50/50' : ''"
                    @click="markAsRead(notification)"
                >
                    <p class="text-sm font-medium text-gray-900">{{ notification.data.title }}</p>
                    <p v-if="notification.data.lines?.length" class="mt-0.5 text-xs text-gray-600">
                        {{ notification.data.lines[0] }}
                    </p>
                    <p class="mt-1 text-xs text-gray-400">{{ timeAgo(notification.created_at) }}</p>
                </button>

                <p v-if="page.props.notifications.recent.length === 0" class="px-4 py-6 text-center text-sm text-gray-500">
                    No tienes notificaciones.
                </p>
            </div>
        </template>
    </Dropdown>
</template>
