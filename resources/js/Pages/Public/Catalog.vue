<script setup>
import MontreLogo from "@/Components/MontreLogo.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
    watches: {
        type: [Array, Object, String],
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    activeCategory: {
        type: String,
        default: "all",
    },
    categoryCounts: {
        type: Object,
        default: () => ({}),
    },
    totalCatalogWatches: {
        type: Number,
        default: 0,
    },
    catalogDebug: {
        type: Object,
        default: () => ({}),
    },
});

const messengerUsername = "montrenova";
const isCatalogLoading = ref(false);

const parsePossibleJson = (value) => {
    if (typeof value !== "string") {
        return value;
    }

    try {
        return JSON.parse(value);
    } catch (error) {
        return value;
    }
};

const looksLikeWatch = (item) => {
    return (
        item &&
        typeof item === "object" &&
        ("id" in item ||
            "brand" in item ||
            "model_name" in item ||
            "reference_number" in item)
    );
};

const toCollectionArray = (collection) => {
    const parsed = parsePossibleJson(collection);

    if (!parsed) {
        return [];
    }

    if (Array.isArray(parsed)) {
        return parsed.filter(Boolean);
    }

    if (Array.isArray(parsed.data)) {
        return parsed.data.filter(Boolean);
    }

    if (Array.isArray(parsed.items)) {
        return parsed.items.filter(Boolean);
    }

    if (Array.isArray(parsed.watches)) {
        return parsed.watches.filter(Boolean);
    }

    if (parsed.props && Array.isArray(parsed.props.watches)) {
        return parsed.props.watches.filter(Boolean);
    }

    if (
        parsed.props &&
        parsed.props.watches &&
        Array.isArray(parsed.props.watches.data)
    ) {
        return parsed.props.watches.data.filter(Boolean);
    }

    if (typeof parsed === "object") {
        const directValues = Object.values(parsed).filter(looksLikeWatch);

        if (directValues.length) {
            return directValues;
        }

        const nestedArrays = Object.values(parsed)
            .filter(Array.isArray)
            .flat()
            .filter(looksLikeWatch);

        if (nestedArrays.length) {
            return nestedArrays;
        }

        const nestedObjects = Object.values(parsed)
            .filter((item) => item && typeof item === "object")
            .flatMap((item) => Object.values(item))
            .filter(looksLikeWatch);

        if (nestedObjects.length) {
            return nestedObjects;
        }
    }

    return [];
};

const catalogWatches = computed(() => toCollectionArray(props.watches));

const normalizeCategory = (category) => {
    return String(category || "all")
        .trim()
        .toLowerCase();
};

const categoryFilters = computed(() => {
    const categoryItems = Array.isArray(props.categories)
        ? props.categories
              .map((category) => String(category || "").trim())
              .filter(Boolean)
        : [];

    return [
        {
            label: "All",
            value: "all",
            count:
                Number(props.totalCatalogWatches || 0) ||
                catalogWatches.value.length,
        },
        ...categoryItems.map((category) => ({
            label: category,
            value: category,
            count: props.categoryCounts?.[category] || 0,
        })),
    ];
});

const isActiveCategory = (category) => {
    return (
        normalizeCategory(props.activeCategory) === normalizeCategory(category)
    );
};

const changeCategory = (category = "all") => {
    if (isCatalogLoading.value || isActiveCategory(category)) {
        return;
    }

    isCatalogLoading.value = true;

    router.get(
        route("public.catalog"),
        normalizeCategory(category) === "all" ? {} : { category },
        {
            preserveScroll: true,
            preserveState: false,
            replace: true,
            onFinish: () => {
                window.setTimeout(() => {
                    isCatalogLoading.value = false;
                }, 180);
            },
        },
    );
};

const peso = (value) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 0,
    }).format(Number(value || 0));
};

const watchFullName = (watch) => {
    if (!watch) {
        return "this watch";
    }

    return `${watch.brand || ""} ${watch.model_name || ""}`.trim();
};

