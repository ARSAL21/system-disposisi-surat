<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Lock } from '@lucide/vue';
import { toast } from 'vue-sonner';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useTwoFactorWalkthrough } from '@/composables/useTwoFactorWalkthrough';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
    label?: string;
}>();

const { isCurrentUrl } = useCurrentUrl();
const { startTour } = useTwoFactorWalkthrough();

const handleLockedItemClick = (item: NavItem) => {
    toast.warning(`Menu ${item.title} terkunci.`, {
        description:
            item.lockReason ||
            'Aktifkan Autentikasi Dua Faktor (2FA) terlebih dahulu untuk membuka akses operasional.',
    });
    startTour();
};
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>{{ label || 'Navigasi' }}</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    v-if="item.isLocked"
                    type="button"
                    class="group relative flex w-full cursor-pointer items-center justify-between opacity-75 transition-colors hover:bg-amber-500/10 hover:text-amber-700 dark:hover:text-amber-300"
                    :tooltip="`${item.title} (Akses Terkunci: Wajib 2FA)`"
                    @click="handleLockedItemClick(item)"
                >
                    <div class="flex min-w-0 items-center gap-2">
                        <component
                            :is="item.icon"
                            class="size-4 shrink-0 text-muted-foreground group-hover:text-amber-600 dark:group-hover:text-amber-400"
                        />
                        <span class="truncate">{{ item.title }}</span>
                    </div>
                    <Lock
                        class="size-3.5 shrink-0 text-amber-500/80 transition-transform group-hover:scale-110"
                    />
                </SidebarMenuButton>

                <SidebarMenuButton
                    v-else
                    as-child
                    :is-active="item.isActive ?? isCurrentUrl(item.href)"
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
