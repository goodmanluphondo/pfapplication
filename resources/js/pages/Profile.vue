<script setup lang="ts">
import { Form, Head, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import ProfileController from "@/actions/App/Http/Controllers/Settings/ProfileController";
import DeleteUser from "@/components/DeleteUser.vue";
import AppFooter from "@/components/AppFooter.vue";
import AppHeader from "@/components/AppHeader.vue";
import InputError from "@/components/InputError.vue";
import ManagePasskeys from "@/components/ManagePasskeys.vue";
import PasswordInput from "@/components/PasswordInput.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import type { Passkey } from "@/types/auth";

type AccountTab = "personal-information" | "password-management" | "passkey" | "delete-account";

const tabs: { id: AccountTab; label: string }[] = [
    { id: "personal-information", label: "Personal information" },
    { id: "password-management", label: "Password management" },
    { id: "passkey", label: "Passkey" },
    { id: "delete-account", label: "Delete account" },
];

const props = defineProps<{
    passwordRules: string;
    canManagePasskeys: boolean;
    passkeys: Passkey[];
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const activeTab = ref<AccountTab>("personal-information");

function handleTabKeydown(event: KeyboardEvent, currentIndex: number): void {
    let nextIndex: number;

    if (event.key === "ArrowRight") {
        nextIndex = (currentIndex + 1) % tabs.length;
    } else if (event.key === "ArrowLeft") {
        nextIndex = (currentIndex - 1 + tabs.length) % tabs.length;
    } else if (event.key === "Home") {
        nextIndex = 0;
    } else if (event.key === "End") {
        nextIndex = tabs.length - 1;
    } else {
        return;
    }

    event.preventDefault();
    activeTab.value = tabs[nextIndex].id;
    document.getElementById(`account-tab-${tabs[nextIndex].id}`)?.focus();
}
</script>

<template>
    <Head title="Profile" />

    <main
        class="relative flex min-h-screen flex-col bg-white text-[#202124] dark:bg-[#202124] dark:text-[#e8eaed]"
    >
        <AppHeader />

        <section class="flex flex-1 flex-col px-5 pb-24 pt-24">
            <div class="mx-auto w-full max-w-4xl space-y-8">
                <header class="space-y-2">
                    <h1
                        class="text-[clamp(1.25rem,3vw,1.75rem)] font-medium leading-tight tracking-tight text-[#202124] dark:text-[#f8f9fa]"
                    >
                        Profile
                    </h1>
                    <p class="text-sm text-[#5f6368] dark:text-[#bdc1c6]">
                        Manage your personal information and account access.
                    </p>
                </header>

                <div
                    class="-mx-4 overflow-x-auto border-b border-[#dadce0] px-4 dark:border-[#5f6368] sm:mx-0 sm:px-0"
                >
                    <div class="flex min-w-max gap-2" role="tablist" aria-label="Profile settings">
                        <button
                            v-for="(tab, index) in tabs"
                            :id="`account-tab-${tab.id}`"
                            :key="tab.id"
                            :aria-controls="`account-panel-${tab.id}`"
                            :aria-selected="activeTab === tab.id"
                            :tabindex="activeTab === tab.id ? 0 : -1"
                            class="relative rounded-t-xl px-4 py-3 text-sm font-medium text-[#5f6368] transition-colors hover:bg-[#f1f3f4] hover:text-[#202124] focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-[#5f6368] dark:text-[#bdc1c6] dark:hover:bg-[#303134] dark:hover:text-[#e8eaed]"
                            :class="
                                activeTab === tab.id
                                    ? 'text-[#202124] after:absolute after:inset-x-3 after:bottom-0 after:h-0.5 after:rounded-full after:bg-[#1a73e8] dark:text-[#e8eaed] dark:after:bg-[#8ab4f8]'
                                    : ''
                            "
                            role="tab"
                            type="button"
                            @click="activeTab = tab.id"
                            @keydown="handleTabKeydown($event, index)"
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                </div>

                <section
                    v-if="activeTab === 'personal-information'"
                    id="account-panel-personal-information"
                    aria-labelledby="account-tab-personal-information"
                    class="max-w-2xl space-y-6"
                    role="tabpanel"
                    tabindex="0"
                >
                    <div class="space-y-1">
                        <h2 class="text-base font-medium text-[#202124] dark:text-[#e8eaed]">
                            Personal information
                        </h2>
                        <p class="text-sm text-[#5f6368] dark:text-[#bdc1c6]">
                            Update your first name, last name and email address.
                        </p>
                    </div>

                    <Form
                        v-bind="ProfileController.update.form()"
                        class="space-y-6"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="first_name">First name</Label>
                            <Input
                                id="first_name"
                                class="mt-1 block w-full rounded-2xl border-[#dadce0] bg-white dark:border-[#5f6368] dark:bg-[#303134]"
                                name="first_name"
                                :default-value="user.first_name"
                                required
                                autocomplete="given-name"
                                placeholder="First name"
                            />
                            <InputError class="mt-2" :message="errors.first_name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="last_name">Last name</Label>
                            <Input
                                id="last_name"
                                class="mt-1 block w-full rounded-2xl border-[#dadce0] bg-white dark:border-[#5f6368] dark:bg-[#303134]"
                                name="last_name"
                                :default-value="user.last_name"
                                required
                                autocomplete="family-name"
                                placeholder="Last name"
                            />
                            <InputError class="mt-2" :message="errors.last_name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">Email address</Label>
                            <Input
                                id="email"
                                type="email"
                                class="mt-1 block w-full rounded-2xl border-[#dadce0] bg-white dark:border-[#5f6368] dark:bg-[#303134]"
                                name="email"
                                :default-value="user.email"
                                required
                                autocomplete="username"
                                placeholder="Email address"
                            />
                            <InputError class="mt-2" :message="errors.email" />
                        </div>

                        <Button
                            class="rounded-full px-5"
                            :disabled="processing"
                            data-test="update-profile-button"
                        >
                            Save changes
                        </Button>
                    </Form>
                </section>

                <section
                    v-else-if="activeTab === 'password-management'"
                    id="account-panel-password-management"
                    aria-labelledby="account-tab-password-management"
                    class="max-w-2xl space-y-6"
                    role="tabpanel"
                    tabindex="0"
                >
                    <div class="space-y-1">
                        <h2 class="text-base font-medium text-[#202124] dark:text-[#e8eaed]">
                            Password management
                        </h2>
                        <p class="text-sm text-[#5f6368] dark:text-[#bdc1c6]">
                            Choose a strong password to protect your account.
                        </p>
                    </div>

                    <Form
                        v-bind="ProfileController.updatePassword.form()"
                        :options="{ preserveScroll: true }"
                        reset-on-success
                        :reset-on-error="['password', 'password_confirmation', 'current_password']"
                        class="space-y-6"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="current_password">Current password</Label>
                            <PasswordInput
                                id="current_password"
                                name="current_password"
                                class="mt-1 block w-full rounded-2xl border-[#dadce0] bg-white dark:border-[#5f6368] dark:bg-[#303134]"
                                autocomplete="current-password"
                                placeholder="Current password"
                            />
                            <InputError :message="errors.current_password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password">New password</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                class="mt-1 block w-full rounded-2xl border-[#dadce0] bg-white dark:border-[#5f6368] dark:bg-[#303134]"
                                autocomplete="new-password"
                                placeholder="New password"
                                :passwordrules="props.passwordRules"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password_confirmation">Confirm password</Label>
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                class="mt-1 block w-full rounded-2xl border-[#dadce0] bg-white dark:border-[#5f6368] dark:bg-[#303134]"
                                autocomplete="new-password"
                                placeholder="Confirm password"
                                :passwordrules="props.passwordRules"
                            />
                            <InputError :message="errors.password_confirmation" />
                        </div>

                        <Button
                            class="rounded-full px-5"
                            :disabled="processing"
                            data-test="update-password-button"
                        >
                            Update password
                        </Button>
                    </Form>
                </section>

                <section
                    v-else-if="activeTab === 'passkey'"
                    id="account-panel-passkey"
                    aria-labelledby="account-tab-passkey"
                    class="max-w-2xl space-y-6"
                    role="tabpanel"
                    tabindex="0"
                >
                    <ManagePasskeys
                        :can-manage-passkeys="props.canManagePasskeys"
                        :passkeys="props.passkeys"
                    />
                </section>

                <section
                    v-else
                    id="account-panel-delete-account"
                    aria-labelledby="account-tab-delete-account"
                    class="max-w-2xl"
                    role="tabpanel"
                    tabindex="0"
                >
                    <DeleteUser />
                </section>
            </div>
        </section>

        <AppFooter />
    </main>
</template>
