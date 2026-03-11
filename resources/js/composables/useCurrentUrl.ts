import type { InertiaLinkProps } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import type { ComputedRef, DeepReadonly } from 'vue';
import { computed, readonly } from 'vue';
import { toUrl } from '@/lib/utils';

export type UseCurrentUrlReturn = {
    currentUrl: DeepReadonly<ComputedRef<string>>;
    isCurrentUrl: (
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        currentUrl?: string,
    ) => boolean;
    whenCurrentUrl: <T, F = null>(
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        ifTrue: T,
        ifFalse?: F,
    ) => T | F;
};

const page = usePage();
const currentUrlReactive = computed(() => {
    const u = new URL(page.url, window?.location.origin);
    return u.pathname + u.search;
});

export function useCurrentUrl(): UseCurrentUrlReturn {
    function isCurrentUrl(
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        currentUrl?: string,
    ) {
        const urlToCompare = currentUrl ?? currentUrlReactive.value;
        const urlString = toUrl(urlToCheck);

        if (!urlString.startsWith('http')) {
            if (urlToCompare === '/my-details' && urlString === '/my-details?section=official-info') {
                return true;
            }
            return urlString === urlToCompare;
        }

        try {
            const absoluteUrl = new URL(urlString);

            return absoluteUrl.pathname + absoluteUrl.search === urlToCompare;
        } catch {
            return false;
        }
    }

    function whenCurrentUrl(
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        ifTrue: any,
        ifFalse: any = null,
    ) {
        return isCurrentUrl(urlToCheck) ? ifTrue : ifFalse;
    }

    return {
        currentUrl: readonly(currentUrlReactive),
        isCurrentUrl,
        whenCurrentUrl,
    };
}
