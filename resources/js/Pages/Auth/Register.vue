<script setup>
import InputError from "@/Components/InputError.vue";
import MontreLogo from "@/Components/MontreLogo.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
});

const submit = () => {
    form.post(route("register"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <Head title="Register | Montre Nova" />

    <div class="min-h-screen bg-[#050505] text-white">
        <div class="grid min-h-screen lg:grid-cols-[0.95fr_1.05fr]">
            <!-- REGISTER FORM -->
            <section
                class="flex min-h-screen items-center justify-center px-6 py-10 sm:px-10"
            >
                <div class="w-full max-w-md">
                    <div class="mb-10">
                        <MontreLogo />
                    </div>

                    <div
                        class="rounded-[2rem] border border-white/10 bg-[#0B0B0D]/90 p-7 shadow-2xl shadow-black/50 backdrop-blur-xl sm:p-9"
                    >
                        <div class="mb-8">
                            <p
                                class="mb-3 text-xs font-medium uppercase tracking-[0.3em] text-zinc-500"
                            >
                                Create Account
                            </p>

                            <h2
                                class="text-3xl font-semibold tracking-tight text-white"
                            >
                                Set up admin access
                            </h2>

                            <p class="mt-3 text-sm leading-6 text-zinc-400">
                                Create your Montre Nova dashboard account to
                                manage stocks, sales, and HD product photos.
                            </p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-5">
                            <div>
                                <label
                                    class="mb-2 block text-xs font-medium uppercase tracking-[0.18em] text-zinc-500"
                                >
                                    Name
                                </label>

                                <input
                                    v-model="form.name"
                                    type="text"
                                    autocomplete="name"
                                    autofocus
                                    class="block w-full rounded-2xl border border-white/10 bg-[#050505] px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-700 focus:border-white/40 focus:ring-2 focus:ring-white/10"
                                    placeholder="Montre Nova Admin"
                                />

                                <InputError
                                    class="mt-2"
                                    :message="form.errors.name"
                                />
                            </div>

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
                                    class="block w-full rounded-2xl border border-white/10 bg-[#050505] px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-700 focus:border-white/40 focus:ring-2 focus:ring-white/10"
                                    placeholder="admin@montrenova.com"
                                />

                                <InputError
                                    class="mt-2"
                                    :message="form.errors.email"
                                />
                            </div>

                            <div>
                                <label
                                    class="mb-2 block text-xs font-medium uppercase tracking-[0.18em] text-zinc-500"
                                >
                                    Password
                                </label>

                                <input
                                    v-model="form.password"
                                    type="password"
                                    autocomplete="new-password"
                                    class="block w-full rounded-2xl border border-white/10 bg-[#050505] px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-700 focus:border-white/40 focus:ring-2 focus:ring-white/10"
                                    placeholder="Create a secure password"
                                />

                                <InputError
                                    class="mt-2"
                                    :message="form.errors.password"
                                />
                            </div>

                            <div>
                                <label
                                    class="mb-2 block text-xs font-medium uppercase tracking-[0.18em] text-zinc-500"
                                >
                                    Confirm Password
                                </label>

                                <input
                                    v-model="form.password_confirmation"
                                    type="password"
                                    autocomplete="new-password"
                                    class="block w-full rounded-2xl border border-white/10 bg-[#050505] px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-700 focus:border-white/40 focus:ring-2 focus:ring-white/10"
                                    placeholder="Repeat your password"
                                />

                                <InputError
                                    class="mt-2"
                                    :message="form.errors.password_confirmation"
                                />
                            </div>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex w-full items-center justify-center rounded-2xl bg-white px-5 py-3.5 text-sm font-semibold text-black transition hover:bg-zinc-200 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <span v-if="!form.processing"
                                    >Create Admin Account</span
                                >
                                <span v-else>Creating account...</span>
                            </button>
                        </form>

                        <div class="mt-7 border-t border-white/10 pt-6">
                            <p class="text-center text-sm text-zinc-500">
                                Already have an account?
                                <Link
                                    :href="route('login')"
                                    class="font-medium text-white transition hover:text-zinc-300"
                                >
                                    Sign in
                                </Link>
                            </p>
                        </div>
                    </div>

                    <p class="mt-8 text-center text-xs text-zinc-700">
                        © {{ new Date().getFullYear() }} Montre Nova. Curated
                        timepieces.
                    </p>
                </div>
            </section>

            <!-- BRAND PANEL -->
            <section
                class="relative hidden overflow-hidden border-l border-white/10 bg-[#080808] lg:block"
            >
                <div class="pointer-events-none absolute inset-0">
                    <div
                        class="absolute right-[-12rem] top-[-12rem] h-[34rem] w-[34rem] rounded-full bg-white/[0.04] blur-3xl"
                    ></div>
                    <div
                        class="absolute bottom-[-14rem] left-[-12rem] h-[34rem] w-[34rem] rounded-full bg-zinc-600/10 blur-3xl"
                    ></div>
                    <div
                        class="absolute inset-0 bg-[linear-gradient(225deg,rgba(255,255,255,0.06),transparent_35%)]"
                    ></div>
                </div>

                <div class="relative flex h-full flex-col justify-between p-12">
                    <div class="flex justify-end">
                        <div
                            class="rounded-full border border-white/10 bg-white/[0.03] px-4 py-2 text-xs uppercase tracking-[0.24em] text-zinc-500"
                        >
                            Luxury Inventory System
                        </div>
                    </div>

                    <div class="max-w-xl">
                        <p
                            class="mb-5 text-xs font-medium uppercase tracking-[0.36em] text-zinc-500"
                        >
                            Montre Nova
                        </p>

                        <h2
                            class="text-5xl font-semibold leading-tight tracking-tight text-white"
                        >
                            Built for curated watch drops and clean stock
                            updates.
                        </h2>

                        <p
                            class="mt-6 max-w-lg text-base leading-8 text-zinc-400"
                        >
                            Add watches, upload HD galleries, set availability,
                            and publish premium product pages for customers.
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
                                Add Stocks
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
                                Upload Photos
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
                                Publish
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
