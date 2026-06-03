<script setup>
import Checkbox from "@/Components/Checkbox.vue";
import InputError from "@/Components/InputError.vue";
import MontreLogo from "@/Components/MontreLogo.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

defineProps({
    canResetPassword: {
        type: Boolean,
        default: true,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head title="Admin Login | Montre Nova" />

    <div class="min-h-screen bg-[#050505] text-white">
        <div class="grid min-h-screen lg:grid-cols-[1.05fr_0.95fr]">
            <!-- BRAND PANEL -->
            <section
                class="relative hidden overflow-hidden border-r border-white/10 bg-[#080808] lg:block"
            >
                <div class="pointer-events-none absolute inset-0">
                    <div
                        class="absolute left-[-12rem] top-[-12rem] h-[34rem] w-[34rem] rounded-full bg-white/[0.04] blur-3xl"
                    ></div>
                    <div
                        class="absolute bottom-[-14rem] right-[-12rem] h-[34rem] w-[34rem] rounded-full bg-zinc-600/10 blur-3xl"
                    ></div>
                    <div
                        class="absolute inset-0 bg-[linear-gradient(135deg,rgba(255,255,255,0.06),transparent_35%)]"
                    ></div>
                </div>

                <div class="relative flex h-full flex-col justify-between p-12">
                    <MontreLogo />

                    <div class="max-w-xl">
                        <p
                            class="mb-5 text-xs font-medium uppercase tracking-[0.36em] text-zinc-500"
                        >
                            Admin Portal
                        </p>

                        <h2
                            class="text-5xl font-semibold leading-tight tracking-tight text-white"
                        >
                            Manage curated watches with a clean luxury system.
                        </h2>

                        <p
                            class="mt-6 max-w-lg text-base leading-8 text-zinc-400"
                        >
                            Update stocks, upload HD watch photos, manage
                            availability, and publish clean product pages for
                            Montre Nova.
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.03] p-5"
                        >
                            <p
                                class="text-xs uppercase tracking-widest text-zinc-600"
                            >
                                01
                            </p>
                            <p class="mt-2 text-sm font-medium text-white">
                                Stocks
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.03] p-5"
                        >
                            <p
                                class="text-xs uppercase tracking-widest text-zinc-600"
                            >
                                02
                            </p>
                            <p class="mt-2 text-sm font-medium text-white">
                                HD Photos
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.03] p-5"
                        >
                            <p
                                class="text-xs uppercase tracking-widest text-zinc-600"
                            >
                                03
                            </p>
                            <p class="mt-2 text-sm font-medium text-white">
                                Sales
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- LOGIN FORM -->
            <section
                class="flex min-h-screen items-center justify-center px-6 py-10 sm:px-10"
            >
                <div class="w-full max-w-md">
                    <div class="mb-10 lg:hidden">
                        <MontreLogo />
                    </div>

                    <div
                        class="rounded-[2rem] border border-white/10 bg-[#0B0B0D]/90 p-7 shadow-2xl shadow-black/50 backdrop-blur-xl sm:p-9"
                    >
                        <div class="mb-8">
                            <p
                                class="mb-3 text-xs font-medium uppercase tracking-[0.3em] text-zinc-500"
                            >
                                Welcome Back
                            </p>

                            <h2
                                class="text-3xl font-semibold tracking-tight text-white"
                            >
                                Sign in to dashboard
                            </h2>

                            <p class="mt-3 text-sm leading-6 text-zinc-400">
                                Access your Montre Nova inventory and stock
                                management system.
                            </p>
                        </div>

                        <div
                            v-if="status"
                            class="mb-5 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300"
                        >
                            {{ status }}
                        </div>

                        <form @submit.prevent="submit" class="space-y-5">
                            <div>
                                <label
                                    class="mb-2 block text-xs font-medium uppercase tracking-[0.18em] text-zinc-500"
                                >
                                    Email Address
                                </label>

                                <input
                                    v-model="form.email"
                                    type="email"
                                    autocomplete="username"
                                    autofocus
                                    class="block w-full rounded-2xl border border-white/10 bg-[#050505] px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-700 focus:border-white/40 focus:ring-2 focus:ring-white/10"
                                    placeholder="admin@montrenova.com"
                                />

                                <InputError
                                    class="mt-2"
                                    :message="form.errors.email"
                                />
                            </div>

                            <div>
                                <div
                                    class="mb-2 flex items-center justify-between gap-4"
                                >
                                    <label
                                        class="block text-xs font-medium uppercase tracking-[0.18em] text-zinc-500"
                                    >
                                        Password
                                    </label>

                                    <Link
                                        v-if="canResetPassword"
                                        :href="route('password.request')"
                                        class="text-xs font-medium text-zinc-400 transition hover:text-white"
                                    >
                                        Forgot password?
                                    </Link>
                                </div>

                                <input
                                    v-model="form.password"
                                    type="password"
                                    autocomplete="current-password"
                                    class="block w-full rounded-2xl border border-white/10 bg-[#050505] px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-700 focus:border-white/40 focus:ring-2 focus:ring-white/10"
                                    placeholder="Enter your password"
                                />

                                <InputError
                                    class="mt-2"
                                    :message="form.errors.password"
                                />
                            </div>

                            <div class="flex items-center justify-between">
                                <label class="flex items-center gap-3">
                                    <Checkbox
                                        v-model:checked="form.remember"
                                        name="remember"
                                        class="rounded border-white/20 bg-[#050505] text-white focus:ring-white"
                                    />

                                    <span class="text-sm text-zinc-400">
                                        Remember me
                                    </span>
                                </label>
                            </div>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex w-full items-center justify-center rounded-2xl bg-white px-5 py-3.5 text-sm font-semibold text-black transition hover:bg-zinc-200 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <span v-if="!form.processing">Sign In</span>
                                <span v-else>Signing in...</span>
                            </button>
                        </form>

                        <div class="mt-7 border-t border-white/10 pt-6">
                            <p class="text-center text-sm text-zinc-500">
                                Need an admin account?
                                <!-- <Link
                                    :href="route('register')"
                                    class="font-medium text-white transition hover:text-zinc-300"
                                >
                                    Create one
                                </Link> -->
                            </p>
                        </div>
                    </div>

                    <p class="mt-8 text-center text-xs text-zinc-700">
                        © {{ new Date().getFullYear() }} Montre Nova. Curated
                        timepieces.
                    </p>
                </div>
            </section>
        </div>
    </div>
</template>
