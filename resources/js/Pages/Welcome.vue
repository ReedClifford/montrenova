<script setup>
import MontreLogo from "@/Components/MontreLogo.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";

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

const isPageOpening = ref(false);

const preparePageOpen = (event = null) => {
    if (
        event?.metaKey ||
        event?.ctrlKey ||
        event?.shiftKey ||
        event?.altKey ||
        event?.button === 1
    ) {
        return;
    }

    isPageOpening.value = true;

    window.setTimeout(() => {
        isPageOpening.value = false;
    }, 1600);
};

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

const messengerUrl = (message) => {
    return `https://m.me/${messengerUsername}?text=${encodeURIComponent(message)}`;
};

const openMessengerInquiry = (watch = null) => {
    window.open(
        messengerUrl(inquiryMessage(watch)),
        "_blank",
        "noopener,noreferrer",
    );
};

const openSimilarInquiry = (watch = null) => {
    window.open(
        messengerUrl(similarInquiryMessage(watch)),
        "_blank",
        "noopener,noreferrer",
    );
};

const contactLinks = [
    {
        label: "Messenger",
        description: "Fastest way to ask for availability and reservations.",
        href: "https://m.me/montrenova",
        icon: "FB",
    },
    {
        label: "TikTok",
        description: "Watch updates, short clips, and direct inquiries.",
        href: "https://www.tiktok.com/@montre_nova",
        icon: "TT",
    },
    {
        label: "Instagram",
        description: "Latest posts, stories, and curated watch drops.",
        href: "https://www.instagram.com/montrenova",
        icon: "IG",
    },
];

