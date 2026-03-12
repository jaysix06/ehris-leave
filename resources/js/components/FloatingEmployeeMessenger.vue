<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { onClickOutside } from '@vueuse/core';
import { echo } from '@laravel/echo-vue';
import { ChevronDown, ChevronLeft, ChevronRight, MessageCircle, Search, Send, Users, X } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

type ApiUser = {
    id: number | string;
    name?: string | null;
    firstname?: string | null;
    middlename?: string | null;
    lastname?: string | null;
    extname?: string | null;
    role?: string | null;
    office?: string | null;
    avatar?: string | null;
    active?: boolean | number | null;
};

type ApiUsersResponse = {
    data?: ApiUser[];
    next_page_url?: string | null;
};

type Contact = {
    id: number | string;
    name: string;
    role: string;
    avatar: string | null;
    online: boolean;
};

type LocalMessage = {
    id: string | number;
    body: string;
    mine: boolean;
    created_at: string;
};

type ConversationMeta = {
    contact_id: number;
    contact?: ApiUser | null;
    last_message: { body: string; mine: boolean; created_at: string } | null;
    unread_count: number;
};

type ConversationListResponse = {
    data?: ConversationMeta[];
    next_cursor?: string | null;
    has_more?: boolean;
};

const page = usePage();
const reverbEnabled = import.meta.env.VITE_REVERB_ENABLED !== 'false';
const authUserId = computed(() => {
    const auth = (page.props as Record<string, unknown>).auth as Record<string, unknown> | undefined;
    const user = auth?.user as Record<string, unknown> | undefined;
    return Number(user?.id ?? user?.userId ?? 0);
});

const isOpen = ref(false);
const messengerRootRef = ref<HTMLElement | null>(null);

onClickOutside(messengerRootRef, () => {
    if (isOpen.value) isOpen.value = false;
});

const onlineExpanded = ref(true);
const offlineExpanded = ref(false);
const loadingContacts = ref(false);
const loadingMoreContacts = ref(false);
const loadingConversations = ref(false);
const loadingMoreConversations = ref(false);
const loadingMessages = ref(false);
const sendingMessage = ref(false);
const error = ref<string | null>(null);
const contacts = ref<Contact[]>([]);
const nextContactsUrl = ref<string | null>('/api/utilities/users?per_page=60');
const nextConversationsCursor = ref<string | null>(null);
const activeConversationId = ref<number | string | null>(null);
const messageInput = ref('');
const searchQuery = ref('');
const messagesContainer = ref<HTMLElement | null>(null);
const contactsListContainer = ref<HTMLElement | null>(null);
const conversations = ref<Record<string, LocalMessage[]>>({});
const conversationActivityAt = ref<Record<string, string>>({});
const unreadByContact = ref<Record<string, number>>({});
const loadedConversations = ref<Set<string>>(new Set());
let contactsPollTimer: number | null = null;
let searchDebounceTimer: number | null = null;

const csrfToken = (): string => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta?.getAttribute('content') ?? '';
};

const filteredContacts = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (q === '') return contacts.value;
    return contacts.value.filter(
        (c) => c.name.toLowerCase().includes(q) || c.role.toLowerCase().includes(q),
    );
});

const sortedContacts = computed(() => [...filteredContacts.value].sort((a, b) => a.name.localeCompare(b.name)));

const contactsById = computed(() => {
    const byId = new Map<string, Contact>();
    for (const contact of contacts.value) {
        byId.set(String(contact.id), contact);
    }
    return byId;
});

const chattedContactIds = computed(() => {
    return new Set(
        Object.entries(conversations.value)
            .filter(([, messages]) => messages.length > 0)
            .map(([id]) => id),
    );
});

const conversationContacts = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    return Array.from(chattedContactIds.value)
        .map((id) => contactsById.value.get(id))
        .filter((contact): contact is Contact => {
            if (!contact) return false;
            if (q === '') return true;
            return contact.name.toLowerCase().includes(q) || contact.role.toLowerCase().includes(q);
        })
        .sort((a, b) => {
            const aUnread = unreadByContact.value[String(a.id)] ?? 0;
            const bUnread = unreadByContact.value[String(b.id)] ?? 0;
            const aHasUnread = aUnread > 0 ? 1 : 0;
            const bHasUnread = bUnread > 0 ? 1 : 0;
            if (aHasUnread !== bHasUnread) return bHasUnread - aHasUnread;

            const aActivity = Date.parse(conversationActivityAt.value[String(a.id)] ?? '1970-01-01T00:00:00.000Z');
            const bActivity = Date.parse(conversationActivityAt.value[String(b.id)] ?? '1970-01-01T00:00:00.000Z');
            if (aActivity !== bActivity) return bActivity - aActivity;
            return a.name.localeCompare(b.name);
        });
});

