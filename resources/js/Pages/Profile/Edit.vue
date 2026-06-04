<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DeleteUserForm from "./Partials/DeleteUserForm.vue";
import UpdatePasswordForm from "./Partials/UpdatePasswordForm.vue";
import UpdateProfileInformationForm from "./Partials/UpdateProfileInformationForm.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();

const user = computed(() => page.props.auth?.user);

const initials = computed(() => {
    if (!user.value?.name) return "MN";

    return user.value.name
        .split(" ")
        .map((part) => part[0])
        .join("")
        .slice(0, 2)
        .toUpperCase();
});
</script>

<template>
    <Head title="Profile | Montre Nova" />

    <AuthenticatedLayout title="Profile">
        <div class="space-y-6">
            <!-- HERO -->
            <section
                class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-[#0B0B0D] p-5 shadow-2xl shadow-black/30 sm:p-8"
            >
                <div class="pointer-events-none absolute inset-0">
                    <div
                        class="absolute right-[-10rem] top-[-10rem] h-[25rem] w-[25rem] rounded-full bg-white/[0.04] blur-3xl"
                    ></div>
                </div>

                <div
                    class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div>
                        <p
                            class="text-xs uppercase tracking-[0.32em] text-zinc-600"
                        >
                            Account Settings
                        </p>

                        <h1
                            class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-5xl"
                        >
                            Profile
                        </h1>

                        <p
                            class="mt-4 max-w-2xl text-sm leading-7 text-zinc-400"
                        >
                            Manage your Montre Nova admin profile, password, and
                            account security settings.
                        </p>
                    </div>

                    <div
                        class="flex items-center gap-4 rounded-[1.4rem] border border-white/10 bg-white/[0.03] p-4"
                    >
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-white text-base font-black text-black"
                        >
                            {{ initials }}
                        </div>

                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-semibold text-white"
                            >
                                {{ user?.name || "Montre Nova Admin" }}
                            </p>

                            <p class="truncate text-xs text-zinc-500">
                                {{ user?.email || "Admin account" }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ACCOUNT OVERVIEW -->
            <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                <div
                    class="rounded-[1.5rem] border border-white/10 bg-[#0B0B0D] p-5"
                >
                    <p
                        class="text-xs uppercase tracking-[0.24em] text-zinc-600"
                    >
                        Account
                    </p>

                    <p class="mt-3 text-lg font-semibold text-white">
                        {{ user?.name || "Admin" }}
                    </p>

                    <p class="mt-2 text-sm text-zinc-500">
                        Basic information used inside the admin dashboard.
                    </p>
                </div>

                <div
                    class="rounded-[1.5rem] border border-white/10 bg-[#0B0B0D] p-5"
                >
                    <p
                        class="text-xs uppercase tracking-[0.24em] text-zinc-600"
                    >
                        Email
                    </p>

                    <p class="mt-3 truncate text-lg font-semibold text-white">
                        {{ user?.email || "No email" }}
                    </p>

                    <p class="mt-2 text-sm text-zinc-500">
                        Used for login and account notifications.
                    </p>
                </div>

                <div
                    class="rounded-[1.5rem] border border-emerald-500/20 bg-emerald-500/10 p-5 sm:col-span-2 xl:col-span-1"
                >
                    <p
                        class="text-xs uppercase tracking-[0.24em] text-emerald-300/80"
                    >
                        Security
                    </p>

                    <p class="mt-3 text-lg font-semibold text-emerald-300">
                        Protected Account
                    </p>

                    <p class="mt-2 text-sm leading-6 text-emerald-200/70">
                        Keep your password updated and avoid sharing your admin
                        access.
                    </p>
                </div>
            </section>

            <!-- PROFILE AND PASSWORD -->
            <section class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
                <!-- PROFILE INFO -->
                <div
                    class="overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] shadow-2xl shadow-black/20"
                >
                    <div class="border-b border-white/10 px-5 py-5 sm:px-6">
                        <p
                            class="text-xs uppercase tracking-[0.28em] text-zinc-600"
                        >
                            Personal Information
                        </p>

                        <h2
                            class="mt-2 text-xl font-semibold tracking-tight text-white"
                        >
                            Profile Details
                        </h2>

                        <p class="mt-2 text-sm leading-6 text-zinc-500">
                            Update your name and email address for your admin
                            account.
                        </p>
                    </div>

                    <div class="p-5 sm:p-6">
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                            class="profile-form max-w-none"
                        />
                    </div>
                </div>

                <!-- PASSWORD -->
                <UpdatePasswordForm class="max-w-none" />
            </section>

            <!-- DANGER ZONE -->
            <section
                class="overflow-hidden rounded-[1.7rem] border border-red-500/20 bg-[#0B0B0D] shadow-2xl shadow-black/20"
            >
                <div
                    class="border-b border-red-500/20 bg-red-500/5 px-5 py-5 sm:px-6"
                >
                    <p
                        class="text-xs uppercase tracking-[0.28em] text-red-300/80"
                    >
                        Danger Zone
                    </p>

                    <h2
                        class="mt-2 text-xl font-semibold tracking-tight text-white"
                    >
                        Delete Account
                    </h2>

                    <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-500">
                        Permanently delete your account and all related account
                        access. This action cannot be undone.
                    </p>
                </div>

                <div class="p-5 sm:p-6">
                    <DeleteUserForm class="danger-form max-w-none" />
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
:deep(.profile-form section > header h2),
:deep(.danger-form section > header h2) {
    color: white;
    font-weight: 700;
    letter-spacing: -0.02em;
}

:deep(.profile-form section > header p),
:deep(.danger-form section > header p) {
    color: rgb(113 113 122);
    line-height: 1.6;
}

:deep(label) {
    color: rgb(161 161 170);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

:deep(input[type="text"]),
:deep(input[type="email"]),
:deep(input[type="password"]) {
    width: 100%;
    border-radius: 1rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: #050505;
    padding: 0.85rem 1rem;
    color: white;
    outline: none;
}

:deep(input[type="text"]::placeholder),
:deep(input[type="email"]::placeholder),
:deep(input[type="password"]::placeholder) {
    color: rgb(63 63 70);
}

:deep(input[type="text"]:focus),
:deep(input[type="email"]:focus),
:deep(input[type="password"]:focus) {
    border-color: rgb(255 255 255 / 0.4);
    box-shadow: 0 0 0 2px rgb(255 255 255 / 0.08);
}

:deep(button[type="submit"]) {
    border-radius: 1rem;
}

:deep(.text-gray-900) {
    color: white !important;
}

:deep(.text-gray-600),
:deep(.text-gray-500) {
    color: rgb(113 113 122) !important;
}

:deep(.bg-white) {
    background: transparent !important;
}
</style>