const orderSteps = [
    {
        number: "01",
        title: "Place Order",
        description:
            "Choose your preferred watch and message us to confirm availability, final photos, and complete details.",
    },
    {
        number: "02",
        title: "Flexible Payment",
        description:
            "Pay through your preferred option. We accept cash, Maribank, GoTyme, QR payments, and selected trade-ins subject to evaluation.",
    },
    {
        number: "03",
        title: "Shipping",
        description:
            "Metro Manila orders can be shipped through Lalamove. Nationwide orders can be shipped through LBC after payment confirmation.",
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
                "border-emerald-400/20 bg-emerald-400/10 text-emerald-200",
        },
        reserved: {
            label: "Reserved",
            className: "border-white/15 bg-white/10 text-zinc-200",
        },
        sold: {
            label: "Sold",
            className: "border-red-400/25 bg-red-500/10 text-red-200",
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
            className: "border-sky-400/20 bg-sky-400/10 text-sky-200",
        });
    }

    if (isBelowSrp(watch)) {
        badges.push({
            label: "Below SRP",
            className: "border-violet-400/20 bg-violet-400/10 text-violet-200",
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
        <!-- BACKGROUND -->
        <div class="pointer-events-none fixed inset-0 z-0 overflow-hidden">
            <div
                class="absolute inset-0 bg-[linear-gradient(180deg,#020202_0%,#070707_42%,#030303_100%)]"
            ></div>

            <div
                class="absolute -left-40 -top-44 h-[34rem] w-[34rem] rounded-full bg-white/[0.055] blur-[115px]"
            ></div>

            <div
                class="absolute -right-48 top-[15rem] h-[36rem] w-[36rem] rounded-full bg-zinc-300/[0.055] blur-[125px]"
            ></div>

            <div
                class="absolute left-1/2 top-[48rem] h-[28rem] w-[42rem] -translate-x-1/2 rounded-full bg-white/[0.028] blur-[135px]"
            ></div>

            <div
                class="absolute inset-x-8 top-0 h-px bg-gradient-to-r from-transparent via-white/25 to-transparent sm:inset-x-16 lg:inset-x-28"
            ></div>

            <div
                class="absolute inset-0 bg-[linear-gradient(90deg,rgba(255,255,255,0.025),transparent_18%,transparent_82%,rgba(255,255,255,0.018))]"
            ></div>
        </div>

        <!-- NAVBAR -->
        <header
            class="sticky top-0 z-50 border-b border-white/10 bg-black/70 backdrop-blur-2xl"
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
                        <a href="#collection" class="nav-link">Collection</a>

                        <a
                            v-if="recentSoldWatches.length"
                            href="#recently-sold"
                            class="nav-link"
                        >
                            Sold Gallery
                        </a>

                        <a href="#process" class="nav-link">How to Order</a>

                        <a href="#contact" class="nav-link">Contact</a>
                    </nav>

                    <div class="hidden items-center gap-3 md:flex">
                        <Link
                            href="/warranty-check"
                            class="secondary-button px-5 py-2.5 text-xs"
                        >
                            Warranty
                        </Link>

                        <button
                            type="button"
                            class="primary-button px-5 py-2.5 text-xs"
                            @click="openMessengerInquiry()"
                        >
                            Message Us
                        </button>
                    </div>
                </div>

                <nav
                    class="mt-3 flex gap-2 overflow-x-auto pb-1 text-xs font-bold text-zinc-400 md:hidden [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    <a href="#collection" class="mobile-nav-pill">Collection</a>

                    <a
                        v-if="recentSoldWatches.length"
                        href="#recently-sold"
                        class="mobile-nav-pill"
                    >
                        Sold
                    </a>

                    <a href="#process" class="mobile-nav-pill">Order</a>

                    <Link href="/warranty-check" class="mobile-nav-pill">
                        Warranty
                    </Link>

                    <a href="#contact" class="mobile-nav-pill">Contact</a>
                </nav>
            </div>
        </header>

        <main
            class="relative z-10 space-y-20 pb-20 sm:space-y-24 sm:pb-24 lg:space-y-32 lg:pb-28 xl:space-y-36"
        >
            <!-- HERO -->
            <section
                class="mx-auto grid max-w-7xl items-stretch gap-9 px-4 pt-8 sm:gap-11 sm:px-6 sm:pt-12 lg:grid-cols-[0.94fr_0.86fr] lg:gap-14 lg:px-8 lg:pt-16"
            >
                <div
                    class="hero-copy animated-in relative flex h-full flex-col justify-center"
                >
                    <div class="premium-eyebrow mb-6">
                        <span class="pulse-dot"></span>
                        Curated brand-new & pre-owned timepieces
                    </div>

                    <h1
                        class="max-w-4xl text-[3.35rem] font-black leading-[0.9] tracking-[-0.075em] text-white sm:text-7xl lg:text-[5.2rem]"
                    >
                        Your next signature watch, curated with confidence.
                    </h1>

                    <p
                        class="mt-7 max-w-2xl text-[15px] leading-8 text-zinc-400 sm:text-lg"
                    >
                        Browse available watches with actual HD photos, clear
                        pricing, warranty support, and a cleaner mobile-first
                        shopping experience.
                    </p>

                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a
                            href="#collection"
                            class="primary-button group px-7 py-4 text-sm"
                        >
                            View Collection

                            <span class="transition group-hover:translate-x-1">
                                →
                            </span>
                        </a>
                    </div>

                    <div class="mt-9 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div class="stat-card">
                            <p class="stat-value">1Y</p>
                            <p class="stat-label">Warranty</p>
                        </div>

                        <div class="stat-card">
                            <p class="stat-value">{{ availableCount }}</p>
                            <p class="stat-label">Available</p>
                        </div>

                        <div class="stat-card">
                            <p class="stat-value">{{ soldTotal }}+</p>
                            <p class="stat-label">Sold Deals</p>
                        </div>
                    </div>
                </div>

                <!-- FEATURED WATCH -->
                <div
                    class="hero-feature-wrap animated-in relative flex h-full items-stretch lg:pl-4 lg:[animation-delay:120ms]"
                >
                    <div
                        class="absolute -inset-4 rounded-[2rem] bg-white/[0.035] blur-2xl sm:-inset-6"
                    ></div>

                    <div
                        class="featured-lux-card lux-card relative flex w-full flex-col overflow-hidden rounded-[1.6rem] border border-white/10 bg-[#0A0A0B]/95 p-3 shadow-2xl shadow-black/45 ring-1 ring-white/[0.04] sm:p-4"
                    >
                        <div class="shine-line"></div>

                        <div
                            class="featured-media group relative flex min-h-[430px] flex-1 overflow-hidden rounded-[1.25rem] border border-white/10 bg-black sm:min-h-[500px] lg:min-h-0"
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
                                    class="rounded-md border px-3 py-1.5 text-[9px] font-black uppercase tracking-[0.14em] shadow-lg shadow-black/30 backdrop-blur"
                                    :class="badge.className"
                                >
                                    {{ badge.label }}
                                </span>
                            </div>

                            <img
                                v-if="watchImage(featuredWatch)"
                                :src="watchImage(featuredWatch)"
                                :alt="`${featuredWatch.brand} ${featuredWatch.model_name}`"
                                class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-[1.045]"
                                @error="handleImageError"
                            />

                            <div
                                v-else
                                class="absolute inset-0 flex h-full items-center justify-center bg-[#050505]"
                            >
                                <div class="text-center">
                                    <div class="placeholder-logo">
                                        <span>MN</span>
                                    </div>

                                    <p class="mt-5 brand-kicker">Montre Nova</p>
                                </div>
                            </div>

                            <div
                                class="absolute inset-0 bg-gradient-to-t from-black/86 via-black/28 to-transparent"
                            ></div>

                            <div
                                class="absolute inset-0 bg-[radial-gradient(circle_at_center,transparent_0%,rgba(0,0,0,0.03)_52%,rgba(0,0,0,0.36)_100%)]"
                            ></div>

                            <div
                                class="absolute inset-x-0 bottom-0 z-20 p-5 sm:p-6"
                            >
                                <p class="brand-kicker">Featured Drop</p>

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
                                    </div>

                                    <div class="shrink-0 text-right">
                                        <p
                                            class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500"
                                        >
                                            Price
                                        </p>

                                        <p
                                            class="mt-1 text-xl font-black text-white sm:text-2xl"
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

                        <div class="featured-card-footer p-2 pt-3">
                            <Link
                                v-if="featuredWatch"
                                :href="
                                    route(
                                        'public.watches.show',
                                        featuredWatch.id,
                                    )
                                "
                                class="feature-cta group"
                                @click="preparePageOpen"
                            >
                                <span>View Featured Piece</span>
                                <span
                                    class="transition group-hover:translate-x-1"
                                >
                                    →
                                </span>
                            </Link>

                            <a
                                v-else
                                href="#collection"
                                class="feature-cta group"
                            >
                                <span>Browse Available Stocks</span>
                                <span
                                    class="transition group-hover:translate-x-1"
                                >
                                    →
                                </span>
                            </a>
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
                    class="animated-in mb-8 flex flex-col justify-between gap-5 sm:mb-10 md:flex-row md:items-end"
                >
                    <div>
                        <p class="section-kicker">Current Collection</p>

                        <h2 class="section-title">Available Watches</h2>

                        <p
                            class="mt-4 max-w-2xl text-sm leading-7 text-zinc-400"
                        >
                            Tap any card to open the full watch details,
                            gallery, specifications, and inquiry option.
                        </p>

                        <p
                            v-if="paginationSummary"
                            class="mt-2 text-xs font-bold uppercase tracking-[0.18em] text-zinc-600"
                        >
                            {{ paginationSummary }}
                        </p>
                    </div>

                    <p
                        v-if="watches.length"
                        class="text-xs font-black uppercase tracking-[0.2em] text-zinc-600 md:hidden"
                    >
                        Swipe collection →
                    </p>
                </div>

                <template v-if="watches.length">
                    <TransitionGroup
                        name="watch-card"
                        tag="div"
                        class="flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-smooth pb-4 pr-4 overscroll-x-contain md:grid md:snap-none md:grid-cols-2 md:overflow-visible md:pb-0 md:pr-0 lg:grid-cols-3 xl:grid-cols-4 xl:gap-5 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                    >
                        <Link
                            v-for="(watch, index) in watches"
                            :key="watch.id"
                            :href="route('public.watches.show', watch.id)"
                            class="watch-card group"
                            :class="isSold(watch) ? 'opacity-70' : ''"
                            :style="{ animationDelay: `${index * 65}ms` }"
                            :aria-label="`View details for ${watchFullName(watch)}${watchReference(watch)}`"
                            @click="preparePageOpen"
                        >
                            <div
                                class="relative min-h-[430px] overflow-hidden bg-[#050505] sm:min-h-[470px] lg:min-h-[500px]"
                            >
                                <img
                                    v-if="watchImage(watch)"
                                    :src="watchImage(watch)"
                                    :alt="`${watch.brand} ${watch.model_name}`"
                                    class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-[1.045]"
                                    loading="lazy"
                                    @error="handleImageError"
                                />

                                <div
                                    v-else
                                    class="absolute inset-0 flex items-center justify-center bg-[#050505]"
                                >
                                    <div class="text-center">
                                        <div class="placeholder-logo">
                                            <span>MN</span>
                                        </div>

                                        <p class="mt-4 brand-kicker">
                                            Montre Nova
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="absolute inset-0 bg-gradient-to-t from-black/84 via-black/24 to-transparent"
                                ></div>

                                <div
                                    class="absolute inset-0 bg-[radial-gradient(circle_at_center,transparent_0%,rgba(0,0,0,0.04)_52%,rgba(0,0,0,0.38)_100%)]"
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
                                    <p
                                        class="truncate text-[10px] font-black uppercase tracking-[0.32em] text-zinc-300/90 sm:text-xs"
                                    >
                                        {{ watch.brand || "Montre Nova" }}
                                    </p>

                                    <h3
                                        class="mt-3 line-clamp-2 text-2xl font-medium leading-tight tracking-[0.02em] text-white drop-shadow-[0_2px_12px_rgba(0,0,0,0.75)] sm:text-3xl"
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
                                        <span class="detail-chip">
                                            {{
                                                watch.condition ||
                                                "Condition upon request"
                                            }}
                                        </span>

                                        <span
                                            v-if="watch.category"
                                            class="detail-chip"
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

                                        <span class="view-detail-pill">
                                            Details
                                            <span aria-hidden="true">→</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </TransitionGroup>

                    <!-- PAGINATION -->
                    <div
                        v-if="hasWatchPagination"
                        class="animated-in mt-8 flex flex-col items-center justify-between gap-5 rounded-[1.25rem] border border-white/10 bg-white/[0.035] p-4 backdrop-blur sm:flex-row md:mt-10"
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
                                    class="rounded-lg border px-4 py-2 text-sm font-bold transition duration-300"
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

                <div v-else class="empty-state">
                    <div class="placeholder-logo mx-auto">
                        <span>MN</span>
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
                        class="primary-button mt-6 px-6 py-3 text-sm"
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
                    class="animated-in mb-7 flex flex-col justify-between gap-5 md:flex-row md:items-end"
                >
                    <div>
                        <p class="section-kicker">Recently Sold</p>

                        <h2 class="section-title">Claimed Timepieces</h2>

                        <p
                            class="mt-4 max-w-2xl text-sm leading-7 text-zinc-400"
                        >
                            Recently claimed pieces from Montre Nova clients.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-2 sm:flex sm:items-center">
                        <div class="sold-mini-stat">
                            <span>{{ soldThisMonthCount }}</span>
                            <p>{{ soldMonthLabel }}</p>
                        </div>

                        <Link
                            href="/sold-watches"
                            class="secondary-button px-5 py-3 text-sm"
                        >
                            View Sold Gallery
                        </Link>
                    </div>
                </div>

                <p
                    class="mb-4 text-xs font-bold uppercase tracking-[0.2em] text-zinc-600 md:hidden"
                >
                    Swipe sold watches →
                </p>

                <TransitionGroup
                    name="watch-card"
                    tag="div"
                    class="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-4 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    <article
                        v-for="(watch, index) in recentSoldWatches"
                        :key="watch.id"
                        class="sold-card group"
                        :style="{ animationDelay: `${index * 65}ms` }"
                    >
                        <div
                            class="relative min-h-[430px] overflow-hidden bg-[#050505] sm:min-h-[500px]"
                        >
                            <img
                                v-if="watchImage(watch)"
                                :src="watchImage(watch)"
                                :alt="`${watch.brand} ${watch.model_name}`"
                                class="absolute inset-0 h-full w-full object-cover grayscale-[12%] transition duration-700 group-hover:scale-[1.045] group-hover:grayscale-0"
                                loading="lazy"
                                @error="handleImageError"
                            />

                            <div
                                v-else
                                class="absolute inset-0 flex items-center justify-center bg-[#050505]"
                            >
                                <div class="text-center">
                                    <div class="placeholder-logo">
                                        <span>MN</span>
                                    </div>

                                    <p class="mt-4 brand-kicker">Montre Nova</p>
                                </div>
                            </div>

                            <div
                                class="absolute inset-0 bg-gradient-to-t from-black/84 via-black/24 to-transparent"
                            ></div>

                            <div
                                class="absolute inset-0 bg-[radial-gradient(circle_at_center,transparent_0%,rgba(0,0,0,0.04)_52%,rgba(0,0,0,0.38)_100%)]"
                            ></div>

                            <div
                                class="absolute left-4 top-4 z-20 flex max-w-[92%] flex-wrap gap-1.5 sm:left-5 sm:top-5"
                            >
                                <span
                                    class="rounded-md border border-red-400/30 bg-red-500/15 px-3 py-1.5 text-[9px] font-black uppercase tracking-[0.14em] text-red-100 shadow-lg shadow-red-950/30 backdrop-blur"
                                >
                                    Sold
                                </span>

                                <span
                                    class="rounded-md border border-white/10 bg-black/45 px-3 py-1.5 text-[9px] font-black uppercase tracking-[0.14em] text-zinc-300 shadow-lg shadow-black/40 backdrop-blur"
                                >
                                    {{ soldDateLabel(watch) }}
                                </span>
                            </div>

                            <div
                                class="absolute inset-x-0 bottom-0 z-20 p-5 sm:p-6"
                            >
                                <p
                                    class="truncate text-[10px] font-black uppercase tracking-[0.32em] text-zinc-300/90 sm:text-xs"
                                >
                                    {{ watch.brand || "Montre Nova" }}
                                </p>

                                <h3
                                    class="mt-3 line-clamp-2 text-2xl font-medium leading-tight tracking-[0.02em] text-white drop-shadow-[0_2px_12px_rgba(0,0,0,0.75)] sm:text-3xl"
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
                                    <span class="detail-chip">
                                        {{ soldConditionLabel(watch) }}
                                    </span>

                                    <span
                                        v-if="watch.category"
                                        class="detail-chip"
                                    >
                                        {{ watch.category }}
                                    </span>
                                </div>

                                <div
                                    class="mt-6 flex items-center justify-between gap-4 border-t border-white/10 pt-4"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500"
                                        >
                                            Looking for similar?
                                        </p>

                                        <p
                                            class="mt-1 truncate text-sm text-zinc-300"
                                        >
                                            Send us your preferred model.
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        class="secondary-button shrink-0 px-4 py-2.5 text-xs"
                                        @click="openSimilarInquiry(watch)"
                                    >
                                        Source
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                </TransitionGroup>
            </section>

            <!-- HOW TO ORDER -->
            <section
                id="process"
                class="scroll-mt-28 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
            >
                <div class="animated-in mb-8 sm:mb-10">
                    <p class="section-kicker">How to Order</p>

                    <h2
                        class="mt-4 max-w-2xl text-3xl font-black tracking-[-0.04em] text-white sm:text-4xl"
                    >
                        A simple, secure flow from inquiry to delivery.
                    </h2>
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    <div
                        v-for="(step, index) in orderSteps"
                        :key="step.title"
                        class="process-card animated-in"
                        :style="{ animationDelay: `${index * 75}ms` }"
                    >
                        <p
                            class="text-xs font-black uppercase tracking-[0.28em] text-zinc-600"
                        >
                            {{ step.number }}
                        </p>

                        <h3
                            class="mt-4 text-xl font-black tracking-[-0.03em] text-white"
                        >
                            {{ step.title }}
                        </h3>

                        <p class="mt-4 text-sm leading-7 text-zinc-400">
                            {{ step.description }}
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
                    class="animated-in relative overflow-hidden rounded-[1.5rem] border border-white/10 bg-[#080809] p-6 shadow-2xl shadow-black/40 sm:p-10 lg:p-12"
                >
                    <div
                        class="absolute -right-32 -top-32 h-80 w-80 rounded-full bg-white/[0.06] blur-3xl"
                    ></div>

                    <div
                        class="absolute -left-24 bottom-0 h-64 w-64 rounded-full bg-zinc-400/[0.035] blur-3xl"
                    ></div>

                    <div class="shine-line"></div>

                    <div
                        class="relative grid gap-10 lg:grid-cols-[1fr_0.9fr] lg:items-center"
                    >
                        <div>
                            <p class="section-kicker">Get in Touch</p>

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
                                <p class="brand-kicker">Faster Assistance</p>

                                <p class="mt-2 text-sm leading-7 text-zinc-400">
                                    Send the model, reference number, budget,
                                    and preferred condition so we can assist
                                    faster.
                                </p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <a
                                v-for="(link, index) in contactLinks"
                                :key="link.label"
                                :href="link.href"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="contact-card group"
                                :style="{ animationDelay: `${index * 65}ms` }"
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
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/[0.04] text-zinc-400 transition duration-300 group-hover:translate-x-1 group-hover:border-white/25 group-hover:bg-white group-hover:text-black"
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

        <!-- PAGE OPENING TRANSITION -->
        <Transition name="page-open">
            <div v-if="isPageOpening" class="page-open-overlay">
                <div class="page-open-card">
                    <div class="page-open-mark">MN</div>

                    <div>
                        <p class="page-open-title">Opening details</p>
                        <p class="page-open-subtitle">
                            Preparing the watch page
                        </p>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- MOBILE STICKY CTA -->
        <div
            class="fixed inset-x-0 bottom-0 z-[60] border-t border-white/10 bg-black/90 px-4 py-3 backdrop-blur-xl md:hidden"
        >
            <div class="grid grid-cols-[0.78fr_1.22fr] gap-3">
                <a
                    href="#collection"
                    class="secondary-button px-4 py-3 text-sm"
                >
                    Stocks
                </a>

                <button
                    type="button"
                    class="primary-button px-4 py-3 text-sm"
                    @click="openMessengerInquiry()"
                >
                    Message Montre Nova
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
:global(html) {
    scroll-behavior: smooth;
}

:global(#collection),
:global(#recently-sold),
:global(#process),
:global(#contact) {
    scroll-margin-top: 7.5rem;
}

:global(#collection:target),
:global(#recently-sold:target),
:global(#process:target),
:global(#contact:target) {
    animation: sectionFocus 950ms ease-out both;
}

main > section {
    position: relative;
}

/* HERO BALANCE */
.hero-copy,
.hero-feature-wrap {
    min-height: 0;
}

.featured-lux-card {
    border-color: rgb(255 255 255 / 0.055);
    background: linear-gradient(
        180deg,
        rgb(12 12 13 / 0.96),
        rgb(5 5 5 / 0.96)
    );
    box-shadow:
        0 28px 90px rgb(0 0 0 / 0.46),
        inset 0 1px 0 rgb(255 255 255 / 0.065);
}

.featured-media {
    border-color: rgb(255 255 255 / 0.055);
    box-shadow: inset 0 1px 0 rgb(255 255 255 / 0.06);
}

.featured-card-footer {
    flex: 0 0 auto;
}

@media (min-width: 1024px) {
    .hero-copy,
    .hero-feature-wrap {
        min-height: clamp(610px, 72vh, 760px);
    }

    .featured-lux-card {
        min-height: 100%;
    }

    .featured-media {
        min-height: 0;
    }
}

@media (min-width: 1280px) {
    .hero-copy,
    .hero-feature-wrap {
        min-height: clamp(650px, 74vh, 800px);
    }
}

.nav-link {
    position: relative;
    transition:
        color 260ms ease,
        opacity 260ms ease;
}

.nav-link::after {
    position: absolute;
    right: 0;
    bottom: -0.45rem;
    left: 0;
    height: 1px;
    content: "";
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.8),
        transparent
    );
    opacity: 0;
    transform: scaleX(0.45);
    transition:
        opacity 260ms ease,
        transform 260ms ease;
}

.nav-link:hover {
    color: white;
}

.nav-link:hover::after {
    opacity: 1;
    transform: scaleX(1);
}

.mobile-nav-pill {
    flex-shrink: 0;
    border-radius: 0.75rem;
    border: 1px solid rgb(255 255 255 / 0.055);
    background: rgb(255 255 255 / 0.035);
    padding: 0.5rem 1rem;
    transition:
        border-color 260ms ease,
        background-color 260ms ease,
        color 260ms ease;
}

.mobile-nav-pill:hover {
    border-color: rgb(255 255 255 / 0.25);
    color: white;
}

.primary-button,
.secondary-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.8rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    transition:
        transform 260ms ease,
        background-color 260ms ease,
        border-color 260ms ease,
        color 260ms ease,
        box-shadow 260ms ease;
}

.primary-button {
    background: white;
    color: black;
    box-shadow: 0 12px 35px rgb(255 255 255 / 0.08);
}

.primary-button:hover {
    transform: translateY(-1px);
    background: rgb(228 228 231);
    box-shadow: 0 18px 48px rgb(255 255 255 / 0.14);
}

.secondary-button {
    border: 1px solid rgb(255 255 255 / 0.06);
    background: rgb(255 255 255 / 0.035);
    color: white;
    backdrop-filter: blur(18px);
}

.secondary-button:hover {
    transform: translateY(-1px);
    border-color: rgb(255 255 255 / 0.16);
    background: rgb(255 255 255 / 0.065);
}

.primary-button:active,
.secondary-button:active {
    transform: scale(0.985);
}

.feature-cta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    border-radius: 1rem;
    border: 1px solid rgb(255 255 255 / 0.08);
    background: linear-gradient(
        135deg,
        rgb(255 255 255 / 0.98),
        rgb(228 228 231 / 0.96)
    );
    padding: 1rem 1.1rem;
    color: black;
    font-size: 0.78rem;
    font-weight: 950;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    box-shadow: 0 18px 46px rgb(255 255 255 / 0.11);
    transition:
        transform 280ms ease,
        box-shadow 280ms ease,
        filter 280ms ease;
}

.feature-cta:hover {
    transform: translateY(-2px);
    filter: brightness(1.02);
    box-shadow: 0 24px 62px rgb(255 255 255 / 0.16);
}

.view-detail-pill {
    display: inline-flex;
    flex-shrink: 0;
    align-items: center;
    gap: 0.35rem;
    border-radius: 9999px;
    border: 1px solid rgb(255 255 255 / 0.12);
    background: rgb(255 255 255 / 0.92);
    padding: 0.65rem 0.85rem;
    color: black;
    font-size: 0.66rem;
    font-weight: 950;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    box-shadow: 0 12px 30px rgb(255 255 255 / 0.1);
    transition:
        transform 260ms ease,
        background-color 260ms ease;
}

.watch-card:hover .view-detail-pill {
    transform: translateX(3px);
    background: white;
}

.premium-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    border-radius: 0.8rem;
    border: 1px solid rgb(255 255 255 / 0.055);
    background: rgb(255 255 255 / 0.035);
    padding: 0.5rem 1rem;
    font-size: 0.625rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.28em;
    color: rgb(161 161 170);
    box-shadow: 0 16px 45px rgb(0 0 0 / 0.28);
}

.pulse-dot {
    height: 0.375rem;
    width: 0.375rem;
    border-radius: 9999px;
    background: rgb(255 255 255 / 0.88);
    box-shadow: 0 0 0 0 rgb(255 255 255 / 0.28);
    animation: pulseDot 2.4s ease-out infinite;
}

.stat-card {
    border-radius: 1rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: rgb(255 255 255 / 0.035);
    padding: 1rem;
    transition:
        transform 260ms ease,
        border-color 260ms ease,
        background-color 260ms ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    border-color: rgb(255 255 255 / 0.24);
    background: rgb(255 255 255 / 0.06);
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 900;
    line-height: 1;
    letter-spacing: -0.03em;
    color: white;
}

.stat-label {
    margin-top: 0.35rem;
    font-size: 0.625rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    color: rgb(113 113 122);
}

.lux-card {
    animation: fadeLift 720ms both;
    isolation: isolate;
}

.shine-line {
    position: absolute;
    top: 0;
    right: 2rem;
    left: 2rem;
    height: 1px;
    background: linear-gradient(
        90deg,
        transparent,
        rgb(255 255 255 / 0.5),
        transparent
    );
}

.placeholder-logo {
    display: flex;
    height: 8rem;
    width: 8rem;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: rgb(255 255 255 / 0.045);
    box-shadow: 0 24px 60px rgb(0 0 0 / 0.5);
}

.placeholder-logo span {
    font-size: 2.5rem;
    font-weight: 900;
    letter-spacing: -0.12em;
    color: white;
}

.brand-kicker,
.section-kicker {
    font-size: 0.68rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.34em;
    color: rgb(113 113 122);
}

.section-title {
    margin-top: 0.75rem;
    font-size: 2.25rem;
    font-weight: 900;
    line-height: 1;
    letter-spacing: -0.06em;
    color: white;
}

@media (min-width: 640px) {
    .section-title {
        font-size: 3rem;
    }
}

.trust-pill {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 0.85rem;
    border-radius: 1rem;
    border: 1px solid rgb(255 255 255 / 0.08);
    background: rgb(0 0 0 / 0.32);
    padding: 1rem;
    animation: fadeLift 650ms both;
    transition:
        transform 260ms ease,
        border-color 260ms ease,
        background-color 260ms ease;
}

.trust-pill:hover {
    transform: translateY(-2px);
    border-color: rgb(255 255 255 / 0.22);
    background: rgb(255 255 255 / 0.055);
}

.watch-card,
.sold-card {
    position: relative;
    display: block;
    color: inherit;
    text-decoration: none;
    min-width: 82vw;
    max-width: 82vw;
    scroll-snap-align: start;
    overflow: hidden;
    border-radius: 1.25rem;
    border: 1px solid rgb(255 255 255 / 0.045);
    background: linear-gradient(
        180deg,
        rgb(255 255 255 / 0.025),
        rgb(0 0 0 / 0.96)
    );
    box-shadow:
        0 26px 82px rgb(0 0 0 / 0.42),
        inset 0 1px 0 rgb(255 255 255 / 0.055);
    animation: fadeLift 700ms both;
    transition:
        transform 420ms cubic-bezier(0.2, 0.8, 0.2, 1),
        border-color 320ms ease,
        box-shadow 320ms ease;
}

.watch-card {
    cursor: pointer;
}

.watch-card:focus-visible {
    outline: 2px solid rgb(255 255 255 / 0.72);
    outline-offset: 4px;
}

.watch-card:hover,
.sold-card:hover {
    border-color: rgb(255 255 255 / 0.13);
    box-shadow:
        0 36px 110px rgb(0 0 0 / 0.65),
        inset 0 1px 0 rgb(255 255 255 / 0.08);
}

@media (min-width: 640px) {
    .watch-card {
        min-width: 360px;
        max-width: 360px;
    }

    .sold-card {
        min-width: 340px;
        max-width: 340px;
    }
}

@media (min-width: 768px) {
    .watch-card {
        min-width: 0;
        max-width: none;
    }

    .watch-card:hover,
    .sold-card:hover {
        transform: translateY(-0.25rem);
    }

    .sold-card {
        min-width: 360px;
        max-width: 360px;
    }
}

.detail-chip {
    border-radius: 9999px;
    border: 1px solid rgb(255 255 255 / 0.065);
    background: rgb(0 0 0 / 0.34);
    padding: 0.3rem 0.72rem;
    font-size: 0.625rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    color: rgb(212 212 216);
    backdrop-filter: blur(14px);
}

.sold-mini-stat {
    display: flex;
    min-height: 3rem;
    align-items: center;
    gap: 0.75rem;
    border-radius: 0.8rem;
    border: 1px solid rgb(255 255 255 / 0.055);
    background: rgb(255 255 255 / 0.035);
    padding: 0.75rem 1rem;
}

.sold-mini-stat span {
    font-size: 1.35rem;
    font-weight: 900;
    color: white;
}

.sold-mini-stat p {
    max-width: 7rem;
    font-size: 0.63rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.16em;
    color: rgb(113 113 122);
}

.process-card {
    border-radius: 1.1rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: rgb(11 11 13 / 0.9);
    padding: 1.75rem;
    box-shadow: 0 20px 60px rgb(0 0 0 / 0.25);
    transition:
        transform 300ms ease,
        border-color 300ms ease,
        background-color 300ms ease;
}

.process-card:hover {
    transform: translateY(-3px);
    border-color: rgb(255 255 255 / 0.24);
    background: rgb(17 17 19 / 0.95);
}

.contact-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.25rem;
    border-radius: 1rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: rgb(0 0 0 / 0.45);
    padding: 1.25rem;
    color: white;
    box-shadow: 0 18px 40px rgb(0 0 0 / 0.22);
    animation: fadeLift 650ms both;
    transition:
        transform 300ms ease,
        border-color 300ms ease,
        background-color 300ms ease,
        box-shadow 300ms ease;
}

.contact-card:hover {
    transform: translateY(-2px);
    border-color: rgb(255 255 255 / 0.25);
    background: rgb(255 255 255 / 0.06);
    box-shadow: 0 25px 70px rgb(0 0 0 / 0.42);
}

.page-open-overlay {
    position: fixed;
    inset: 0;
    z-index: 90;
    display: flex;
    align-items: center;
    justify-content: center;
    background:
        radial-gradient(
            circle at center,
            rgb(255 255 255 / 0.06),
            transparent 32%
        ),
        rgb(0 0 0 / 0.72);
    backdrop-filter: blur(18px);
}

.page-open-card {
    display: flex;
    align-items: center;
    gap: 0.95rem;
    border-radius: 1.15rem;
    border: 1px solid rgb(255 255 255 / 0.08);
    background: linear-gradient(
        135deg,
        rgb(18 18 19 / 0.92),
        rgb(5 5 5 / 0.94)
    );
    padding: 1rem 1.15rem;
    box-shadow:
        0 28px 90px rgb(0 0 0 / 0.58),
        inset 0 1px 0 rgb(255 255 255 / 0.07);
}

.page-open-mark {
    display: flex;
    height: 2.5rem;
    width: 2.5rem;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    background: white;
    color: black;
    font-size: 0.78rem;
    font-weight: 950;
    letter-spacing: -0.08em;
}

.page-open-title {
    font-size: 0.75rem;
    font-weight: 950;
    text-transform: uppercase;
    letter-spacing: 0.16em;
    color: white;
}

.page-open-subtitle {
    margin-top: 0.2rem;
    font-size: 0.76rem;
    color: rgb(113 113 122);
}

.page-open-enter-active,
.page-open-leave-active {
    transition:
        opacity 260ms ease,
        transform 260ms ease;
}

.page-open-enter-from,
.page-open-leave-to {
    opacity: 0;
}

.page-open-enter-from .page-open-card,
.page-open-leave-to .page-open-card {
    transform: translateY(8px) scale(0.98);
}

.empty-state {
    border-radius: 1.25rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: rgb(11 11 13);
    padding: 2.5rem;
    text-align: center;
    box-shadow: 0 25px 70px rgb(0 0 0 / 0.25);
}

.animated-in {
    animation: fadeLift 720ms both;
}

.watch-card-enter-active,
.watch-card-leave-active {
    transition: all 320ms ease;
}

.watch-card-enter-from,
.watch-card-leave-to {
    opacity: 0;
    transform: translateY(18px) scale(0.985);
}

.watch-card-move {
    transition: transform 320ms ease;
}

.watch-card:active,
.sold-card:active,
.feature-cta:active,
.primary-button:active,
.secondary-button:active,
.mobile-nav-pill:active,
.nav-link:active {
    transform: scale(0.985);
}

@keyframes sectionFocus {
    0% {
        filter: brightness(1);
        transform: translateY(0);
    }

    35% {
        filter: brightness(1.18);
        transform: translateY(-4px);
    }

    100% {
        filter: brightness(1);
        transform: translateY(0);
    }
}

@keyframes fadeLift {
    from {
        opacity: 0;
        transform: translateY(18px) scale(0.985);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes floatSoft {
    0%,
    100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-6px);
    }
}

@keyframes pulseDot {
    0% {
        box-shadow: 0 0 0 0 rgb(255 255 255 / 0.28);
    }

    70% {
        box-shadow: 0 0 0 10px rgb(255 255 255 / 0);
    }

    100% {
        box-shadow: 0 0 0 0 rgb(255 255 255 / 0);
    }
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.001ms !important;
        animation-iteration-count: 1 !important;
        scroll-behavior: auto !important;
        transition-duration: 0.001ms !important;
    }
}
</style>
