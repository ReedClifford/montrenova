<script setup>
import MontreLogo from "@/Components/MontreLogo.vue";

import { Head, Link, router } from "@inertiajs/vue3";

import { computed, ref, watch } from "vue";

const props = defineProps({
    soldWatches: {
        type: Object,

        default: () => ({
            data: [],

            links: [],
        }),
    },

    filters: {
        type: Object,

        default: () => ({
            search: "",
        }),
    },

    soldCount: {
        type: Number,

        default: 0,
    },
});

const search = ref(props.filters.search || "");

const messengerUsername = "montrenova";

let searchTimeout = null;

const watches = computed(() => props.soldWatches?.data || []);

const paginationLinks = computed(() => props.soldWatches?.links || []);

const hasPagination = computed(() => paginationLinks.value.length > 3);

const paginationSummary = computed(() => {
    const pagination = props.soldWatches;

    if (!pagination?.total) return "";

    return `Showing ${pagination.from || 0}-${pagination.to || 0} of ${
        pagination.total
    } sold watches`;
});

const cleanPaginationLabel = (label) => {
    return String(label)
        .replace("« Previous", "‹ Prev")

        .replace("Next »", "Next ›");
};

const watchFullName = (watch) => {
    return `${watch?.brand || ""} ${watch?.model_name || ""}`.trim();
};

const watchReference = (watch) => {
    return watch?.reference_number ? ` Ref. ${watch.reference_number}` : "";
};

const similarInquiryMessage = (watch = null) => {
    if (!watch) {
        return "Hi Montre Nova, I’m looking for a similar watch. Can you help me source one?";
    }

    return `Hi Montre Nova, I’m interested in sourcing a similar piece to ${watchFullName(watch)}${watchReference(watch)}. Do you have available options?`;
};

const openSimilarInquiry = async (watch = null) => {
    const message = similarInquiryMessage(watch);

    try {
        if (navigator?.clipboard?.writeText) {
            await navigator.clipboard.writeText(message);
        }
    } catch (error) {
        console.warn("Unable to copy inquiry message:", error);
    }

    window.open(
        `https\://m.me/${messengerUsername}`,

        "_blank",

        "noopener,noreferrer",
    );
};

const watchImage = (watch) => {
    return (
        watch?.primary_hd_url ||
        watch?.primary_image_url ||
        watch?.image_url ||
        watch?.thumbnail_url ||
        watch?.primary_image?.hd_url ||
        watch?.primary_image?.image_url ||
        watch?.primary_image?.thumbnail_url ||
        watch?.primaryImage?.hd_url ||
        watch?.primaryImage?.image_url ||
        watch?.primaryImage?.thumbnail_url ||
        watch?.images?.[0]?.hd_url ||
        watch?.images?.[0]?.image_url ||
        watch?.images?.[0]?.thumbnail_url ||
        null
    );
};

const soldDateLabel = (watch) => {
    const dateValue =
        watch?.date_sold ||
        watch?.sold_at ||
        watch?.updated_at ||
        watch?.created_at;

    if (!dateValue) return "Recently sold";

    const date = new Date(dateValue);

    if (Number.isNaN(date.getTime())) return "Recently sold";

    return date.toLocaleDateString("en-PH", {
        month: "short",

        day: "2-digit",

        year: "numeric",
    });
};

const soldConditionLabel = (watch) => {
    return watch?.condition || "Curated timepiece";
};

watch(search, () => {
    clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => {
        router.get(
            route("public.sold-watches.index"),

            {
                search: search.value,
            },

            {
                preserveState: true,

                preserveScroll: true,

                replace: true,
            },
        );
    }, 350);
});
</script>

