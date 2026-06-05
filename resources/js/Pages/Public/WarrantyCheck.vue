<script setup>
import MontreLogo from "@/Components/MontreLogo.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    result: {
        type: Object,
        default: null,
    },
    searched: {
        type: Boolean,
        default: false,
    },
});

const form = useForm({
    buyer_name: "",
    serial_number: "",
});

const formatDate = (value) => {
    if (!value) return "—";

    return new Date(value).toLocaleDateString("en-PH", {
        year: "numeric",
        month: "long",
        day: "2-digit",
    });
};

const statusLabel = computed(() => {
    if (!props.result) return "";

    if (props.result.status === "active") return "Active Warranty";
    if (props.result.status === "expiring_soon") return "Expiring Soon";
    if (props.result.status === "expired") return "Expired Warranty";

    return "Unknown";
});

const statusClass = computed(() => {
    if (!props.result) return "";

    if (props.result.status === "active") {
        return "border-emerald-400/20 bg-emerald-400/10 text-emerald-300";
    }

    if (props.result.status === "expiring_soon") {
        return "border-amber-400/20 bg-amber-400/10 text-amber-300";
    }

    return "border-red-400/20 bg-red-400/10 text-red-300";
});

const daysLeftLabel = computed(() => {
    if (!props.result) return "";

    const days = Number(props.result.days_left);

    if (days < 0) return "Warranty coverage has ended.";
    if (days === 0) return "Warranty expires today.";
    if (days === 1) return "1 day remaining.";

    return `${days} days remaining.`;
});

