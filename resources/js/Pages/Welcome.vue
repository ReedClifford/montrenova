<script setup>
import MontreLogo from "@/Components/MontreLogo.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";

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
    featuredWatches: {
        type: [Array, Object],
        default: () => [],
    },
    bestSellerWatches: {
        type: [Array, Object],
        default: () => [],
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
    catalogPreviewWatches: {
        type: [Array, Object],
        default: () => [],
    },
    catalogCategories: {
        type: Array,
        default: () => [],
    },
});

const isPageOpening = ref(false);
const isMobileCtaOpen = ref(false);
const activeSection = ref("");
const activeBestSellerIndex = ref(0);

let sectionObserver = null;
let bestSellerCarouselTimer = null;

const toggleMobileCta = () => {
    isMobileCtaOpen.value = !isMobileCtaOpen.value;
};

const closeMobileCta = () => {
    isMobileCtaOpen.value = false;
};

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
    }, 1200);
};

const toCollectionArray = (collection) => {
    if (Array.isArray(collection)) {
        return collection;
    }

    if (Array.isArray(collection?.data)) {
        return collection.data;
    }

    return [];
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

const hasFeaturedFlag = (watch) => {
    const value = watch?.is_featured;

    return value === true || value === 1 || value === "1" || value === "true";
};

const isAvailableWatch = (watch) => {
    return String(watch?.status || "available").toLowerCase() === "available";
};

const isSoldWatch = (watch) => {
    return String(watch?.status || "").toLowerCase() === "sold";
};

const isHeroEligibleWatch = (watch) => {
    const status = String(watch?.status || "available").toLowerCase();

    return !["hidden", "draft"].includes(status);
};

const bestSellerCarouselWatches = computed(() => {
    const bestSellersFromProp = toCollectionArray(props.bestSellerWatches);
    const soldBestSellers = soldWatches.value;
    const featuredFromProp = toCollectionArray(props.featuredWatches);
    const featuredFromCollection = watches.value.filter(hasFeaturedFlag);

    const fallbackCandidates = [
        ...soldBestSellers,
        ...featuredFromProp,
        ...(featuredWatch.value ? [featuredWatch.value] : []),
        ...featuredFromCollection,
        ...watches.value,
    ];

    /*
     * Keep the backend best-seller order first, but still fill the hero
     * carousel up to 5 items when the prop only returns 4 or fewer.
     */
    const candidates = [
        ...bestSellersFromProp,
        ...(bestSellersFromProp.length < 5 ? fallbackCandidates : []),
    ].filter(Boolean);

    const uniqueWatches = new Map();

    candidates.forEach((watch, index) => {
        const key =
            watch?.id ||
            watch?.reference_number ||
            watch?.model_name ||
            `best-seller-${index}`;

        if (!uniqueWatches.has(key) && isHeroEligibleWatch(watch)) {
            uniqueWatches.set(key, watch);
        }
    });

    return [...uniqueWatches.values()].slice(0, 5);
});

const activeBestSellerWatch = computed(() => {
    return (
        bestSellerCarouselWatches.value[activeBestSellerIndex.value] ||
        bestSellerCarouselWatches.value[0] ||
        null
    );
});

const bestSellerPreviewWatches = computed(() => {
    return bestSellerCarouselWatches.value.slice(0, 5);
});

const hasBestSellerCarousel = computed(() => {
    return bestSellerCarouselWatches.value.length > 1;
});

const bestSellerRank = (watch = null, fallbackIndex = 0) => {
    return Number(watch?.best_seller_rank || fallbackIndex + 1);
};

const heroParallaxImageStyle = computed(() => {
    const direction = activeBestSellerIndex.value % 2 === 0 ? 1 : -1;

    return {
        transform: `scale(1.08) translate3d(${direction * 14}px, 0, 0)`,
    };
});

const heroParallaxBackdropStyle = computed(() => {
    const direction = activeBestSellerIndex.value % 2 === 0 ? -1 : 1;

    return {
        transform: `scale(1.18) translate3d(${direction * 18}px, 0, 0)`,
    };
});

const heroCarouselLabel = (watch = null) => {
    if (isSoldWatch(watch)) {
        return "Client Favorite";
    }

    if (hasFeaturedFlag(watch)) {
        return "Featured Favorite";
    }

    return "Best Seller";
};

const heroCtaLabel = (watch = null) => {
    return isAvailableWatch(watch) ? "View Watch" : "Source Similar";
};

const heroStatusLabel = (watch = null) => {
    return isAvailableWatch(watch) ? "Available now" : "Sold favorite";
};

const setActiveBestSeller = (index) => {
    const total = bestSellerCarouselWatches.value.length;

    if (!total) return;

    activeBestSellerIndex.value = ((index % total) + total) % total;
};

const nextBestSeller = () => {
    setActiveBestSeller(activeBestSellerIndex.value + 1);
};

const previousBestSeller = () => {
    setActiveBestSeller(activeBestSellerIndex.value - 1);
};

const stopBestSellerAutoPlay = () => {
    if (bestSellerCarouselTimer) {
        window.clearInterval(bestSellerCarouselTimer);
        bestSellerCarouselTimer = null;
    }
};

const startBestSellerAutoPlay = () => {
    stopBestSellerAutoPlay();

    if (!hasBestSellerCarousel.value) return;

    bestSellerCarouselTimer = window.setInterval(() => {
        nextBestSeller();
    }, 4200);
};

const resetBestSellerAutoPlay = () => {
    startBestSellerAutoPlay();
};

watch(bestSellerCarouselWatches, () => {
    if (activeBestSellerIndex.value >= bestSellerCarouselWatches.value.length) {
        activeBestSellerIndex.value = 0;
    }

    if (typeof window !== "undefined") {
        startBestSellerAutoPlay();
    }
});

const catalogPreviewWatches = computed(() => {
    return toCollectionArray(props.catalogPreviewWatches);
});

const hasCatalogPreview = computed(() => {
    return catalogPreviewWatches.value.length > 0;
});

const vlogs = [
    {
        id: 1,
        title: "GIFT KAY ERPAT",
        description:
            "A special Montre Nova handoff story made for a meaningful birthday gift.",
        url: "https://www.facebook.com/reel/2215560775962814",
        thumbnail: "/images/021.jpg",
        preview: "/videos/vlogs/vlog-021.mp4",
    },
    {
        id: 2,
        title: "LEFT INSPIRED",
        description:
            "A quick Montre Nova vlog feature with one of our recent watch stories.",
        url: "https://www.facebook.com/reel/959389140424011",
        thumbnail: "/images/020.jpg",
        preview: "/videos/vlogs/vlog-020.mp4",
    },
    {
        id: 3,
        title: "DOUBLE DEAL CLOSED",
        description:
            "Two watches, one smooth transaction, and another successful Montre Nova deal.",
        url: "https://www.facebook.com/reel/1422997365989569",
        thumbnail: "/images/011.jpg",
        preview: "/videos/vlogs/vlog-011.mp4",
    },
];

const hasVlogs = computed(() => vlogs.length > 0);

const playVlogPreview = (event) => {
    const video = event?.currentTarget?.querySelector("video");

    if (!video) return;

    video.currentTime = 0;

    const playPromise = video.play();

    if (playPromise?.catch) {
        playPromise.catch(() => {});
    }
};

const pauseVlogPreview = (event) => {
    const video = event?.currentTarget?.querySelector("video");

    if (!video) return;

    video.pause();
    video.currentTime = 0;
};

const navSections = computed(() => {
    const sections = [
        {
            id: "collection",
            label: "Available",
            shortLabel: "Available",
            href: "#collection",
        },
    ];

    if (hasCatalogPreview.value) {
        sections.push({
            id: "catalog",
            label: "Catalog",
            shortLabel: "Catalog",
            href: "#catalog",
        });
    }

    if (recentSoldWatches.value.length) {
        sections.push({
            id: "recently-sold",
            label: "Sold Gallery",
            shortLabel: "Sold",
            href: "#recently-sold",
        });
    }

    if (hasVlogs.value) {
        sections.push({
            id: "vlogs",
            label: "Vlogs",
            shortLabel: "Vlogs",
            href: "#vlogs",
        });
    }

    sections.push(
        {
            id: "process",
            label: "How to Order",
            shortLabel: "Order",
            href: "#process",
        },
        {
            id: "contact",
            label: "Contact",
            shortLabel: "Contact",
            href: "#contact",
        },
    );

    return sections;
});

const activateSection = (sectionId) => {
    activeSection.value = sectionId;
};

const isActiveSection = (sectionId) => {
    return activeSection.value === sectionId;
};

const sectionNavClass = (sectionId, variant = "desktop") => {
    const active = isActiveSection(sectionId);

    if (variant === "mobile") {
        return [
            "group relative inline-flex shrink-0 items-center justify-center rounded-full border px-4 py-2.5 transition duration-300",
            "focus:outline-none focus-visible:ring-2 focus-visible:ring-white/40",
            "active:scale-[0.97]",
            active
                ? "border-white bg-white text-black shadow-lg shadow-white/10"
                : "border-white/10 bg-white/[0.045] text-zinc-400 active:border-white/40 active:bg-white/[0.1] active:text-white",
        ].join(" ");
    }

    return [
        "group relative inline-flex items-center gap-2 rounded-full px-1 py-2 transition duration-300",
        "focus:outline-none focus-visible:ring-2 focus-visible:ring-white/40",
        "active:scale-[0.97]",
        active ? "text-white" : "text-zinc-400 hover:text-white",
    ].join(" ");
};

onMounted(() => {
    const sectionIds = [
        "collection",
        "catalog",
        "recently-sold",
        "vlogs",
        "process",
        "contact",
    ];
    const sections = sectionIds
        .map((sectionId) => document.getElementById(sectionId))
        .filter(Boolean);

    const currentHash = window.location.hash?.replace("#", "");

    if (currentHash && sectionIds.includes(currentHash)) {
        activeSection.value = currentHash;
    }

    sectionObserver = new IntersectionObserver(
        (entries) => {
            const visibleEntry = entries
                .filter((entry) => entry.isIntersecting)
                .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

            if (visibleEntry?.target?.id) {
                activeSection.value = visibleEntry.target.id;
            }
        },
        {
            root: null,
            rootMargin: "-30% 0px -52% 0px",
            threshold: [0.01, 0.15, 0.35, 0.6],
        },
    );

    sections.forEach((section) => sectionObserver.observe(section));

    startBestSellerAutoPlay();
});

onBeforeUnmount(() => {
    if (sectionObserver) {
        sectionObserver.disconnect();
    }

    stopBestSellerAutoPlay();
});

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
        return "Hi Montre Nova, I’m interested in your available watches. Can I see the latest stocks?";
    }

    return `Hi Montre Nova, I’m interested in ${watchFullName(watch)}${watchReference(watch)}. Is this still available?`;
};

