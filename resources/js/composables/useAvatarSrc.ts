import type { MaybeRefOrGetter } from 'vue';
import { computed, toValue } from 'vue';

/**
 * Resolve avatar URL for header/sidebar/profile so that:
 * - IdCard upload (selfservice/idcard): path like "uploads/20856/20856.jpg" → "/uploads/20856/20856.jpg"
 * - Settings profile upload: filename like "72_123.jpg" (stored in storage/avatars) → "/storage/avatars/72_123.jpg"
 * - Full URLs, /, data:, blob: passed through
 */
export function useAvatarSrc(avatar: MaybeRefOrGetter<string | null | undefined>) {
    return computed(() => {
        const raw = toValue(avatar);
        if (typeof raw !== 'string') return null;
        const s = raw.trim();
        if (s === '') return null;

        const cleaned = s.split('?')[0]?.split('#')[0] ?? '';
        const normalizedName = cleaned.split('/').pop()?.toLowerCase() ?? '';

        if (normalizedName === 'avatar-default.jpg' || cleaned.toLowerCase().endsWith('/avatar-default.jpg')) {
            return '/storage/avatars/avatar-default.jpg';
        }

        if (/^(https?:)?\/\//i.test(cleaned) || cleaned.startsWith('data:') || cleaned.startsWith('blob:')) {
            return cleaned;
        }
        if (cleaned.startsWith('/')) {
            return cleaned;
        }

        // Path under public (e.g. IdCard: "uploads/20856/20856.jpg")
        if (cleaned.includes('/')) {
            return `/${cleaned}`;
        }

        // Plain filename (Settings profile: stored in storage/avatars)
        return `/storage/avatars/${cleaned}`;
    });
}