const watchReference = (watch) => {
    return watch?.reference_number ? ` Ref. ${watch.reference_number}` : "";
};

const inquiryMessage = (watch = null) => {
    if (!watch) {
        return "Hi Montre Nova, I’m interested in your watch catalog. Can I see the latest available pieces?";
    }

    return `Hi Montre Nova, I’m interested in ${watchFullName(watch)}${watchReference(watch)}. Is this available?`;
};

const messengerUrl = (message) => {
    return `https://m.me/${messengerUsername}?text=${encodeURIComponent(message)}`;
};

const openInquiry = (watch = null) => {
    window.open(
        messengerUrl(inquiryMessage(watch)),
        "_blank",
        "noopener,noreferrer",
    );
};

const normalizeImageUrl = (url) => {
    if (!url) {
        return null;
    }

    const cleanUrl = String(url).trim();

    if (!cleanUrl) {
        return null;
    }

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

    if (!image) {
        return;
    }

    image.style.display = "none";
};

const finalPrice = (watch) => {
    if (!watch) {
        return 0;
    }

    const discounted = Number(watch.discounted_price || 0);
    const selling = Number(watch.selling_price || 0);
    const price = Number(watch.price || 0);

    if (discounted > 0 && selling > discounted) {
        return discounted;
    }

    return price || selling || discounted || 0;
};

const originalPrice = (watch) => {
    if (!watch) {
        return 0;
    }

    return Number(watch.selling_price || watch.price || 0);
};

const displayPrice = (watch) => {
    const price = finalPrice(watch);

    return Number(price || 0) > 0 ? peso(price) : "Ask price";
};

const isBelowSrp = (watch) => {
    return (
        Number(watch?.discounted_price || 0) > 0 &&
        Number(watch?.selling_price || 0) > Number(watch?.discounted_price || 0)
    );
};

const statusLabel = (watch) => {
    const status = normalizeCategory(watch?.status || "available");

    const labels = {
        available: "Available",
        reserved: "Reserved",
        sold: "Sold",
        hidden: "Hidden",
        draft: "Draft",
    };

    return labels[status] || "Catalog";
};

const statusClass = (watch) => {
    const status = normalizeCategory(watch?.status || "available");

    const classes = {
        available: "border-emerald-400/20 bg-emerald-400/10 text-emerald-200",
        reserved: "border-amber-400/20 bg-amber-400/10 text-amber-200",
        sold: "border-red-400/25 bg-red-500/10 text-red-200",
        hidden: "border-zinc-400/20 bg-zinc-400/10 text-zinc-300",
        draft: "border-zinc-400/20 bg-zinc-400/10 text-zinc-300",
    };

    return classes[status] || "border-white/10 bg-black/45 text-zinc-200";
};

const resultSummary = computed(() => {
    const count = catalogWatches.value.length;
    const active = String(props.activeCategory || "all").trim();

    if (normalizeCategory(active) === "all") {
        return `${count} catalog ${count === 1 ? "piece" : "pieces"}`;
    }

    return `${count} ${active} ${count === 1 ? "piece" : "pieces"}`;
});

const rawWatchType = computed(() => {
    if (Array.isArray(props.watches)) {
        return "array";
    }

    if (props.watches === null) {
        return "null";
    }

    return typeof props.watches;
});

const rawWatchKeys = computed(() => {
    if (!props.watches || typeof props.watches !== "object") {
        return [];
    }

    return Object.keys(props.watches).slice(0, 10);
});
</script>

