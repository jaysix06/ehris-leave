<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';

type Props = {
    user: User;
    showEmail?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});

const { getInitials } = useInitials();

const avatarSrc = computed(() => {
    const avatar = props.user.avatar;
    if (typeof avatar !== 'string') return null;

    const s = avatar.trim();
    if (s === '') return null;

    const cleaned = s.split('?')[0]?.split('#')[0] ?? '';
    const normalizedName = cleaned.split('/').pop()?.toLowerCase() ?? '';
    if (normalizedName === 'avatar-default.jpg') return null;

    if (/^(https?:)?\/\//i.test(s) || s.startsWith('/') || s.startsWith('data:') || s.startsWith('blob:')) {
        return s;
    }

    return `/${s}`;
});

const showAvatar = computed(() => avatarSrc.value !== null);
</script>

<template>
    <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
        <AvatarImage v-if="showAvatar" :src="avatarSrc!" :alt="user.name" />
        <AvatarFallback class="rounded-lg text-black dark:text-white">
            {{ getInitials(user.name) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">{{ user.name }}</span>
        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">{{
            user.email
        }}</span>
    </div>
</template>
