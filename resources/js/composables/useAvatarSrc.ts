import type { MaybeRefOrGetter } from 'vue';
import { computed, toValue } from 'vue';

/**
 * Resolve avatar URL for header/sidebar/profile.
 * Prefer avatar_url from the User model (e.g. "/avatars/72_123.jpg"); otherwise build from avatar filename.
 * - User.avatar_url (from model): path like "/avatars/72_123.jpg" → used as-is (route serves or redirects to default)
 * - IdCard: path like "uploads/20856/20856.jpg" → "/uploads/20856/20856.jpg"
 * - Plain filename (legacy): "72_123.jpg" → "/avatars/72_123.jpg"
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
            return '/avatar-default.jpg';
        }

        if (/^(https?:)?\/\//i.test(cleaned) || cleaned.startsWith('data:') || cleaned.startsWith('blob:')) {
            return cleaned;
        }
        if (cleaned.startsWith('/')) {
            return cleaned;
        }

        if (cleaned.includes('/')) {
            return `/${cleaned}`;
        }

        return `/avatars/${cleaned}`;
    });
}
