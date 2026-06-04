<script setup>
import InputError from "@/Components/InputError.vue";
import { useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
});

const passwordRules = computed(() => [
    {
        label: "At least 8 characters",
        passed: form.password.length >= 8,
    },
    {
        label: "Has uppercase letter",
        passed: /[A-Z]/.test(form.password),
    },
    {
        label: "Has lowercase letter",
        passed: /[a-z]/.test(form.password),
    },
    {
        label: "Has number",
        passed: /[0-9]/.test(form.password),
    },
    {
        label: "Passwords match",
        passed:
            form.password.length > 0 &&
            form.password === form.password_confirmation,
    },
]);

const passedRules = computed(() => {
    return passwordRules.value.filter((rule) => rule.passed).length;
});

const strengthPercentage = computed(() => {
    return (passedRules.value / passwordRules.value.length) * 100;
});

const strengthLabel = computed(() => {
    if (!form.password) return "Not started";
    if (passedRules.value <= 2) return "Weak";
    if (passedRules.value <= 4) return "Good";

    return "Strong";
});

const strengthClass = computed(() => {
    if (!form.password) return "bg-zinc-800";
    if (passedRules.value <= 2) return "bg-red-400";
    if (passedRules.value <= 4) return "bg-amber-400";

    return "bg-emerald-400";
});

const canSubmit = computed(() => {
    return (
        form.current_password &&
        form.password &&
        form.password_confirmation &&
        form.password === form.password_confirmation &&
        form.password.length >= 8 &&
        !form.processing
    );
});