const onlineEmployees = computed(() =>
    sortedContacts.value.filter((contact) => contact.online && !chattedContactIds.value.has(String(contact.id))),
);

const offlineEmployees = computed(() =>
    sortedContacts.value.filter((contact) => !contact.online && !chattedContactIds.value.has(String(contact.id))),
);

const unreadButtonCount = computed(() =>
    Object.values(unreadByContact.value).reduce((sum, count) => sum + (count > 0 ? count : 0), 0),
);

const hasMoreContacts = computed(() => Boolean(nextContactsUrl.value));
const hasMoreConversations = computed(() => Boolean(nextConversationsCursor.value));

const selectedContact = computed(() => contacts.value.find((contact) => contact.id === activeConversationId.value) ?? null);

const selectedMessages = computed(() => {
    if (!activeConversationId.value) return [];
    return conversations.value[String(activeConversationId.value)] ?? [];
});

const getInitials = (name: string): string => {
    const words = name.split(' ').filter(Boolean);
    if (words.length === 0) return '??';
    if (words.length === 1) return words[0].slice(0, 2).toUpperCase();
    return `${words[0][0]}${words[1][0]}`.toUpperCase();
};

const formatTime = (value: string | null): string => {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    const now = new Date();
    const sameDay = date.toDateString() === now.toDateString();
    if (sameDay) {
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
};

const getName = (user: ApiUser): string => {
    if (typeof user.name === 'string' && user.name.trim() !== '') {
        return user.name.trim();
    }
    const parts = [user.firstname, user.middlename, user.lastname, user.extname]
        .filter((part): part is string => typeof part === 'string' && part.trim() !== '')
        .map((part) => part.trim());
    if (parts.length > 0) {
        return parts.join(' ');
    }
    return 'Unknown Employee';
};

const toContact = (user: ApiUser): Contact => ({
    id: user.id,
    name: getName(user),
    role: (user.role ?? user.office ?? 'Employee').toString(),
    avatar: user.avatar ?? null,
    online: Boolean(user.active),
});

const upsertContacts = (users: ApiUser[]) => {
    const byId = new Map<string, Contact>();
    for (const existing of contacts.value) {
        byId.set(String(existing.id), existing);
    }
    for (const user of users) {
        const mapped = toContact(user);
        const key = String(mapped.id);
        const previous = byId.get(key);
        byId.set(key, previous ? { ...previous, ...mapped } : mapped);
    }
    contacts.value = Array.from(byId.values());
};

const scrollMessagesToBottom = async () => {
    await nextTick();
    const container = messagesContainer.value;
    if (!container) return;
    container.scrollTop = container.scrollHeight;
};

const updateConversationActivity = (contactId: number | string, createdAt?: string | null) => {
    const parsed = createdAt ? new Date(createdAt) : new Date();
    const activityDate = Number.isNaN(parsed.getTime()) ? new Date() : parsed;
    conversationActivityAt.value[String(contactId)] = activityDate.toISOString();
};

const markConversationRead = async (contactId: number | string) => {
    const key = String(contactId);
    if ((unreadByContact.value[key] ?? 0) <= 0) return;

    unreadByContact.value[key] = 0;

    try {
        await fetch(`/api/messages/${contactId}/read`, {
            method: 'PATCH',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
    } catch {
        // Silently fail — will sync on next load
    }
};

const fetchContactsPage = async (
    url: string,
    options?: { showLoader?: boolean; append?: boolean },
) => {
    if (options?.append) {
        loadingMoreContacts.value = true;
    } else if (options?.showLoader) {
        loadingContacts.value = true;
    }
    error.value = null;

    try {
        const response = await fetch(url, {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (!response.ok) {
            throw new Error(`Failed to load employees (${response.status})`);
        }
        const payload = (await response.json()) as ApiUsersResponse;
        upsertContacts(payload.data ?? []);
        nextContactsUrl.value = payload.next_page_url ?? null;
    } catch (fetchError) {
        error.value = fetchError instanceof Error ? fetchError.message : 'Failed to load contacts.';
    } finally {
        if (options?.append) {
            loadingMoreContacts.value = false;
        } else if (options?.showLoader) {
            loadingContacts.value = false;
        }
    }
};

const loadInitialContacts = async () => {
    contacts.value = [];
    const params = new URLSearchParams({ per_page: '60' });
    const q = searchQuery.value.trim();
    if (q !== '') {
        params.set('search', q);
    }
    nextContactsUrl.value = `/api/utilities/users?${params.toString()}`;
    await fetchContactsPage(nextContactsUrl.value, { showLoader: true, append: false });
};

const loadNextContacts = async () => {
    if (!nextContactsUrl.value || loadingMoreContacts.value) return;
    const url = nextContactsUrl.value;
    await fetchContactsPage(url, { append: true });
};

const refreshContacts = async () => {
    const params = new URLSearchParams({ per_page: '60' });
    const q = searchQuery.value.trim();
    if (q !== '') {
        params.set('search', q);
    }
    await fetchContactsPage(`/api/utilities/users?${params.toString()}`);
};

const conversationListUrl = (cursor?: string | null): string => {
    const params = new URLSearchParams({ per_page: '25' });
    if (cursor) params.set('cursor', cursor);
    return `/api/messages/conversations?${params.toString()}`;
};

const loadConversationsList = async (options?: { reset?: boolean; append?: boolean; showLoader?: boolean }) => {
    if (options?.append) {
        if (!nextConversationsCursor.value || loadingMoreConversations.value) return;
        loadingMoreConversations.value = true;
    } else if (options?.showLoader) {
        loadingConversations.value = true;
    }

    if (options?.reset) {
        nextConversationsCursor.value = null;
    }

    const cursor = options?.append ? nextConversationsCursor.value : null;
    const endpoint = conversationListUrl(cursor);

    try {
        const response = await fetch(endpoint, {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (!response.ok) return;
        const payload = (await response.json()) as ConversationListResponse;
        const data = payload.data ?? [];

        for (const conv of data) {
            const key = String(conv.contact_id);
            if (conv.contact) {
                upsertContacts([conv.contact]);
            }
            unreadByContact.value[key] = Math.max(0, conv.unread_count ?? 0);
            if (conv.last_message) {
                updateConversationActivity(conv.contact_id, conv.last_message.created_at);
                if (!conversations.value[key] || conversations.value[key].length === 0) {
                    conversations.value[key] = [
                        {
                            id: `preview-${key}`,
                            body: conv.last_message.body,
                            mine: conv.last_message.mine,
                            created_at: conv.last_message.created_at,
                        },
                    ];
                }
            }
        }

        nextConversationsCursor.value = payload.next_cursor ?? null;
    } catch {
        // Silently fail
    } finally {
        if (options?.append) {
            loadingMoreConversations.value = false;
        } else if (options?.showLoader) {
            loadingConversations.value = false;
        }
    }
};

const loadNextConversations = async () => {
    await loadConversationsList({ append: true });
};

const loadMessages = async (contactId: number | string) => {
    const key = String(contactId);
    if (loadedConversations.value.has(key)) return;

    loadingMessages.value = true;
    try {
        const response = await fetch(`/api/messages/${contactId}`, {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (!response.ok) return;
        const data = (await response.json()) as LocalMessage[];
        conversations.value[key] = data;
        loadedConversations.value.add(key);
    } catch {
        // Silently fail
    } finally {
        loadingMessages.value = false;
    }
};

const ensureConversation = (contactId: number | string) => {
    const key = String(contactId);
    if (!conversations.value[key]) {
        conversations.value[key] = [];
    }
};

const selectContact = async (contactId: number | string) => {
    activeConversationId.value = contactId;
    ensureConversation(contactId);
    await loadMessages(contactId);
    await markConversationRead(contactId);
    await scrollMessagesToBottom();
};

const sendMessage = async () => {
    if (!activeConversationId.value || sendingMessage.value) return;
    const body = messageInput.value.trim();
    if (body === '') return;

    sendingMessage.value = true;
    const key = String(activeConversationId.value);

    try {
        const response = await fetch('/api/messages', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                receiver_id: Number(activeConversationId.value),
                body,
            }),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => null);
            throw new Error(errorData?.message ?? `Failed to send (${response.status})`);
        }

        const sent = (await response.json()) as LocalMessage;
        ensureConversation(activeConversationId.value);
        conversations.value[key] = [...conversations.value[key], sent];
        updateConversationActivity(activeConversationId.value, sent.created_at);
        messageInput.value = '';
        await scrollMessagesToBottom();
    } catch {
        // Could show a toast here
    } finally {
        sendingMessage.value = false;
    }
};

const pushIncomingMessage = async (senderId: number | string, body: string, id: number | string, createdAt: string) => {
    ensureConversation(senderId);
    const key = String(senderId);
    const incoming: LocalMessage = { id, body, mine: false, created_at: createdAt };

    const exists = conversations.value[key].some((m) => m.id === id);
    if (exists) return;

    conversations.value[key] = [...conversations.value[key], incoming];
    updateConversationActivity(senderId, createdAt);

    if (!isOpen.value || activeConversationId.value !== senderId) {
        unreadByContact.value[key] = (unreadByContact.value[key] ?? 0) + 1;
        return;
    }

    await markConversationRead(senderId);
    await scrollMessagesToBottom();
};

const onContactsListScroll = () => {
    const container = contactsListContainer.value;
    if (!container) return;
    const remaining = container.scrollHeight - container.scrollTop - container.clientHeight;
    if (remaining < 80) {
        void loadNextConversations();
        void loadNextContacts();
    }
};

const getLastMessagePreview = (contactId: string): string => {
    const msgs = conversations.value[contactId];
    if (!msgs || msgs.length === 0) return '';
    const last = msgs[msgs.length - 1];
    const prefix = last.mine ? 'You: ' : '';
    const text = last.body.length > 30 ? last.body.slice(0, 30) + '...' : last.body;
    return prefix + text;
};

const getLastMessageTime = (contactId: string): string => {
    const msgs = conversations.value[contactId];
    if (!msgs || msgs.length === 0) return '';
    return formatTime(msgs[msgs.length - 1].created_at);
};

const stopPolling = () => {
    if (contactsPollTimer) {
        window.clearInterval(contactsPollTimer);
        contactsPollTimer = null;
    }
};

const startPolling = () => {
    stopPolling();
    contactsPollTimer = window.setInterval(() => {
        if (isOpen.value) void refreshContacts();
    }, 30000);
};

const setupEchoListener = () => {
    if (!reverbEnabled || !authUserId.value) return;
    try {
        echo()
            .private(`messages.${authUserId.value}`)
            .listen('.MessageSent', (payload: { id: number; sender_id: number; body: string; created_at: string }) => {
                void pushIncomingMessage(payload.sender_id, payload.body, payload.id, payload.created_at);
            });
    } catch {
        // Reverb not available
    }
};

const teardownEchoListener = () => {
    if (!reverbEnabled || !authUserId.value) return;
    try {
        echo().private(`messages.${authUserId.value}`).stopListening('.MessageSent');
    } catch {
        // ignore
    }
};

watch(isOpen, async (opened) => {
    if (opened) {
        if (contacts.value.length === 0) {
            await loadInitialContacts();
        } else {
            await refreshContacts();
        }
        await loadConversationsList({ reset: true, showLoader: true });
        startPolling();
        await scrollMessagesToBottom();
        return;
    }
    stopPolling();
});

watch(activeConversationId, async (contactId) => {
    if (!contactId) return;
    ensureConversation(contactId);
    await loadMessages(contactId);
    await markConversationRead(contactId);
    await scrollMessagesToBottom();
});

watch(searchQuery, (value) => {
    if (!isOpen.value) return;

    if (searchDebounceTimer) {
        window.clearTimeout(searchDebounceTimer);
    }

    searchDebounceTimer = window.setTimeout(() => {
        const params = new URLSearchParams({ per_page: '60' });
        const q = value.trim();
        if (q !== '') {
            params.set('search', q);
        }

        nextContactsUrl.value = `/api/utilities/users?${params.toString()}`;
        void fetchContactsPage(nextContactsUrl.value, { showLoader: true, append: false });
    }, 300);
});

onMounted(() => {
    setupEchoListener();
});

onBeforeUnmount(() => {
    stopPolling();
    if (searchDebounceTimer) {
        window.clearTimeout(searchDebounceTimer);
        searchDebounceTimer = null;
    }
    teardownEchoListener();
});
</script>

<template>
    <div ref="messengerRootRef" class="fixed right-2 bottom-2 z-[70] flex flex-col-reverse items-end gap-2 sm:right-6 sm:bottom-6 sm:gap-3">
        <div class="pointer-events-auto">
            <button
                type="button"
                class="relative inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-lg transition hover:bg-slate-50"
                @click="isOpen = !isOpen"
            >
                <MessageCircle class="h-4 w-4" />
                <span>Messages</span>
                <span
                    v-if="!isOpen && unreadButtonCount > 0"
                    class="absolute -top-1 -right-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-semibold text-white"
                >
                    {{ unreadButtonCount > 99 ? '99+' : unreadButtonCount }}
                </span>
            </button>
        </div>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-2 opacity-0"
        >
            <section
                v-if="isOpen"
                class="pointer-events-auto flex h-[min(620px,calc(100dvh-5.5rem))] w-[calc(100vw-1rem)] max-w-[700px] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white text-slate-900 shadow-2xl sm:h-[620px] sm:w-[min(88vw,700px)]"
            >
                <header class="flex items-center border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <Users class="h-4 w-4 text-slate-500" />
                        <span>Employees</span>
                    </div>
                </header>

                <div class="min-h-0 flex flex-1 flex-col sm:flex-row">
                    <aside
                        class="min-h-0 w-full border-r border-slate-200 sm:w-[280px]"
                        :class="activeConversationId ? 'hidden sm:flex sm:flex-col' : 'flex flex-col'"
                    >
                        <!-- Search bar -->
                        <div class="border-b border-slate-200 px-3 py-2">
                            <div class="relative">
                                <Search class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search employees..."
                                    class="w-full rounded-lg border border-slate-200 bg-slate-50 py-1.5 pl-8 pr-8 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-400 focus:bg-white focus:ring-1 focus:ring-blue-100"
                                />
                                <button
                                    v-if="searchQuery"
                                    type="button"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                    @click="searchQuery = ''"
                                >
                                    <X class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>

                        <div ref="contactsListContainer" class="flex-1 overflow-y-auto px-3 py-3" @scroll.passive="onContactsListScroll">
                            <div v-if="loadingContacts" class="py-4 text-sm text-slate-500">Loading employees...</div>
                            <div v-else-if="error" class="py-4 text-sm text-rose-600">{{ error }}</div>
                            <template v-else>
                                <!-- No results -->
                                <div
                                    v-if="searchQuery && conversationContacts.length === 0 && onlineEmployees.length === 0 && offlineEmployees.length === 0"
                                    class="py-6 text-center text-sm text-slate-400"
                                >
                                    No employees found for "{{ searchQuery }}"
                                </div>

                                <!-- Conversations list -->
                                <div v-if="conversationContacts.length > 0" class="pb-3">
                                    <div class="py-2 text-left text-sm font-semibold text-slate-700">
                                        Chats - {{ conversationContacts.length }}
                                    </div>
                                    <ul class="space-y-1 pb-1">
                                        <li v-for="employee in conversationContacts" :key="`chat-${employee.id}`">
                                            <button
                                                type="button"
                                                class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left transition hover:bg-slate-100"
                                                :class="activeConversationId === employee.id ? 'bg-slate-100 ring-1 ring-slate-200' : ''"
                                                @click="void selectContact(employee.id)"
                                            >
                                                <div class="relative h-10 w-10 shrink-0 overflow-visible">
                                                    <div
                                                        class="h-full w-full overflow-hidden rounded-full text-white"
                                                        :class="employee.online ? 'bg-cyan-600/90' : 'bg-slate-400/90'"
                                                    >
                                                        <img
                                                            v-if="employee.avatar"
                                                            :src="employee.avatar"
                                                            :alt="employee.name"
                                                            class="h-full w-full object-cover"
                                                        />
                                                        <div v-else class="flex h-full w-full items-center justify-center text-xs font-semibold">
                                                            {{ getInitials(employee.name) }}
                                                        </div>
                                                    </div>
                                                    <span
                                                        class="absolute -right-0.5 -bottom-0.5 h-3 w-3 rounded-full border-2 border-white"
                                                        :class="employee.online ? 'bg-emerald-500' : 'bg-slate-300'"
                                                    />
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center justify-between">
                                                        <div class="truncate text-sm font-semibold text-slate-800">{{ employee.name }}</div>
                                                        <span class="ml-2 shrink-0 text-[10px] text-slate-400">{{ getLastMessageTime(String(employee.id)) }}</span>
                                                    </div>
                                                    <div class="truncate text-xs text-slate-500">{{ getLastMessagePreview(String(employee.id)) || employee.role }}</div>
                                                </div>
                                                <span
                                                    v-if="(unreadByContact[String(employee.id)] ?? 0) > 0"
                                                    class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-semibold text-white"
                                                >
                                                    {{
                                                        (unreadByContact[String(employee.id)] ?? 0) > 99
                                                            ? '99+'
                                                            : unreadByContact[String(employee.id)]
                                                    }}
                                                </span>
                                            </button>
                                        </li>
                                    </ul>
                                    <div v-if="loadingConversations || loadingMoreConversations" class="py-2 text-xs text-slate-500">
                                        Loading chats...
                                    </div>
                                    <button
                                        v-else-if="hasMoreConversations"
                                        type="button"
                                        class="mt-1 w-full rounded-md border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50"
                                        @click="void loadNextConversations()"
                                    >
                                        Load more chats
                                    </button>
                                </div>

                                <!-- Online employees -->
                                <button
                                    v-if="onlineEmployees.length > 0"
                                    type="button"
                                    class="flex w-full items-center justify-between py-2 text-left text-sm font-semibold text-slate-700"
                                    @click="onlineExpanded = !onlineExpanded"
                                >
                                    <span>Online - {{ onlineEmployees.length }}</span>
                                    <ChevronDown v-if="onlineExpanded" class="h-4 w-4" />
                                    <ChevronRight v-else class="h-4 w-4" />
                                </button>

                                <ul v-show="onlineExpanded && onlineEmployees.length > 0" class="space-y-1 pb-3">
                                    <li v-for="employee in onlineEmployees" :key="`online-${employee.id}`">
                                        <button
                                            type="button"
                                            class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left transition hover:bg-slate-100"
                                            :class="activeConversationId === employee.id ? 'bg-slate-100 ring-1 ring-slate-200' : ''"
                                            @click="void selectContact(employee.id)"
                                        >
                                            <div class="relative h-10 w-10 shrink-0 overflow-visible">
                                                <div class="h-full w-full overflow-hidden rounded-full bg-cyan-600/90 text-white">
                                                    <img
                                                        v-if="employee.avatar"
                                                        :src="employee.avatar"
                                                        :alt="employee.name"
                                                        class="h-full w-full object-cover"
                                                    />
                                                    <div v-else class="flex h-full w-full items-center justify-center text-xs font-semibold">
                                                        {{ getInitials(employee.name) }}
                                                    </div>
                                                </div>
                                                <span class="absolute -right-0.5 -bottom-0.5 h-3 w-3 rounded-full border-2 border-white bg-emerald-500" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="truncate text-sm font-semibold text-slate-800">{{ employee.name }}</div>
                                                <div class="truncate text-xs text-slate-500">{{ employee.role }}</div>
                                            </div>
                                        </button>
                                    </li>
                                </ul>

                                <!-- Offline employees -->
                                <button
                                    v-if="offlineEmployees.length > 0"
                                    type="button"
                                    class="flex w-full items-center justify-between border-t border-slate-200 py-2 text-left text-sm font-semibold text-slate-600"
                                    @click="offlineExpanded = !offlineExpanded"
                                >
                                    <span>Offline - {{ offlineEmployees.length }}</span>
                                    <ChevronDown v-if="offlineExpanded" class="h-4 w-4" />
                                    <ChevronRight v-else class="h-4 w-4" />
                                </button>

                                <ul v-show="offlineExpanded && offlineEmployees.length > 0" class="space-y-1">
                                    <li v-for="employee in offlineEmployees" :key="`offline-${employee.id}`">
                                        <button
                                            type="button"
                                            class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left transition hover:bg-slate-100"
                                            :class="activeConversationId === employee.id ? 'bg-slate-100 ring-1 ring-slate-200' : ''"
                                            @click="void selectContact(employee.id)"
                                        >
                                            <div class="h-10 w-10 shrink-0 overflow-hidden rounded-full bg-slate-200 text-slate-500">
                                                <img
                                                    v-if="employee.avatar"
                                                    :src="employee.avatar"
                                                    :alt="employee.name"
                                                    class="h-full w-full object-cover grayscale"
                                                />
                                                <div v-else class="flex h-full w-full items-center justify-center text-xs font-semibold">
                                                    {{ getInitials(employee.name) }}
                                                </div>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="truncate text-sm font-semibold text-slate-700">{{ employee.name }}</div>
                                                <div class="truncate text-xs text-slate-500">{{ employee.role }}</div>
                                            </div>
                                        </button>
                                    </li>
                                </ul>

                                <div v-if="loadingMoreContacts" class="py-3 text-center text-xs text-slate-500">
                                    Loading more employees...
                                </div>
                                <button
                                    v-else-if="hasMoreContacts"
                                    type="button"
                                    class="mt-2 w-full rounded-md border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50"
                                    @click="void loadNextContacts()"
                                >
                                    Load more employees
                                </button>
                            </template>
                        </div>
                    </aside>

                    <section
                        class="min-h-0 flex-1 flex-col bg-slate-50"
                        :class="activeConversationId ? 'flex' : 'hidden sm:flex'"
                    >
                        <header class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3">
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 sm:hidden"
                                    @click="activeConversationId = null"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                </button>
                                <div v-if="selectedContact" class="flex items-center gap-3">
                                    <div class="relative h-9 w-9 shrink-0 overflow-visible">
                                        <div class="h-full w-full overflow-hidden rounded-full bg-cyan-600/90 text-white">
                                            <img
                                                v-if="selectedContact.avatar"
                                                :src="selectedContact.avatar"
                                                :alt="selectedContact.name"
                                                class="h-full w-full object-cover"
                                            />
                                            <div v-else class="flex h-full w-full items-center justify-center text-xs font-semibold">
                                                {{ getInitials(selectedContact.name) }}
                                            </div>
                                        </div>
                                        <span
                                            class="absolute -right-0.5 -bottom-0.5 h-3 w-3 rounded-full border-2 border-white"
                                            :class="selectedContact.online ? 'bg-emerald-500' : 'bg-slate-300'"
                                        />
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-slate-800">{{ selectedContact.name }}</div>
                                        <div class="text-xs text-slate-500">{{ selectedContact.online ? 'Online' : 'Offline' }}</div>
                                    </div>
                                </div>
                                <div v-else class="text-sm text-slate-500">Select an employee</div>
                            </div>
                        </header>

                        <div ref="messagesContainer" class="min-h-0 flex-1 overflow-y-auto px-3 py-3 sm:px-4 sm:py-4">
                            <div v-if="!selectedContact" class="flex h-full items-center justify-center text-sm text-slate-400">
                                Choose an employee from the list to start messaging.
                            </div>
                            <div v-else-if="loadingMessages" class="flex h-full items-center justify-center">
                                <div class="text-sm text-slate-400">Loading messages...</div>
                            </div>
                            <div v-else-if="selectedMessages.length === 0" class="flex h-full items-center justify-center">
                                <div class="text-center">
                                    <div class="text-sm text-slate-500">No messages yet.</div>
                                    <div class="mt-1 text-xs text-slate-400">Send a message to start the conversation.</div>
                                </div>
                            </div>
                            <ul v-else class="space-y-2.5 sm:space-y-3">
                                <li
                                    v-for="message in selectedMessages"
                                    :key="message.id"
                                    class="flex"
                                    :class="message.mine ? 'justify-end' : 'justify-start'"
                                >
                                    <div
                                        class="max-w-[85%] rounded-2xl px-3 py-2 text-sm shadow-sm sm:max-w-[80%]"
                                        :class="
                                            message.mine
                                                ? 'bg-blue-600 text-white rounded-br-md'
                                                : 'bg-white text-slate-800 border border-slate-200 rounded-bl-md'
                                        "
                                    >
                                        <p class="whitespace-pre-wrap break-words">{{ message.body }}</p>
                                        <p class="mt-1 text-[10px]" :class="message.mine ? 'text-blue-100' : 'text-slate-400'">
                                            {{ formatTime(message.created_at) }}
                                        </p>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <form class="border-t border-slate-200 bg-white p-2.5 sm:p-3" @submit.prevent="void sendMessage()">
                            <div class="flex items-end gap-2">
                                <textarea
                                    v-model="messageInput"
                                    rows="2"
                                    class="max-h-28 min-h-[42px] flex-1 resize-y rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    placeholder="Type a message..."
                                    :disabled="!selectedContact || sendingMessage"
                                    @keydown.enter.exact.prevent="void sendMessage()"
                                />
                                <button
                                    type="submit"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                                    :disabled="!selectedContact || sendingMessage || messageInput.trim() === ''"
                                >
                                    <Send class="h-4 w-4" />
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </section>
        </Transition>
    </div>
</template>
