<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { LogOut, User as UserIcon } from '@lucide/vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';

type Props = {
    user: User;
};

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-3 px-3 py-3 text-left text-sm">
            <UserInfo
                :user="user"
                :show-email="true"
                avatar-class="size-10 rounded-full"
                avatar-fallback-class="rounded-full bg-[#f1f3f4] text-[#5f6368] dark:bg-[#3c4043] dark:text-[#e8eaed]"
            />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator class="mx-2 my-2 bg-[#e8eaed] dark:bg-[#5f6368]" />
    <DropdownMenuGroup>
        <DropdownMenuItem
            :as-child="true"
            class="rounded-full px-3 py-2.5 text-[13px] text-[#3c4043] transition-colors focus:bg-[#f1f3f4] focus:text-[#202124] dark:text-[#e8eaed] dark:focus:bg-[#3c4043] dark:focus:text-white"
        >
            <Link class="flex w-full cursor-pointer items-center" :href="edit()" prefetch>
                <UserIcon class="mr-2 h-4 w-4" />
                Profile
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem
            :as-child="true"
            class="rounded-full px-3 py-2.5 text-[13px] text-[#3c4043] transition-colors focus:bg-[#f1f3f4] focus:text-[#202124] dark:text-[#e8eaed] dark:focus:bg-[#3c4043] dark:focus:text-white"
        >
        <Link
            class="flex w-full cursor-pointer items-center"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Sign out
        </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
</template>