const updatePassword = () => {
    form.put(route("password.update"), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showCurrentPassword.value = false;
            showNewPassword.value = false;
            showConfirmPassword.value = false;
        },
        onError: () => {
            if (form.errors.password) {
                form.reset("password", "password_confirmation");
                passwordInput.value?.focus();
            }

            if (form.errors.current_password) {
                form.reset("current_password");
                currentPasswordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <section
        class="overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] shadow-2xl shadow-black/30"
    >
        <!-- HEADER -->
        <div class="border-b border-white/10 px-5 py-5 sm:px-6">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
            >
                <div>
                    <p
                        class="text-xs uppercase tracking-[0.28em] text-zinc-600"
                    >
                        Account Security
                    </p>

                    <h2
                        class="mt-2 text-xl font-semibold tracking-tight text-white sm:text-2xl"
                    >
                        Update Password
                    </h2>

                    <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-500">
                        Use a strong password to keep your Montre Nova admin
                        account secure.
                    </p>
                </div>

                <div
                    class="w-fit rounded-full border border-white/10 bg-white/[0.03] px-4 py-2 text-xs font-semibold text-zinc-400"
                >
                    Secure Area
                </div>
            </div>
        </div>

        <!-- BODY -->
        <form @submit.prevent="updatePassword" class="space-y-6 p-5 sm:p-6">
            <!-- PASSWORD FIELDS -->
            <div class="grid gap-5 lg:grid-cols-[1fr_0.8fr]">
                <div class="space-y-5">
                    <!-- CURRENT PASSWORD -->
                    <div>
                        <label
                            for="current_password"
                            class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500"
                        >
                            Current Password
                        </label>

                        <div class="relative">
                            <input
                                id="current_password"
                                ref="currentPasswordInput"
                                v-model="form.current_password"
                                :type="
                                    showCurrentPassword ? 'text' : 'password'
                                "
                                autocomplete="current-password"
                                class="mn-input pr-24"
                                placeholder="Enter current password"
                            />

                            <button
                                type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-xl border border-white/10 px-3 py-2 text-xs font-semibold text-zinc-400 transition hover:border-white/30 hover:text-white"
                                @click="
                                    showCurrentPassword = !showCurrentPassword
                                "
                            >
                                {{ showCurrentPassword ? "Hide" : "Show" }}
                            </button>
                        </div>

                        <InputError
                            :message="form.errors.current_password"
                            class="mt-2"
                        />
                    </div>

                    <!-- NEW PASSWORD -->
                    <div>
                        <label
                            for="password"
                            class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500"
                        >
                            New Password
                        </label>

                        <div class="relative">
                            <input
                                id="password"
                                ref="passwordInput"
                                v-model="form.password"
                                :type="showNewPassword ? 'text' : 'password'"
                                autocomplete="new-password"
                                class="mn-input pr-24"
                                placeholder="Create new password"
                            />

                            <button
                                type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-xl border border-white/10 px-3 py-2 text-xs font-semibold text-zinc-400 transition hover:border-white/30 hover:text-white"
                                @click="showNewPassword = !showNewPassword"
                            >
                                {{ showNewPassword ? "Hide" : "Show" }}
                            </button>
                        </div>

                        <InputError
                            :message="form.errors.password"
                            class="mt-2"
                        />
                    </div>

                    <!-- CONFIRM PASSWORD -->
                    <div>
                        <label
                            for="password_confirmation"
                            class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500"
                        >
                            Confirm Password
                        </label>

                        <div class="relative">
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                :type="
                                    showConfirmPassword ? 'text' : 'password'
                                "
                                autocomplete="new-password"
                                class="mn-input pr-24"
                                placeholder="Repeat new password"
                            />

                            <button
                                type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-xl border border-white/10 px-3 py-2 text-xs font-semibold text-zinc-400 transition hover:border-white/30 hover:text-white"
                                @click="
                                    showConfirmPassword = !showConfirmPassword
                                "
                            >
                                {{ showConfirmPassword ? "Hide" : "Show" }}
                            </button>
                        </div>

                        <InputError
                            :message="form.errors.password_confirmation"
                            class="mt-2"
                        />
                    </div>
                </div>

                <!-- SECURITY CHECKLIST -->
                <div
                    class="rounded-[1.4rem] border border-white/10 bg-white/[0.03] p-5"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p
                                class="text-xs uppercase tracking-[0.22em] text-zinc-600"
                            >
                                Password Strength
                            </p>

                            <h3 class="mt-2 text-lg font-semibold text-white">
                                {{ strengthLabel }}
                            </h3>
                        </div>

                        <div
                            class="rounded-full border border-white/10 bg-[#050505] px-3 py-1 text-xs font-semibold text-zinc-400"
                        >
                            {{ passedRules }} / {{ passwordRules.length }}
                        </div>
                    </div>

                    <div
                        class="mt-5 h-2 overflow-hidden rounded-full bg-zinc-900"
                    >
                        <div
                            class="h-full rounded-full transition-all duration-300"
                            :class="strengthClass"
                            :style="{ width: `${strengthPercentage}%` }"
                        ></div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <div
                            v-for="rule in passwordRules"
                            :key="rule.label"
                            class="flex items-center gap-3 rounded-2xl border px-4 py-3 text-sm transition"
                            :class="
                                rule.passed
                                    ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300'
                                    : 'border-white/10 bg-[#050505] text-zinc-500'
                            "
                        >
                            <span
                                class="flex h-5 w-5 items-center justify-center rounded-full text-xs font-bold"
                                :class="
                                    rule.passed
                                        ? 'bg-emerald-400 text-black'
                                        : 'bg-zinc-800 text-zinc-500'
                                "
                            >
                                {{ rule.passed ? "✓" : "•" }}
                            </span>

                            <span>{{ rule.label }}</span>
                        </div>
                    </div>

                    <p class="mt-5 text-xs leading-5 text-zinc-500">
                        Tip: Avoid using your brand name, birthday, or common
                        passwords. Use a mix of letters, numbers, and symbols.
                    </p>
                </div>
            </div>

            <!-- FOOTER -->
            <div
                class="flex flex-col gap-4 border-t border-white/10 pt-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0 translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in-out"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-1"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-semibold text-emerald-300"
                    >
                        Password updated successfully.
                    </p>
                </Transition>

                <div
                    v-if="!form.recentlySuccessful"
                    class="text-sm text-zinc-500"
                >
                    Changes apply immediately after saving.
                </div>

                <button
                    type="submit"
                    :disabled="!canSubmit"
                    class="inline-flex items-center justify-center rounded-2xl bg-white px-6 py-3 text-sm font-bold text-black transition hover:bg-zinc-200 disabled:cursor-not-allowed disabled:bg-zinc-700 disabled:text-zinc-400 sm:min-w-36"
                >
                    {{ form.processing ? "Saving..." : "Save Password" }}
                </button>
            </div>
        </form>
    </section>
</template>

<style scoped>
.mn-input {
    width: 100%;
    border-radius: 1rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: #050505;
    padding: 0.85rem 1rem;
    font-size: 0.875rem;
    color: white;
    outline: none;
    transition:
        border-color 150ms ease,
        box-shadow 150ms ease,
        background-color 150ms ease;
}

.mn-input::placeholder {
    color: rgb(63 63 70);
}

.mn-input:focus {
    border-color: rgb(255 255 255 / 0.4);
    background: #070707;
    box-shadow: 0 0 0 2px rgb(255 255 255 / 0.08);
}
</style>
