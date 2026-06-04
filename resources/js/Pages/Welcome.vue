<script setup>
import MontreLogo from "@/Components/MontreLogo.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    canLogin: {
        type: Boolean,
        default: true,
    },
    canRegister: {
        type: Boolean,
        default: false,
    },
    featuredWatch: {
        type: Object,
        default: null,
    },
    watches: {
        type: [Array, Object],
        default: () => [],
    },
    soldWatches: {
        type: Array,
        default: () => [],
    },
    soldCount: {
        type: Number,
        default: null,
    },
});

const watchPagination = computed(() => {
    return Array.isArray(props.watches) ? null : props.watches;
});

const watches = computed(() => {
    if (Array.isArray(props.watches)) {
        return props.watches;
    }

    return props.watches?.data || [];
});

const soldWatches = computed(() => props.soldWatches || []);
const recentSoldWatches = computed(() => soldWatches.value.slice(0, 8));
const featuredWatch = computed(() => props.featuredWatch);

const paginationLinks = computed(() => {
    return watchPagination.value?.links || [];
});

const hasWatchPagination = computed(() => {
    return paginationLinks.value.length > 3;
});

const availableCount = computed(() => {
    return Number(watchPagination.value?.total ?? watches.value.length);
});

const soldTotal = computed(() => {
    if (props.soldCount !== null && props.soldCount !== undefined) {
        return Number(props.soldCount || 0);
    }

    return Number(soldWatches.value.length || 0);
});

const paginationSummary = computed(() => {
    const pagination = watchPagination.value;

    if (!pagination?.total) {
        return "";
    }

    return `Showing ${pagination.from || 0}-${pagination.to || 0} of ${
        pagination.total
    } watches`;
});

const paginationUrl = (url) => {
    return url ? `${url}#collection` : null;
};

const cleanPaginationLabel = (label) => {
    return String(label)
        .replace("&laquo; Previous", "‹ Prev")
        .replace("Next &raquo;", "Next ›");
};

const contactLinks = [
    {
        label: "Messenger",
        description: "Chat with us directly on Facebook Messenger",
        href: "https://m.me/montrenova",
    },
    {
        label: "Viber Community",
        description: "Join our Viber community for latest drops",
        href: "#",
    },
    {
        label: "Instagram",
        description: "View our latest posts and send us a DM",
        href: "https://www.instagram.com/montrenova",
    },
];

const trustItems = [
    {
        title: "Actual HD Photos",
        description:
            "Every listed watch is presented with real product photos so buyers can inspect the exact timepiece before inquiry.",
        label: "Real photos",
    },
    {
        title: "Clear Pricing",
        description:
            "Prices are displayed clearly to make browsing simple, direct, and transparent for every buyer.",
        label: "No guessing",
    },
    {
        title: "Montre Card Warranty",
        description:
            "Selected watches include Montre Card service warranty coverage for movement and internal mechanism concerns.",
        label: "Warranty support",
    },
    {
        title: "Curated Timepieces",
        description:
            "Stocks are carefully selected from brand-new and pre-owned watches suited for collectors and daily wearers.",
        label: "Curated picks",
    },
];

const peso = (value) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 0,
    }).format(Number(value || 0));
};

const finalPrice = (watch) => {
    if (!watch) return 0;

    const discounted = Number(watch.discounted_price || 0);
    const selling = Number(watch.selling_price || 0);
    const price = Number(watch.price || 0);

    if (discounted > 0 && selling > discounted) {
        return discounted;
    }

    return price || selling || discounted || 0;
};

const originalPrice = (watch) => {
    if (!watch) return 0;

    return Number(watch.selling_price || watch.price || 0);
};

const watchImage = (watch) => {
    return (
        watch?.primary_hd_url ||
        watch?.primary_image_url ||
        watch?.image_url ||
        watch?.thumbnail_url ||
        null
    );
};

