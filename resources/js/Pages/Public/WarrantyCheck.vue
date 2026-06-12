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

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return "—";

    return date.toLocaleDateString("en-PH", {
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

const statusDotClass = computed(() => {
    if (!props.result) return "bg-zinc-500";

    if (props.result.status === "active") return "bg-emerald-300";
    if (props.result.status === "expiring_soon") return "bg-amber-300";

    return "bg-red-300";
});

const daysLeftLabel = computed(() => {
    if (!props.result) return "";

    const days = Number(props.result.days_left);

    if (days < 0) return "Warranty coverage has ended.";
    if (days === 0) return "Warranty expires today.";
    if (days === 1) return "1 day remaining.";

    return `${days} days remaining.`;
});

const warrantyProgress = computed(() => {
    if (!props.result) return 0;

    const days = Number(props.result.days_left || 0);

    if (days <= 0) return 100;

    const usedDays = 365 - Math.min(days, 365);

    return Math.max(0, Math.min(100, Math.round((usedDays / 365) * 100)));
});

const resultTitle = computed(() => {
    if (!props.result) return "";

    return `${props.result.brand || ""} ${props.result.model_name || ""}`.trim();
});

const warrantyItems = [
    {
        title: "1 Year Coverage",
        description: "Coverage starts from the official purchase date.",
    },
    {
        title: "Movement Support",
        description: "Covers movement and internal mechanism concerns.",
    },
    {
        title: "After-Sales Service",
        description: "Message Montre Nova for verification and support.",
    },
];

const checklistItems = [
    "Use the same buyer name from the Montre Card.",
    "Add the serial number when available for a more accurate match.",
    "For missing records, request manual verification from Montre Nova.",
];

const submit = () => {
    form.post(route("public.warranty-check.check"), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Warranty Check | Montre Nova" />

    <div
        class="relative min-h-screen overflow-hidden bg-[#050505] text-white antialiased"
    >
        <!-- PREMIUM AMBIENT BACKGROUND -->
        <div class="pointer-events-none fixed inset-0">
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_20%_8%,rgba(255,255,255,0.075),transparent_28%),radial-gradient(circle_at_82%_18%,rgba(120,120,120,0.10),transparent_30%),linear-gradient(180deg,#050505_0%,#080808_46%,#030303_100%)]"
            ></div>
            <div
                class="absolute left-[-10rem] top-24 h-80 w-80 rounded-full bg-white/[0.035] blur-3xl"
            ></div>
            <div
                class="absolute bottom-[-12rem] right-[-8rem] h-96 w-96 rounded-full bg-zinc-500/[0.055] blur-3xl"
            ></div>
            <div
                class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/25 to-transparent"
            ></div>
        </div>

        <div
            class="relative z-10 mx-auto flex min-h-screen max-w-6xl flex-col px-4 py-5 sm:px-6 lg:px-8"
        >
            <!-- HEADER -->
            <header
                class="flex items-center justify-between gap-4 rounded-xl border border-white/10 bg-black/25 px-4 py-3 backdrop-blur-xl sm:px-5"
            >
                <Link href="/" class="inline-flex min-w-0 items-center">
                    <MontreLogo />
                </Link>

                <div class="flex items-center gap-2">
                    <Link
                        href="/"
                        aria-label="Back to home"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-white text-black transition hover:bg-zinc-200 sm:h-auto sm:w-auto sm:px-4 sm:py-2"
                    >
                        <!-- Mobile home icon -->
                        <svg
                            class="h-5 w-5 sm:hidden"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M3 10.5 12 3l9 7.5" />
                            <path d="M5 10v10h14V10" />
                            <path d="M9 20v-6h6v6" />
                        </svg>

                        <!-- Desktop text -->
                        <span
                            class="hidden text-xs font-black uppercase tracking-[0.12em] sm:inline"
                        >
                            Back Home
                        </span>
                    </Link>
                </div>
            </header>

            <main
                class="grid flex-1 gap-5 py-7 lg:grid-cols-[0.9fr_1.1fr] lg:items-start lg:py-10"
            >
                <!-- LEFT CONTENT -->
                <section class="space-y-5 lg:sticky lg:top-6">
                    <div
                        class="relative overflow-hidden rounded-xl border border-white/10 bg-[#0B0B0D]/90 p-6 shadow-2xl shadow-black/40 backdrop-blur sm:p-8"
                    >
                        <div
                            class="pointer-events-none absolute right-[-8rem] top-[-8rem] h-72 w-72 rounded-full bg-white/[0.06] blur-3xl"
                        ></div>
                        <div
                            class="pointer-events-none absolute bottom-0 left-0 h-px w-full bg-gradient-to-r from-transparent via-white/25 to-transparent"
                        ></div>

                        <div class="relative">
                            <div
                                class="mb-7 inline-flex items-center gap-3 rounded-lg border border-white/10 bg-white/[0.04] px-3 py-2"
                            >
                                <span
                                    class="h-2 w-2 rounded-full bg-white"
                                ></span>
                                <span
                                    class="text-[10px] font-black uppercase tracking-[0.24em] text-zinc-500"
                                >
                                    Montre Card Warranty
                                </span>
                            </div>

                            <h1
                                class="max-w-xl text-4xl font-black leading-[0.95] tracking-[-0.06em] text-white sm:text-5xl lg:text-6xl"
                            >
                                Verify your warranty coverage.
                            </h1>

                            <p
                                class="mt-5 max-w-xl text-sm leading-7 text-zinc-400 sm:text-base"
                            >
                                Check the warranty status of your Montre Nova
                                purchase using the buyer name and watch serial
                                number.
                            </p>

                            <div
                                class="mt-8 grid gap-3 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3"
                            >
                                <div
                                    v-for="item in warrantyItems"
                                    :key="item.title"
                                    class="rounded-lg border border-white/10 bg-white/[0.035] p-4"
                                >
                                    <p class="text-sm font-bold text-white">
                                        {{ item.title }}
                                    </p>

                                    <p
                                        class="mt-2 text-xs leading-5 text-zinc-500"
                                    >
                                        {{ item.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-white/10 bg-[#0B0B0D]/80 p-5 backdrop-blur"
                    >
                        <p
                            class="text-[11px] font-black uppercase tracking-[0.24em] text-zinc-500"
                        >
                            Before Checking
                        </p>

                        <div class="mt-4 space-y-3">
                            <div
                                v-for="item in checklistItems"
                                :key="item"
                                class="flex gap-3 rounded-lg border border-white/10 bg-white/[0.025] p-3"
                            >
                                <span
                                    class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-emerald-400/20 bg-emerald-400/10 text-[10px] font-black text-emerald-300"
                                >
                                    ✓
                                </span>

                                <p class="text-sm leading-6 text-zinc-400">
                                    {{ item }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- RIGHT CONTENT -->
                <section class="space-y-5">
                    <div
                        class="rounded-xl border border-white/10 bg-[#0B0B0D]/95 p-5 shadow-2xl shadow-black/40 backdrop-blur sm:p-7"
                    >
                        <div
                            class="mb-6 flex flex-col justify-between gap-3 border-b border-white/10 pb-5 sm:flex-row sm:items-end"
                        >
                            <div>
                                <p
                                    class="text-[11px] font-black uppercase tracking-[0.28em] text-zinc-500"
                                >
                                    Warranty Lookup
                                </p>

                                <h2
                                    class="mt-2 text-2xl font-black tracking-[-0.04em] text-white"
                                >
                                    Enter warranty details
                                </h2>
                            </div>

                            <p
                                class="max-w-sm text-xs leading-5 text-zinc-500 sm:text-right"
                            >
                                Buyer name is required. Serial number is
                                optional but recommended.
                            </p>
                        </div>

                        <form class="space-y-5" @submit.prevent="submit">
                            <div>
                                <label class="mn-label">Buyer Name</label>

                                <div class="relative">
                                    <input
                                        v-model="form.buyer_name"
                                        type="text"
                                        class="mn-input"
                                        placeholder="Enter buyer name"
                                        autocomplete="name"
                                    />
                                </div>

                                <p
                                    v-if="form.errors.buyer_name"
                                    class="mt-2 text-sm text-red-300"
                                >
                                    {{ form.errors.buyer_name }}
                                </p>
                            </div>

                            <div>
                                <label class="mn-label">
                                    Serial Number
                                    <span class="text-zinc-600"
                                        >(Optional)</span
                                    >
                                </label>

                                <input
                                    v-model="form.serial_number"
                                    type="text"
                                    class="mn-input uppercase tracking-[0.08em]"
                                    placeholder="Optional: enter serial number"
                                    autocomplete="off"
                                />

                                <p class="mt-2 text-xs leading-5 text-zinc-600">
                                    Leave this blank if the buyer does not know
                                    the serial number.
                                </p>

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
                                class="group inline-flex w-full items-center justify-center gap-3 rounded-lg bg-white px-5 py-4 text-sm font-black text-black transition hover:bg-zinc-200 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <span
                                    v-if="form.processing"
                                    class="h-4 w-4 animate-spin rounded-full border-2 border-black/30 border-t-black"
                                ></span>

                                <span>
                                    {{
                                        form.processing
                                            ? "Checking Warranty..."
                                            : "Check Warranty"
                                    }}
                                </span>
                            </button>
                        </form>
                    </div>

                    <!-- RESULT -->
                    <div
                        v-if="result"
                        class="overflow-hidden rounded-xl border border-white/10 bg-[#0B0B0D]/95 shadow-2xl shadow-black/40"
                    >
                        <div class="relative p-5 sm:p-7">
                            <div
                                class="pointer-events-none absolute right-[-7rem] top-[-7rem] h-64 w-64 rounded-full bg-white/[0.045] blur-3xl"
                            ></div>

                            <div
                                class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                            >
                                <div class="min-w-0">
                                    <p
                                        class="text-[11px] font-black uppercase tracking-[0.28em] text-zinc-500"
                                    >
                                        Warranty Result
                                    </p>

                                    <h2
                                        class="mt-2 text-2xl font-black tracking-[-0.04em] text-white sm:text-3xl"
                                    >
                                        {{ resultTitle || "Montre Nova Watch" }}
                                    </h2>

                                    <p class="mt-2 text-sm text-zinc-500">
                                        Ref.
                                        {{
                                            result.reference_number ||
                                            "No reference"
                                        }}
                                    </p>
                                </div>

                                <span
                                    class="inline-flex w-fit items-center gap-2 rounded-lg border px-3 py-2 text-xs font-black uppercase tracking-[0.12em]"
                                    :class="statusClass"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full"
                                        :class="statusDotClass"
                                    ></span>
                                    {{ statusLabel }}
                                </span>
                            </div>

                            <div
                                class="relative mt-6 rounded-lg border border-white/10 bg-white/[0.035] p-4"
                            >
                                <div
                                    class="mb-3 flex items-center justify-between gap-4"
                                >
                                    <p
                                        class="text-xs font-bold uppercase tracking-[0.18em] text-zinc-500"
                                    >
                                        Warranty Timeline
                                    </p>

                                    <p class="text-xs font-bold text-zinc-400">
                                        {{ warrantyProgress }}%
                                    </p>
                                </div>

                                <div
                                    class="h-2 overflow-hidden rounded-full bg-white/10"
                                >
                                    <div
                                        class="h-full rounded-full bg-white transition-all duration-500"
                                        :style="{
                                            width: `${warrantyProgress}%`,
                                        }"
                                    ></div>
                                </div>

                                <p
                                    class="mt-3 text-sm font-semibold"
                                    :class="statusClass"
                                >
                                    {{ daysLeftLabel }}
                                </p>
                            </div>

                            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                <div class="mn-info">
                                    <p class="mn-info-label">Buyer</p>
                                    <p class="mn-info-value">
                                        {{ result.buyer_name || "—" }}
                                    </p>
                                </div>

                                <div class="mn-info">
                                    <p class="mn-info-label">Serial</p>
                                    <p class="mn-info-value">
                                        {{ result.serial_number || "—" }}
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
                                        {{
                                            formatDate(result.warranty_end_date)
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="border-t border-white/10 bg-black/20 p-5 sm:p-6"
                        >
                            <div
                                class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
                            >
                                <div>
                                    <p class="text-sm font-bold text-white">
                                        Need help with this warranty?
                                    </p>

                                    <p
                                        class="mt-1 text-xs leading-5 text-zinc-500"
                                    >
                                        Send the warranty result to Montre Nova
                                        for after-sales support.
                                    </p>
                                </div>

                                <a
                                    href="https://m.me/montrenova"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center justify-center rounded-lg border border-white/10 bg-white/[0.04] px-5 py-3 text-sm font-bold text-white transition hover:border-white/30 hover:bg-white/[0.07]"
                                >
                                    Message Support
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- EMPTY / NOT FOUND STATE -->
                    <div
                        v-else-if="searched"
                        class="rounded-xl border border-red-400/20 bg-red-400/10 p-5 shadow-2xl shadow-black/30 sm:p-6"
                    >
                        <div class="flex gap-4">
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-red-400/20 bg-red-400/10 text-lg font-black text-red-300"
                            >
                                !
                            </span>

                            <div>
                                <p class="text-sm font-black text-red-300">
                                    No warranty record found.
                                </p>

                                <p
                                    class="mt-2 text-sm leading-6 text-red-100/70"
                                >
                                    Please check the buyer name and serial
                                    number, or message Montre Nova for manual
                                    verification.
                                </p>

                                <a
                                    href="https://m.me/montrenova"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="mt-4 inline-flex rounded-lg border border-red-300/20 bg-red-300/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.12em] text-red-100 transition hover:border-red-200/40"
                                >
                                    Request Manual Check
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- TERMS -->
                    <div
                        class="rounded-xl border border-white/10 bg-[#0B0B0D]/75 p-5 backdrop-blur sm:p-6"
                    >
                        <p
                            class="text-[11px] font-black uppercase tracking-[0.28em] text-zinc-500"
                        >
                            Warranty Terms
                        </p>

                        <div class="mt-4 grid gap-3 lg:grid-cols-3">
                            <div
                                class="rounded-lg border border-white/10 bg-white/[0.025] p-4"
                            >
                                <p class="text-sm font-bold text-white">
                                    Covered
                                </p>

                                <p class="mt-2 text-xs leading-5 text-zinc-500">
                                    Movement and internal mechanism defects
                                    including abnormal timekeeping and movement
                                    stoppage.
                                </p>
                            </div>

                            <div
                                class="rounded-lg border border-white/10 bg-white/[0.025] p-4"
                            >
                                <p class="text-sm font-bold text-white">
                                    Not Covered
                                </p>

                                <p class="mt-2 text-xs leading-5 text-zinc-500">
                                    Scratches, dents, broken glass, water
                                    damage, misuse, unauthorized repairs,
                                    batteries, and cosmetic damage.
                                </p>
                            </div>

                            <div
                                class="rounded-lg border border-white/10 bg-white/[0.025] p-4"
                            >
                                <p class="text-sm font-bold text-white">
                                    Verification
                                </p>

                                <p class="mt-2 text-xs leading-5 text-zinc-500">
                                    Warranty claims may require official
                                    purchase details, photos, and manual
                                    assessment by Montre Nova.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>
</template>

<style scoped>
.mn-label {
    margin-bottom: 0.55rem;
    display: block;
    font-size: 0.7rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    color: rgb(113 113 122);
}

.mn-input {
    width: 100%;
    border-radius: 0.75rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: rgb(255 255 255 / 0.035);
    padding: 1rem;
    font-size: 0.92rem;
    font-weight: 600;
    color: white;
    outline: none;
    transition:
        border-color 160ms ease,
        background 160ms ease,
        box-shadow 160ms ease;
}

.mn-input::placeholder {
    color: rgb(82 82 91);
    font-weight: 500;
    letter-spacing: normal;
    text-transform: none;
}

.mn-input:focus {
    border-color: rgb(255 255 255 / 0.36);
    background: rgb(255 255 255 / 0.055);
    box-shadow: 0 0 0 3px rgb(255 255 255 / 0.08);
}

.mn-info {
    border-radius: 0.75rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: rgb(255 255 255 / 0.03);
    padding: 1rem;
}

.mn-info-label {
    font-size: 0.68rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    color: rgb(113 113 122);
}

.mn-info-value {
    margin-top: 0.45rem;
    overflow-wrap: anywhere;
    font-size: 0.92rem;
    font-weight: 800;
    color: white;
}
</style>