const similarInquiryMessage = (watch = null) => {
    if (!watch) {
        return "Hi Montre Nova, I’m looking for a similar watch. Can you help me source one?";
    }

    return `Hi Montre Nova, I’m interested in  ${watchFullName(watch)}${watchReference(watch)}. Is this available for preorder?`;
};

const messengerUrl = (message) => {
    return `https://m.me/${messengerUsername}?text=${encodeURIComponent(message)}`;
};

const openInquiry = (watch = null) => {
    closeMobileCta();

    window.open(
        messengerUrl(inquiryMessage(watch)),
        "_blank",
        "noopener,noreferrer",
    );
};

const openSimilarInquiry = (watch = null) => {
    closeMobileCta();

    window.open(
        messengerUrl(similarInquiryMessage(watch)),
        "_blank",
        "noopener,noreferrer",
    );
};

const contactLinks = [
    {
        label: "Messenger",
        description: "Availability, reservations, and watch assistance.",
        href: "https://m.me/montrenova",
        icon: "MS",
    },
    {
        label: "Facebook",
        description: "Follow us for more updates.",
        href: "https://m.me/montrenova",
        icon: "FB",
    },
    {
        label: "TikTok",
        description: "Watch updates, short clips, and new drops.",
        href: "https://www.tiktok.com/@montre_nova",
        icon: "TT",
    },
    {
        label: "Instagram",
        description: "Latest posts, stories, and curated watches.",
        href: "https://www.instagram.com/montrenova",
        icon: "IG",
    },
];

