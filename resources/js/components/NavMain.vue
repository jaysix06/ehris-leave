<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { type NavItem } from '@/types';
import { ChevronRight } from 'lucide-vue-next';

const props = defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl, currentUrl } = useCurrentUrl();
const dropdownOpen = ref<Record<string, boolean>>({});

const itemHasChildren = (item: NavItem) => Boolean(item.children?.length);
const itemHasActiveChild = (item: NavItem) =>
    item.children?.some((child) => (child.href ? isSidebarActive(child.href) : false)) ?? false;

const isSidebarActive = (href: string): boolean => {
    if (isCurrentUrl(href)) return true;

    const current = currentUrl.value;
    if (!href || href === '/') return current === '/';
    return current === href || current.startsWith(`${href}/`);
};

const dropdownKey = (item: NavItem) => item.title;
const isDropdownOpen = (item: NavItem): boolean =>
    itemHasActiveChild(item) || Boolean(dropdownOpen.value[dropdownKey(item)]);

const setDropdownOpen = (item: NavItem, open: boolean): void => {
    const key = dropdownKey(item);
    // Keep active section expanded.
    if (itemHasActiveChild(item)) {
        dropdownOpen.value[key] = true;
        return;
    }
    dropdownOpen.value[key] = open;
};

watch(
    currentUrl,
    () => {
        for (const item of props.items) {
            if (itemHasChildren(item) && itemHasActiveChild(item)) {
                dropdownOpen.value[dropdownKey(item)] = true;
            }
        }
    },
    { immediate: true },
);
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Main Navigation</SidebarGroupLabel>
        <SidebarMenu class="space-y-2">
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <template v-if="!itemHasChildren(item) && item.href">
                    <SidebarMenuButton
                        as-child
                        :is-active="isSidebarActive(item.href)"
                        :tooltip="item.title"
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </template>

                <Collapsible
                    v-else
                    :open="isDropdownOpen(item)"
                    @update:open="(open) => setDropdownOpen(item, open)"
                >
                    <CollapsibleTrigger as-child>
                        <SidebarMenuButton
                            class="group/dropdown-trigger"
                            :is-active="itemHasActiveChild(item)"
                            :tooltip="item.title"
                        >
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                            <ChevronRight
                                class="ml-auto size-4 transition-transform duration-200 group-data-[state=open]/dropdown-trigger:rotate-90 group-data-[collapsible=icon]:hidden"
                            />
                        </SidebarMenuButton>
                    </CollapsibleTrigger>

                    <CollapsibleContent>
                        <SidebarMenuSub class="space-y-1">
                            <SidebarMenuSubItem
                                v-for="child in item.children"
                                :key="`${item.title}-${child.title}`"
                            >
                                <SidebarMenuSubButton
                                    v-if="child.href"
                                    as-child
                                    :is-active="isSidebarActive(child.href)"
                                >
                                    <Link :href="child.href">
                                        <component :is="child.icon" v-if="child.icon" />
                                        <span>{{ child.title }}</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        </SidebarMenuSub>
                    </CollapsibleContent>
                </Collapsible>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
