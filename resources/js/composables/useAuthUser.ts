import { echo } from '@laravel/echo-vue';
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, type Ref } from 'vue';

type AuthUser = Record<string, unknown> | null;

let broadcastUserRef: Ref<AuthUser> | null = null;
let listenerSetup = false;

/**
 * Reactive auth user that updates in real time when profile is updated (e.g. avatar)
 * via Reverb broadcast. Use this in header and sidebar so they reflect profile changes
 * without a full page reload.
 */
export function useAuthUser() {
    const page = usePage();

    if (broadcastUserRef === null) {
        broadcastUserRef = ref<AuthUser>(null);
    }

    const authUserId = computed(() => {
        const u = page.props.auth?.user as Record<string, unknown> | undefined;
        return u?.id ?? u?.userId ?? null;
    });

    onMounted(() => {
        if (listenerSetup) return;
        const reverbEnabled = import.meta.env.VITE_REVERB_ENABLED !== 'false';
        if (!reverbEnabled) return;

        const id = authUserId.value;
        if (id == null || id === '') return;

        listenerSetup = true;
        try {
            echo()
                .private('App.Models.User.' + id)
                .listen('.AuthUserProfileUpdated', (e: { user?: AuthUser }) => {
                    if (broadcastUserRef && e?.user) {
                        broadcastUserRef.value = e.user;
                    }
                });
        } catch {
            listenerSetup = false;
        }
    });

    return computed(() => broadcastUserRef?.value ?? (page.props.auth?.user as AuthUser) ?? null);
}