<template>
    <Head title="Catalog | Montre Nova" />

    <div
        class="min-h-screen overflow-x-hidden bg-[#030303] text-white selection:bg-white selection:text-black"
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
                class="absolute left-1/2 top-[42rem] h-[28rem] w-[42rem] -translate-x-1/2 rounded-full bg-white/[0.025] blur-[135px]"
            ></div>
        </div>

        <!-- HEADER -->
        <header
            class="fixed inset-x-0 top-0 z-[90] border-b border-white/10 bg-black/80 shadow-xl shadow-black/30 backdrop-blur-2xl"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8"
            >
                <Link
                    :href="route('welcome')"
                    class="flex min-w-0 items-center rounded-xl transition duration-300 hover:opacity-80 active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-white/40"
                >
                    <MontreLogo />
                </Link>

                <Link
                    :href="route('welcome')"
                    class="group inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/[0.04] text-zinc-300 shadow-lg shadow-black/20 transition duration-300 hover:-translate-y-0.5 hover:border-white/35 hover:bg-white hover:text-black hover:shadow-xl hover:shadow-white/15 active:scale-[0.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-white/50 sm:h-auto sm:w-auto sm:gap-2 sm:px-4 sm:py-2.5"
                    aria-label="Back to home"
                >
                    <svg
                        viewBox="0 0 24 24"
                        class="h-4 w-4 transition duration-300 group-hover:-translate-y-0.5 group-hover:scale-110"
                        aria-hidden="true"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M3 10.5 12 3l9 7.5" />
                        <path d="M5 10v10h14V10" />
                        <path d="M9 20v-6h6v6" />
                    </svg>

                    <span
                        class="hidden text-xs font-bold uppercase tracking-[0.12em] sm:inline"
                    >
                        Home
                    </span>
                </Link>
            </div>
        </header>

        <!-- LOADING BAR -->
        <Transition name="catalog-loading">
            <div
                v-if="isCatalogLoading"
                class="fixed left-0 right-0 top-[4.15rem] z-[95] h-[2px] overflow-hidden bg-white/5"
            >
                <div class="catalog-loading-bar"></div>
            </div>
        </Transition>

        <main
            class="relative z-10 px-4 pb-20 pt-[5.65rem] sm:px-6 sm:pt-[5.9rem] lg:px-8"
        >
            <section class="mx-auto max-w-7xl">
                <!-- HERO -->
                <div
                    class="catalog-hero mb-5 overflow-hidden rounded-[1.4rem] border border-white/10 bg-white/[0.035] p-5 shadow-2xl shadow-black/35 sm:mb-7 sm:p-7 lg:p-8"
                >
                    <div class="shine-line"></div>

                    <div
                        class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
                    >
                        <div class="max-w-4xl">
                            <p class="section-kicker">Montre Nova Catalog</p>

                            <h1
                                class="mt-4 text-[2.45rem] font-black leading-[0.9] tracking-[-0.075em] text-white sm:text-6xl lg:text-7xl"
                            >
                                Browse all curated watches.
                            </h1>

                            <p
                                class="mt-5 max-w-2xl text-sm leading-7 text-zinc-400 sm:text-base"
                            >
                                Pick a category, tap a watch, and message Montre
                                Nova directly to ask availability.
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-white/10 bg-black/35 p-4 sm:min-w-[13rem]"
                        >
                            <p
                                class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600"
                            >
                                Showing
                            </p>

                            <p class="mt-1 text-xl font-black text-white">
                                {{ resultSummary }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- CATEGORY FILTERS -->
                <div
                    class="sticky top-[4.25rem] z-30 mb-5 -mx-4 border-y border-white/10 bg-black/80 px-4 py-3 shadow-xl shadow-black/30 backdrop-blur-2xl sm:-mx-6 sm:mb-7 sm:px-6 lg:-mx-8 lg:px-8"
                >
                    <div
                        class="flex gap-2 overflow-x-auto pr-3 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                    >
                        <button
                            v-for="filter in categoryFilters"
                            :key="filter.value"
                            type="button"
                            class="inline-flex min-h-10 shrink-0 items-center gap-2 rounded-full border px-3.5 py-2.5 text-[10px] font-black uppercase tracking-[0.13em] transition duration-300 active:scale-[0.97] disabled:pointer-events-none sm:px-4 sm:text-xs"
                            :class="
                                isActiveCategory(filter.value)
                                    ? 'border-white bg-white text-black shadow-lg shadow-white/10'
                                    : 'border-white/10 bg-white/[0.045] text-zinc-400 hover:border-white/30 hover:bg-white/[0.07] hover:text-white'
                            "
                            :disabled="
                                isCatalogLoading ||
                                isActiveCategory(filter.value)
                            "
                            @click="changeCategory(filter.value)"
                        >
                            <span>{{ filter.label }}</span>

                            <span
                                class="rounded-full border px-2 py-0.5 text-[10px]"
                                :class="
                                    isActiveCategory(filter.value)
                                        ? 'border-black/10 bg-black/5 text-black'
                                        : 'border-white/10 bg-white/[0.035] text-zinc-500'
                                "
                            >
                                {{ filter.count }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- MOBILE LOADING NOTICE -->
                <Transition name="catalog-mobile-loading">
                    <div
                        v-if="isCatalogLoading"
                        class="mb-4 flex items-center gap-3 rounded-2xl border border-white/10 bg-white/[0.045] p-3 text-xs font-bold text-zinc-300 shadow-xl shadow-black/25 sm:hidden"
                    >
                        <span class="catalog-spinner"></span>
                        Updating catalog...
                    </div>
                </Transition>

                <!-- WATCH GRID -->
                <template v-if="catalogWatches.length">
                    <div
                        class="catalog-grid grid gap-4 transition duration-300 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 xl:gap-5"
                        :class="
                            isCatalogLoading
                                ? 'pointer-events-none scale-[0.997] opacity-60 blur-[1px]'
                                : 'opacity-100 blur-0'
                        "
                    >
                        <button
                            v-for="(watch, index) in catalogWatches"
                            :key="
                                watch.id ||
                                `${watch.brand}-${watch.model_name}-${index}`
                            "
                            type="button"
                            class="catalog-card group text-left"
                            :style="{ animationDelay: `${index * 45}ms` }"
                            :aria-label="
                                'Ask availability for ' +
                                watchFullName(watch) +
                                watchReference(watch)
                            "
                            @click="openInquiry(watch)"
                        >
                            <div
                                class="relative min-h-[385px] overflow-hidden bg-[#050505] sm:min-h-[430px]"
                            >
                                <div class="image-shimmer"></div>

                                <img
                                    v-if="watchImage(watch)"
                                    :src="watchImage(watch)"
                                    :alt="
                                        (watch.brand || 'Montre Nova') +
                                        ' ' +
                                        (watch.model_name || 'Watch')
                                    "
                                    class="absolute inset-0 z-[1] h-full w-full object-cover transition duration-700 group-hover:scale-[1.045]"
                                    loading="lazy"
                                    @error="handleImageError"
                                />

                                <div
                                    v-else
                                    class="absolute inset-0 z-[1] flex items-center justify-center bg-[#050505]"
                                >
                                    <div class="text-center">
                                        <div class="placeholder-logo mx-auto">
                                            <span>MN</span>
                                        </div>

                                        <p class="mt-4 brand-kicker">
                                            Montre Nova
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="absolute inset-0 z-[2] bg-gradient-to-t from-black/94 via-black/34 to-black/8"
                                ></div>

                                <div
                                    class="absolute inset-x-0 top-0 z-[3] h-28 bg-gradient-to-b from-black/40 to-transparent"
                                ></div>

                                <div
                                    class="absolute left-4 top-4 z-20 flex max-w-[92%] flex-wrap gap-1.5"
                                >
                                    <span
                                        class="rounded-md border px-3 py-1.5 text-[9px] font-black uppercase tracking-[0.14em] shadow-lg shadow-black/40 backdrop-blur"
                                        :class="statusClass(watch)"
                                    >
                                        {{ statusLabel(watch) }}
                                    </span>

                                    <span
                                        v-if="watch.category"
                                        class="rounded-md border border-white/10 bg-black/45 px-3 py-1.5 text-[9px] font-black uppercase tracking-[0.14em] text-zinc-200 shadow-lg shadow-black/40 backdrop-blur"
                                    >
                                        {{ watch.category }}
                                    </span>

                                    <span
                                        v-if="isBelowSrp(watch)"
                                        class="rounded-md border border-violet-400/20 bg-violet-400/10 px-3 py-1.5 text-[9px] font-black uppercase tracking-[0.14em] text-violet-200 shadow-lg shadow-black/40 backdrop-blur"
                                    >
                                        Below SRP
                                    </span>
                                </div>

                                <div
                                    class="absolute inset-x-0 bottom-0 z-20 p-5"
                                >
                                    <p
                                        class="truncate text-[10px] font-black uppercase tracking-[0.3em] text-zinc-400"
                                    >
                                        {{ watch.brand || "Montre Nova" }}
                                    </p>

                                    <h2
                                        class="mt-3 line-clamp-2 text-[1.45rem] font-medium leading-tight tracking-[0.02em] text-white drop-shadow-[0_2px_12px_rgba(0,0,0,0.75)] sm:text-2xl"
                                    >
                                        {{
                                            watch.model_name || "Curated Watch"
                                        }}
                                    </h2>

                                    <p
                                        class="mt-2 truncate text-sm text-zinc-400"
                                    >
                                        Ref.
                                        {{
                                            watch.reference_number ||
                                            "No reference"
                                        }}
                                    </p>

                                    <div
                                        class="mt-5 flex items-end justify-between gap-4 border-t border-white/10 pt-4"
                                    >
                                        <div class="min-w-0">
                                            <p
                                                class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500"
                                            >
                                                Price
                                            </p>

                                            <p
                                                class="mt-1 truncate text-xl font-black text-white"
                                            >
                                                {{ displayPrice(watch) }}
                                            </p>

                                            <p
                                                v-if="isBelowSrp(watch)"
                                                class="text-xs text-zinc-500 line-through"
                                            >
                                                {{ peso(originalPrice(watch)) }}
                                            </p>
                                        </div>

                                        <span class="ask-pill">
                                            Ask
                                            <span aria-hidden="true">→</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </button>
                    </div>
                </template>

                <!-- EMPTY / DEBUG STATE -->
                <div v-else class="empty-state">
                    <div class="placeholder-logo mx-auto">
                        <span>MN</span>
                    </div>

                    <h2 class="mt-6 text-xl font-black text-white">
                        No catalog watches received.
                    </h2>

                    <p
                        class="mx-auto mt-3 max-w-md text-sm leading-7 text-zinc-500"
                    >
                        The page loaded, but the frontend did not receive usable
                        watch records yet.
                    </p>

                    <div
                        class="mx-auto mt-6 max-w-xl rounded-2xl border border-white/10 bg-black/35 p-4 text-left text-xs leading-6 text-zinc-400"
                    >
                        <p
                            class="font-black uppercase tracking-[0.18em] text-white"
                        >
                            Data Check
                        </p>

                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            <p>
                                Raw prop type:
                                <span class="text-white">
                                    {{ rawWatchType }}
                                </span>
                            </p>

                            <p>
                                Parsed cards:
                                <span class="text-white">
                                    {{ catalogWatches.length }}
                                </span>
                            </p>

                            <p>
                                Total from controller:
                                <span class="text-white">
                                    {{ totalCatalogWatches }}
                                </span>
                            </p>

                            <p>
                                DB count:
                                <span class="text-white">
                                    {{
                                        catalogDebug?.database_watch_count ??
                                        "not sent"
                                    }}
                                </span>
                            </p>
                        </div>

                        <p v-if="rawWatchKeys.length" class="mt-3">
                            Prop keys:
                            <span class="text-white">
                                {{ rawWatchKeys.join(", ") }}
                            </span>
                        </p>
                    </div>

                    <button
                        type="button"
                        class="primary-button mt-6 px-6 py-3 text-sm"
                        @click="openInquiry()"
                    >
                        Ask Availability
                    </button>
                </div>
            </section>
        </main>

        <!-- MOBILE-ONLY MESSENGER FLOATING BUTTON -->
        <button
            type="button"
            class="messenger-float-button fixed bottom-[max(1.25rem,env(safe-area-inset-bottom))] right-4 z-[80] flex sm:hidden"
            aria-label="Inquire on Messenger"
            @click="openInquiry()"
        >
            <span class="messenger-float-ring"></span>

            <svg
                viewBox="0 0 24 24"
                class="h-7 w-7"
                aria-hidden="true"
                fill="currentColor"
            >
                <path
                    d="M12 2.25C6.49 2.25 2.25 6.27 2.25 11.7c0 2.84 1.17 5.28 3.08 6.95v3.1c0 .48.5.8.94.59l3.2-1.5c.81.22 1.66.34 2.53.34 5.51 0 9.75-4.02 9.75-9.45S17.51 2.25 12 2.25Zm1.03 12.5-2.48-2.64-4.84 2.64 5.32-5.65 2.48 2.64 4.79-2.64-5.27 5.65Z"
                />
            </svg>

            <span class="messenger-online-dot"></span>
        </button>
    </div>
</template>

<style scoped>
:global(html) {
    scroll-behavior: smooth;
}

.section-kicker,
.brand-kicker {
    font-size: 0.68rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.34em;
    color: rgb(113 113 122);
}

.catalog-hero {
    position: relative;
    background:
        radial-gradient(
            circle at top right,
            rgb(255 255 255 / 0.08),
            transparent 32%
        ),
        linear-gradient(180deg, rgb(13 13 15 / 0.96), rgb(5 5 5 / 0.95));
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
        rgb(255 255 255 / 0.45),
        transparent
    );
}

.catalog-loading-bar {
    height: 100%;
    width: 45%;
    border-radius: 9999px;
    background: linear-gradient(
        90deg,
        transparent,
        rgb(255 255 255 / 0.95),
        transparent
    );
    animation: catalogLoadingSlide 900ms ease-in-out infinite;
}

.catalog-spinner {
    height: 1rem;
    width: 1rem;
    flex-shrink: 0;
    border-radius: 9999px;
    border: 2px solid rgb(255 255 255 / 0.18);
    border-top-color: white;
    animation: catalogSpin 700ms linear infinite;
}

.catalog-loading-enter-active,
.catalog-loading-leave-active,
.catalog-mobile-loading-enter-active,
.catalog-mobile-loading-leave-active {
    transition:
        opacity 180ms ease,
        transform 180ms ease;
}

.catalog-loading-enter-from,
.catalog-loading-leave-to,
.catalog-mobile-loading-enter-from,
.catalog-mobile-loading-leave-to {
    opacity: 0;
}

.catalog-mobile-loading-enter-from,
.catalog-mobile-loading-leave-to {
    transform: translateY(-6px);
}

.catalog-card {
    position: relative;
    display: block;
    width: 100%;
    overflow: hidden;
    border-radius: 1.25rem;
    border: 1px solid rgb(255 255 255 / 0.055);
    background: linear-gradient(
        180deg,
        rgb(255 255 255 / 0.03),
        rgb(0 0 0 / 0.96)
    );
    color: inherit;
    box-shadow:
        0 26px 82px rgb(0 0 0 / 0.42),
        inset 0 1px 0 rgb(255 255 255 / 0.055);
    animation: fadeLift 700ms both;
    transition:
        transform 420ms cubic-bezier(0.2, 0.8, 0.2, 1),
        border-color 320ms ease,
        box-shadow 320ms ease,
        filter 320ms ease;
}

.catalog-card:hover {
    transform: translateY(-0.25rem);
    border-color: rgb(255 255 255 / 0.15);
    box-shadow:
        0 36px 110px rgb(0 0 0 / 0.65),
        inset 0 1px 0 rgb(255 255 255 / 0.08);
}

.catalog-card:focus-visible {
    outline: 2px solid rgb(255 255 255 / 0.72);
    outline-offset: 4px;
}

.catalog-card:active {
    transform: scale(0.985);
}

.image-shimmer {
    position: absolute;
    inset: 0;
    z-index: 0;
    background:
        linear-gradient(
            110deg,
            rgb(255 255 255 / 0.02) 8%,
            rgb(255 255 255 / 0.075) 18%,
            rgb(255 255 255 / 0.02) 33%
        ),
        #050505;
    background-size: 220% 100%;
    animation: imageShimmer 1.45s ease-in-out infinite;
}

.ask-pill {
    display: inline-flex;
    min-height: 2.65rem;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    border-radius: 9999px;
    border: 1px solid rgb(255 255 255 / 0.12);
    background: rgb(255 255 255 / 0.92);
    padding: 0.72rem 0.95rem;
    color: black;
    font-size: 0.68rem;
    font-weight: 950;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    box-shadow: 0 12px 30px rgb(255 255 255 / 0.1);
    transition:
        transform 260ms ease,
        background-color 260ms ease;
}

.catalog-card:hover .ask-pill {
    transform: translateX(3px);
    background: white;
}

.primary-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    border-radius: 0.9rem;
    background: white;
    color: black;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    box-shadow: 0 12px 35px rgb(255 255 255 / 0.08);
    transition:
        transform 260ms ease,
        background-color 260ms ease,
        box-shadow 260ms ease;
}