const submit = () => {
    form.post(route("public.warranty-check.check"), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Warranty Check | Montre Nova" />

    <div class="min-h-screen bg-[#050505] px-4 py-6 text-white sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <header class="flex items-center justify-between gap-4">
                <Link href="/" class="inline-flex items-center">
                    <MontreLogo />
                </Link>

                <Link
                    href="/"
                    class="rounded-2xl border border-white/10 px-4 py-2 text-sm font-bold text-zinc-300 transition hover:border-white/30 hover:text-white"
                >
                    Back Home
                </Link>
            </header>

            <main
                class="grid gap-6 py-10 lg:grid-cols-[0.85fr_1.15fr] lg:items-start"
            >
                <section
                    class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-[#0B0B0D] p-6 shadow-2xl shadow-black/40 sm:p-8"
                >
                    <div class="pointer-events-none absolute inset-0">
                        <div
                            class="absolute right-[-10rem] top-[-10rem] h-80 w-80 rounded-full bg-white/[0.05] blur-3xl"
                        ></div>
                    </div>

                    <div class="relative">
                        <p
                            class="text-xs font-bold uppercase tracking-[0.28em] text-zinc-600"
                        >
                            Montre Card Warranty
                        </p>

                        <h1
                            class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-5xl"
                        >
                            Check your warranty coverage.
                        </h1>

                        <p class="mt-5 text-sm leading-7 text-zinc-400">
                            Enter the buyer name and watch serial number to
                            verify the warranty period of a Montre Nova
                            purchase.
                        </p>

                        <div
                            class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-5"
                        >
                            <p class="text-sm font-semibold text-white">
                                Warranty Terms
                            </p>

                            <p class="mt-3 text-sm leading-7 text-zinc-500">
                                Warranty coverage is valid for one year from the
                                date of purchase and covers movement and
                                internal mechanism concerns.
                            </p>
                        </div>
                    </div>
                </section>

                <section
                    class="rounded-[2rem] border border-white/10 bg-[#0B0B0D] p-6 shadow-2xl shadow-black/40 sm:p-8"
                >
                    <form class="space-y-5" @submit.prevent="submit">
                        <div>
                            <label class="mn-label"> Buyer Name </label>

                            <input
                                v-model="form.buyer_name"
                                type="text"
                                class="mn-input"
                                placeholder="Enter buyer name"
                            />

                            <p
                                v-if="form.errors.buyer_name"
                                class="mt-2 text-sm text-red-300"
                            >
                                {{ form.errors.buyer_name }}
                            </p>
                        </div>

                        <div>
                            <label class="mn-label"> Serial Number </label>

                            <input
                                v-model="form.serial_number"
                                type="text"
                                class="mn-input"
                                placeholder="Enter watch serial number"
                            />

                            <p
                                v-if="form.errors.serial_number"
                                class="mt-2 text-sm text-red-300"
                            >
                                {{ form.errors.serial_number }}
                            </p>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full rounded-2xl bg-white px-5 py-4 text-sm font-bold text-black transition hover:bg-zinc-200 disabled:opacity-60"
                        >
                            {{
                                form.processing
                                    ? "Checking..."
                                    : "Check Warranty"
                            }}
                        </button>
                    </form>

                    <div
                        v-if="result"
                        class="mt-8 rounded-[1.5rem] border border-white/10 bg-white/[0.03] p-5"
                    >
                        <div
                            class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div>
                                <p
                                    class="text-xs uppercase tracking-[0.22em] text-zinc-600"
                                >
                                    Warranty Result
                                </p>

                                <h2
                                    class="mt-2 text-xl font-semibold text-white"
                                >
                                    {{ result.brand }} {{ result.model_name }}
                                </h2>

                                <p class="mt-1 text-sm text-zinc-500">
                                    Ref.
                                    {{
                                        result.reference_number ||
                                        "No reference"
                                    }}
                                </p>
                            </div>

                            <span
                                class="w-fit rounded-full border px-3 py-1 text-xs font-bold"
                                :class="statusClass"
                            >
                                {{ statusLabel }}
                            </span>
                        </div>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <div class="mn-info">
                                <p class="mn-info-label">Buyer</p>
                                <p class="mn-info-value">
                                    {{ result.buyer_name }}
                                </p>
                            </div>

                            <div class="mn-info">
                                <p class="mn-info-label">Serial</p>
                                <p class="mn-info-value">
                                    {{ result.serial_number }}
                                </p>
                            </div>

                            <div class="mn-info">
                                <p class="mn-info-label">Date Sold</p>
                                <p class="mn-info-value">
                                    {{ formatDate(result.date_sold) }}
                                </p>
                            </div>

                            <div class="mn-info">
                                <p class="mn-info-label">Warranty Until</p>
                                <p class="mn-info-value">
                                    {{ formatDate(result.warranty_end_date) }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="mt-5 rounded-2xl border p-4"
                            :class="statusClass"
                        >
                            <p class="text-sm font-semibold">
                                {{ daysLeftLabel }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-else-if="searched"
                        class="mt-8 rounded-[1.5rem] border border-red-400/20 bg-red-400/10 p-5"
                    >
                        <p class="text-sm font-semibold text-red-300">
                            No warranty record found.
                        </p>

                        <p class="mt-2 text-sm leading-6 text-red-200/70">
                            Please check the buyer name and serial number, or
                            message Montre Nova for manual verification.
                        </p>
                    </div>
                </section>
            </main>
        </div>
    </div>
</template>

<style scoped>
.mn-label {
    margin-bottom: 0.5rem;
    display: block;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    color: rgb(113 113 122);
}

.mn-input {
    width: 100%;
    border-radius: 1rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: #050505;
    padding: 0.9rem 1rem;
    font-size: 0.9rem;
    color: white;
    outline: none;
}

.mn-input::placeholder {
    color: rgb(63 63 70);
}

.mn-input:focus {
    border-color: rgb(255 255 255 / 0.4);
    box-shadow: 0 0 0 2px rgb(255 255 255 / 0.1);
}

.mn-info {
    border-radius: 1rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: rgb(0 0 0 / 0.2);
    padding: 1rem;
}

.mn-info-label {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    color: rgb(113 113 122);
}

.mn-info-value {
    margin-top: 0.4rem;
    font-size: 0.9rem;
    font-weight: 700;
    color: white;
}
</style>
