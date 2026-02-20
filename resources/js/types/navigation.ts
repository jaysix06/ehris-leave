import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export type BreadcrumbItem = {
    title: string;
    href?: string;
};

export type NavItem = {
    title: string;
    href?: NonNullable<InertiaLinkProps['href']>;
    children?: NavItem[];
    icon?: LucideIcon;
    isActive?: boolean;
};