.primary-button:hover {
    transform: translateY(-1px);
    background: rgb(228 228 231);
    box-shadow: 0 18px 48px rgb(255 255 255 / 0.14);
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

.empty-state {
    border-radius: 1.35rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background:
        radial-gradient(
            circle at top,
            rgb(255 255 255 / 0.055),
            transparent 32%
        ),
        rgb(11 11 13);
    padding: 2.5rem;
    text-align: center;
    box-shadow: 0 25px 70px rgb(0 0 0 / 0.25);
}

.messenger-float-button {
    height: 3.65rem;
    width: 3.65rem;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    border: 1px solid rgb(255 255 255 / 0.2);
    background: linear-gradient(135deg, #00a6ff, #006aff);
    color: white;
    box-shadow:
        0 24px 70px rgb(0 0 0 / 0.68),
        0 0 0 1px rgb(255 255 255 / 0.14),
        0 0 46px rgb(0 132 255 / 0.32),
        inset 0 1px 0 rgb(255 255 255 / 0.22);
    isolation: isolate;
    transition:
        transform 240ms ease,
        box-shadow 240ms ease,
        filter 240ms ease;
    animation: messengerFloatAttention 5.2s ease-in-out infinite;
}

.messenger-float-button:active {
    transform: scale(0.94);
}

.messenger-online-dot {
    position: absolute;
    right: 0.32rem;
    bottom: 0.34rem;
    height: 0.78rem;
    width: 0.78rem;
    border-radius: 9999px;
    border: 2px solid white;
    background: #22c55e;
    box-shadow: 0 0 14px rgb(34 197 94 / 0.8);
}

.messenger-float-ring {
    position: absolute;
    inset: -0.45rem;
    z-index: -1;
    border-radius: 9999px;
    border: 1px solid rgb(0 132 255 / 0.45);
    opacity: 0;
    animation: messengerFloatRing 2.6s ease-out infinite;
}

@media (min-width: 640px) {
    .messenger-float-button {
        display: none !important;
    }
}

@keyframes catalogLoadingSlide {
    from {
        transform: translateX(-110%);
    }

    to {
        transform: translateX(250%);
    }
}

@keyframes catalogSpin {
    to {
        transform: rotate(360deg);
    }
}

@keyframes imageShimmer {
    from {
        background-position: 220% 0;
    }

    to {
        background-position: -220% 0;
    }
}

@keyframes messengerFloatRing {
    0% {
        opacity: 0.7;
        transform: scale(0.9);
    }

    70% {
        opacity: 0;
        transform: scale(1.22);
    }

    100% {
        opacity: 0;
        transform: scale(1.22);
    }
}

@keyframes messengerFloatAttention {
    0%,
    72%,
    100% {
        transform: translateY(0);
    }

    78% {
        transform: translateY(-5px);
    }

    84% {
        transform: translateY(0);
    }

    90% {
        transform: translateY(-2px);
    }
}

@keyframes fadeLift {
    from {
        opacity: 0;
        transform: translateY(14px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
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
