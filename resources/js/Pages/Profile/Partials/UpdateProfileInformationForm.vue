<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});

const initials = computed(() => {
    if (!user.name) return "MN";

    return user.name
        .split(" ")
        .map((word) => word.charAt(0))
        .join("")
        .slice(0, 2)
        .toUpperCase();
});
</script>

<template>
    <section
        class="overflow-hidden rounded-[1.5rem] border border-white/10 bg-[#0A0A0B] shadow-2xl shadow-black/30"
    >
        <!-- HEADER -->
        <div
            class="border-b border-white/10 bg-gradient-to-br from-white/[0.06] to-transparent p-5 sm:p-6"
        >
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                <div
                    class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border border-white/10 bg-white text-xl font-black text-black shadow-lg shadow-black/30"
                >
                    {{ initials }}
                </div>

                <div class="min-w-0 flex-1">
                    <p
                        class="text-[11px] font-black uppercase tracking-[0.28em] text-zinc-500"
                    >
                        Account Settings
                    </p>

                    <h2
                        class="mt-2 text-2xl font-black tracking-[-0.04em] text-white"
                    >
                        Profile Information
                    </h2>

                    <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-400">
                        Update your account name and email address used for your
                        Montre Nova admin profile.
                    </p>
                </div>

                <div
                    class="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-[11px] font-bold text-emerald-300"
                >
                    Active Account
                </div>
            </div>
        </div>

        <!-- FORM -->
        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="space-y-6 p-5 sm:p-6"
        >
            <div class="grid gap-5 lg:grid-cols-2">
                <!-- NAME -->
                <div>
                    <InputLabel
                        for="name"
                        value="Full Name"
                        class="!text-xs !font-black !uppercase !tracking-[0.18em] !text-zinc-500"
                    />

                    <div class="relative mt-2">
                        <TextInput
                            id="name"
                            type="text"
                            class="!block !w-full !rounded-2xl !border-white/10 !bg-white/[0.04] !px-4 !py-3.5 !text-sm !font-semibold !text-white !shadow-none !outline-none placeholder:!text-zinc-600 focus:!border-white/30 focus:!ring-0"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="name"
                            placeholder="Enter your full name"
                        />
                    </div>

                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <!-- EMAIL -->
                <div>
                    <InputLabel
                        for="email"
                        value="Email Address"
                        class="!text-xs !font-black !uppercase !tracking-[0.18em] !text-zinc-500"
                    />

                    <div class="relative mt-2">
                        <TextInput
                            id="email"
                            type="email"
                            class="!block !w-full !rounded-2xl !border-white/10 !bg-white/[0.04] !px-4 !py-3.5 !text-sm !font-semibold !text-white !shadow-none !outline-none placeholder:!text-zinc-600 focus:!border-white/30 focus:!ring-0"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            placeholder="Enter your email address"
                        />
                    </div>

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>
            </div>

            <!-- EMAIL VERIFICATION NOTICE -->
            <div
                v-if="mustVerifyEmail && user.email_verified_at === null"
                class="rounded-2xl border border-amber-400/20 bg-amber-400/10 p-4"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-400/15 text-amber-300"
                    >
                        !
                    </div>

                    <div class="flex-1">
                        <p class="text-sm font-bold text-amber-200">
                            Your email address is unverified.
                        </p>

                        <p class="mt-1 text-sm leading-6 text-amber-100/70">
                            Please verify your email to keep your account secure
                            and make sure you can receive system notifications.
                        </p>

                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="mt-3 inline-flex rounded-xl border border-amber-300/20 bg-amber-300/10 px-4 py-2 text-xs font-black uppercase tracking-[0.14em] text-amber-200 transition hover:border-amber-300/40 hover:bg-amber-300/15"
                        >
                            Re-send Verification Email
                        </Link>

                        <div
                            v-show="status === 'verification-link-sent'"
                            class="mt-3 rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm font-semibold text-emerald-300"
                        >
                            A new verification link has been sent to your email
                            address.
                        </div>
                    </div>
                </div>
            </div>

            <!-- ACTION BAR -->
            <div
                class="flex flex-col gap-3 border-t border-white/10 pt-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="text-sm font-bold text-white">
                        Save profile changes
                    </p>

                    <p class="mt-1 text-xs leading-5 text-zinc-500">
                        Changes will apply immediately after saving.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Transition
                        enter-active-class="transition ease-in-out duration-300"
                        enter-from-class="opacity-0 translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition ease-in-out duration-300"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 translate-y-1"
                    >
                        <p
                            v-if="form.recentlySuccessful"
                            class="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-2 text-xs font-bold text-emerald-300"
                        >
                            Saved successfully
                        </p>
                    </Transition>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex min-w-28 items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-black text-black transition hover:bg-zinc-200 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <span v-if="form.processing">Saving...</span>
                        <span v-else>Save</span>
                    </button>
                </div>
            </div>
        </form>
    </section>
</template>
