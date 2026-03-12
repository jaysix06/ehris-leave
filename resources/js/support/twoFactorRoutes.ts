import type { RouteDefinition, RouteFormDefinition, RouteQueryOptions } from '@/wayfinder';
import { queryParams } from '@/wayfinder';

const withForm = <TMethod extends 'get' | 'post' | 'delete'>(
    route: ((options?: RouteQueryOptions) => RouteDefinition<TMethod>) & {
        url: (options?: RouteQueryOptions) => string;
    },
    method: TMethod,
): ((options?: RouteQueryOptions) => RouteDefinition<TMethod>) & {
    url: (options?: RouteQueryOptions) => string;
    form: ((options?: RouteQueryOptions) => RouteFormDefinition<TMethod>) & {
        [K in TMethod]: (options?: RouteQueryOptions) => RouteFormDefinition<TMethod>;
    };
} => {
    const form = ((options?: RouteQueryOptions): RouteFormDefinition<TMethod> => ({
        action: route.url(options),
        method,
    })) as ((options?: RouteQueryOptions) => RouteFormDefinition<TMethod>) & {
        [K in TMethod]: (options?: RouteQueryOptions) => RouteFormDefinition<TMethod>;
    };

    form[method] = (options?: RouteQueryOptions): RouteFormDefinition<TMethod> => ({
        action: route.url(options),
        method,
    });

    return Object.assign(route, { form });
};

const makeGetRoute = (url: string) => {
    const route = ((options?: RouteQueryOptions): RouteDefinition<'get'> => ({
        url: route.url(options),
        method: 'get',
    })) as ((options?: RouteQueryOptions) => RouteDefinition<'get'>) & {
        url: (options?: RouteQueryOptions) => string;
    };

    route.url = (options?: RouteQueryOptions): string => url + queryParams(options);

    return withForm(route, 'get');
};

const makePostRoute = (url: string) => {
    const route = ((options?: RouteQueryOptions): RouteDefinition<'post'> => ({
        url: route.url(options),
        method: 'post',
    })) as ((options?: RouteQueryOptions) => RouteDefinition<'post'>) & {
        url: (options?: RouteQueryOptions) => string;
    };

    route.url = (options?: RouteQueryOptions): string => url + queryParams(options);

    return withForm(route, 'post');
};

const makeDeleteRoute = (url: string) => {
    const route = ((options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
        url: route.url(options),
        method: 'delete',
    })) as ((options?: RouteQueryOptions) => RouteDefinition<'delete'>) & {
        url: (options?: RouteQueryOptions) => string;
    };

    route.url = (options?: RouteQueryOptions): string => url + queryParams(options);

    return withForm(route, 'delete');
};

export const show = makeGetRoute('/settings/two-factor');
export const enable = makePostRoute('/user/two-factor-authentication');
export const disable = makeDeleteRoute('/user/two-factor-authentication');
export const confirm = makePostRoute('/user/confirmed-two-factor-authentication');
export const qrCode = makeGetRoute('/user/two-factor-qr-code');
export const secretKey = makeGetRoute('/user/two-factor-secret-key');
export const recoveryCodes = makeGetRoute('/user/two-factor-recovery-codes');
export const regenerateRecoveryCodes = makePostRoute('/user/two-factor-recovery-codes');