<template>
    <Head title="Sold Watches | Montre Nova">
        <meta
            name="description"
            content="View recently sold watches from Montre Nova and request similar curated timepieces."
        />
    </Head>

    <div
        class="mn-subpage min-h-screen overflow-hidden bg-[#050505] pb-24 text-white md:pb-0"
    >
        <div class="mn-marble-underlay" aria-hidden="true"></div>

        <div class="pointer-events-none fixed inset-0">
            <div
                class="absolute left-[-14rem] top-[-14rem] h-[36rem] w-[36rem] rounded-full bg-white/[0.04] blur-3xl"
            ></div>

            <div
                class="absolute bottom-[-16rem] right-[-12rem] h-[36rem] w-[36rem] rounded-full bg-red-400/[0.04] blur-3xl"
            ></div>
        </div>

        <!-- NAVBAR -->

        <header
            class="mn-unified-header sticky top-0 z-50 border-b border-white/10 bg-[#050505]/90 backdrop-blur-xl"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8"
            >
                <Link href="/" class="flex items-center">
                    <MontreLogo />
                </Link>

                <div class="flex items-center gap-2">
                    <Link
                        href="/#collection"
                        class="hidden rounded-full border border-white/10 bg-white/[0.03] px-4 py-2 text-xs font-semibold text-zinc-300 transition hover:border-white/30 hover:bg-white/[0.06] hover:text-white sm:inline-flex"
                    >
                        Collection
                    </Link>

                    <Link
                        href="/warranty-check"
                        class="hidden rounded-full border border-white/10 bg-white/[0.03] px-4 py-2 text-xs font-semibold text-zinc-300 transition hover:border-white/30 hover:bg-white/[0.06] hover:text-white sm:inline-flex"
                    >
                        Warranty Check
                    </Link>

                    <button
                        type="button"
                        class="rounded-full bg-white px-4 py-2 text-xs font-black text-black transition hover:bg-zinc-200"
                        @click="openSimilarInquiry()"
                    >
                        Source Watch
                    </button>
                </div>
            </div>
        </header>

        <main class="relative z-10">
            <!-- HERO -->

            <section
                class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-14"
            >
                <div
                    class="overflow-hidden rounded-[2.2rem] border border-white/10 bg-[#0B0B0D] p-6 shadow-2xl shadow-black/40 sm:p-8 lg:p-10"
                >
                    <div
                        class="grid gap-8 lg:grid-cols-[1fr_0.8fr] lg:items-end"
                    >
                        <div>
                            <div
                                class="mb-5 inline-flex rounded-full border border-white/15 bg-white/[0.055] px-4 py-2"
                            >
                                <span
                                    class="text-xs font-black uppercase tracking-[0.24em] text-zinc-300"
                                >
                                    Sold Gallery
                                </span>
                            </div>

                            <h1
                                class="max-w-4xl text-4xl font-black leading-[0.95] tracking-[-0.06em] text-white sm:text-6xl lg:text-7xl"
                            >
                                Claimed timepieces from Montre Nova.
                            </h1>

                            <p
                                class="mt-6 max-w-2xl text-sm leading-7 text-zinc-400 sm:text-base"
                            >
                                Browse watches that were recently sold. Missed a
                                piece? Message us and we’ll help source a
                                similar watch based on your preferred model,
                                budget, and condition.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div
                                class="rounded-[1.5rem] border border-white/10 bg-white/[0.03] p-5"
                            >
                                <p class="text-4xl font-black text-white">
                                    {{ soldCount }}+
                                </p>

                                <p
                                    class="mt-1 text-[10px] font-bold uppercase tracking-[0.18em] text-zinc-600"
                                >
                                    Total sold
                                </p>
                            </div>

                            <button
                                type="button"
                                class="rounded-[1.5rem] border border-white/15 bg-white/[0.055] p-5 text-left transition hover:border-red-400/40"
                                @click="openSimilarInquiry()"
                            >
                                <p class="text-lg font-black text-white">
                                    Source Similar
                                </p>

                                <p
                                    class="mt-2 text-xs leading-5 text-red-100/60"
                                >
                                    Tell us your target watch.
                                </p>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SEARCH -->

            <section class="mx-auto max-w-7xl px-4 pb-6 sm:px-6 lg:px-8">
                <div
                    class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-4 sm:p-5"
                >
                    <div
                        class="grid gap-4 md:grid-cols-[1fr_auto] md:items-end"
                    >
                        <div>
                            <p
                                class="mb-3 text-xs font-black uppercase tracking-[0.24em] text-zinc-600"
                            >
                                Search sold watches
                            </p>

                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search brand, model, reference, category..."
                                class="mn-input"
                            />
                        </div>

                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3"
                        >
                            <p class="text-sm text-zinc-500">
                                {{
                                    paginationSummary ||
                                    `${watches.length} sold watches`
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- GALLERY -->

            <section class="mx-auto max-w-7xl px-4 pb-10 sm:px-6 lg:px-8">
                <template v-if="watches.length">
                    <div
                        class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <div
                            v-for="watch in watches"
                            :key="watch.id"
                            class="group overflow-hidden rounded-[1.85rem] border border-white/10 bg-[#0B0B0D]/95 p-3 opacity-95 transition hover:-translate-y-0.5 hover:border-white/30 hover:opacity-100"
                        >
                            <div
                                class="relative aspect-square overflow-hidden rounded-[1.45rem] border border-white/10 bg-[#050505]"
                            >
                                <div class="absolute left-3 top-3 z-10">
                                    <span
                                        class="rounded-full border border-white/15 bg-white/[0.055] px-3 py-1 text-[10px] font-black uppercase tracking-[0.12em] text-zinc-300 backdrop-blur"
                                    >
                                        Sold
                                    </span>
                                </div>

                                <div class="absolute right-3 top-3 z-10">
                                    <span
                                        class="rounded-full border border-white/10 bg-black/60 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-zinc-300 backdrop-blur"
                                    >
                                        Claimed
                                    </span>
                                </div>

                                <img
                                    v-if="watchImage(watch)"
                                    :src="watchImage(watch)"
                                    :alt="`${watch.brand} ${watch.model_name}`"
                                    class="h-full w-full object-cover grayscale-[25%] transition duration-500 group-hover:scale-105 group-hover:grayscale-0"
                                    loading="lazy"
                                />

                                <div
                                    v-else
                                    class="flex h-full items-center justify-center bg-[radial-gradient(circle_at_center,rgba(255,255,255,0.08),transparent_40%)]"
                                >
                                    <img
                                        src="/images/montre-nova-logo.png"
                                        alt="Montre Nova"
                                        class="h-32 w-32 object-contain opacity-70"
                                        loading="lazy"
                                    />
                                </div>

                                <div
                                    class="absolute inset-x-0 bottom-0 z-10 bg-gradient-to-t from-black/90 via-black/55 to-transparent p-4"
                                >
                                    <p
                                        class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400"
                                    >
                                        Sold Date
                                    </p>

                                    <p
                                        class="mt-1 truncate text-sm font-bold text-white"
                                    >
                                        {{ soldDateLabel(watch) }}
                                    </p>
                                </div>
                            </div>

                            <div class="p-2 pt-4">
                                <p
                                    class="truncate text-xs font-bold uppercase tracking-[0.24em] text-zinc-500"
                                >
                                    {{ watch.brand }}
                                </p>

                                <h3
                                    class="mt-2 truncate text-base font-semibold text-white"
                                >
                                    {{ watch.model_name }}
                                </h3>

                                <p class="mt-1 truncate text-sm text-zinc-500">
                                    Ref.

                                    {{
                                        watch.reference_number || "No reference"
                                    }}
                                </p>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <span
                                        class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-xs text-zinc-400"
                                    >
                                        {{ soldConditionLabel(watch) }}
                                    </span>

                                    <span
                                        v-if="watch.category"
                                        class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-xs text-zinc-400"
                                    >
                                        {{ watch.category }}
                                    </span>
                                </div>

                                <div
                                    class="mt-5 grid grid-cols-2 gap-2 border-t border-white/10 pt-4"
                                >
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-2xl border border-white/15 bg-white/[0.055] px-4 py-3 text-sm font-bold text-zinc-300 transition hover:border-red-400/40"
                                        @click="openSimilarInquiry(watch)"
                                    >
                                        Find Similar
                                    </button>

                                    <Link
                                        href="/#collection"
                                        class="inline-flex items-center justify-center rounded-2xl bg-white px-4 py-3 text-sm font-bold text-black transition hover:bg-zinc-200"
                                    >
                                        View Stocks
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PAGINATION -->

                    <div
                        v-if="hasPagination"
                        class="mt-8 flex flex-col items-center justify-between gap-5 rounded-[1.5rem] border border-white/10 bg-[#0B0B0D]/80 p-4 sm:flex-row"
                    >
                        <p class="text-sm text-zinc-500">
                            {{ paginationSummary }}
                        </p>

                        <div
                            class="flex flex-wrap items-center justify-center gap-2"
                        >
                            <template
                                v-for="link in paginationLinks"
                                :key="link.label"
                            >
                                <span
                                    v-if="!link.url"
                                    class="cursor-not-allowed rounded-xl border border-white/5 bg-white/[0.02] px-4 py-2 text-sm font-semibold text-zinc-700"
                                >
                                    {{ cleanPaginationLabel(link.label) }}
                                </span>

                                <Link
                                    v-else
                                    :href="link.url"
                                    preserve-scroll
                                    preserve-state
                                    class="rounded-xl border px-4 py-2 text-sm font-semibold transition"
                                    :class="
                                        link.active
                                            ? 'border-white bg-white text-black'
                                            : 'border-white/10 bg-white/[0.03] text-zinc-400 hover:border-white/30 hover:text-white'
                                    "
                                >
                                    {{ cleanPaginationLabel(link.label) }}
                                </Link>
                            </template>
                        </div>
                    </div>
                </template>

                <div
                    v-else
                    class="rounded-[2rem] border border-white/10 bg-[#0B0B0D] p-10 text-center"
                >
                    <img
                        src="/images/montre-nova-logo.png"
                        alt="Montre Nova"
                        class="mx-auto h-24 w-24 object-contain opacity-70"
                    />

                    <h3 class="mt-6 text-xl font-semibold text-white">
                        No sold watches found.
                    </h3>

                    <p class="mt-3 text-sm leading-7 text-zinc-500">
                        Try changing your search keyword.
                    </p>
                </div>
            </section>
        </main>

        <!-- MOBILE STICKY CTA -->

        <div
            class="fixed inset-x-0 bottom-0 z-[60] border-t border-white/10 bg-[#050505]/95 px-4 py-3 backdrop-blur-xl md:hidden"
        >
            <div class="grid grid-cols-2 gap-3">
                <Link
                    href="/#collection"
                    class="inline-flex items-center justify-center rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3 text-sm font-bold text-white"
                >
                    View Stocks
                </Link>

                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-2xl bg-white px-4 py-3 text-sm font-bold text-black"
                    @click="openSimilarInquiry()"
                >
                    Source Watch
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.mn-input {
    width: 100%;

    border-radius: 1rem;

    border: 1px solid rgb(255 255 255 / 0.1);

    background: #050505;

    padding: 0.95rem 1rem;

    font-size: 0.95rem;

    color: white;

    outline: none;

    transition:
        border-color 150ms ease,
        background-color 150ms ease,
        box-shadow 150ms ease;
}

.mn-input::placeholder {
    color: rgb(63 63 70);
}

.mn-input:focus {
    border-color: rgb(255 255 255 / 0.4);

    background: rgb(255 255 255 / 0.04);

    box-shadow: 0 0 0 2px rgb(255 255 255 / 0.08);
}

/* Montre Nova design system — dark marble, Manrope and restrained monochrome. */
@import url("https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap");
.mn-subpage {
    --mn-bg: #080809;
    --mn-surface: #111113;
    --mn-muted: #a7a7ac;
    min-height: 100vh;
    color: #f2f2f3;
    font-family:
        "Manrope",
        ui-sans-serif,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;
    background: #080809;
    isolation: isolate;
}
.mn-subpage,
.mn-subpage button,
.mn-subpage input,
.mn-subpage select {
    font-family: "Manrope", ui-sans-serif, system-ui, sans-serif;
}
.mn-subpage :is(h1, h2, h3, h4) {
    font-family: "Manrope", ui-sans-serif, system-ui, sans-serif;
    overflow-wrap: break-word;
}
.mn-subpage h1 {
    letter-spacing: -0.055em;
    line-height: 1.09;
    font-weight: 650;
}
.mn-subpage :is(h2, h3) {
    letter-spacing: -0.035em;
    line-height: 1.18;
}
.mn-marble-underlay {
    position: fixed;
    inset: 0;
    z-index: -1;
    pointer-events: none;
    overflow: hidden;
    background:
        radial-gradient(
            ellipse at 73% 12%,
            rgba(64, 65, 70, 0.13),
            transparent 45%
        ),
        linear-gradient(155deg, #0b0b0e 0%, #060607 52%, #0b0b0e 100%);
}
.mn-marble-underlay::before {
    content: "";
    position: absolute;
    inset: -5%;
    opacity: 0.32;
    background-image:
        linear-gradient(
            154deg,
            transparent 0 36%,
            rgba(200, 201, 208, 0.1) 36.09%,
            rgba(200, 201, 208, 0.025) 36.3%,
            transparent 36.6%
        ),
        linear-gradient(
            147deg,
            transparent 0 66%,
            rgba(195, 196, 202, 0.085) 66.08%,
            rgba(195, 196, 202, 0.02) 66.28%,
            transparent 66.55%
        ),
        linear-gradient(
            27deg,
            transparent 0 81%,
            rgba(180, 182, 191, 0.07) 81.07%,
            transparent 81.4%
        );
    filter: blur(0.45px);
    transform: scale(1.08);
}
.mn-marble-underlay::after {
    content: "";
    position: absolute;
    inset: 0;
    background: radial-gradient(
        ellipse at 48% 38%,
        transparent 0%,
        rgba(0, 0, 0, 0.45) 80%
    );
}
.mn-subpage .mn-unified-header {
    border-color: rgba(255, 255, 255, 0.09) !important;
    background: rgba(12, 12, 14, 0.92) !important;
    backdrop-filter: blur(22px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.16);
}
.mn-subpage
    :is(.premium-panel, .gallery-panel, .product-panel, .carousel-shell) {
    border-radius: 12px;
    border-color: rgba(255, 255, 255, 0.11);
    background: linear-gradient(
        160deg,
        rgba(29, 29, 32, 0.96),
        rgba(13, 13, 15, 0.98)
    );
    box-shadow: 0 20px 55px rgba(0, 0, 0, 0.25);
}
.mn-subpage
    :is(
        .primary-action,
        .secondary-action,
        .soft-chip,
        .gallery-arrow,
        .view-chip
    ) {
    border-radius: 5px;
}
.mn-subpage button,
.mn-subpage a {
    -webkit-tap-highlight-color: transparent;
}
.mn-subpage button:focus-visible,
.mn-subpage a:focus-visible,
.mn-subpage input:focus-visible {
    outline: 2px solid #dedee1;
    outline-offset: 3px;
}
.mn-subpage .micro-label {
    color: #a2a2a8;
    font-weight: 700;
    letter-spacing: 0.17em;
}
.mn-subpage :is(.text-red-300, .text-violet-300, .text-red-400) {
    color: #d6d6da !important;
}
.mn-subpage :is(.bg-red-400\/10, .bg-violet-400\/10) {
    background-color: rgba(255, 255, 255, 0.065) !important;
}
.mn-subpage :is(.border-red-400\/20, .border-violet-400\/20) {
    border-color: rgba(255, 255, 255, 0.14) !important;
}
.mn-subpage .mn-float-remove {
    display: none !important;
}
@media (max-width: 639px) {
    .mn-subpage h1 {
        font-size: clamp(1.95rem, 8vw, 2.65rem) !important;
        line-height: 1.14 !important;
        letter-spacing: -0.048em !important;
    }
    .mn-subpage h2 {
        line-height: 1.22;
    }
    .mn-subpage .product-hero-grid {
        grid-template-columns: minmax(0, 1fr) !important;
    }
    .mn-subpage .gallery-stage {
        height: min(88vw, 420px);
    }
    .mn-subpage .primary-action {
        min-height: 48px;
    }
}
@media (prefers-reduced-motion: reduce) {
    .mn-subpage *,
    .mn-subpage *::before,
    .mn-subpage *::after {
        scroll-behavior: auto !important;
    }
}

.mn-subpage .sold-watch-grid,
.mn-subpage .sold-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
</style>
