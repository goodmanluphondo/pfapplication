<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, Moon, Sun, User as UserIcon } from '@lucide/vue';
import { computed } from 'vue';
import { home, login, register } from '@/routes';
import { index as history } from '@/routes/generations';
import AppLogoLockup from '@/components/AppLogoLockup.vue';
import { useAppearance } from '@/composables/useAppearance';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const { resolvedAppearance, updateAppearance } = useAppearance();
const isDarkMode = computed(() => resolvedAppearance.value === 'dark');
const userName = computed(() =>
    [user.value?.first_name, user.value?.last_name].filter(Boolean).join(' '),
);

function toggleAppearance(): void {
    updateAppearance(isDarkMode.value ? 'light' : 'dark');
}
</script>

<template>
    <header
        class="absolute inset-x-0 top-0 z-20 flex h-16 items-center justify-between gap-5 px-6 text-[13px]"
    >
        <Link
            :href="home()"
            :aria-label="`${page.props.name} home`"
            class="rounded-xl focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#5f6368]"
        >
            <AppLogoLockup />
        </Link>

        <div class="flex items-center gap-3">
            <Link
                v-if="user"
                :href="history()"
                class="px-2 text-[#202124] hover:underline focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-[#5f6368] dark:text-[#e8eaed]"
            >
                History
            </Link>

            <button
                :aria-label="
                    isDarkMode ? 'Switch to light mode' : 'Switch to dark mode'
                "
                :aria-pressed="isDarkMode"
                class="flex size-10 items-center justify-center rounded-full text-[#5f6368] transition hover:bg-[#f1f3f4] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5f6368] dark:text-[#bdc1c6] dark:hover:bg-[#3c4043]"
                type="button"
                @click="toggleAppearance"
            >
                <Sun v-if="isDarkMode" aria-hidden="true" class="size-5" />
                <Moon v-else aria-hidden="true" class="size-5" />
            </button>

            <DropdownMenu v-if="user">
                <DropdownMenuTrigger :as-child="true">
                    <button
                        :aria-label="`Open account menu for ${userName}`"
                        class="flex h-10 w-16 items-center justify-center gap-1 rounded-full bg-[#f1f3f4] p-1 text-[#5f6368] transition hover:bg-[#f1f3f4] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5f6368] dark:bg-[#3c4043] dark:text-[#e8eaed] dark:hover:bg-[#3c4043]"
                        type="button"
                    >
                        <Avatar
                            class="size-8 shrink-0 overflow-hidden rounded-full"
                        >
                            <AvatarImage
                                v-if="user.avatar"
                                :src="user.avatar"
                                :alt="userName"
                            />
                            <AvatarFallback
                                class="rounded-full bg-[#e8eaed] text-xs font-semibold text-[#202124] dark:bg-[#5f6368] dark:text-[#f8f9fa]"
                            >
                                <UserIcon aria-hidden="true" class="size-4" />
                            </AvatarFallback>
                        </Avatar>
                        <ChevronDown
                            aria-hidden="true"
                            class="size-4 shrink-0"
                        />
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    align="end"
                    :side-offset="8"
                    class="w-72 rounded-[20px] border-[#dadce0] bg-white p-2 text-[#202124] shadow-[0_8px_28px_rgba(60,64,67,0.2)] dark:border-[#5f6368] dark:bg-[#303134] dark:text-[#e8eaed]"
                >
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
            <div v-else class="flex items-center gap-5">
                <Link
                    :href="login()"
                    class="text-[#202124] hover:underline focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-[#5f6368] dark:text-[#e8eaed]"
                >
                    Log in
                </Link>
                <Link
                    :href="register()"
                    class="rounded-full bg-[#202124] px-5 py-2.5 font-medium text-white transition hover:bg-[#3c4043] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5f6368] dark:bg-[#e8eaed] dark:text-[#202124] dark:hover:bg-white"
                >
                    Register
                </Link>
            </div>
        </div>
    </header>
</template>