const orderSteps = [
    {
        number: "01",
        title: "Inquire",
        description:
            "Choose your preferred watch and inquire with us to confirm availability, final photos, and complete details.",
    },
    {
        number: "02",
        title: "Payment",
        description:
            "Pay through your preferred option. We accept cash, Maribank, GoTyme, QR payments, and selected trade-ins subject to evaluation.",
    },
    {
        number: "03",
        title: "Delivery",
        description:
            "Metro Manila orders may be delivered via Lalamove, while nationwide orders are shipped through LBC after payment confirmation. Scheduled meetups around Metro Manila are also available every Friday, Saturday, and Sunday.",
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
        watch?.hero_image_url ||
            watch?.wristshot_image_url ||
            watch?.wrist_shot_url ||
            watch?.wristshot_url ||
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
    return hasFeaturedFlag(watch);
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
        class="min-h-screen overflow-x-hidden bg-[#030303] pb-8 text-white selection:bg-white selection:text-black"
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
            class="fixed inset-x-0 top-0 z-[90] border-b border-white/10 bg-black/80 shadow-xl shadow-black/30 backdrop-blur-2xl"
        >
            <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between gap-4">
                    <a
                        href="/"
                        class="flex min-w-0 items-center rounded-xl transition duration-300 hover:opacity-80 active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-white/40"
                    >
                        <MontreLogo />
                    </a>

                    <nav
                        class="hidden items-center gap-7 text-[13px] font-semibold lg:flex"
                    >
                        <a
                            v-for="item in navSections"
                            :key="item.id"
                            :href="item.href"
                            :class="sectionNavClass(item.id, 'desktop')"
                            :aria-current="
                                isActiveSection(item.id) ? 'page' : undefined
                            "
                            @click="activateSection(item.id)"
                        >
                            <span>{{ item.label }}</span>

                            <span
                                class="pointer-events-none absolute -bottom-1 left-0 h-[2px] rounded-full bg-white transition-all duration-300"
                                :class="
                                    isActiveSection(item.id)
                                        ? 'w-full opacity-100'
                                        : 'w-0 opacity-0 group-hover:w-full group-hover:opacity-50'
                                "
                            ></span>
                        </a>
                    </nav>

                    <div class="hidden items-center gap-3 lg:flex">
                        <Link
                            href="/warranty-check"
                            class="rounded-xl border border-white/10 bg-white/[0.04] px-5 py-2.5 text-xs font-black uppercase tracking-[0.16em] text-zinc-300 transition duration-300 hover:border-white/30 hover:bg-white/[0.075] hover:text-white active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-white/40"
                        >
                            Warranty Check
                        </Link>

                        <button
                            type="button"
                            class="rounded-xl bg-white px-5 py-2.5 text-xs font-black uppercase tracking-[0.16em] text-black shadow-lg shadow-white/10 transition duration-300 hover:bg-zinc-200 active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-white/40"
                            @click="openInquiry()"
                        >
                            Message Us
                        </button>
                    </div>
                </div>

                <nav
                    class="mt-3 flex gap-2 overflow-x-auto pb-1 text-xs font-bold md:hidden [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    <a
                        v-for="item in navSections"
                        :key="item.id"
                        :href="item.href"
                        :class="sectionNavClass(item.id, 'mobile')"
                        :aria-current="
                            isActiveSection(item.id) ? 'page' : undefined
                        "
                        @click="activateSection(item.id)"
                    >
                        <span>{{ item.shortLabel }}</span>
                    </a>

                    <Link
                        href="/warranty-check"
                        class="inline-flex shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/[0.045] px-4 py-2.5 text-zinc-400 transition duration-300 active:scale-[0.97] active:border-white/40 active:bg-white/[0.1] active:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/40"
                    >
                        Warranty Check
                    </Link>
                </nav>
            </div>
        </header>

        <main
            class="relative z-10 space-y-20 pt-[7.75rem] pb-20 sm:space-y-24 sm:pb-24 md:pt-[5.25rem] lg:space-y-32 lg:pb-28 xl:space-y-36"
        >
            <!-- HERO -->
            <section
                class="hero-full-carousel-section animated-in"
                @mouseenter="stopBestSellerAutoPlay"
                @mouseleave="startBestSellerAutoPlay"
                @focusin="stopBestSellerAutoPlay"
                @focusout="startBestSellerAutoPlay"
            >
                <!-- Full Background Carousel -->
                <div class="hero-full-bg">
                    <template v-if="activeBestSellerWatch">
                        <Transition name="hero-slide" mode="out-in">
                            <div
                                :key="
                                    activeBestSellerWatch.id ||
                                    activeBestSellerWatch.reference_number ||
                                    activeBestSellerIndex
                                "
                                class="hero-full-slide"
                            >
                                <img
                                    v-if="watchImage(activeBestSellerWatch)"
                                    :src="watchImage(activeBestSellerWatch)"
                                    :alt="`${activeBestSellerWatch.brand} ${activeBestSellerWatch.model_name}`"
                                    class="hero-full-backdrop-image"
                                    :style="heroParallaxBackdropStyle"
                                    @error="handleImageError"
                                />

                                <img
                                    v-if="watchImage(activeBestSellerWatch)"
                                    :src="watchImage(activeBestSellerWatch)"
                                    :alt="`${activeBestSellerWatch.brand} ${activeBestSellerWatch.model_name}`"
                                    class="hero-full-main-image"
                                    :style="heroParallaxImageStyle"
                                    @error="handleImageError"
                                />

                                <div
                                    v-else
                                    class="absolute inset-0 flex h-full items-center justify-center bg-[#050505]"
                                >
                                    <div class="placeholder-logo">
                                        <span>MN</span>
                                    </div>
                                </div>
                            </div>
                        </Transition>
                    </template>

                    <div
                        v-else
                        class="absolute inset-0 flex h-full items-center justify-center bg-[#050505]"
                    >
                        <div class="placeholder-logo">
                            <span>MN</span>
                        </div>
                    </div>

                    <!-- Overlays keep text readable but still emphasize photo -->
                    <div class="hero-full-left-gradient"></div>
                    <div class="hero-full-vignette"></div>
                    <div class="hero-full-bottom-gradient"></div>
                    <div class="hero-full-top-glass"></div>
                </div>

                <!-- Best Seller Badge -->
                <div v-if="activeBestSellerWatch" class="hero-full-rank-badge">
                    #{{
                        bestSellerRank(
                            activeBestSellerWatch,
                            activeBestSellerIndex,
                        )
                    }}
                    Best Seller
                </div>

                <!-- Carousel Arrows -->
                <template v-if="hasBestSellerCarousel">
                    <button
                        type="button"
                        class="hero-full-arrow hero-full-arrow-left"
                        aria-label="Previous best seller watch"
                        @click.stop="
                            previousBestSeller();
                            resetBestSellerAutoPlay();
                        "
                    >
                        <svg
                            viewBox="0 0 24 24"
                            class="h-6 w-6"
                            aria-hidden="true"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.4"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M15 18l-6-6 6-6" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        class="hero-full-arrow hero-full-arrow-right"
                        aria-label="Next best seller watch"
                        @click.stop="
                            nextBestSeller();
                            resetBestSellerAutoPlay();
                        "
                    >
                        <svg
                            viewBox="0 0 24 24"
                            class="h-6 w-6"
                            aria-hidden="true"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.4"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M9 6l6 6-6 6" />
                        </svg>
                    </button>
                </template>

                <!-- Overlay Text Content -->
                <div class="hero-full-content">
                    <div class="hero-full-copy">
                        <div class="premium-eyebrow hero-full-eyebrow mb-6">
                            <span class="pulse-dot"></span>
                            Curated brand-new & pre-owned timepieces
                        </div>

                        <h1 class="hero-full-title">
                            Your next signature watch, curated with confidence.
                        </h1>

                        <p class="hero-full-description">
                            Browse available watches with actual HD photos,
                            clear pricing, and trusted after-sales support.
                        </p>

                        <div class="hero-full-actions">
                            <a
                                href="#collection"
                                class="primary-button hero-full-cta group"
                                @click="activateSection('collection')"
                            >
                                <span>View Available Watches</span>

                                <span
                                    class="transition group-hover:translate-x-1"
                                >
                                    →
                                </span>
                            </a>
                        </div>

                        <div class="hero-full-stats">
                            <div class="stat-card hero-full-stat-card">
                                <p class="stat-value">1Y</p>
                                <p class="stat-label">Warranty</p>
                            </div>

                            <div class="stat-card hero-full-stat-card">
                                <p class="stat-value">{{ soldTotal }}+</p>
                                <p class="stat-label">Sold Deals</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Thumbnail Dock -->
                <div v-if="hasBestSellerCarousel" class="hero-full-dock">
                    <button
                        v-for="(watch, index) in bestSellerPreviewWatches"
                        :key="watch.id || watch.reference_number || index"
                        type="button"
                        class="hero-full-thumb-button group/thumb"
                        :class="
                            activeBestSellerIndex === index ? 'is-active' : ''
                        "
                        :aria-label="`Show ${watchFullName(watch)}${watchReference(watch)} in best seller carousel`"
                        @click="
                            setActiveBestSeller(index);
                            resetBestSellerAutoPlay();
                        "
                    >
                        <span class="hero-full-thumb-media">
                            <span class="hero-full-thumb-rank">
                                #{{ bestSellerRank(watch, index) }}
                            </span>

                            <img
                                v-if="watchImage(watch)"
                                :src="watchImage(watch)"
                                :alt="`${watch.brand} ${watch.model_name}`"
                                class="h-full w-full object-cover transition duration-500 group-hover/thumb:scale-110"
                                loading="lazy"
                                @error="handleImageError"
                            />

                            <span
                                v-else
                                class="flex h-full w-full items-center justify-center text-xs font-black text-zinc-500"
                            >
                                MN
                            </span>
                        </span>
                    </button>
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

                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center"
                    >
                        <p
                            v-if="watches.length"
                            class="text-xs font-black uppercase tracking-[0.2em] text-zinc-600 md:hidden"
                        >
                            Swipe collection →
                        </p>
                    </div>
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
                                    class="absolute inset-0 bg-gradient-to-t from-black/86 via-black/24 to-transparent"
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
                        @click="openInquiry()"
                    >
                        Inquire
                    </button>
                </div>
            </section>

            <!-- CATALOG PREVIEW -->
            <section
                v-if="catalogPreviewWatches.length"
                id="catalog"
                class="scroll-mt-28 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
            >
                <div
                    class="animated-in mb-8 flex flex-col justify-between gap-5 sm:mb-10 md:flex-row md:items-end"
                >
                    <div>
                        <p class="section-kicker">Catalog</p>

                        <h2 class="section-title">Browse by Category</h2>

                        <p
                            class="mt-4 max-w-2xl text-sm leading-7 text-zinc-400"
                        >
                            A quick preview of our watch catalog. Each category
                            shows one representative piece. Open the full
                            catalog to browse every model with category filters.
                        </p>
                    </div>

                    <Link
                        :href="route('public.catalog')"
                        class="primary-button w-full justify-between px-6 py-3 text-sm sm:w-auto sm:justify-center"
                        @click="preparePageOpen"
                    >
                        <span>Full Catalog</span>
                        <span aria-hidden="true">→</span>
                    </Link>
                </div>

                <div
                    class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 xl:gap-5"
                >
                    <button
                        v-for="(watch, index) in catalogPreviewWatches"
                        :key="watch.id"
                        type="button"
                        class="catalog-preview-card group text-left"
                        :style="{ animationDelay: `${index * 65}ms` }"
                        :aria-label="`Ask availability for ${watchFullName(watch)}${watchReference(watch)}`"
                        @click="openInquiry(watch)"
                    >
                        <div
                            class="relative min-h-[410px] overflow-hidden bg-[#050505]"
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

                                    <p class="mt-4 brand-kicker">Montre Nova</p>
                                </div>
                            </div>

                            <div
                                class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/28 to-transparent"
                            ></div>

                            <div
                                class="absolute inset-0 bg-[radial-gradient(circle_at_center,transparent_0%,rgba(0,0,0,0.04)_52%,rgba(0,0,0,0.38)_100%)]"
                            ></div>

                            <div
                                class="absolute left-4 top-4 z-20 flex max-w-[92%] flex-wrap gap-1.5 sm:left-5 sm:top-5"
                            >
                                <span
                                    class="rounded-md border border-white/10 bg-black/45 px-3 py-1.5 text-[9px] font-black uppercase tracking-[0.14em] text-zinc-200 shadow-lg shadow-black/40 backdrop-blur"
                                >
                                    {{ watch.category || "Catalog" }}
                                </span>

                                <span
                                    v-if="isBelowSrp(watch)"
                                    class="rounded-md border border-violet-400/20 bg-violet-400/10 px-3 py-1.5 text-[9px] font-black uppercase tracking-[0.14em] text-violet-200 shadow-lg shadow-black/40 backdrop-blur"
                                >
                                    Below SRP
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
                                    <span
                                        v-if="watch.condition"
                                        class="detail-chip"
                                    >
                                        {{ watch.condition }}
                                    </span>

                                    <span class="detail-chip">
                                        Ask availability
                                    </span>
                                </div>

                                <div
                                    class="mt-6 flex items-end justify-between gap-4 border-t border-white/10 pt-4"
                                >
                                    <div></div>

                                    <span class="view-detail-pill">
                                        Inquire
                                        <span aria-hidden="true">→</span>
                                    </span>
                                </div>
                            </div>
                        </div>
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
                        <Link
                            href="/sold-watches"
                            class="secondary-button px-5 py-3 text-sm"
                        >
                            Sold Gallery
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
                                class="absolute inset-0 bg-gradient-to-t from-black/86 via-black/24 to-transparent"
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
                                            Similar model
                                        </p>

                                        <p
                                            class="mt-1 truncate text-sm text-zinc-300"
                                        >
                                            Source request available.
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        class="secondary-button shrink-0 px-4 py-2.5 text-xs"
                                        @click="openSimilarInquiry(watch)"
                                    >
                                        Inquire
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                </TransitionGroup>
            </section>

            <!-- VLOGS -->
            <section
                v-if="hasVlogs"
                id="vlogs"
                class="scroll-mt-28 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
            >
                <div
                    class="animated-in mb-8 flex flex-col justify-between gap-5 sm:mb-10 md:flex-row md:items-end"
                >
                    <div>
                        <p class="section-kicker">Montre Nova Vlogs</p>

                        <h2 class="section-title">Watch Our Stories</h2>

                        <p
                            class="mt-4 max-w-2xl text-sm leading-7 text-zinc-400"
                        >
                            Short stories from our watch handoffs, client
                            features, and curated Montre Nova deals. Hover on
                            desktop to preview, then open the full reel on
                            Facebook.
                        </p>
                    </div>

                    <a
                        href="https://www.facebook.com/montrenova"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="secondary-button w-full px-5 py-3 text-sm sm:w-auto"
                    >
                        Follow on Facebook
                    </a>
                </div>

                <p
                    v-if="vlogs.length > 1"
                    class="mb-4 text-xs font-bold uppercase tracking-[0.2em] text-zinc-600 md:hidden"
                >
                    Swipe vlogs →
                </p>

                <div
                    class="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-4 pr-4 overscroll-x-contain md:grid md:snap-none md:grid-cols-2 md:overflow-visible md:pb-0 md:pr-0 lg:grid-cols-3 xl:gap-5 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    <a
                        v-for="(vlog, index) in vlogs"
                        :key="vlog.id"
                        :href="vlog.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="vlog-card group"
                        :style="{ animationDelay: `${index * 65}ms` }"
                        :aria-label="`Watch ${vlog.title} on Facebook`"
                        @mouseenter="playVlogPreview"
                        @mouseleave="pauseVlogPreview"
                        @focusin="playVlogPreview"
                        @focusout="pauseVlogPreview"
                    >
                        <div
                            class="relative overflow-hidden rounded-[1.35rem] border border-white/10 bg-[#050505] shadow-2xl shadow-black/45 ring-1 ring-white/[0.035] transition duration-300 group-hover:border-white/25 group-hover:bg-white/[0.04] group-hover:shadow-black/70"
                        >
                            <div
                                class="relative aspect-[9/16] min-h-[460px] overflow-hidden bg-black sm:min-h-[560px]"
                            >
                                <img
                                    v-if="vlog.thumbnail"
                                    :src="normalizeImageUrl(vlog.thumbnail)"
                                    :alt="`${vlog.title} thumbnail`"
                                    class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-[1.045] group-hover:brightness-75"
                                    loading="lazy"
                                    @error="handleImageError"
                                />

                                <video
                                    v-if="vlog.preview"
                                    :src="normalizeImageUrl(vlog.preview)"
                                    :poster="normalizeImageUrl(vlog.thumbnail)"
                                    class="absolute inset-0 h-full w-full object-cover opacity-0 transition duration-500 group-hover:scale-[1.045] group-hover:opacity-100 group-focus-visible:opacity-100"
                                    muted
                                    loop
                                    playsinline
                                    preload="metadata"
                                    @error="
                                        $event.target.style.display = 'none'
                                    "
                                ></video>

                                <div
                                    v-if="!vlog.thumbnail && !vlog.preview"
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
                                    class="absolute inset-0 bg-gradient-to-t from-black via-black/42 to-black/20 transition duration-500 group-hover:from-black/96 group-hover:via-black/28 group-hover:to-black/10"
                                ></div>

                                <div
                                    class="absolute inset-0 bg-[radial-gradient(circle_at_center,transparent_0%,rgba(0,0,0,0.08)_48%,rgba(0,0,0,0.62)_100%)]"
                                ></div>

                                <div class="shine-line"></div>

                                <div
                                    class="absolute left-4 top-4 z-20 flex max-w-[92%] flex-wrap gap-1.5 sm:left-5 sm:top-5"
                                >
                                    <span
                                        class="rounded-md border border-white/15 bg-black/60 px-3 py-1.5 text-[9px] font-black uppercase tracking-[0.16em] text-zinc-100 shadow-lg shadow-black/40 backdrop-blur"
                                    >
                                        Facebook Reel
                                    </span>

                                    <span
                                        v-if="vlog.preview"
                                        class="rounded-md border border-emerald-300/20 bg-emerald-300/10 px-3 py-1.5 text-[9px] font-black uppercase tracking-[0.16em] text-emerald-100 shadow-lg shadow-black/40 backdrop-blur"
                                    >
                                        Hover Preview
                                    </span>
                                </div>

                                <div
                                    class="absolute inset-0 z-20 flex items-center justify-center"
                                >
                                    <div
                                        class="flex h-16 w-16 items-center justify-center rounded-full border border-white/25 bg-white/95 text-black shadow-2xl shadow-black/50 backdrop-blur transition duration-300 group-hover:scale-105 group-hover:bg-white sm:h-20 sm:w-20"
                                    >
                                        <svg
                                            viewBox="0 0 24 24"
                                            class="ml-1 h-7 w-7 sm:h-8 sm:w-8"
                                            aria-hidden="true"
                                            fill="currentColor"
                                        >
                                            <path
                                                d="M8 5.14v13.72c0 .76.84 1.22 1.48.8l10.3-6.86a.96.96 0 0 0 0-1.6L9.48 4.34A.96.96 0 0 0 8 5.14Z"
                                            />
                                        </svg>
                                    </div>
                                </div>

                                <div
                                    class="absolute inset-x-0 bottom-0 z-30 p-4 sm:p-5"
                                >
                                    <div
                                        class="rounded-2xl border border-white/10 bg-black/72 p-4 shadow-2xl shadow-black/45 backdrop-blur-xl transition duration-300 group-hover:border-white/20 group-hover:bg-black/78 sm:p-5"
                                    >
                                        <p
                                            class="text-[10px] font-black uppercase tracking-[0.28em] text-zinc-300"
                                        >
                                            Montre Nova Vlog
                                        </p>

                                        <h3
                                            class="mt-2 line-clamp-2 text-2xl font-black leading-tight tracking-[-0.04em] text-white drop-shadow-[0_2px_12px_rgba(0,0,0,0.9)] sm:text-3xl"
                                        >
                                            {{ vlog.title }}
                                        </h3>

                                        <p
                                            class="mt-3 line-clamp-2 text-sm leading-6 text-zinc-200/90"
                                        >
                                            {{ vlog.description }}
                                        </p>

                                        <div
                                            class="mt-5 flex items-center justify-between gap-4 border-t border-white/10 pt-4"
                                        >
                                            <p
                                                class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-400"
                                            >
                                                Opens on Facebook
                                            </p>

                                            <span class="view-detail-pill">
                                                Watch
                                                <span aria-hidden="true"
                                                    >→</span
                                                >
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
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
                            <p class="section-kicker">Contact</p>

                            <h2
                                class="mt-4 max-w-2xl text-4xl font-black tracking-[-0.06em] text-white sm:text-6xl"
                            >
                                Ready to find your next timepiece?
                            </h2>

                            <p
                                class="mt-5 max-w-2xl text-sm leading-7 text-zinc-400 sm:text-base"
                            >
                                Inquire with Montre Nova for available stocks,
                                reservations, curated recommendations, and
                                assistance based on your target model and
                                budget.
                            </p>

                            <div
                                class="mt-8 rounded-2xl border border-white/10 bg-white/[0.035] p-5"
                            >
                                <p class="brand-kicker">Faster Assistance</p>

                                <p class="mt-2 text-sm leading-7 text-zinc-400">
                                    Include the model, reference number, budget,
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

        <!-- MOBILE MESSENGER FLOATING BUTTON -->
        <button
            type="button"
            class="messenger-float-button fixed bottom-[max(1.25rem,env(safe-area-inset-bottom))] right-4 z-[80] lg:hidden"
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

