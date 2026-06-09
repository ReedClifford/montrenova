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
        type: [Array, Object],
        default: () => [],
    },
    recentSoldWatches: {
        type: [Array, Object],
        default: () => [],
    },
    soldCount: {
        type: Number,
        default: null,
    },
    soldThisMonthCount: {
        type: Number,
        default: 0,
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

const toCollectionArray = (collection) => {
    if (Array.isArray(collection)) {
        return collection;
    }

    if (Array.isArray(collection?.data)) {
        return collection.data;
    }

    return [];
};

const soldTimestamp = (watch) => {
    const dateValue =
        watch?.date_sold ||
        watch?.sold_at ||
        watch?.updated_at ||
        watch?.created_at;

    if (!dateValue) return 0;

    const date = new Date(dateValue);

    return Number.isNaN(date.getTime()) ? 0 : date.getTime();
};

const soldWatches = computed(() => {
    const directSoldWatches = toCollectionArray(props.soldWatches);
    const recentSoldWatchesProp = toCollectionArray(props.recentSoldWatches);

    const source = directSoldWatches.length
        ? directSoldWatches
        : recentSoldWatchesProp;

    return [...source].sort((a, b) => soldTimestamp(b) - soldTimestamp(a));
});

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

const soldThisMonthCount = computed(() => {
    return Number(props.soldThisMonthCount || 0);
});

const soldProofCount = computed(() => {
    return soldThisMonthCount.value;
});

const soldMonthLabel = computed(() => {
    return new Date().toLocaleDateString("en-PH", {
        month: "long",
        year: "numeric",
    });
});

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

const paginationSummary = computed(() => {
    const pagination = watchPagination.value;

    if (!pagination?.total) {
        return "";
    }

    return `Showing ${pagination.from || 0}-${pagination.to || 0} of ${pagination.total} watches`;
});

const paginationUrl = (url) => {
    return url ? `${url}#collection` : null;
};

const cleanPaginationLabel = (label) => {
    return String(label)
        .replace("&laquo; Previous", "‹ Prev")
        .replace("Next &raquo;", "Next ›");
};

const messengerUsername = "montrenova";

const watchFullName = (watch) => {
    if (!watch) return "this watch";

    return `${watch.brand || ""} ${watch.model_name || ""}`.trim();
};

const watchReference = (watch) => {
    return watch?.reference_number ? ` Ref. ${watch.reference_number}` : "";
};

const inquiryMessage = (watch = null) => {
    if (!watch) {
        return "Hi Montre Nova, I’m interested in your available watches. Can you send me the latest stocks?";
    }

    return `Hi Montre Nova, I’m interested in ${watchFullName(watch)}${watchReference(watch)}. Is this still available?`;
};

const similarInquiryMessage = (watch = null) => {
    if (!watch) {
        return "Hi Montre Nova, I’m looking for a similar watch. Can you help me source one?";
    }

    return `Hi Montre Nova, I’m interested in sourcing a similar piece to ${watchFullName(watch)}${watchReference(watch)}. Do you have available options?`;
};

const openMessengerInquiry = async (watch = null) => {
    const message = inquiryMessage(watch);

    try {
        if (navigator?.clipboard?.writeText) {
            await navigator.clipboard.writeText(message);
        }
    } catch (error) {
        console.warn("Unable to copy inquiry message:", error);
    }

    window.open(
        `https://m.me/${messengerUsername}`,
        "_blank",
        "noopener,noreferrer",
    );
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
        `https://m.me/${messengerUsername}`,
        "_blank",
        "noopener,noreferrer",
    );
};