const statusBadge = (watch) => {
    const status = String(watch?.status || "available").toLowerCase();

    const badges = {
        available: {
            label: "Available",
            className:
                "border-emerald-400/20 bg-emerald-400/10 text-emerald-300",
        },
        reserved: {
            label: "Reserved",
            className: "border-amber-400/20 bg-amber-400/10 text-amber-300",
        },
        sold: {
            label: "Sold",
            className: "border-red-400/20 bg-red-400/10 text-red-300",
        },
        draft: {
            label: "Draft",
            className: "border-zinc-400/20 bg-zinc-400/10 text-zinc-400",
        },
        hidden: {
            label: "Hidden",
            className: "border-zinc-400/20 bg-zinc-400/10 text-zinc-400",
        },
    };

    return badges[status] || badges.available;
};

const isNewDrop = (watch) => {
    if (!watch?.created_at) return false;

    const createdDate = new Date(watch.created_at);
    const today = new Date();

    const differenceInDays =
        (today.getTime() - createdDate.getTime()) / (1000 * 60 * 60 * 24);

    return differenceInDays <= 14;
};

const isFeatured = (watch) => {
    return Boolean(watch?.is_featured);
};

const isBelowSrp = (watch) => {
    return (
        Number(watch?.discounted_price || 0) > 0 &&
        Number(watch?.selling_price || 0) > Number(watch?.discounted_price || 0)
    );
};

const isSold = (watch) => {
    return String(watch?.status || "").toLowerCase() === "sold";
};

const productBadges = (watch) => {
    const badges = [];

    badges.push(statusBadge(watch));

    if (isNewDrop(watch)) {
        badges.push({
            label: "New Drop",
            className: "border-sky-400/20 bg-sky-400/10 text-sky-300",
        });
    }

    if (isBelowSrp(watch)) {
        badges.push({
            label: "Below SRP",
            className: "border-violet-400/20 bg-violet-400/10 text-violet-300",
        });
    }

    if (isFeatured(watch)) {
        badges.push({
            label: "Featured",
            className: "border-white/20 bg-white/10 text-white",
        });
    }

    return badges;
};
</script>