:global(#collection),
:global(#vlogs),
:global(#catalog),
:global(#recently-sold),
:global(#process),
:global(#contact) {
    scroll-margin-top: 9rem;
}

@media (min-width: 768px) {
    :global(#collection),
    :global(#vlogs),
    :global(#catalog),
    :global(#recently-sold),
    :global(#process),
    :global(#contact) {
        scroll-margin-top: 6.25rem;
    }
}

:global(#collection:target),
:global(#vlogs:target),
:global(#catalog:target),
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

.featured-carousel-stage {
    border-color: rgb(255 255 255 / 0.055);
    box-shadow: inset 0 1px 0 rgb(255 255 255 / 0.06);
}

.featured-arrow {
    position: absolute;
    top: 50%;
    z-index: 50;
    display: none;
    height: 2.75rem;
    width: 2.75rem;
    transform: translateY(-50%);
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    border: 1px solid rgb(255 255 255 / 0.12);
    background: rgb(0 0 0 / 0.42);
    color: white;
    font-size: 2rem;
    font-weight: 300;
    line-height: 1;
    box-shadow: 0 16px 36px rgb(0 0 0 / 0.34);
    backdrop-filter: blur(18px);
    transition:
        transform 260ms ease,
        border-color 260ms ease,
        background-color 260ms ease,
        color 260ms ease;
}

.featured-arrow:hover {
    transform: translateY(-50%) scale(1.04);
    border-color: rgb(255 255 255 / 0.24);
    background: white;
    color: black;
}

.featured-arrow:active {
    transform: translateY(-50%) scale(0.96);
}

@media (min-width: 768px) {
    .featured-arrow {
        display: inline-flex;
    }
}

.featured-dock {
    box-shadow:
        inset 0 1px 0 rgb(255 255 255 / 0.045),
        0 20px 48px rgb(0 0 0 / 0.24);
}

.featured-thumb-button {
    min-width: 0;
    border-radius: 0.9rem;
    border: 1px solid transparent;
    padding: 0.35rem;
    transition:
        transform 260ms ease,
        border-color 260ms ease,
        background-color 260ms ease,
        opacity 260ms ease;
}

.featured-thumb-button:hover {
    transform: translateY(-1px);
    border-color: rgb(255 255 255 / 0.14);
    background: rgb(255 255 255 / 0.045);
}

.featured-thumb-button.is-active {
    border-color: rgb(255 255 255 / 0.42);
    background: rgb(255 255 255 / 0.085);
}

.image-only-dock {
    overflow: hidden;
}

.primary-button,
.secondary-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.9rem;
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
    border: 1px solid rgb(255 255 255 / 0.08);
    background: rgb(255 255 255 / 0.04);
    color: white;
    backdrop-filter: blur(18px);
}

.secondary-button:hover {
    transform: translateY(-1px);
    border-color: rgb(255 255 255 / 0.18);
    background: rgb(255 255 255 / 0.07);
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

.feature-cta-dark {
    border-color: rgb(255 255 255 / 0.1);
    background: rgb(255 255 255 / 0.045);
    color: white;
    box-shadow: 0 18px 46px rgb(0 0 0 / 0.28);
}

.feature-cta-dark:hover {
    background: rgb(255 255 255 / 0.08);
    box-shadow: 0 24px 62px rgb(0 0 0 / 0.38);
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
    border-radius: 0.9rem;
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

.vlog-card {
    display: block;
    min-width: 82vw;
    max-width: 82vw;
    scroll-snap-align: start;
    color: inherit;
    text-decoration: none;
    animation: fadeLift 700ms both;
    transition:
        transform 420ms cubic-bezier(0.2, 0.8, 0.2, 1),
        filter 320ms ease;
}

.vlog-card:focus-visible {
    outline: 2px solid rgb(255 255 255 / 0.72);
    outline-offset: 4px;
}

.vlog-card:hover {
    filter: brightness(1.04);
}

.vlog-card video {
    pointer-events: none;
}

@media (min-width: 640px) {
    .vlog-card {
        min-width: 390px;
        max-width: 390px;
    }
}

@media (min-width: 768px) {
    .vlog-card {
        min-width: 0;
        max-width: none;
    }

    .vlog-card:hover {
        transform: translateY(-0.25rem);
    }
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

.watch-card:active,
.sold-card:active,
.feature-cta:active {
    transform: scale(0.985);
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
    border-radius: 0.9rem;
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

.catalog-preview-card {
    position: relative;
    display: block;
    overflow: hidden;
    width: 100%;
    border-radius: 1.25rem;
    border: 1px solid rgb(255 255 255 / 0.045);
    background: linear-gradient(
        180deg,
        rgb(255 255 255 / 0.025),
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
        box-shadow 320ms ease;
}

.catalog-preview-card:hover {
    transform: translateY(-0.25rem);
    border-color: rgb(255 255 255 / 0.13);
    box-shadow:
        0 36px 110px rgb(0 0 0 / 0.65),
        inset 0 1px 0 rgb(255 255 255 / 0.08);
}

.catalog-preview-card:active {
    transform: scale(0.985);
}

.catalog-preview-card:focus-visible {
    outline: 2px solid rgb(255 255 255 / 0.72);
    outline-offset: 4px;
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

.empty-state {
    border-radius: 1.25rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: rgb(11 11 13);
    padding: 2.5rem;
    text-align: center;
    box-shadow: 0 25px 70px rgb(0 0 0 / 0.25);
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

.mobile-cta-panel {
    overflow: hidden;
    border-radius: 1.35rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background:
        radial-gradient(
            circle at top right,
            rgb(255 255 255 / 0.105),
            transparent 34%
        ),
        rgb(5 5 5 / 0.92);
    padding: 0.85rem;
    box-shadow:
        0 26px 80px rgb(0 0 0 / 0.68),
        inset 0 1px 0 rgb(255 255 255 / 0.07);
    backdrop-filter: blur(22px);
}

.mobile-cta-kicker {
    font-size: 0.62rem;
    font-weight: 950;
    text-transform: uppercase;
    letter-spacing: 0.24em;
    color: rgb(113 113 122);
}

.mobile-cta-action {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-radius: 1rem;
    border-width: 1px;
    padding: 0.9rem 1rem;
    font-size: 0.86rem;
    font-weight: 950;
    text-transform: uppercase;
    letter-spacing: 0.11em;
    transition:
        transform 220ms ease,
        border-color 220ms ease,
        background-color 220ms ease,
        color 220ms ease;
}

.mobile-cta-action:active {
    transform: scale(0.98);
}

.mobile-cta-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    border-radius: 9999px;
    border: 1px solid rgb(255 255 255 / 0.16);
    background: white;
    padding: 0.35rem 0.85rem 0.35rem 0.35rem;
    color: black;
    box-shadow:
        0 22px 65px rgb(0 0 0 / 0.58),
        0 0 0 1px rgb(255 255 255 / 0.08);
    transition:
        transform 260ms ease,
        box-shadow 260ms ease;
}

.mobile-cta-toggle:active {
    transform: scale(0.96);
}

.mobile-cta-toggle:focus-visible {
    outline: 2px solid rgb(255 255 255 / 0.55);
    outline-offset: 4px;
}

.mobile-cta-panel-enter-active,
.mobile-cta-panel-leave-active,
.mobile-cta-backdrop-enter-active,
.mobile-cta-backdrop-leave-active {
    transition:
        opacity 220ms ease,
        transform 220ms ease;
}

.mobile-cta-panel-enter-from,
.mobile-cta-panel-leave-to {
    opacity: 0;
    transform: translateY(10px) scale(0.96);
}

.mobile-cta-backdrop-enter-from,
.mobile-cta-backdrop-leave-to {
    opacity: 0;
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

.r-float-button {
    display: inline-flex;
    align-items: center;
    gap: 0.65rem;
    min-height: 3.6rem;
    border-radius: 9999px;
    border: 1px solid rgb(255 255 255 / 0.2);
    background: white;
    padding: 0.45rem 1rem 0.45rem 0.45rem;
    color: black;
    box-shadow:
        0 22px 70px rgb(0 0 0 / 0.7),
        0 0 0 1px rgb(255 255 255 / 0.12),
        0 0 42px rgb(255 255 255 / 0.18);
    transition:
        transform 260ms ease,
        box-shadow 260ms ease,
        filter 260ms ease;
    animation: messengerFloatNudge 4.5s ease-in-out infinite;
}

.messenger-float-button {
    display: flex;
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

.messenger-float-button:focus-visible {
    outline: 2px solid rgb(255 255 255 / 0.7);
    outline-offset: 4px;
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

@keyframes sectionFocus {
    0% {
        filter: brightness(1);
        transform: translateY(0);
    }

    35% {
        filter: brightness(1.16);
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
    :global(html) {
        scroll-behavior: auto;
    }

    *,
    *::before,
    *::after {
        animation-duration: 1ms !important;
        animation-iteration-count: 1 !important;
        scroll-behavior: auto !important;
        transition-duration: 1ms !important;
    }
}

@media (min-width: 1024px) {
    .messenger-float-button {
        display: none !important;
    }
}

.best-seller-lux-card {
    background:
        radial-gradient(
            circle at 72% 18%,
            rgb(253 230 138 / 0.1),
            transparent 32%
        ),
        linear-gradient(180deg, rgb(12 12 13 / 0.98), rgb(5 5 5 / 0.97));
}

.best-seller-stage {
    isolation: isolate;
}

.best-seller-backdrop {
    filter: blur(26px) saturate(1.45) brightness(0.88);
}

.best-seller-main-image {
    will-change: transform;
    animation: heroParallaxDrift 5.2s ease-in-out infinite alternate;
}

.hero-slide-enter-active,
.hero-slide-leave-active {
    transition:
        opacity 420ms ease,
        transform 520ms cubic-bezier(0.2, 0.8, 0.2, 1),
        filter 520ms ease;
}

.hero-slide-enter-from {
    opacity: 0;
    transform: scale(1.035) translateX(1.5rem);
    filter: blur(10px);
}

.hero-slide-leave-to {
    opacity: 0;
    transform: scale(0.985) translateX(-1.5rem);
    filter: blur(8px);
}

@keyframes heroParallaxDrift {
    from {
        object-position: 48% 50%;
    }

    to {
        object-position: 54% 50%;
    }
}

/* =========================================================
   FINAL UI/UX POLISH OVERRIDES
========================================================= */

main {
    overflow: hidden;
}

@media (min-width: 1024px) {
    main > section:first-of-type {
        min-height: calc(100vh - 5.25rem);
        align-items: center;
    }

    .hero-copy,
    .hero-feature-wrap {
        min-height: clamp(560px, 68vh, 720px);
    }
}

.hero-copy h1 {
    text-wrap: balance;
}

@media (max-width: 639px) {
    .hero-copy h1 {
        font-size: clamp(2.75rem, 16vw, 4.1rem);
        line-height: 0.88;
        letter-spacing: -0.085em;
    }

    .hero-copy p {
        margin-top: 1.25rem;
        font-size: 0.92rem;
        line-height: 1.85;
    }

    .premium-eyebrow {
        max-width: 100%;
        padding: 0.48rem 0.75rem;
        font-size: 0.52rem;
        letter-spacing: 0.2em;
    }
}

.primary-button {
    border: 1px solid rgb(255 255 255 / 0.16);
}

.primary-button span:first-child {
    white-space: nowrap;
}

@media (max-width: 639px) {
    .primary-button {
        min-height: 3.45rem;
        border-radius: 1rem;
        padding-inline: 1.15rem !important;
        font-size: 0.72rem;
        letter-spacing: 0.11em;
    }
}

.stat-card {
    display: flex;
    min-height: 5.25rem;
    flex-direction: column;
    justify-content: center;
}

.stat-value {
    font-size: clamp(1.55rem, 5vw, 2rem);
}

.stat-label {
    color: rgb(161 161 170 / 0.72);
}

.best-seller-lux-card {
    border-radius: 1.85rem;
}

.best-seller-stage {
    border-radius: 1.45rem;
}

.best-seller-main-image {
    object-position: center;
}

@media (max-width: 767px) {
    .best-seller-lux-card {
        padding: 0.65rem;
        border-radius: 1.35rem;
    }

    .best-seller-stage {
        min-height: 430px !important;
        border-radius: 1.05rem;
    }

    .featured-arrow {
        display: inline-flex;
        height: 2.45rem;
        width: 2.45rem;
        background: rgb(0 0 0 / 0.35);
    }

    .featured-arrow.left-4 {
        left: 0.75rem;
    }

    .featured-arrow.right-4 {
        right: 0.75rem;
    }

    .image-only-dock {
        gap: 0.4rem;
        overflow-x: auto;
        grid-template-columns: repeat(5, minmax(58px, 1fr));
        padding: 0.45rem;
        border-radius: 0.95rem;
    }

    .featured-thumb-button {
        padding: 0.22rem;
        border-radius: 0.75rem;
    }

    .featured-thumb-button span.relative {
        border-radius: 0.62rem;
    }
}

.section-kicker {
    color: rgb(161 161 170 / 0.7);
}

.section-title {
    text-wrap: balance;
}

@media (max-width: 639px) {
    .section-title {
        font-size: 2.35rem;
        line-height: 0.95;
    }

    .section-kicker {
        font-size: 0.58rem;
        letter-spacing: 0.28em;
    }
}

@media (max-width: 767px) {
    .watch-card,
    .sold-card,
    .vlog-card {
        min-width: 78vw;
        max-width: 78vw;
        border-radius: 1.15rem;
    }

    .watch-card > div,
    .sold-card > div {
        min-height: 390px;
    }

    .watch-card h3,
    .sold-card h3,
    .catalog-preview-card h3 {
        font-size: 1.55rem;
        line-height: 1.05;
        letter-spacing: -0.025em;
    }

    .detail-chip {
        padding: 0.28rem 0.58rem;
        font-size: 0.54rem;
        letter-spacing: 0.1em;
    }

    .view-detail-pill {
        padding: 0.55rem 0.7rem;
        font-size: 0.56rem;
        letter-spacing: 0.09em;
    }
}

@media (min-width: 1024px) {
    .watch-card > div {
        min-height: 470px;
    }

    .watch-card h3 {
        font-size: 1.65rem;
    }
}

.catalog-preview-card {
    border-radius: 1.3rem;
}

@media (max-width: 639px) {
    .catalog-preview-card > div {
        min-height: 390px;
    }
}

.process-card {
    position: relative;
    overflow: hidden;
}

.process-card::before {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: radial-gradient(
        circle at 20% 0%,
        rgb(255 255 255 / 0.055),
        transparent 34%
    );
    opacity: 0;
    transition: opacity 280ms ease;
}

.process-card:hover::before {
    opacity: 1;
}

@media (max-width: 639px) {
    .process-card {
        padding: 1.35rem;
        border-radius: 1rem;
    }
}

.contact-card {
    min-height: 5.3rem;
}

@media (max-width: 639px) {
    #contact .animated-in {
        border-radius: 1.25rem;
        padding: 1.35rem;
    }

    #contact h2 {
        font-size: 2.35rem;
        line-height: 0.95;
    }

    .contact-card {
        padding: 1rem;
        border-radius: 0.95rem;
    }
}

@media (max-width: 767px) {
    header .mx-auto {
        padding-top: 0.7rem;
        padding-bottom: 0.6rem;
    }

    header nav.mt-3 {
        padding-bottom: 0.25rem;
    }

    header nav.mt-3 a {
        font-size: 0.68rem;
        white-space: nowrap;
    }
}

.messenger-float-button {
    background: linear-gradient(135deg, #00a6ff 0%, #0084ff 45%, #006aff 100%);
}

.messenger-float-button:hover {
    filter: brightness(1.08);
    box-shadow:
        0 28px 78px rgb(0 0 0 / 0.7),
        0 0 0 1px rgb(255 255 255 / 0.16),
        0 0 54px rgb(0 132 255 / 0.42),
        inset 0 1px 0 rgb(255 255 255 / 0.28);
}

.watch-card,
.sold-card,
.vlog-card,
.catalog-preview-card {
    will-change: transform;
}

@media (max-width: 639px) {
    main {
        padding-top: 7.25rem !important;
    }

    main > section {
        margin-bottom: 0;
    }
}

/* =========================================================
   FULL BACKGROUND HERO CAROUSEL
========================================================= */

.hero-full-carousel-section {
    position: relative;
    isolation: isolate;
    min-height: calc(100dvh - 5.25rem);
    overflow: hidden;
    border-bottom: 1px solid rgb(255 255 255 / 0.08);
    background: #050505;
}

.hero-full-bg {
    position: absolute;
    inset: 0;
    z-index: 0;
    overflow: hidden;
    background: #050505;
}

.hero-full-slide {
    position: absolute;
    inset: 0;
}

.hero-full-backdrop-image {
    position: absolute;
    inset: -2rem;
    height: calc(100% + 4rem);
    width: calc(100% + 4rem);
    object-fit: cover;
    opacity: 0.36;
    filter: blur(30px) saturate(1.5) brightness(0.72);
    transition:
        transform 1400ms ease,
        opacity 500ms ease;
}

.hero-full-main-image {
    position: absolute;
    inset: 0;
    height: 100%;
    width: 100%;
    object-fit: cover;
    object-position: center;
    opacity: 0.92;
    filter: saturate(1.08) contrast(1.06) brightness(0.95);
    transition:
        transform 1400ms ease,
        opacity 500ms ease,
        filter 500ms ease;
    will-change: transform;
    animation: heroParallaxDrift 5.2s ease-in-out infinite alternate;
}

.hero-full-left-gradient {
    position: absolute;
    inset: 0;
    z-index: 2;
    pointer-events: none;
    background: linear-gradient(
        90deg,
        rgb(0 0 0 / 0.92) 0%,
        rgb(0 0 0 / 0.78) 31%,
        rgb(0 0 0 / 0.36) 58%,
        rgb(0 0 0 / 0.16) 100%
    );
}

.hero-full-vignette {
    position: absolute;
    inset: 0;
    z-index: 3;
    pointer-events: none;
    background: radial-gradient(
        circle at 72% 45%,
        transparent 0%,
        rgb(0 0 0 / 0.08) 38%,
        rgb(0 0 0 / 0.56) 100%
    );
}

.hero-full-bottom-gradient {
    position: absolute;
    inset-x: 0;
    bottom: 0;
    z-index: 4;
    height: 38%;
    pointer-events: none;
    background: linear-gradient(
        to top,
        rgb(0 0 0 / 0.78),
        rgb(0 0 0 / 0.34),
        transparent
    );
}

.hero-full-top-glass {
    position: absolute;
    inset-x: 0;
    top: 0;
    z-index: 5;
    height: 32%;
    pointer-events: none;
    background: linear-gradient(to bottom, rgb(0 0 0 / 0.42), transparent);
}

.hero-full-content {
    position: relative;
    z-index: 20;
    display: flex;
    min-height: calc(100dvh - 5.25rem);
    align-items: center;
    margin-inline: auto;
    max-width: 80rem;
    padding: 5.5rem 1rem 7rem;
}

.hero-full-copy {
    max-width: 46rem;
}

.hero-full-eyebrow {
    background: rgb(0 0 0 / 0.36);
    backdrop-filter: blur(18px);
    box-shadow:
        0 18px 55px rgb(0 0 0 / 0.35),
        inset 0 1px 0 rgb(255 255 255 / 0.08);
}

.hero-full-title {
    max-width: 46rem;
    text-wrap: balance;
    font-size: clamp(3.15rem, 7.1vw, 6.7rem);
    font-weight: 950;
    line-height: 0.86;
    letter-spacing: -0.085em;
    color: white;
    text-shadow: 0 12px 40px rgb(0 0 0 / 0.62);
}

.hero-full-description {
    margin-top: 1.75rem;
    max-width: 38rem;
    font-size: 1.08rem;
    line-height: 1.9;
    color: rgb(228 228 231 / 0.78);
    text-shadow: 0 8px 26px rgb(0 0 0 / 0.7);
}

.hero-full-actions {
    margin-top: 2.35rem;
    width: min(100%, 31rem);
}

.hero-full-cta {
    width: 100%;
    justify-content: space-between;
    padding: 1rem 2rem;
    font-size: 0.83rem;
    box-shadow:
        0 22px 65px rgb(255 255 255 / 0.12),
        0 0 0 1px rgb(255 255 255 / 0.12);
}

.hero-full-stats {
    margin-top: 2.35rem;
    display: grid;
    width: min(100%, 31rem);
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
}

.hero-full-stat-card {
    min-height: 5.5rem;
    border-color: rgb(255 255 255 / 0.12);
    background: rgb(0 0 0 / 0.34);
    box-shadow:
        0 18px 55px rgb(0 0 0 / 0.35),
        inset 0 1px 0 rgb(255 255 255 / 0.08);
    backdrop-filter: blur(18px);
}

.hero-full-rank-badge {
    position: absolute;
    top: 2rem;
    right: clamp(1rem, 4vw, 4.5rem);
    z-index: 30;
    border-radius: 9999px;
    border: 1px solid rgb(255 255 255 / 0.18);
    background: rgb(0 0 0 / 0.45);
    padding: 0.7rem 1rem;
    color: white;
    font-size: 0.68rem;
    font-weight: 950;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    box-shadow: 0 18px 48px rgb(0 0 0 / 0.42);
    backdrop-filter: blur(20px);
}

.hero-full-arrow {
    position: absolute;
    top: 50%;
    z-index: 35;
    display: inline-flex;
    height: 3rem;
    width: 3rem;
    transform: translateY(-50%);
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    border: 1px solid rgb(255 255 255 / 0.16);
    background: rgb(0 0 0 / 0.35);
    color: white;
    box-shadow: 0 18px 48px rgb(0 0 0 / 0.38);
    backdrop-filter: blur(18px);
    transition:
        transform 260ms ease,
        background-color 260ms ease,
        border-color 260ms ease,
        color 260ms ease;
}

.hero-full-arrow:hover {
    transform: translateY(-50%) scale(1.04);
    border-color: rgb(255 255 255 / 0.28);
    background: white;
    color: black;
}

.hero-full-arrow:active {
    transform: translateY(-50%) scale(0.96);
}

.hero-full-arrow-left {
    left: clamp(1rem, 4vw, 4.5rem);
}

.hero-full-arrow-right {
    right: clamp(1rem, 4vw, 4.5rem);
}

.hero-full-dock {
    position: absolute;
    right: clamp(1rem, 4vw, 4.5rem);
    bottom: 2rem;
    z-index: 32;
    display: grid;
    grid-template-columns: repeat(5, minmax(4.5rem, 5.4rem));
    gap: 0.6rem;
    max-width: calc(100% - 2rem);
    overflow: hidden;
    border-radius: 1.25rem;
    border: 1px solid rgb(255 255 255 / 0.12);
    background: rgb(0 0 0 / 0.38);
    padding: 0.65rem;
    box-shadow:
        0 22px 65px rgb(0 0 0 / 0.42),
        inset 0 1px 0 rgb(255 255 255 / 0.08);
    backdrop-filter: blur(22px);
}

.hero-full-thumb-button {
    min-width: 0;
    border-radius: 0.95rem;
    border: 1px solid transparent;
    padding: 0.32rem;
    transition:
        transform 260ms ease,
        border-color 260ms ease,
        background-color 260ms ease,
        opacity 260ms ease;
}

.hero-full-thumb-button:hover {
    transform: translateY(-1px);
    border-color: rgb(255 255 255 / 0.18);
    background: rgb(255 255 255 / 0.06);
}

.hero-full-thumb-button.is-active {
    border-color: rgb(255 255 255 / 0.52);
    background: rgb(255 255 255 / 0.1);
}

.hero-full-thumb-media {
    position: relative;
    display: block;
    aspect-ratio: 1 / 1;
    overflow: hidden;
    border-radius: 0.75rem;
    background: #050505;
}

.hero-full-thumb-rank {
    position: absolute;
    left: 0.4rem;
    top: 0.4rem;
    z-index: 10;
    display: flex;
    min-width: 1.45rem;
    height: 1.45rem;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    border: 1px solid rgb(255 255 255 / 0.18);
    background: rgb(0 0 0 / 0.62);
    padding-inline: 0.35rem;
    color: white;
    font-size: 0.56rem;
    font-weight: 950;
    box-shadow: 0 10px 24px rgb(0 0 0 / 0.38);
    backdrop-filter: blur(14px);
}

/* Tablet */
@media (max-width: 1023px) {
    .hero-full-carousel-section {
        min-height: calc(100dvh - 7.25rem);
    }

    .hero-full-content {
        min-height: calc(100dvh - 7.25rem);
        align-items: flex-end;
        padding: 5rem 1rem 9.25rem;
    }

    .hero-full-copy {
        max-width: 42rem;
    }

    .hero-full-left-gradient {
        background: linear-gradient(
            to top,
            rgb(0 0 0 / 0.9) 0%,
            rgb(0 0 0 / 0.72) 36%,
            rgb(0 0 0 / 0.24) 72%,
            rgb(0 0 0 / 0.14) 100%
        );
    }

    .hero-full-rank-badge {
        top: 1rem;
        right: 1rem;
    }

    .hero-full-dock {
        right: 1rem;
        bottom: 1rem;
        left: 1rem;
        grid-template-columns: repeat(5, minmax(3.4rem, 1fr));
        gap: 0.45rem;
        padding: 0.5rem;
        border-radius: 1rem;
    }

    .hero-full-arrow {
        height: 2.65rem;
        width: 2.65rem;
    }

    .hero-full-arrow-left {
        left: 0.85rem;
    }

    .hero-full-arrow-right {
        right: 0.85rem;
    }
}

/* Mobile */
@media (max-width: 639px) {
    .hero-full-carousel-section {
        min-height: calc(100dvh - 7.25rem);
    }

    .hero-full-main-image {
        opacity: 0.88;
        object-position: center;
    }

    .hero-full-vignette {
        background: radial-gradient(
            circle at 50% 34%,
            transparent 0%,
            rgb(0 0 0 / 0.12) 42%,
            rgb(0 0 0 / 0.72) 100%
        );
    }

    .hero-full-content {
        padding: 4.5rem 1rem 8.25rem;
    }

    .hero-full-eyebrow {
        margin-bottom: 1rem;
        max-width: 100%;
        padding: 0.48rem 0.72rem;
        font-size: 0.5rem;
        letter-spacing: 0.18em;
    }

    .hero-full-title {
        font-size: clamp(2.7rem, 15vw, 4rem);
        line-height: 0.86;
        letter-spacing: -0.086em;
    }

    .hero-full-description {
        margin-top: 1.15rem;
        max-width: 22rem;
        font-size: 0.9rem;
        line-height: 1.75;
    }

    .hero-full-actions {
        margin-top: 1.45rem;
        width: 100%;
    }

    .hero-full-cta {
        min-height: 3.35rem;
        padding-inline: 1rem;
        font-size: 0.68rem;
        letter-spacing: 0.1em;
    }

    .hero-full-stats {
        margin-top: 1rem;
        width: 100%;
        gap: 0.55rem;
    }

    .hero-full-stat-card {
        min-height: 4.65rem;
        padding: 0.85rem;
        border-radius: 0.9rem;
    }

    .hero-full-rank-badge {
        top: 0.85rem;
        right: 0.85rem;
        padding: 0.55rem 0.78rem;
        font-size: 0.56rem;
        letter-spacing: 0.13em;
    }

    .hero-full-arrow {
        top: 43%;
        height: 2.35rem;
        width: 2.35rem;
        background: rgb(0 0 0 / 0.32);
    }

    .hero-full-arrow-left {
        left: 0.7rem;
    }

    .hero-full-arrow-right {
        right: 0.7rem;
    }

    .hero-full-dock {
        right: 0.75rem;
        bottom: 0.75rem;
        left: 0.75rem;
        grid-template-columns: repeat(5, minmax(2.9rem, 1fr));
        gap: 0.35rem;
        padding: 0.42rem;
        border-radius: 0.9rem;
    }

    .hero-full-thumb-button {
        padding: 0.2rem;
        border-radius: 0.65rem;
    }

    .hero-full-thumb-media {
        border-radius: 0.55rem;
    }

    .hero-full-thumb-rank {
        left: 0.28rem;
        top: 0.28rem;
        min-width: 1.15rem;
        height: 1.15rem;
        padding-inline: 0.25rem;
        font-size: 0.46rem;
    }
}

/* =========================================================
   MOBILE HERO RESTORE + BACKGROUND VISIBILITY TUNE
   Desktop remains unchanged. This only improves phone view.
========================================================= */

@media (max-width: 639px) {
    .hero-full-carousel-section {
        min-height: calc(100dvh - 7.25rem);
    }

    .hero-full-main-image {
        opacity: 1;
        object-position: center center;
        filter: saturate(1.14) contrast(1.04) brightness(1.06);
        transform: scale(1.025) !important;
    }

    .hero-full-backdrop-image {
        opacity: 0.24;
        object-position: center center;
        filter: blur(20px) saturate(1.35) brightness(0.92);
    }

    .hero-full-left-gradient {
        background: linear-gradient(
            to top,
            rgb(0 0 0 / 0.88) 0%,
            rgb(0 0 0 / 0.68) 28%,
            rgb(0 0 0 / 0.34) 48%,
            rgb(0 0 0 / 0.1) 72%,
            rgb(0 0 0 / 0.18) 100%
        );
    }

    .hero-full-vignette {
        background: radial-gradient(
            circle at 50% 30%,
            transparent 0%,
            transparent 30%,
            rgb(0 0 0 / 0.12) 56%,
            rgb(0 0 0 / 0.5) 100%
        );
    }

    .hero-full-bottom-gradient {
        height: 38%;
        background: linear-gradient(
            to top,
            rgb(0 0 0 / 0.82),
            rgb(0 0 0 / 0.34),
            transparent
        );
    }

    .hero-full-top-glass {
        height: 17%;
        background: linear-gradient(to bottom, rgb(0 0 0 / 0.26), transparent);
    }

    .hero-full-content {
        align-items: flex-end;
        padding: 4.6rem 1rem 7.95rem;
    }

    .hero-full-copy {
        max-width: 21.75rem;
        border-radius: 1.05rem;
        background: linear-gradient(
            180deg,
            rgb(0 0 0 / 0.14),
            rgb(0 0 0 / 0.08)
        );
        padding: 0.68rem 0.55rem 0;
        box-shadow: none;
        backdrop-filter: none;
    }

    .hero-full-eyebrow {
        margin-bottom: 0.82rem;
        max-width: 100%;
        padding: 0.42rem 0.64rem;
        font-size: 0.45rem;
        letter-spacing: 0.15em;
        background: rgb(0 0 0 / 0.38);
        backdrop-filter: blur(12px);
    }

    .hero-full-title {
        max-width: 21rem;
        font-size: clamp(2.15rem, 10.5vw, 2.95rem);
        line-height: 0.92;
        letter-spacing: -0.074em;
        text-shadow:
            0 6px 20px rgb(0 0 0 / 0.9),
            0 18px 52px rgb(0 0 0 / 0.75);
    }

    .hero-full-description {
        margin-top: 1rem;
        max-width: 20.25rem;
        font-size: 0.84rem;
        line-height: 1.65;
        color: rgb(244 244 245 / 0.84);
        text-shadow: 0 5px 18px rgb(0 0 0 / 0.82);
    }

    .hero-full-actions {
        margin-top: 1.2rem;
        width: 100%;
    }

    .hero-full-cta {
        min-height: 3.15rem;
        padding-inline: 0.95rem;
        border-radius: 0.95rem;
        font-size: 0.64rem;
        letter-spacing: 0.1em;
    }

    .hero-full-stats {
        margin-top: 0.85rem;
        width: 100%;
        gap: 0.55rem;
    }

    .hero-full-stat-card {
        min-height: 4.25rem;
        padding: 0.75rem;
        border-radius: 0.9rem;
        background: rgb(0 0 0 / 0.34);
        backdrop-filter: blur(12px);
    }

    .hero-full-stat-card .stat-value {
        font-size: 1.35rem;
    }

    .hero-full-stat-card .stat-label {
        font-size: 0.52rem;
        letter-spacing: 0.15em;
    }

    .hero-full-rank-badge {
        top: 0.85rem;
        right: 0.85rem;
        padding: 0.55rem 0.78rem;
        font-size: 0.56rem;
        letter-spacing: 0.13em;
        background: rgb(0 0 0 / 0.42);
    }

    .hero-full-arrow {
        top: 36%;
        height: 2.2rem;
        width: 2.2rem;
        background: rgb(0 0 0 / 0.26);
        backdrop-filter: blur(10px);
    }

    .hero-full-arrow-left {
        left: 0.7rem;
    }

    .hero-full-arrow-right {
        right: 0.7rem;
    }

    .hero-full-dock {
        right: 0.65rem;
        bottom: 0.65rem;
        left: 0.65rem;
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 0.28rem;
        padding: 0.36rem;
        border-radius: 0.85rem;
        background: rgb(0 0 0 / 0.3);
        backdrop-filter: blur(12px);
    }

    .hero-full-thumb-button {
        min-width: 0;
        padding: 0.15rem;
        border-radius: 0.58rem;
    }

    .hero-full-thumb-media {
        border-radius: 0.48rem;
    }

    .hero-full-thumb-rank {
        left: 0.22rem;
        top: 0.22rem;
        min-width: 1.05rem;
        height: 1.05rem;
        padding-inline: 0.2rem;
        font-size: 0.42rem;
    }

    .messenger-float-button {
        bottom: calc(env(safe-area-inset-bottom) + 5.35rem) !important;
    }
}

/* =========================================================
   FINAL MOBILE HERO CENTER CROP OVERRIDE
   Desktop unchanged. Mobile carousel image stays centered.
========================================================= */

@media (max-width: 639px) {
    .hero-full-main-image {
        object-position: center center !important;
        opacity: 1;
        filter: saturate(1.12) contrast(1.04) brightness(1.04);
    }

    .hero-full-backdrop-image {
        object-position: center center !important;
    }

    .hero-full-vignette {
        background: radial-gradient(
            circle at 50% 30%,
            transparent 0%,
            transparent 34%,
            rgb(0 0 0 / 0.14) 58%,
            rgb(0 0 0 / 0.48) 100%
        );
    }

    .hero-full-title {
        font-size: clamp(2.05rem, 10.2vw, 2.85rem);
        line-height: 0.94;
        letter-spacing: -0.072em;
        max-width: 20rem;
    }

    .hero-full-description {
        max-width: 20rem;
        font-size: 0.82rem;
        line-height: 1.6;
    }

    .hero-full-copy {
        max-width: 22rem;
    }
}

/* =========================================================
   FINAL MOBILE HERO CENTERED LAYOUT
   Text/Button upper-middle + centered carousel image
========================================================= */

@media (max-width: 639px) {
    .hero-full-carousel-section {
        min-height: calc(100dvh - 7.25rem);
    }

    /* Keep the watch image centered and visible */
    .hero-full-main-image {
        object-position: center center !important;
        opacity: 1 !important;
        transform: scale(1.03) !important;
        filter: saturate(1.12) contrast(1.04) brightness(1.02) !important;
    }

    .hero-full-backdrop-image {
        object-position: center center !important;
        opacity: 0.18 !important;
        filter: blur(22px) saturate(1.25) brightness(0.88) !important;
    }

    /* Lighter overlay so the background carousel is more visible */
    .hero-full-left-gradient {
        background: linear-gradient(
            to bottom,
            rgb(0 0 0 / 0.28) 0%,
            rgb(0 0 0 / 0.12) 22%,
            rgb(0 0 0 / 0.22) 48%,
            rgb(0 0 0 / 0.74) 100%
        ) !important;
    }

    .hero-full-vignette {
        background: radial-gradient(
            circle at 50% 45%,
            transparent 0%,
            rgb(0 0 0 / 0.08) 42%,
            rgb(0 0 0 / 0.42) 100%
        ) !important;
    }

    .hero-full-bottom-gradient {
        height: 30% !important;
        background: linear-gradient(
            to top,
            rgb(0 0 0 / 0.76),
            rgb(0 0 0 / 0.26),
            transparent
        ) !important;
    }

    .hero-full-top-glass {
        height: 18% !important;
        background: linear-gradient(
            to bottom,
            rgb(0 0 0 / 0.22),
            transparent
        ) !important;
    }

    /* Put text/button at upper-middle */
    .hero-full-content {
        min-height: calc(100dvh - 7.25rem) !important;
        align-items: flex-start !important;
        justify-content: center !important;
        padding: 4.45rem 1rem 8.45rem !important;
    }

    .hero-full-copy {
        width: 100%;
        max-width: 22.5rem !important;
        margin-inline: auto !important;
        padding: 0 !important;
        text-align: center !important;
        background: transparent !important;
        box-shadow: none !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }

    .hero-full-eyebrow {
        width: fit-content;
        max-width: 100%;
        margin-inline: auto !important;
        margin-bottom: 0.8rem !important;
        padding: 0.42rem 0.68rem !important;
        border-color: rgb(255 255 255 / 0.14) !important;
        background: rgb(0 0 0 / 0.38) !important;
        font-size: 0.45rem !important;
        letter-spacing: 0.15em !important;
        box-shadow:
            0 10px 28px rgb(0 0 0 / 0.34),
            inset 0 1px 0 rgb(255 255 255 / 0.1) !important;
        backdrop-filter: blur(12px) !important;
        -webkit-backdrop-filter: blur(12px) !important;
    }

    .hero-full-title {
        max-width: 21rem !important;
        margin-inline: auto !important;
        font-size: clamp(2.05rem, 10.3vw, 2.9rem) !important;
        line-height: 0.94 !important;
        letter-spacing: -0.072em !important;
        text-align: center !important;
        text-shadow:
            0 5px 18px rgb(0 0 0 / 0.82),
            0 16px 45px rgb(0 0 0 / 0.76) !important;
    }

    .hero-full-description {
        max-width: 20rem !important;
        margin: 0.9rem auto 0 !important;
        font-size: 0.82rem !important;
        line-height: 1.6 !important;
        text-align: center !important;
        color: rgb(244 244 245 / 0.82) !important;
        text-shadow: 0 5px 18px rgb(0 0 0 / 0.82) !important;
    }

    .hero-full-actions {
        width: 100% !important;
        margin-top: 1.15rem !important;
    }

    .hero-full-cta {
        min-height: 3.15rem !important;
        width: 100% !important;
        justify-content: space-between !important;
        border-radius: 1rem !important;
        padding-inline: 1rem !important;
        font-size: 0.64rem !important;
        letter-spacing: 0.1em !important;
        box-shadow:
            0 18px 48px rgb(0 0 0 / 0.34),
            0 0 0 1px rgb(255 255 255 / 0.18) !important;
    }

    .hero-full-stats {
        width: 100% !important;
        margin-top: 0.85rem !important;
        gap: 0.55rem !important;
    }

    .hero-full-stat-card {
        min-height: 4.1rem !important;
        padding: 0.72rem !important;
        border-radius: 0.9rem !important;
        background: rgb(0 0 0 / 0.34) !important;
        backdrop-filter: blur(10px) !important;
        -webkit-backdrop-filter: blur(10px) !important;
    }

    .hero-full-stat-card .stat-value {
        font-size: 1.25rem !important;
    }

    .hero-full-stat-card .stat-label {
        margin-top: 0.32rem !important;
        font-size: 0.5rem !important;
        letter-spacing: 0.14em !important;
    }

    /* Move arrows away from the text */
    .hero-full-arrow {
        top: 53% !important;
        height: 2.35rem !important;
        width: 2.35rem !important;
        background: rgb(0 0 0 / 0.34) !important;
        backdrop-filter: blur(10px) !important;
        -webkit-backdrop-filter: blur(10px) !important;
    }

    .hero-full-arrow-left {
        left: 0.7rem !important;
    }

    .hero-full-arrow-right {
        right: 0.7rem !important;
    }

    .hero-full-rank-badge {
        top: 0.8rem !important;
        right: 0.8rem !important;
        padding: 0.52rem 0.75rem !important;
        font-size: 0.55rem !important;
        letter-spacing: 0.13em !important;
        background: rgb(0 0 0 / 0.38) !important;
    }

    /* Keep all 5 thumbnails visible */
    .hero-full-dock {
        right: 0.65rem !important;
        bottom: 0.65rem !important;
        left: 0.65rem !important;
        display: grid !important;
        grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
        gap: 0.3rem !important;
        padding: 0.36rem !important;
        border-radius: 0.9rem !important;
        background: rgb(0 0 0 / 0.32) !important;
        backdrop-filter: blur(12px) !important;
        -webkit-backdrop-filter: blur(12px) !important;
    }

    .hero-full-thumb-button {
        min-width: 0 !important;
        padding: 0.16rem !important;
        border-radius: 0.58rem !important;
    }

    .hero-full-thumb-media {
        border-radius: 0.5rem !important;
    }

    .hero-full-thumb-rank {
        left: 0.22rem !important;
        top: 0.22rem !important;
        min-width: 1.05rem !important;
        height: 1.05rem !important;
        padding-inline: 0.2rem !important;
        font-size: 0.42rem !important;
    }

    /* Prevent Messenger button from covering the thumbnail dock */
    .messenger-float-button {
        bottom: calc(env(safe-area-inset-bottom) + 5.35rem) !important;
        right: 0.85rem !important;
        height: 3.35rem !important;
        width: 3.35rem !important;
    }
}
</style>