const contactLinks = [
    {
        label: "Messenger",
        description: "Chat with us directly on Facebook Messenger",
        href: "https://m.me/montrenova",
        icon: "FB",
    },
    {
        label: "TikTok",
        description: "Message us on TikTok for inquiries and watch updates",
        href: "https://www.tiktok.com/@montre_nova",
        icon: "TT",
    },
    {
        label: "Instagram",
        description: "View our latest posts and send us a DM",
        href: "https://www.instagram.com/montrenova",
        icon: "IG",
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

const placeholderImage = `data:image/svg+xml;utf8,${encodeURIComponent(`
<svg xmlns="http://www.w3.org/2000/svg" width="900" height="900" viewBox="0 0 900 900">
    <rect width="900" height="900" fill="#050505"/>
    <circle cx="450" cy="405" r="165" fill="#101010" stroke="#2A2A2A" stroke-width="2"/>
    <text x="450" y="430" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="86" font-weight="800" fill="#FFFFFF" letter-spacing="-8">MN</text>
    <text x="450" y="560" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="28" font-weight="700" fill="#8A8A8A" letter-spacing="8">MONTRE NOVA</text>
</svg>`)}`;

const normalizeImageUrl = (url) => {
    if (!url) return null;

    const cleanUrl = String(url).trim();

    if (!cleanUrl) return null;

    if (
        cleanUrl.startsWith("http://") ||
        cleanUrl.startsWith("https://") ||
        cleanUrl.startsWith("data:") ||
        cleanUrl.startsWith("blob:") ||
        cleanUrl.startsWith("/")
    ) {
        return cleanUrl;
    }

    if (cleanUrl.startsWith("storage/")) {
        return `/${cleanUrl}`;
    }

    if (cleanUrl.startsWith("public/")) {
        return `/storage/${cleanUrl.replace(/^public\//, "")}`;
    }

    return `/storage/${cleanUrl}`;
};

const watchImage = (watch) => {
    return normalizeImageUrl(
        watch?.primary_hd_url ||
            watch?.primary_image_url ||
            watch?.image_url ||
            watch?.thumbnail_url ||
            watch?.image_path ||
            watch?.thumbnail_path ||
            watch?.path ||
            watch?.url ||
            watch?.primary_image?.primary_hd_url ||
            watch?.primary_image?.primary_image_url ||
            watch?.primary_image?.image_url ||
            watch?.primary_image?.thumbnail_url ||
            watch?.primary_image?.image_path ||
            watch?.primary_image?.thumbnail_path ||
            watch?.primary_image?.file_path ||
            watch?.primary_image?.path ||
            watch?.primary_image?.url ||
            watch?.primaryImage?.primary_hd_url ||
            watch?.primaryImage?.primary_image_url ||
            watch?.primaryImage?.image_url ||
            watch?.primaryImage?.thumbnail_url ||
            watch?.primaryImage?.image_path ||
            watch?.primaryImage?.thumbnail_path ||
            watch?.primaryImage?.file_path ||
            watch?.primaryImage?.path ||
            watch?.primaryImage?.url ||
            null,
    );
};

const handleImageError = (event) => {
    const image = event?.target;

    if (!image) return;

    if (image.src !== placeholderImage) {
        image.src = placeholderImage;
        return;
    }

    image.style.display = "none";
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
            className: "border-white/15 bg-white/10 text-zinc-200",
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

    <div
        class="min-h-screen overflow-hidden bg-[#030303] pb-24 text-white selection:bg-white selection:text-black md:pb-0"
    >
        <!-- PREMIUM SOFT GLOW BACKGROUND -->
        <div class="pointer-events-none fixed inset-0 z-0 overflow-hidden">
            <div
                class="absolute inset-0 bg-[linear-gradient(180deg,#020202_0%,#070707_46%,#030303_100%)]"
            ></div>
            <div
                class="absolute -left-40 -top-44 h-[34rem] w-[34rem] rounded-full bg-white/[0.055] blur-[115px]"
            ></div>
            <div
                class="absolute -right-48 top-[16rem] h-[36rem] w-[36rem] rounded-full bg-zinc-400/[0.06] blur-[125px]"
            ></div>
            <div
                class="absolute left-1/2 top-[50rem] h-[30rem] w-[42rem] -translate-x-1/2 rounded-full bg-white/[0.035] blur-[135px]"
            ></div>
            <div
                class="absolute bottom-[-18rem] right-[8%] h-[32rem] w-[32rem] rounded-full bg-zinc-300/[0.035] blur-[120px]"
            ></div>
            <div
                class="absolute inset-x-0 top-0 h-96 bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.07),transparent_42%)]"
            ></div>
            <div
                class="absolute inset-x-8 top-0 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent sm:inset-x-16 lg:inset-x-28"
            ></div>
            <div
                class="absolute inset-0 bg-[linear-gradient(90deg,rgba(255,255,255,0.025),transparent_18%,transparent_82%,rgba(255,255,255,0.018))]"
            ></div>
            <div
                class="absolute inset-x-0 bottom-0 h-96 bg-gradient-to-t from-black/85 via-black/35 to-transparent"
            ></div>
        </div>

        <!-- NAVBAR -->
        <header
            class="sticky top-0 z-50 border-b border-white/10 bg-black/70 backdrop-blur-xl"
        >
            <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between gap-4">
                    <a
                        href="/"
                        class="flex min-w-0 items-center transition duration-300 hover:opacity-80"
                    >
                        <MontreLogo />
                    </a>

                    <nav
                        class="hidden items-center gap-7 text-[13px] font-semibold text-zinc-400 lg:flex"
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
                            Sold Gallery
                        </a>

                        <a href="#process" class="transition hover:text-white">
                            How to Order
                        </a>

                        <a href="#contact" class="transition hover:text-white">
                            Contact
                        </a>
                    </nav>

                    <div class="hidden items-center gap-3 md:flex">
                        <Link
                            href="/warranty-check"
                            class="rounded-lg border border-white/10 bg-white/[0.035] px-5 py-2.5 text-xs font-bold uppercase tracking-[0.16em] text-zinc-300 transition hover:border-white/30 hover:bg-white/[0.07] hover:text-white"
                        >
                            Warranty Check
                        </Link>

                        <button
                            type="button"
                            class="rounded-lg bg-white px-5 py-2.5 text-xs font-black uppercase tracking-[0.16em] text-black transition hover:bg-zinc-200"
                            @click="openMessengerInquiry()"
                        >
                            Message Us
                        </button>
                    </div>

                    <button
                        type="button"
                        class="inline-flex shrink-0 items-center justify-center rounded-lg bg-white px-4 py-2.5 text-xs font-black uppercase tracking-[0.14em] text-black transition hover:bg-zinc-200 md:hidden"
                        @click="openMessengerInquiry()"
                    >
                        Message
                    </button>
                </div>

                <nav
                    class="mt-3 flex gap-2 overflow-x-auto pb-1 text-xs font-bold text-zinc-400 md:hidden [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    <a
                        href="#collection"
                        class="shrink-0 rounded-lg border border-white/10 bg-white/[0.04] px-4 py-2 transition hover:border-white/25 hover:text-white"
                    >
                        Collection
                    </a>

                    <a
                        v-if="recentSoldWatches.length"
                        href="#recently-sold"
                        class="shrink-0 rounded-lg border border-white/10 bg-white/[0.04] px-4 py-2 transition hover:border-white/25 hover:text-white"
                    >
                        Sold
                    </a>

                    <a
                        href="#process"
                        class="shrink-0 rounded-lg border border-white/10 bg-white/[0.04] px-4 py-2 transition hover:border-white/25 hover:text-white"
                    >
                        How To Order
                    </a>

                    <Link
                        href="/warranty-check"
                        class="shrink-0 rounded-lg border border-white/10 bg-white/[0.04] px-4 py-2 transition hover:border-white/25 hover:text-white"
                    >
                        Warranty Check
                    </Link>

                    <a
                        href="#contact"
                        class="shrink-0 rounded-lg border border-white/10 bg-white/[0.04] px-4 py-2 transition hover:border-white/25 hover:text-white"
                    >
                        Contact
                    </a>
                </nav>
            </div>
        </header>

        <main
            class="relative z-10 space-y-12 pb-16 sm:space-y-16 sm:pb-20 lg:space-y-20 lg:pb-24"
        >
            <!-- HERO -->
            <section
                class="mx-auto grid max-w-7xl items-center gap-10 px-4 pt-10 sm:gap-12 sm:px-6 sm:pt-12 lg:grid-cols-[1.03fr_0.97fr] lg:gap-14 lg:px-8 lg:pt-16"
            >
                <div class="relative">
                    <div
                        class="mb-6 inline-flex items-center gap-3 rounded-lg border border-white/10 bg-white/[0.04] px-4 py-2 shadow-xl shadow-black/25"
                    >
                        <span
                            class="h-1.5 w-1.5 rounded-full bg-white/80"
                        ></span>

                        <span
                            class="text-[10px] font-black uppercase tracking-[0.28em] text-zinc-400 sm:text-xs"
                        >
                            Curated Brand-New & Pre-Owned Timepieces
                        </span>
                    </div>

                    <h1
                        class="max-w-4xl text-5xl font-black leading-[0.92] tracking-[-0.075em] text-white sm:text-7xl lg:text-[5.8rem]"
                    >
                        Your next signature watch, curated with confidence.
                    </h1>

                    <p
                        class="mt-7 max-w-2xl text-[15px] leading-8 text-zinc-400 sm:text-lg"
                    >
                        Discover Montre Nova’s available watches with actual HD
                        photos, transparent pricing, warranty support, and a
                        direct inquiry experience built for smooth, trusted
                        deals.
                    </p>

                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a
                            href="#collection"
                            class="group inline-flex items-center justify-center gap-3 rounded-lg bg-white px-7 py-4 text-sm font-black uppercase tracking-[0.14em] text-black transition hover:bg-zinc-200"
                        >
                            View Collection

                            <span class="transition group-hover:translate-x-1">
                                →
                            </span>
                        </a>
                    </div>

                    <div class="mt-9 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div
                            class="rounded-xl border border-white/10 bg-white/[0.035] p-4 transition hover:border-white/25 hover:bg-white/[0.06]"
                        >
                            <p
                                class="text-2xl font-black tracking-tight text-white"
                            >
                                HD
                            </p>

                            <p
                                class="mt-1 text-[10px] font-bold uppercase tracking-[0.18em] text-zinc-500"
                            >
                                Actual Photos
                            </p>
                        </div>

                        <div
                            class="rounded-xl border border-white/10 bg-white/[0.035] p-4 transition hover:border-white/25 hover:bg-white/[0.06]"
                        >
                            <p
                                class="text-2xl font-black tracking-tight text-white"
                            >
                                1Y
                            </p>

                            <p
                                class="mt-1 text-[10px] font-bold uppercase tracking-[0.18em] text-zinc-500"
                            >
                                Warranty
                            </p>
                        </div>

                        <div
                            class="rounded-xl border border-white/10 bg-white/[0.035] p-4 transition hover:border-white/25 hover:bg-white/[0.06]"
                        >
                            <p
                                class="text-2xl font-black tracking-tight text-white"
                            >
                                {{ availableCount }}
                            </p>

                            <p
                                class="mt-1 text-[10px] font-bold uppercase tracking-[0.18em] text-zinc-500"
                            >
                                Available
                            </p>
                        </div>

                        <div
                            class="rounded-xl border border-white/10 bg-white/[0.035] p-4 transition hover:border-white/25 hover:bg-white/[0.06]"
                        >
                            <p
                                class="text-2xl font-black tracking-tight text-white"
                            >
                                {{ soldTotal }}+
                            </p>

                            <p
                                class="mt-1 text-[10px] font-bold uppercase tracking-[0.18em] text-zinc-500"
                            >
                                Sold Deals
                            </p>
                        </div>
                    </div>
                </div>

                <!-- FEATURED WATCH -->
                <div class="relative lg:pl-4">
                    <div
                        class="absolute -inset-4 rounded-2xl bg-white/[0.035] blur-2xl sm:-inset-6"
                    ></div>

                    <div
                        class="absolute -right-6 top-10 h-40 w-40 rounded-full bg-white/[0.04] blur-3xl"
                    ></div>

                    <div
                        class="relative overflow-hidden rounded-xl border border-white/10 bg-[#0A0A0B]/95 p-3 shadow-xl shadow-black/45 ring-1 ring-white/[0.03] sm:p-4"
                    >
                        <div
                            class="absolute inset-x-8 top-0 h-[1px] bg-gradient-to-r from-transparent via-white/50 to-transparent"
                        ></div>

                        <div
                            class="relative aspect-[4/4.7] overflow-hidden rounded-xl border border-white/10 bg-black sm:aspect-[4/5]"
                        >
                            <div
                                v-if="featuredWatch"
                                class="absolute left-4 top-4 z-20 flex max-w-[90%] flex-wrap gap-2"
                            >
                                <span
                                    v-for="badge in productBadges(
                                        featuredWatch,
                                    ).slice(0, 3)"
                                    :key="badge.label"
                                    class="rounded-md border px-3 py-1.5 text-[9px] font-black uppercase tracking-[0.14em] shadow-lg shadow-black/30"
                                    :class="badge.className"
                                >
                                    {{ badge.label }}
                                </span>
                            </div>

                            <img
                                v-if="watchImage(featuredWatch)"
                                :src="watchImage(featuredWatch)"
                                :alt="`${featuredWatch.brand} ${featuredWatch.model_name}`"
                                class="h-full w-full object-cover transition duration-700 hover:scale-105"
                                @error="handleImageError"
                            />

                            <div
                                v-else
                                class="flex h-full items-center justify-center bg-[#050505]"
                            >
                                <div class="text-center">
                                    <div
                                        class="mx-auto flex h-32 w-32 items-center justify-center rounded-full border border-white/10 bg-white/[0.045] shadow-2xl shadow-black/50"
                                    >
                                        <span
                                            class="text-4xl font-black tracking-[-0.12em] text-white"
                                        >
                                            MN
                                        </span>
                                    </div>

                                    <p
                                        class="mt-5 text-xs font-black uppercase tracking-[0.32em] text-zinc-500"
                                    >
                                        Montre Nova
                                    </p>
                                </div>
                            </div>

                            <div
                                class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 via-black/35 to-transparent p-5 sm:p-6"
                            >
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.28em] text-zinc-400"
                                >
                                    Featured Drop
                                </p>

                                <div
                                    class="mt-2 flex items-end justify-between gap-4"
                                >
                                    <div class="min-w-0">
                                        <h2
                                            class="truncate text-2xl font-black tracking-[-0.04em] text-white sm:text-3xl"
                                        >
                                            <template v-if="featuredWatch">
                                                {{ featuredWatch.brand }}
                                                {{ featuredWatch.model_name }}
                                            </template>

                                            <template v-else>
                                                Premium Timepiece
                                            </template>
                                        </h2>

                                        <p
                                            class="mt-1 truncate text-sm text-zinc-400"
                                        >
                                            <template v-if="featuredWatch">
                                                Ref.
                                                {{
                                                    featuredWatch.reference_number ||
                                                    "No reference"
                                                }}
                                                ·
                                                {{
                                                    featuredWatch.condition ||
                                                    "Condition upon request"
                                                }}
                                            </template>

                                            <template v-else>
                                                Brand New · Complete Set ·
                                                Available
                                            </template>
                                        </p>

                                        <div
                                            class="mt-3 flex flex-wrap gap-1.5 sm:gap-2"
                                        >
                                            <span
                                                class="rounded-lg border border-white/10 bg-black/35 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-zinc-300 backdrop-blur sm:text-xs"
                                            >
                                                <template v-if="featuredWatch">
                                                    {{
                                                        featuredWatch.condition ||
                                                        "Condition upon request"
                                                    }}
                                                </template>

                                                <template v-else>
                                                    Brand New
                                                </template>
                                            </span>

                                            <span
                                                v-if="featuredWatch?.category"
                                                class="rounded-lg border border-white/10 bg-black/35 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-zinc-300 backdrop-blur sm:text-xs"
                                            >
                                                {{ featuredWatch.category }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="shrink-0 text-right">
                                        <p
                                            class="text-xs uppercase tracking-[0.2em] text-zinc-500"
                                        >
                                            Price
                                        </p>

                                        <p
                                            class="text-xl font-black text-white sm:text-2xl"
                                        >
                                            {{
                                                featuredWatch
                                                    ? peso(
                                                          finalPrice(
                                                              featuredWatch,
                                                          ),
                                                      )
                                                    : "₱XX,XXX"
                                            }}
                                        </p>

                                        <p
                                            v-if="isBelowSrp(featuredWatch)"
                                            class="text-xs text-zinc-500 line-through"
                                        >
                                            {{
                                                peso(
                                                    originalPrice(
                                                        featuredWatch,
                                                    ),
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-3 p-2 pt-4 sm:grid-cols-2">
                            <button
                                v-if="featuredWatch"
                                type="button"
                                class="inline-flex items-center justify-center rounded-lg border border-white/10 bg-white/[0.045] px-5 py-3.5 text-sm font-bold text-white transition hover:border-white/30 hover:bg-white/[0.08]"
                                @click="openMessengerInquiry(featuredWatch)"
                            >
                                Ask Availability
                            </button>

                            <button
                                v-else
                                type="button"
                                class="inline-flex items-center justify-center rounded-lg border border-white/10 bg-white/[0.045] px-5 py-3.5 text-sm font-bold text-white transition hover:border-white/30 hover:bg-white/[0.08]"
                                @click="openMessengerInquiry()"
                            >
                                Ask Availability
                            </button>

                            <Link
                                v-if="featuredWatch"
                                :href="
                                    route(
                                        'public.watches.show',
                                        featuredWatch.id,
                                    )
                                "
                                class="inline-flex items-center justify-center rounded-lg bg-white px-5 py-3.5 text-sm font-black text-black transition hover:bg-zinc-200"
                            >
                                View Details
                            </Link>

                            <a
                                v-else
                                href="#collection"
                                class="inline-flex items-center justify-center rounded-lg bg-white px-5 py-3.5 text-sm font-black text-black transition hover:bg-zinc-200"
                            >
                                Browse Stocks
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- PREMIUM TRUST STRIP -->
            <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    class="relative overflow-hidden rounded-xl border border-white/10 bg-white/[0.035] p-4 shadow-xl shadow-black/25 sm:p-5"
                >
                    <div
                        class="pointer-events-none absolute -right-20 -top-24 h-56 w-56 rounded-full bg-white/[0.035] blur-3xl"
                    ></div>

                    <div
                        class="pointer-events-none absolute -left-20 bottom-0 h-44 w-44 rounded-full bg-zinc-400/[0.025] blur-3xl"
                    ></div>

                    <div class="relative grid gap-3 md:grid-cols-4">
                        <div
                            v-for="item in trustItems"
                            :key="item.title"
                            class="group rounded-xl border border-white/10 bg-black/35 p-5 transition hover:border-white/25 hover:bg-white/[0.055]"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p
                                        class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500"
                                    >
                                        {{ item.label }}
                                    </p>

                                    <h3
                                        class="mt-2 text-base font-black tracking-[-0.03em] text-white"
                                    >
                                        {{ item.title }}
                                    </h3>
                                </div>

                                <span
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/[0.05] text-xs font-black text-zinc-300 transition group-hover:bg-white group-hover:text-black"
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
            </section>

            <!-- COLLECTION -->
            <section
                id="collection"
                class="scroll-mt-28 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
            >
                <div
                    class="mb-8 flex flex-col justify-between gap-5 sm:mb-10 md:flex-row md:items-end"
                >
                    <div>
                        <p
                            class="text-[11px] font-black uppercase tracking-[0.34em] text-zinc-500"
                        >
                            Current Collection
                        </p>

                        <h2
                            class="mt-3 text-4xl font-black tracking-[-0.06em] text-white sm:text-5xl"
                        >
                            Available Watches
                        </h2>

                        <p
                            class="mt-4 max-w-2xl text-sm leading-7 text-zinc-400"
                        >
                            Clean, direct browsing for currently available
                            Montre Nova pieces.
                        </p>

                        <p
                            v-if="paginationSummary"
                            class="mt-2 text-xs font-bold uppercase tracking-[0.18em] text-zinc-600"
                        >
                            {{ paginationSummary }}
                        </p>
                    </div>

                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center md:items-end"
                    >
                        <p
                            v-if="watches.length"
                            class="text-xs font-black uppercase tracking-[0.2em] text-zinc-600 md:hidden"
                        >
                            Swipe collection →
                        </p>

                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-lg border border-white/10 bg-white/[0.04] px-5 py-3 text-sm font-bold text-white transition hover:border-white/30 hover:bg-white/[0.08]"
                            @click="openMessengerInquiry()"
                        >
                            Ask for Latest Stocks
                        </button>
                    </div>
                </div>

                <template v-if="watches.length">
                    <div
                        class="flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-smooth pb-4 pr-4 overscroll-x-contain md:grid md:snap-none md:grid-cols-2 md:overflow-visible md:pb-0 md:pr-0 lg:grid-cols-3 xl:gap-5 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                    >
                        <article
                            v-for="watch in watches"
                            :key="watch.id"
                            class="group relative min-w-[82vw] max-w-[82vw] snap-start overflow-hidden rounded-[1.35rem] border border-white/10 bg-black shadow-2xl shadow-black/35 ring-1 ring-white/[0.03] transition duration-500 hover:border-white/25 hover:shadow-black/60 sm:min-w-[360px] sm:max-w-[360px] md:min-w-0 md:max-w-none md:hover:-translate-y-1"
                            :class="isSold(watch) ? 'opacity-70' : ''"
                        >
                            <div
                                class="relative min-h-[450px] overflow-hidden bg-[#050505] sm:min-h-[500px] lg:min-h-[540px]"
                            >
                                <img
                                    v-if="watchImage(watch)"
                                    :src="watchImage(watch)"
                                    :alt="`${watch.brand} ${watch.model_name}`"
                                    class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-105"
                                    loading="lazy"
                                    @error="handleImageError"
                                />

                                <div
                                    v-else
                                    class="absolute inset-0 flex items-center justify-center bg-[#050505]"
                                >
                                    <div class="text-center">
                                        <div
                                            class="mx-auto flex h-24 w-24 items-center justify-center rounded-full border border-white/10 bg-white/[0.04] sm:h-28 sm:w-28"
                                        >
                                            <span
                                                class="text-3xl font-black tracking-[-0.1em] text-white sm:text-4xl"
                                            >
                                                MN
                                            </span>
                                        </div>

                                        <p
                                            class="mt-4 text-[10px] font-black uppercase tracking-[0.28em] text-zinc-500"
                                        >
                                            Montre Nova
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="absolute inset-0 bg-gradient-to-t from-black/82 via-black/30 to-transparent"
                                ></div>

                                <div
                                    class="absolute inset-0 bg-[radial-gradient(circle_at_center,transparent_0%,rgba(0,0,0,0.08)_50%,rgba(0,0,0,0.42)_100%)]"
                                ></div>

                                <div
                                    class="absolute inset-x-0 top-0 h-28 bg-gradient-to-b from-black/35 to-transparent"
                                ></div>

                                <div
                                    class="absolute left-4 top-4 z-20 flex max-w-[92%] flex-wrap gap-1.5 sm:left-5 sm:top-5"
                                >
                                    <span
                                        v-for="badge in productBadges(
                                            watch,
                                        ).slice(0, 2)"
                                        :key="badge.label"
                                        class="rounded-md border px-2.5 py-1 text-[8px] font-black uppercase tracking-[0.12em] shadow-lg shadow-black/40 backdrop-blur sm:px-3 sm:py-1.5 sm:text-[9px]"
                                        :class="badge.className"
                                    >
                                        {{ badge.label }}
                                    </span>
                                </div>

                                <div
                                    class="absolute inset-x-0 bottom-0 z-20 p-5 sm:p-6"
                                >
                                    <div class="mb-4 flex items-center gap-3">
                                        <p
                                            class="truncate text-[10px] font-black uppercase tracking-[0.32em] text-zinc-300/90 sm:text-xs"
                                        >
                                            {{ watch.brand || "Montre Nova" }}
                                        </p>
                                    </div>

                                    <h3
                                        class="line-clamp-2 text-2xl font-medium leading-tight tracking-[0.02em] text-white drop-shadow-[0_2px_12px_rgba(0,0,0,0.75)] sm:text-3xl"
                                    >
                                        {{ watch.model_name }}
                                    </h3>

                                    <p
                                        class="mt-2 truncate text-sm text-zinc-300/90"
                                    >
                                        Ref.
                                        {{
                                            watch.reference_number ||
                                            "No reference"
                                        }}
                                    </p>

                                    <div
                                        class="mt-4 flex flex-wrap gap-1.5 sm:gap-2"
                                    >
                                        <span
                                            class="rounded-lg border border-white/10 bg-black/35 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-zinc-300 backdrop-blur sm:text-xs"
                                        >
                                            {{
                                                watch.condition ||
                                                "Condition upon request"
                                            }}
                                        </span>

                                        <span
                                            v-if="watch.category"
                                            class="rounded-lg border border-white/10 bg-black/35 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-zinc-300 backdrop-blur sm:text-xs"
                                        >
                                            {{ watch.category }}
                                        </span>
                                    </div>

                                    <div
                                        class="mt-6 flex items-end justify-between gap-4 border-t border-white/10 pt-4"
                                    >
                                        <div>
                                            <p
                                                class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500"
                                            >
                                                Price
                                            </p>

                                            <p
                                                class="mt-1 text-xl font-black tracking-tight text-white sm:text-2xl"
                                            >
                                                {{ peso(finalPrice(watch)) }}
                                            </p>

                                            <p
                                                v-if="isBelowSrp(watch)"
                                                class="text-[11px] text-zinc-500 line-through sm:text-xs"
                                            >
                                                {{ peso(originalPrice(watch)) }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-4 grid grid-cols-2 gap-2">
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center rounded-lg border border-white/15 bg-black/40 px-3 py-2.5 text-xs font-black text-white backdrop-blur transition hover:border-white/35 hover:bg-white/[0.08] sm:px-4 sm:py-3 sm:text-sm"
                                            @click="openMessengerInquiry(watch)"
                                        >
                                            Ask
                                        </button>

                                        <Link
                                            :href="
                                                route(
                                                    'public.watches.show',
                                                    watch.id,
                                                )
                                            "
                                            class="inline-flex items-center justify-center rounded-lg bg-white px-3 py-2.5 text-xs font-black text-black transition hover:bg-zinc-200 sm:px-4 sm:py-3 sm:text-sm"
                                        >
                                            Details
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>

                    <!-- PAGINATION -->
                    <div
                        v-if="hasWatchPagination"
                        class="mt-8 flex flex-col items-center justify-between gap-5 rounded-xl border border-white/10 bg-white/[0.035] p-4 backdrop-blur sm:flex-row md:mt-10"
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
                                    class="cursor-not-allowed rounded-lg border border-white/5 bg-white/[0.02] px-4 py-2 text-sm font-semibold text-zinc-700"
                                >
                                    {{ cleanPaginationLabel(link.label) }}
                                </span>

                                <Link
                                    v-else
                                    :href="paginationUrl(link.url)"
                                    preserve-scroll
                                    preserve-state
                                    class="rounded-lg border px-4 py-2 text-sm font-bold transition"
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
                    class="rounded-xl border border-white/10 bg-[#0B0B0D] p-10 text-center shadow-xl shadow-black/25"
                >
                    <div
                        class="mx-auto flex h-24 w-24 items-center justify-center rounded-full border border-white/10 bg-white/[0.04]"
                    >
                        <span
                            class="text-3xl font-black tracking-[-0.1em] text-white"
                        >
                            MN
                        </span>
                    </div>

                    <h3 class="mt-6 text-xl font-black text-white">
                        No available watches yet.
                    </h3>

                    <p
                        class="mx-auto mt-3 max-w-md text-sm leading-7 text-zinc-500"
                    >
                        Stocks will appear here once they are marked as
                        available and visible from the admin dashboard.
                    </p>

                    <button
                        type="button"
                        class="mt-6 inline-flex rounded-lg bg-white px-6 py-3 text-sm font-black text-black transition hover:bg-zinc-200"
                        @click="openMessengerInquiry()"
                    >
                        Message Us for Availability
                    </button>
                </div>
            </section>

            <!-- RECENTLY SOLD -->
            <section
                v-if="recentSoldWatches.length"
                id="recently-sold"
                class="scroll-mt-28 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
            >
                <div
                    class="mb-8 overflow-hidden rounded-xl border border-white/10 bg-[#0B0B0D] p-5 shadow-xl shadow-black/25 sm:mb-10 sm:p-7"
                >
                    <div
                        class="grid gap-6 lg:grid-cols-[0.92fr_1.08fr] lg:items-center"
                    >
                        <div>
                            <p
                                class="text-[11px] font-black uppercase tracking-[0.34em] text-zinc-500"
                            >
                                Recently Sold
                            </p>

                            <h2
                                class="mt-3 text-4xl font-black tracking-[-0.06em] text-white sm:text-5xl"
                            >
                                Claimed Timepieces
                            </h2>

                            <p
                                class="mt-4 max-w-2xl text-sm leading-7 text-zinc-400"
                            >
                                Showcasing recently claimed watches builds buyer
                                trust and makes it easier for customers to
                                request similar pieces.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            <div
                                class="rounded-xl border border-white/10 bg-white/[0.04] p-4"
                            >
                                <p
                                    class="text-3xl font-black tracking-tight text-white"
                                >
                                    {{ soldProofCount }}
                                </p>

                                <p
                                    class="mt-1 text-[10px] font-black uppercase tracking-[0.18em] text-zinc-600"
                                >
                                    Sold this month
                                </p>

                                <p class="mt-2 text-xs text-zinc-500">
                                    {{ soldMonthLabel }}
                                </p>
                            </div>

                            <div
                                class="rounded-xl border border-white/10 bg-white/[0.04] p-4"
                            >
                                <p
                                    class="text-3xl font-black tracking-tight text-white"
                                >
                                    {{ soldTotal }}+
                                </p>

                                <p
                                    class="mt-1 text-[10px] font-black uppercase tracking-[0.18em] text-zinc-600"
                                >
                                    Total sold
                                </p>

                                <p class="mt-2 text-xs text-zinc-500">
                                    Trusted deals
                                </p>
                            </div>

                            <button
                                type="button"
                                class="group col-span-2 flex items-center justify-between rounded-xl border border-white bg-white p-4 text-left text-black shadow-[0_12px_35px_rgba(255,255,255,0.12)] transition duration-300 hover:-translate-y-0.5 hover:bg-zinc-100 hover:shadow-[0_18px_45px_rgba(255,255,255,0.18)] active:scale-[0.98] sm:col-span-1"
                                @click="openSimilarInquiry()"
                            >
                                <div>
                                    <p
                                        class="text-[10px] font-black uppercase tracking-[0.18em] text-zinc-500"
                                    >
                                        Looking for a model?
                                    </p>

                                    <p class="mt-2 text-sm font-black">
                                        Inquire Now!
                                    </p>

                                    <p
                                        class="mt-2 text-xs leading-5 text-zinc-600"
                                    >
                                        Send your target model, budget, and
                                        condition.
                                    </p>
                                </div>

                                <div
                                    class="ml-4 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-black text-white transition duration-300 group-hover:translate-x-1"
                                >
                                    →
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    class="mb-6 flex flex-col justify-between gap-3 sm:mb-7 md:flex-row md:items-center"
                >
                    <p
                        class="text-xs font-bold uppercase tracking-[0.2em] text-zinc-600 md:hidden"
                    >
                        Swipe sold watches →
                    </p>

                    <p class="hidden text-sm text-zinc-500 md:block">
                        Recently claimed watches from Montre Nova.
                    </p>

                    <div
                        class="grid w-full grid-cols-2 gap-2 sm:flex sm:w-auto sm:items-center"
                    >
                        <Link
                            href="/sold-watches"
                            class="inline-flex items-center justify-center rounded-lg bg-white px-5 py-3 text-sm font-black text-black transition hover:bg-zinc-200"
                        >
                            View Sold Gallery
                        </Link>
                    </div>
                </div>

                <div
                    class="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-4 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    <article
                        v-for="watch in recentSoldWatches"
                        :key="watch.id"
                        class="group relative min-w-[82vw] max-w-[82vw] snap-start overflow-hidden rounded-[1.35rem] border border-white/10 bg-black shadow-2xl shadow-black/35 ring-1 ring-white/[0.03] transition duration-500 hover:border-white/25 hover:shadow-black/60 sm:min-w-[340px] sm:max-w-[340px] md:min-w-[360px] md:max-w-[360px] md:hover:-translate-y-1"
                    >
                        <div
                            class="relative min-h-[430px] overflow-hidden bg-[#050505] sm:min-h-[500px]"
                        >
                            <img
                                v-if="watchImage(watch)"
                                :src="watchImage(watch)"
                                :alt="`${watch.brand} ${watch.model_name}`"
                                class="absolute inset-0 h-full w-full object-cover grayscale-[18%] transition duration-700 group-hover:scale-105 group-hover:grayscale-0"
                                loading="lazy"
                                @error="handleImageError"
                            />

                            <div
                                v-else
                                class="absolute inset-0 flex items-center justify-center bg-[#050505]"
                            >
                                <div class="text-center">
                                    <div
                                        class="mx-auto flex h-24 w-24 items-center justify-center rounded-full border border-white/10 bg-white/[0.04] sm:h-28 sm:w-28"
                                    >
                                        <span
                                            class="text-3xl font-black tracking-[-0.1em] text-white sm:text-4xl"
                                        >
                                            MN
                                        </span>
                                    </div>

                                    <p
                                        class="mt-4 text-[10px] font-black uppercase tracking-[0.28em] text-zinc-500"
                                    >
                                        Montre Nova
                                    </p>
                                </div>
                            </div>

                            <div
                                class="absolute inset-0 bg-gradient-to-t from-black/82 via-black/32 to-transparent"
                            ></div>

                            <div
                                class="absolute inset-0 bg-[radial-gradient(circle_at_center,transparent_0%,rgba(0,0,0,0.07)_50%,rgba(0,0,0,0.42)_100%)]"
                            ></div>

                            <div
                                class="absolute inset-x-0 top-0 h-28 bg-gradient-to-b from-black/35 to-transparent"
                            ></div>

                            <div
                                class="absolute left-4 top-4 z-20 flex max-w-[92%] flex-wrap gap-1.5 sm:left-5 sm:top-5"
                            >
                                <span
                                    class="rounded-md border border-red-400/30 bg-red-500/15 px-3 py-1.5 text-[9px] font-black uppercase tracking-[0.14em] text-red-200 shadow-lg shadow-red-950/30 backdrop-blur"
                                >
                                    Sold
                                </span>

                                <span
                                    class="rounded-md border border-white/10 bg-black/45 px-3 py-1.5 text-[9px] font-black uppercase tracking-[0.14em] text-zinc-300 shadow-lg shadow-black/40 backdrop-blur"
                                >
                                    Claimed
                                </span>
                            </div>

                            <div
                                class="absolute inset-x-0 bottom-0 z-20 p-5 sm:p-6"
                            >
                                <div class="mb-4 flex items-center gap-3">
                                    <span
                                        class="h-px w-10 bg-gradient-to-r from-white/80 to-transparent"
                                    ></span>

                                    <p
                                        class="truncate text-[10px] font-black uppercase tracking-[0.32em] text-zinc-300/90 sm:text-xs"
                                    >
                                        {{ watch.brand || "Montre Nova" }}
                                    </p>
                                </div>

                                <h3
                                    class="line-clamp-2 text-2xl font-medium leading-tight tracking-[0.02em] text-white drop-shadow-[0_2px_12px_rgba(0,0,0,0.75)] sm:text-3xl"
                                >
                                    {{ watch.model_name }}
                                </h3>

                                <p
                                    class="mt-2 truncate text-sm text-zinc-300/90"
                                >
                                    Ref.
                                    {{
                                        watch.reference_number || "No reference"
                                    }}
                                </p>

                                <div
                                    class="mt-4 flex flex-wrap gap-1.5 sm:gap-2"
                                >
                                    <span
                                        class="rounded-lg border border-white/10 bg-black/35 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-zinc-300 backdrop-blur sm:text-xs"
                                    >
                                        {{ soldConditionLabel(watch) }}
                                    </span>

                                    <span
                                        v-if="watch.category"
                                        class="rounded-lg border border-white/10 bg-black/35 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-zinc-300 backdrop-blur sm:text-xs"
                                    >
                                        {{ watch.category }}
                                    </span>
                                </div>

                                <div
                                    class="mt-6 flex items-end justify-between gap-4 border-t border-white/10 pt-4"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500"
                                        >
                                            Recently Claimed
                                        </p>

                                        <p
                                            class="mt-1 truncate text-base font-black text-white sm:text-lg"
                                        >
                                            {{ soldDateLabel(watch) }}
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        class="group/link inline-flex items-center gap-2 whitespace-nowrap text-[10px] font-black uppercase tracking-[0.28em] text-zinc-200 transition hover:text-white sm:text-xs"
                                        @click="openSimilarInquiry(watch)"
                                    >
                                        Find Similar

                                        <span
                                            class="text-white transition group-hover/link:translate-x-1"
                                        >
                                            →
                                        </span>
                                    </button>
                                </div>

                                <div class="mt-4 flex justify-end">
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-white/15 bg-black/40 px-4 py-2.5 text-xs font-black text-white backdrop-blur transition hover:border-white/35 hover:bg-white/[0.08] active:scale-[0.98] sm:px-5 sm:py-3 sm:text-sm"
                                        @click="openSimilarInquiry(watch)"
                                    >
                                        Source Similar

                                        <span aria-hidden="true">→</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <!-- HOW TO ORDER -->
            <section
                id="process"
                class="scroll-mt-28 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
            >
                <div class="mb-8 sm:mb-10">
                    <p
                        class="text-[11px] font-black uppercase tracking-[0.34em] text-zinc-500"
                    >
                        How to Order
                    </p>

                    <h2
                        class="mt-4 max-w-2xl text-3xl font-black tracking-[-0.04em] text-white sm:text-4xl"
                    >
                        Order your next timepiece in three simple steps.
                    </h2>

                    <p class="mt-4 max-w-2xl text-sm leading-7 text-zinc-400">
                        Browse our available watches, confirm your preferred
                        payment option, and have your order shipped safely.
                    </p>
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    <div
                        class="flex flex-col rounded-xl border border-white/10 bg-[#0B0B0D]/90 p-7 shadow-xl shadow-black/25 transition hover:border-white/25 hover:bg-[#111113]"
                    >
                        <p
                            class="text-xs font-black uppercase tracking-[0.28em] text-zinc-600"
                        >
                            01
                        </p>

                        <h3
                            class="mt-4 text-xl font-black tracking-[-0.03em] text-white"
                        >
                            Place Order
                        </h3>

                        <p class="mt-4 text-sm leading-7 text-zinc-400">
                            Choose your preferred watch and message us through
                            our official channels to confirm availability,
                            complete details, and updated photos.
                        </p>

                        <div class="mt-auto flex justify-end pt-6">
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-5 py-3 text-sm font-black text-black transition hover:bg-zinc-200 active:scale-[0.98]"
                                @click="openMessengerInquiry()"
                            >
                                Start Inquiry

                                <span aria-hidden="true">→</span>
                            </button>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-white/10 bg-[#0B0B0D]/90 p-7 shadow-xl shadow-black/25 transition hover:border-white/25 hover:bg-[#111113]"
                    >
                        <p
                            class="text-xs font-black uppercase tracking-[0.28em] text-zinc-600"
                        >
                            02
                        </p>

                        <h3
                            class="mt-4 text-xl font-black tracking-[-0.03em] text-white"
                        >
                            Flexible Payment
                        </h3>

                        <p class="mt-4 text-sm leading-7 text-zinc-400">
                            Pay through your preferred option. We accept cash,
                            Maribank, GoTyme, QR code payments, and selected
                            trade-ins subject to evaluation.
                        </p>

                        <p
                            class="mt-6 rounded-lg border border-white/10 bg-white/[0.04] px-4 py-3 text-xs leading-6 text-zinc-400"
                        >
                            Reservation or payment instructions will be sent
                            only through our official channels.
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-white/10 bg-[#0B0B0D]/90 p-7 shadow-xl shadow-black/25 transition hover:border-white/25 hover:bg-[#111113]"
                    >
                        <p
                            class="text-xs font-black uppercase tracking-[0.28em] text-zinc-600"
                        >
                            03
                        </p>

                        <h3
                            class="mt-4 text-xl font-black tracking-[-0.03em] text-white"
                        >
                            Shipping
                        </h3>

                        <p class="mt-4 text-sm leading-7 text-zinc-400">
                            Once payment is confirmed, your watch will be
                            securely packed and prepared for delivery. Shipping
                            details and tracking updates will be provided.
                        </p>

                        <p
                            class="mt-6 rounded-lg border border-white/10 bg-white/[0.04] px-4 py-3 text-xs leading-6 text-zinc-400"
                        >
                            Every purchase includes Montre Card warranty
                            coverage for added peace of mind.
                        </p>
                    </div>
                </div>
            </section>

            <!-- CONTACT -->
            <section
                id="contact"
                class="scroll-mt-28 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
            >
                <div
                    class="relative overflow-hidden rounded-[1.5rem] border border-white/10 bg-[#080809] p-6 shadow-2xl shadow-black/40 sm:p-10 lg:p-12"
                >
                    <div
                        class="absolute -right-32 -top-32 h-80 w-80 rounded-full bg-white/[0.06] blur-3xl"
                    ></div>

                    <div
                        class="absolute -left-24 bottom-0 h-64 w-64 rounded-full bg-zinc-400/[0.035] blur-3xl"
                    ></div>

                    <div
                        class="absolute inset-x-10 top-0 h-px bg-gradient-to-r from-transparent via-white/40 to-transparent"
                    ></div>

                    <div
                        class="relative grid gap-10 lg:grid-cols-[1fr_0.9fr] lg:items-center"
                    >
                        <div>
                            <p
                                class="text-[11px] font-black uppercase tracking-[0.34em] text-zinc-500"
                            >
                                Get in Touch
                            </p>

                            <h2
                                class="mt-4 max-w-2xl text-4xl font-black tracking-[-0.06em] text-white sm:text-6xl"
                            >
                                Ready to find your next timepiece?
                            </h2>

                            <p
                                class="mt-5 max-w-2xl text-sm leading-7 text-zinc-400 sm:text-base"
                            >
                                Message Montre Nova for available stocks,
                                reservations, curated recommendations, and
                                assistance based on your target model and
                                budget.
                            </p>

                            <div
                                class="mt-8 rounded-2xl border border-white/10 bg-white/[0.035] p-5"
                            >
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.24em] text-zinc-500"
                                >
                                    Quick Response
                                </p>

                                <p class="mt-2 text-sm leading-7 text-zinc-400">
                                    For faster assistance, send the watch model,
                                    reference number, budget, and preferred
                                    condition.
                                </p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <a
                                v-for="link in contactLinks"
                                :key="link.label"
                                :href="link.href"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="group flex items-center justify-between gap-5 rounded-2xl border border-white/10 bg-black/45 px-5 py-5 text-sm text-white shadow-lg shadow-black/20 transition duration-300 hover:-translate-y-0.5 hover:border-white/25 hover:bg-white/[0.06] hover:shadow-black/40"
                                :class="
                                    link.href === '#'
                                        ? 'pointer-events-none opacity-50'
                                        : ''
                                "
                            >
                                <div class="min-w-0">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/[0.04] text-xs font-black text-white"
                                        >
                                            {{ link.icon }}
                                        </div>

                                        <div class="min-w-0">
                                            <p
                                                class="truncate font-black text-white"
                                            >
                                                {{ link.label }}
                                            </p>

                                            <p
                                                class="mt-1 line-clamp-2 text-xs font-normal leading-5 text-zinc-500"
                                            >
                                                {{ link.description }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/[0.04] text-zinc-400 transition group-hover:translate-x-1 group-hover:border-white/25 group-hover:bg-white group-hover:text-black"
                                >
                                    →
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="relative z-10 border-t border-white/10">
            <div
                class="mx-auto flex max-w-7xl flex-col justify-between gap-3 px-4 py-8 text-sm text-zinc-600 sm:px-6 md:flex-row md:items-center lg:px-8"
            >
                <p>
                    © {{ new Date().getFullYear() }} Montre Nova. Curated
                    timepieces.
                </p>

                <p class="text-zinc-700">
                    Brand-new and pre-owned watch selections.
                </p>
            </div>
        </footer>

        <!-- MOBILE STICKY CTA -->
        <div
            class="fixed inset-x-0 bottom-0 z-[60] border-t border-white/10 bg-black/90 px-4 py-3 backdrop-blur-xl md:hidden"
        >
            <div class="grid grid-cols-2 gap-3">
                <a
                    href="#collection"
                    class="inline-flex items-center justify-center rounded-lg border border-white/10 bg-white/[0.055] px-4 py-3 text-sm font-black text-white"
                >
                    View Stocks
                </a>

                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-lg bg-white px-4 py-3 text-sm font-black text-black"
                    @click="openMessengerInquiry()"
                >
                    Message Us
                </button>
            </div>
        </div>
    </div>
</template>