<template>
    <Head title="Montre Nova | Curated Timepieces" />

    <div class="min-h-screen overflow-hidden bg-[#050505] text-white">
        <div class="pointer-events-none fixed inset-0">
            <div
                class="absolute left-[-14rem] top-[-14rem] h-[36rem] w-[36rem] rounded-full bg-white/[0.04] blur-3xl"
            ></div>
            <div
                class="absolute right-[-16rem] top-[18rem] h-[34rem] w-[34rem] rounded-full bg-zinc-700/10 blur-3xl"
            ></div>
            <div
                class="absolute bottom-[-16rem] left-[28%] h-[36rem] w-[36rem] rounded-full bg-white/[0.025] blur-3xl"
            ></div>
        </div>

        <!-- NAVBAR -->
        <!-- NAVBAR -->
        <header
            class="sticky top-0 z-50 border-b border-white/10 bg-[#050505]/85 backdrop-blur-xl"
        >
            <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
                <!-- TOP NAV ROW -->
                <div class="flex items-center justify-between gap-3">
                    <a href="/" class="flex min-w-0 items-center">
                        <MontreLogo />
                    </a>

                    <!-- DESKTOP NAV -->
                    <nav
                        class="hidden items-center gap-8 text-sm font-medium text-zinc-500 md:flex"
                    >
                        <a
                            href="#collection"
                            class="transition hover:text-white"
                        >
                            Collection
                        </a>

                        <a
                            v-if="recentSoldWatches.length"
                            href="#recently-sold"
                            class="transition hover:text-white"
                        >
                            Sold
                        </a>

                        <a href="#process" class="transition hover:text-white">
                            Process
                        </a>

                        <a href="#warranty" class="transition hover:text-white">
                            Warranty
                        </a>

                        <a href="#contact" class="transition hover:text-white">
                            Contact
                        </a>
                    </nav>

                    <!-- MOBILE CTA -->
                    <a
                        href="#contact"
                        class="inline-flex shrink-0 items-center justify-center rounded-2xl bg-white px-4 py-2 text-xs font-bold text-black transition hover:bg-zinc-200 md:hidden"
                    >
                        Message
                    </a>

                    <div
                        v-if="props.canLogin"
                        class="hidden items-center gap-3"
                    >
                        <!-- Admin login hidden for public page -->
                    </div>
                </div>

                <!-- MOBILE SCROLL NAV -->
                <nav
                    class="mt-3 flex gap-2 overflow-x-auto pb-1 text-xs font-semibold text-zinc-400 md:hidden [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    <a
                        href="#collection"
                        class="shrink-0 rounded-full border border-white/10 bg-white/[0.03] px-4 py-2 transition hover:border-white/30 hover:text-white"
                    >
                        Collection
                    </a>

                    <a
                        v-if="recentSoldWatches.length"
                        href="#recently-sold"
                        class="shrink-0 rounded-full border border-white/10 bg-white/[0.03] px-4 py-2 transition hover:border-white/30 hover:text-white"
                    >
                        Sold
                    </a>

                    <a
                        href="#process"
                        class="shrink-0 rounded-full border border-white/10 bg-white/[0.03] px-4 py-2 transition hover:border-white/30 hover:text-white"
                    >
                        Process
                    </a>

                    <a
                        href="#warranty"
                        class="shrink-0 rounded-full border border-white/10 bg-white/[0.03] px-4 py-2 transition hover:border-white/30 hover:text-white"
                    >
                        Warranty
                    </a>

                    <a
                        href="#contact"
                        class="shrink-0 rounded-full border border-white/10 bg-white/[0.03] px-4 py-2 transition hover:border-white/30 hover:text-white"
                    >
                        Contact
                    </a>
                </nav>
            </div>
        </header>

        <main class="relative z-10">
            <!-- HERO -->
            <section
                class="mx-auto grid max-w-7xl items-center gap-14 px-6 py-20 lg:grid-cols-[1fr_0.9fr] lg:px-8 lg:py-28"
            >
                <div>
                    <div
                        class="mb-6 inline-flex items-center gap-3 rounded-full border border-white/10 bg-white/[0.03] px-4 py-2"
                    >
                        <span class="h-2 w-2 rounded-full bg-white"></span>
                        <span
                            class="text-xs font-medium uppercase tracking-[0.28em] text-zinc-500"
                        >
                            Brand-new & Preowned Watches
                        </span>
                    </div>

                    <h2
                        class="max-w-4xl text-5xl font-semibold leading-[1.05] tracking-tight text-white sm:text-6xl lg:text-7xl"
                    >
                        Curated watches for your next signature timepiece.
                    </h2>

                    <p
                        class="mt-7 max-w-2xl text-base leading-8 text-zinc-400 sm:text-lg"
                    >
                        Explore brand new and pre-owned watches selected with
                        care, presented with actual HD photos, clear pricing,
                        and a smooth inquiry process.
                    </p>

                    <div class="mt-10 flex flex-col gap-4 sm:flex-row">
                        <a
                            href="#collection"
                            class="inline-flex items-center justify-center rounded-2xl bg-white px-7 py-4 text-sm font-semibold text-black transition hover:bg-zinc-200"
                        >
                            View Collection
                        </a>

                        <a
                            href="#contact"
                            class="inline-flex items-center justify-center rounded-2xl border border-white/10 bg-white/[0.03] px-7 py-4 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/[0.06]"
                        >
                            Message Us
                        </a>
                    </div>

                    <div
                        class="mt-12 grid max-w-2xl grid-cols-2 gap-4 sm:grid-cols-4"
                    >
                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.03] p-5"
                        >
                            <p class="text-2xl font-semibold text-white">HD</p>
                            <p
                                class="mt-1 text-xs uppercase tracking-widest text-zinc-600"
                            >
                                Actual Photos
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.03] p-5"
                        >
                            <p class="text-2xl font-semibold text-white">1Y</p>
                            <p
                                class="mt-1 text-xs uppercase tracking-widest text-zinc-600"
                            >
                                Warranty
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.03] p-5"
                        >
                            <p class="text-2xl font-semibold text-white">
                                {{ availableCount }}
                            </p>
                            <p
                                class="mt-1 text-xs uppercase tracking-widest text-zinc-600"
                            >
                                Available
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.03] p-5"
                        >
                            <p class="text-2xl font-semibold text-white">
                                {{ soldTotal }}+
                            </p>
                            <p
                                class="mt-1 text-xs uppercase tracking-widest text-zinc-600"
                            >
                                Sold
                            </p>
                        </div>
                    </div>
                </div>

                <!-- FEATURED WATCH -->
                <div class="relative">
                    <div
                        class="absolute inset-0 rounded-[2.5rem] bg-white/[0.04] blur-3xl"
                    ></div>

                    <div
                        class="relative overflow-hidden rounded-[2.5rem] border border-white/10 bg-[#0B0B0D]/90 p-5 shadow-2xl shadow-black/50"
                    >
                        <div
                            class="relative aspect-[4/5] overflow-hidden rounded-[2rem] border border-white/10 bg-[#050505]"
                        >
                            <div
                                v-if="featuredWatch"
                                class="absolute left-3 top-3 z-10 flex flex-wrap gap-2"
                            >
                                <span
                                    v-for="badge in productBadges(
                                        featuredWatch,
                                    )"
                                    :key="badge.label"
                                    class="rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-[0.12em] backdrop-blur"
                                    :class="badge.className"
                                >
                                    {{ badge.label }}
                                </span>
                            </div>

                            <img
                                v-if="watchImage(featuredWatch)"
                                :src="watchImage(featuredWatch)"
                                :alt="`${featuredWatch.brand} ${featuredWatch.model_name}`"
                                class="h-full w-full object-cover"
                            />

                            <div
                                v-else
                                class="flex h-full items-center justify-center bg-[radial-gradient(circle_at_center,rgba(255,255,255,0.10),transparent_38%)]"
                            >
                                <img
                                    src="/images/montre-nova-logo.png"
                                    alt="Montre Nova"
                                    class="h-72 w-72 object-contain opacity-90"
                                />
                            </div>
                        </div>

                        <div class="p-3 pt-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p
                                        class="text-xs uppercase tracking-[0.28em] text-zinc-500"
                                    >
                                        Featured Drop
                                    </p>

                                    <h3
                                        class="mt-2 text-2xl font-semibold tracking-tight text-white"
                                    >
                                        <template v-if="featuredWatch">
                                            {{ featuredWatch.brand }}
                                            {{ featuredWatch.model_name }}
                                        </template>

                                        <template v-else>
                                            Premium Timepiece
                                        </template>
                                    </h3>

                                    <p class="mt-2 text-sm text-zinc-500">
                                        <template v-if="featuredWatch">
                                            Ref.
                                            {{
                                                featuredWatch.reference_number ||
                                                "No reference"
                                            }}
                                            |
                                            {{
                                                featuredWatch.condition ||
                                                "Condition available upon request"
                                            }}
                                        </template>

                                        <template v-else>
                                            Brand New | Complete Set | Available
                                        </template>
                                    </p>
                                </div>

                                <span
                                    v-if="featuredWatch"
                                    class="shrink-0 rounded-full border px-3 py-1 text-xs font-medium"
                                    :class="
                                        statusBadge(featuredWatch).className
                                    "
                                >
                                    {{ statusBadge(featuredWatch).label }}
                                </span>
                            </div>

                            <div
                                class="mt-6 flex items-center justify-between border-t border-white/10 pt-5"
                            >
                                <p class="text-sm text-zinc-500">
                                    Starting from
                                </p>

                                <div class="text-right">
                                    <p
                                        class="text-2xl font-semibold text-white"
                                    >
                                        {{
                                            featuredWatch
                                                ? peso(
                                                      finalPrice(featuredWatch),
                                                  )
                                                : "₱XX,XXX"
                                        }}
                                    </p>

                                    <p
                                        v-if="isBelowSrp(featuredWatch)"
                                        class="text-sm text-zinc-600 line-through"
                                    >
                                        {{ peso(originalPrice(featuredWatch)) }}
                                    </p>
                                </div>
                            </div>

                            <Link
                                v-if="featuredWatch"
                                :href="
                                    route(
                                        'public.watches.show',
                                        featuredWatch.id,
                                    )
                                "
                                class="mt-5 inline-flex w-full items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200"
                            >
                                View Details
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- TRUST SECTION -->
            <section
                class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12"
            >
                <div
                    class="overflow-hidden rounded-[2rem] border border-white/10 bg-[#0A0A0B] p-5 shadow-2xl shadow-black/30 sm:p-8 lg:p-10"
                >
                    <div
                        class="grid gap-8 lg:grid-cols-[0.8fr_1.2fr] lg:items-start"
                    >
                        <div>
                            <p
                                class="text-[11px] font-black uppercase tracking-[0.32em] text-zinc-500"
                            >
                                Why Buy From Us
                            </p>

                            <h2
                                class="mt-3 max-w-xl text-3xl font-black tracking-[-0.05em] text-white sm:text-4xl"
                            >
                                Built for smoother and safer watch deals.
                            </h2>

                            <p
                                class="mt-4 max-w-xl text-sm leading-7 text-zinc-400"
                            >
                                Montre Nova keeps the buying experience simple
                                with actual photos, clear pricing, curated
                                stocks, and after-sales service support.
                            </p>

                            <div
                                class="mt-6 grid grid-cols-2 gap-2 sm:max-w-md sm:grid-cols-4"
                            >
                                <div
                                    class="rounded-2xl border border-white/10 bg-white/[0.035] p-4"
                                >
                                    <p class="text-xl font-black text-white">
                                        HD
                                    </p>
                                    <p
                                        class="mt-1 text-[9px] font-bold uppercase tracking-[0.18em] text-zinc-600"
                                    >
                                        Photos
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-white/10 bg-white/[0.035] p-4"
                                >
                                    <p class="text-xl font-black text-white">
                                        1Y
                                    </p>
                                    <p
                                        class="mt-1 text-[9px] font-bold uppercase tracking-[0.18em] text-zinc-600"
                                    >
                                        Warranty
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-white/10 bg-white/[0.035] p-4"
                                >
                                    <p class="text-xl font-black text-white">
                                        {{ availableCount }}
                                    </p>
                                    <p
                                        class="mt-1 text-[9px] font-bold uppercase tracking-[0.18em] text-zinc-600"
                                    >
                                        Stocks
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-white/10 bg-white/[0.035] p-4"
                                >
                                    <p class="text-xl font-black text-white">
                                        {{ soldTotal }}+
                                    </p>
                                    <p
                                        class="mt-1 text-[9px] font-bold uppercase tracking-[0.18em] text-zinc-600"
                                    >
                                        Sold
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div
                                v-for="item in trustItems"
                                :key="item.title"
                                class="group rounded-[1.5rem] border border-white/10 bg-white/[0.03] p-5 transition hover:border-white/30 hover:bg-white/[0.055]"
                            >
                                <div
                                    class="flex items-start justify-between gap-4"
                                >
                                    <div>
                                        <span
                                            class="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.14em] text-emerald-300"
                                        >
                                            {{ item.label }}
                                        </span>

                                        <h3
                                            class="mt-4 text-lg font-black tracking-[-0.03em] text-white"
                                        >
                                            {{ item.title }}
                                        </h3>
                                    </div>

                                    <span
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl border border-white/10 bg-[#050505] text-sm font-black text-zinc-500 transition group-hover:border-white/30 group-hover:text-white"
                                    >
                                        ✓
                                    </span>
                                </div>

                                <p class="mt-3 text-sm leading-6 text-zinc-500">
                                    {{ item.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- COLLECTION -->
            <section
                id="collection"
                class="mx-auto max-w-7xl px-6 py-20 lg:px-8"
            >
                <div
                    class="mb-10 flex flex-col justify-between gap-5 md:flex-row md:items-end"
                >
                    <div>
                        <p
                            class="text-xs font-medium uppercase tracking-[0.32em] text-zinc-500"
                        >
                            Collection
                        </p>

                        <h2
                            class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl"
                        >
                            Available Watches
                        </h2>

                        <p
                            class="mt-4 max-w-2xl text-sm leading-7 text-zinc-400"
                        >
                            Browse real-time available watch stocks from Montre
                            Nova.
                        </p>

                        <p
                            v-if="paginationSummary"
                            class="mt-2 text-xs font-medium uppercase tracking-[0.18em] text-zinc-600"
                        >
                            {{ paginationSummary }}
                        </p>
                    </div>

                    <a
                        href="#contact"
                        class="inline-flex rounded-2xl border border-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/[0.04]"
                    >
                        Ask for Latest Stocks
                    </a>
                </div>

                <template v-if="watches.length">
                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        <div
                            v-for="watch in watches"
                            :key="watch.id"
                            class="group overflow-hidden rounded-[2rem] border border-white/10 bg-[#0B0B0D]/90 p-4 transition hover:border-white/30"
                            :class="isSold(watch) ? 'opacity-70' : ''"
                        >
                            <div
                                class="relative aspect-square overflow-hidden rounded-[1.5rem] border border-white/10 bg-[#050505]"
                            >
                                <div
                                    class="absolute left-3 top-3 z-10 flex flex-wrap gap-1.5"
                                >
                                    <span
                                        v-for="badge in productBadges(
                                            watch,
                                        ).slice(0, 3)"
                                        :key="badge.label"
                                        class="rounded-full border px-2.5 py-1 text-[9px] font-black uppercase tracking-[0.1em] backdrop-blur"
                                        :class="badge.className"
                                    >
                                        {{ badge.label }}
                                    </span>
                                </div>

                                <img
                                    v-if="watchImage(watch)"
                                    :src="watchImage(watch)"
                                    :alt="`${watch.brand} ${watch.model_name}`"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                />

                                <div
                                    v-else
                                    class="flex h-full items-center justify-center bg-[radial-gradient(circle_at_center,rgba(255,255,255,0.08),transparent_40%)]"
                                >
                                    <img
                                        src="/images/montre-nova-logo.png"
                                        alt="Montre Nova"
                                        class="h-40 w-40 object-contain opacity-70"
                                    />
                                </div>
                            </div>

                            <div class="p-2 pt-5">
                                <div
                                    class="flex items-start justify-between gap-4"
                                >
                                    <div>
                                        <p
                                            class="text-xs font-medium uppercase tracking-[0.26em] text-zinc-500"
                                        >
                                            {{ watch.brand }}
                                        </p>

                                        <h3
                                            class="mt-2 text-lg font-semibold text-white"
                                        >
                                            {{ watch.model_name }}
                                        </h3>

                                        <p class="mt-1 text-sm text-zinc-500">
                                            Ref.
                                            {{
                                                watch.reference_number ||
                                                "No reference"
                                            }}
                                        </p>
                                    </div>

                                    <span
                                        class="shrink-0 rounded-full border px-3 py-1 text-xs font-medium"
                                        :class="statusBadge(watch).className"
                                    >
                                        {{ statusBadge(watch).label }}
                                    </span>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <span
                                        class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-xs text-zinc-400"
                                    >
                                        {{
                                            watch.condition ||
                                            "Condition upon request"
                                        }}
                                    </span>

                                    <span
                                        v-if="watch.category"
                                        class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-xs text-zinc-400"
                                    >
                                        {{ watch.category }}
                                    </span>
                                </div>

                                <div
                                    class="mt-6 flex items-center justify-between border-t border-white/10 pt-5"
                                >
                                    <div>
                                        <p
                                            class="text-xl font-semibold text-white"
                                        >
                                            {{ peso(finalPrice(watch)) }}
                                        </p>

                                        <p
                                            v-if="isBelowSrp(watch)"
                                            class="text-sm text-zinc-600 line-through"
                                        >
                                            {{ peso(originalPrice(watch)) }}
                                        </p>
                                    </div>

                                    <Link
                                        :href="
                                            route(
                                                'public.watches.show',
                                                watch.id,
                                            )
                                        "
                                        class="text-sm font-medium text-zinc-300 transition group-hover:text-white"
                                    >
                                        View Details
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PAGINATION -->
                    <div
                        v-if="hasWatchPagination"
                        class="mt-10 flex flex-col items-center justify-between gap-5 rounded-[1.5rem] border border-white/10 bg-[#0B0B0D]/80 p-4 sm:flex-row"
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
                                    :href="paginationUrl(link.url)"
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
                        No available watches yet.
                    </h3>

                    <p class="mt-3 text-sm leading-7 text-zinc-500">
                        Stocks will appear here once they are marked as
                        available and visible from the admin dashboard.
                    </p>

                    <a
                        href="#contact"
                        class="mt-6 inline-flex rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200"
                    >
                        Message Us for Availability
                    </a>
                </div>
            </section>

            <!-- RECENTLY SOLD -->
            <section
                v-if="recentSoldWatches.length"
                id="recently-sold"
                class="mx-auto max-w-7xl px-6 py-16 lg:px-8"
            >
                <div
                    class="mb-8 flex flex-col justify-between gap-5 md:flex-row md:items-end"
                >
                    <div>
                        <p
                            class="text-xs font-medium uppercase tracking-[0.32em] text-zinc-500"
                        >
                            Recently Sold
                        </p>

                        <h2
                            class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl"
                        >
                            Claimed Timepieces
                        </h2>

                        <p
                            class="mt-4 max-w-2xl text-sm leading-7 text-zinc-400"
                        >
                            A glimpse of recently sold watches from Montre Nova.
                            Message us if you want us to source a similar piece.
                        </p>
                    </div>

                    <a
                        href="#contact"
                        class="inline-flex rounded-2xl border border-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/[0.04]"
                    >
                        Source Similar Watch
                    </a>
                </div>

                <div
                    class="flex gap-4 overflow-x-auto pb-4 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    <div
                        v-for="watch in recentSoldWatches"
                        :key="watch.id"
                        class="group min-w-[250px] max-w-[250px] overflow-hidden rounded-[1.75rem] border border-white/10 bg-[#0B0B0D]/90 p-3 opacity-90 transition hover:border-white/30 hover:opacity-100 sm:min-w-[280px] sm:max-w-[280px]"
                    >
                        <div
                            class="relative aspect-square overflow-hidden rounded-[1.35rem] border border-white/10 bg-[#050505]"
                        >
                            <div class="absolute left-3 top-3 z-10">
                                <span
                                    class="rounded-full border border-red-400/20 bg-red-400/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.12em] text-red-300 backdrop-blur"
                                >
                                    Sold
                                </span>
                            </div>

                            <div
                                class="absolute inset-x-0 bottom-0 z-10 bg-gradient-to-t from-black/80 to-transparent p-4"
                            >
                                <p
                                    class="text-xs font-bold uppercase tracking-[0.18em] text-zinc-300"
                                >
                                    Recently Claimed
                                </p>
                            </div>

                            <img
                                v-if="watchImage(watch)"
                                :src="watchImage(watch)"
                                :alt="`${watch.brand} ${watch.model_name}`"
                                class="h-full w-full object-cover grayscale-[20%] transition duration-500 group-hover:scale-105 group-hover:grayscale-0"
                            />

                            <div
                                v-else
                                class="flex h-full items-center justify-center bg-[radial-gradient(circle_at_center,rgba(255,255,255,0.08),transparent_40%)]"
                            >
                                <img
                                    src="/images/montre-nova-logo.png"
                                    alt="Montre Nova"
                                    class="h-32 w-32 object-contain opacity-70"
                                />
                            </div>
                        </div>

                        <div class="p-2 pt-4">
                            <p
                                class="text-xs font-medium uppercase tracking-[0.24em] text-zinc-500"
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
                                {{ watch.reference_number || "No reference" }}
                            </p>

                            <div
                                class="mt-4 flex items-center justify-between border-t border-white/10 pt-4"
                            >
                                <span
                                    class="rounded-full border border-red-400/20 bg-red-400/10 px-3 py-1 text-xs font-semibold text-red-300"
                                >
                                    Sold
                                </span>

                                <a
                                    href="#contact"
                                    class="text-sm font-medium text-zinc-300 transition hover:text-white"
                                >
                                    Find Similar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- PROCESS -->
            <section id="process" class="mx-auto max-w-7xl px-6 py-20 lg:px-8">
                <div class="grid gap-5 lg:grid-cols-3">
                    <div
                        class="rounded-[2rem] border border-white/10 bg-[#0B0B0D]/90 p-8"
                    >
                        <p
                            class="text-xs font-medium uppercase tracking-[0.28em] text-zinc-600"
                        >
                            01
                        </p>

                        <h3 class="mt-4 text-xl font-semibold text-white">
                            Order & Purchase Process
                        </h3>

                        <p class="mt-4 text-sm leading-7 text-zinc-400">
                            Message us through our official channels to confirm
                            availability, request details, or reserve a watch.
                        </p>
                    </div>

                    <div
                        id="warranty"
                        class="rounded-[2rem] border border-white/10 bg-[#0B0B0D]/90 p-8"
                    >
                        <p
                            class="text-xs font-medium uppercase tracking-[0.28em] text-zinc-600"
                        >
                            02
                        </p>

                        <h3 class="mt-4 text-xl font-semibold text-white">
                            Service Warranty
                        </h3>

                        <p class="mt-4 text-sm leading-7 text-zinc-400">
                            Montre Card warranty coverage is valid for one year
                            from the date of purchase for movement and internal
                            mechanism defects.
                        </p>
                    </div>

                    <div
                        class="rounded-[2rem] border border-white/10 bg-[#0B0B0D]/90 p-8"
                    >
                        <p
                            class="text-xs font-medium uppercase tracking-[0.28em] text-zinc-600"
                        >
                            03
                        </p>

                        <h3 class="mt-4 text-xl font-semibold text-white">
                            Payment Methods
                        </h3>

                        <p class="mt-4 text-sm leading-7 text-zinc-400">
                            Accepted payment methods include cash, Maribank,
                            GoTyme, QR code payments, and selected trade-ins
                            subject to evaluation.
                        </p>
                    </div>
                </div>
            </section>

            <!-- CONTACT -->
            <section id="contact" class="mx-auto max-w-7xl px-6 py-20 lg:px-8">
                <div
                    class="overflow-hidden rounded-[2.5rem] border border-white/10 bg-[#0B0B0D] p-8 sm:p-12"
                >
                    <div
                        class="grid gap-10 lg:grid-cols-[1fr_0.8fr] lg:items-center"
                    >
                        <div>
                            <p
                                class="text-xs font-medium uppercase tracking-[0.32em] text-zinc-500"
                            >
                                Get in Touch
                            </p>

                            <h2
                                class="mt-4 max-w-2xl text-3xl font-semibold tracking-tight text-white sm:text-5xl"
                            >
                                Looking for your next timepiece?
                            </h2>

                            <p
                                class="mt-5 max-w-2xl text-sm leading-7 text-zinc-400 sm:text-base"
                            >
                                Message Montre Nova for available stocks,
                                reservations, and curated recommendations.
                            </p>
                        </div>

                        <div class="space-y-3">
                            <a
                                v-for="link in contactLinks"
                                :key="link.label"
                                :href="link.href"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="group flex items-center justify-between rounded-2xl border border-white/10 bg-[#050505] px-5 py-4 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/[0.04]"
                                :class="
                                    link.href === '#'
                                        ? 'pointer-events-none opacity-50'
                                        : ''
                                "
                            >
                                <div>
                                    <p class="font-semibold text-white">
                                        {{ link.label }}
                                    </p>

                                    <p
                                        class="mt-1 text-xs font-normal leading-5 text-zinc-500"
                                    >
                                        {{ link.description }}
                                    </p>
                                </div>

                                <span
                                    class="text-zinc-500 transition group-hover:text-white"
                                >
                                    →
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="relative z-10 border-t border-white/10">
            <div
                class="mx-auto flex max-w-7xl flex-col justify-between gap-4 px-6 py-8 text-sm text-zinc-600 md:flex-row md:items-center lg:px-8"
            >
                <p>
                    © {{ new Date().getFullYear() }} Montre Nova. Curated
                    timepieces.
                </p>

                <p>Minimalist luxury watch boutique.</p>
            </div>
        </footer>
    </div>
</template>
