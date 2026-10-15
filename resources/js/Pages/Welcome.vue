<script setup>
import MontreLogo from "@/Components/MontreLogo.vue";
import { Head, Link, router } from "@inertiajs/vue3";
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

const isInitialLoading = ref(true);
const isNavigating = ref(false);
const loadingLabel = ref("Preparing the collection");
const showMontreLoader = computed(
    () => isInitialLoading.value || isNavigating.value,
);
const markHeroReady = () => {
    isInitialLoading.value = false;
};
let removeStartListener = null;
let removeFinishListener = null;
let initialLoadingFallback = null;
const isMobileCtaOpen = ref(false);
const activeSection = ref("");
const activeBestSellerIndex = ref(0);
const isNavScrolled = ref(false);
const activeWatchFilter = ref("All");
const watchSort = ref("newest");

let sectionObserver = null;
let bestSellerCarouselTimer = null;
let scrollHandler = null;

const toggleMobileCta = () => {
    isMobileCtaOpen.value = !isMobileCtaOpen.value;
};

const closeMobileCta = () => {
    isMobileCtaOpen.value = false;
};

// Inertia's router lifecycle handles all internal page visits, including
// pagination and data refreshes. No arbitrary 1.2-second loading overlay.
const preparePageOpen = (event = null) => {
    if (
        event?.metaKey ||
        event?.ctrlKey ||
        event?.shiftKey ||
        event?.altKey ||
        event?.button === 1
    )
        return;
    loadingLabel.value = "Opening collection";
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

const bestSellerCarouselWatches = computed(() => [
    {
        id: 1,
        image: "/images/hero/pic1.jpg",
    },
    {
        id: 2,
        image: "/images/hero/pic2.jpg",
    },
    {
        id: 3,
        image: "/images/hero/pic3.jpg",
    },
    {
        id: 4,
        image: "/images/hero/pic4.jpg",
    },
    {
        id: 5,
        image: "/images/hero/pic5.jpg",
    },
]);

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
            "focus:outline-none focus-visible:ring-2 focus-visible:ring-white/35",
            "active:scale-[0.97]",
            active
                ? "border-white bg-white text-black shadow-sm"
                : "border-white/12 bg-white/[0.045] text-zinc-400 active:border-white/35 active:bg-white/10 active:text-white",
        ].join(" ");
    }

    return [
        "group relative inline-flex items-center gap-2 rounded-full px-1 py-2 transition duration-300",
        "focus:outline-none focus-visible:ring-2 focus-visible:ring-white/35",
        "active:scale-[0.97]",
        active ? "text-white" : "text-zinc-400 hover:text-white",
    ].join(" ");
};

onMounted(() => {
    // Hide the first-load mark once the main promotional image has resolved;
    // fallback avoids a stuck overlay if an asset fails or never fires load.
    initialLoadingFallback = window.setTimeout(markHeroReady, 6500);
    removeStartListener = router.on("start", () => {
        loadingLabel.value = "Preparing your next page";
        isNavigating.value = true;
    });
    removeFinishListener = router.on("finish", () => {
        isNavigating.value = false;
    });
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

    scrollHandler = () => {
        isNavScrolled.value = window.scrollY > 36;
    };

    scrollHandler();
    window.addEventListener("scroll", scrollHandler, { passive: true });

    startBestSellerAutoPlay();
});

onBeforeUnmount(() => {
    if (initialLoadingFallback) window.clearTimeout(initialLoadingFallback);
    if (removeStartListener) removeStartListener();
    if (removeFinishListener) removeFinishListener();
    if (sectionObserver) {
        sectionObserver.disconnect();
    }

    if (scrollHandler) {
        window.removeEventListener("scroll", scrollHandler);
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

const openViewingInquiry = () => {
    closeMobileCta();
    window.open(
        messengerUrl(
            "Hi Montre Nova! I would like to schedule a watch viewing at Avida Towers One Union Place, ARCA South, Taguig. May I know the available viewing dates and times?",
        ),
        "_blank",
        "noopener,noreferrer",
    );
};

const contactLinks = [
    {
        label: "Messenger",
        description: "Availability, reservations, and watch assistance.",
        href: "https://m.me/montrenova",
        icon: "messenger",
    },
    {
        label: "Facebook",
        description: "Follow us for more updates.",
        href: "https://www.facebook.com/montrenova",
        icon: "facebook",
    },
    {
        label: "TikTok",
        description: "Watch updates, short clips, and new drops.",
        href: "https://www.tiktok.com/@montre_nova",
        icon: "tiktok",
    },
    {
        label: "Instagram",
        description: "Latest posts, stories, and curated watches.",
        href: "https://www.instagram.com/montrenova",
        icon: "instagram",
    },
];

const orderSteps = [
    {
        number: "01",
        title: "Choose Your Watch",
        description:
            "Browse our on-hand selection or send us the model and reference you want us to source.",
    },
    {
        number: "02",
        title: "Secure Your Piece",
        description:
            "Confirm the watch and complete the required payment or down payment. Reservations are only confirmed upon payment.",
    },
    {
        number: "03",
        title: "Receive Your Watch",
        description:
            "Choose a scheduled Metro Manila meetup or nationwide delivery once your order is ready for release.",
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

const watchFilterOptions = computed(() => {
    const brands = [];
    const categories = [];

    watches.value.forEach((watch) => {
        const brand = String(watch?.brand || "").trim();
        const category = String(watch?.category || "").trim();

        if (
            brand &&
            !brands.some((item) => item.toLowerCase() === brand.toLowerCase())
        ) {
            brands.push(brand);
        }

        if (
            category &&
            !categories.some(
                (item) => item.toLowerCase() === category.toLowerCase(),
            )
        ) {
            categories.push(category);
        }
    });

    const priority = [
        "Seiko",
        "Tissot",
        "Casio",
        "GMT",
        "Diver",
        "Chronograph",
    ];
    const source = [...brands, ...categories];
    const ordered = [
        ...priority.filter((label) =>
            source.some((value) =>
                value.toLowerCase().includes(label.toLowerCase()),
            ),
        ),
        ...source,
    ];

    const unique = [];
    ordered.forEach((label) => {
        if (
            !unique.some((item) => item.toLowerCase() === label.toLowerCase())
        ) {
            unique.push(label);
        }
    });

    return ["All", ...unique.slice(0, 7)];
});

const filteredAndSortedWatches = computed(() => {
    const filter = activeWatchFilter.value.toLowerCase();
    let collection = [...watches.value];

    if (filter !== "all") {
        collection = collection.filter((watch) => {
            const brand = String(watch?.brand || "").toLowerCase();
            const category = String(watch?.category || "").toLowerCase();
            return brand.includes(filter) || category.includes(filter);
        });
    }

    if (watchSort.value === "price-low") {
        collection.sort((a, b) => finalPrice(a) - finalPrice(b));
    } else if (watchSort.value === "price-high") {
        collection.sort((a, b) => finalPrice(b) - finalPrice(a));
    } else if (watchSort.value === "name") {
        collection.sort((a, b) =>
            watchFullName(a).localeCompare(watchFullName(b)),
        );
    } else {
        collection.sort((a, b) => {
            const aDate =
                new Date(a?.created_at || a?.updated_at || 0).getTime() || 0;
            const bDate =
                new Date(b?.created_at || b?.updated_at || 0).getTime() || 0;
            return bDate - aDate;
        });
    }

    return collection;
});

const filteredWatchCount = computed(
    () => filteredAndSortedWatches.value.length,
);

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
        watch?.image ||
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
        class="min-h-screen overflow-x-hidden bg-[#050505] text-[#f5f5f5] selection:bg-white selection:text-black"
    >
        <!-- BACKGROUND -->
        <div
            class="pointer-events-none fixed inset-0 z-0 overflow-hidden bg-[#030304]"
        >
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_52%_8%,rgba(60,60,66,0.13),transparent_38%),linear-gradient(180deg,#050506_0%,#030304_55%,#08080a_100%)]"
            ></div>
            <div
                class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/10 to-transparent"
            ></div>
        </div>

        <!-- REFINED RESPONSIVE NAVBAR -->
        <header class="mn-luxe-nav" :class="{ 'is-scrolled': isNavScrolled }">
            <div class="mn-luxe-nav-inner">
                <a
                    href="/"
                    class="mn-luxe-brand"
                    aria-label="Montre Nova homepage"
                    @click="closeMobileCta"
                >
                    <MontreLogo />
                </a>

                <nav class="mn-desktop-links" aria-label="Main navigation">
                    <a href="#collection" @click="activateSection('collection')"
                        >Watches</a
                    >
                    <a
                        v-if="hasCatalogPreview"
                        href="#catalog"
                        @click="activateSection('catalog')"
                        >Catalog</a
                    >
                    <a
                        v-if="recentSoldWatches.length"
                        href="#recently-sold"
                        @click="activateSection('recently-sold')"
                        >Sold Gallery</a
                    >
                    <a href="#process" @click="activateSection('process')"
                        >How to Order</a
                    >
                    <a href="#contact" @click="activateSection('contact')"
                        >Contact</a
                    >
                </nav>

                <div class="mn-desktop-actions">
                    <Link href="/warranty-check" class="mn-nav-warranty"
                        >Warranty Check</Link
                    >
                </div>

                <div class="mn-mobile-actions">
                    <button
                        type="button"
                        class="mn-mobile-toggle"
                        :aria-expanded="isMobileCtaOpen"
                        aria-controls="mn-mobile-navigation"
                        :aria-label="
                            isMobileCtaOpen ? 'Close menu' : 'Open menu'
                        "
                        @click="toggleMobileCta"
                    >
                        <span
                            :class="{ 'line-open-first': isMobileCtaOpen }"
                        ></span>
                        <span
                            :class="{ 'line-open-last': isMobileCtaOpen }"
                        ></span>
                    </button>
                </div>
            </div>
            <Transition name="mn-menu-fade">
                <nav
                    v-if="isMobileCtaOpen"
                    id="mn-mobile-navigation"
                    class="mn-mobile-panel"
                    aria-label="Mobile navigation"
                >
                    <a
                        v-for="(item, index) in navSections"
                        :key="item.id"
                        :href="item.href"
                        @click="
                            activateSection(item.id);
                            closeMobileCta();
                        "
                    >
                        <span class="mn-menu-number">{{
                            String(index + 1).padStart(2, "0")
                        }}</span>
                        <span>{{ item.label }}</span>
                    </a>
                    <Link href="/warranty-check" @click="closeMobileCta">
                        <span class="mn-menu-number">—</span
                        ><span>Warranty Check</span></Link
                    >
                    <p class="mn-menu-footer">MONTRE NOVA · TAGUIG, PH</p>
                </nav>
            </Transition>
        </header>

        <main class="relative z-10 mn-site-main">
            <!-- MONOCHROME EDITORIAL HERO / STATIC UPLOADED IMAGES -->
            <section
                class="hero-full-carousel-section"
                aria-label="Montre Nova featured collection"
                @mouseenter="stopBestSellerAutoPlay"
                @mouseleave="startBestSellerAutoPlay"
                @focusin="stopBestSellerAutoPlay"
                @focusout="startBestSellerAutoPlay"
            >
                <div class="hero-full-bg">
                    <Transition name="hero-slide" mode="out-in">
                        <div
                            v-if="activeBestSellerWatch"
                            :key="activeBestSellerWatch.id"
                            class="hero-full-slide"
                        >
                            <img
                                :src="watchImage(activeBestSellerWatch)"
                                :alt="`Montre Nova featured Tissot collection image ${activeBestSellerIndex + 1}`"
                                class="hero-full-backdrop-image"
                                @error="handleImageError"
                            />
                            <img
                                :src="watchImage(activeBestSellerWatch)"
                                :alt="`Featured Montre Nova timepiece ${activeBestSellerIndex + 1}`"
                                class="hero-full-main-image"
                                @load="markHeroReady"
                                @error="
                                    markHeroReady();
                                    handleImageError($event);
                                "
                            />
                        </div>
                    </Transition>
                    <div class="hero-full-left-gradient"></div>
                    <div class="hero-full-vignette"></div>
                    <div class="hero-full-bottom-gradient"></div>
                    <div class="hero-full-top-glass"></div>
                </div>

                <div class="hero-full-content">
                    <div class="hero-full-copy">
                        <p class="mn-hero-eyebrow">
                            MONTRE NOVA <span>/</span>
                        </p>
                        <h1 class="hero-full-title">
                            Your Next Signature Timepiece.
                        </h1>
                        <p class="hero-full-description">
                            Discover thoughtfully selected watches from Tissot,
                            Seiko, Casio, and more.
                        </p>
                        <div class="hero-full-actions">
                            <a
                                href="#collection"
                                class="hero-full-cta mn-hero-explore"
                                @click="activateSection('collection')"
                            >
                                <span>EXPLORE COLLECTION</span>
                            </a>
                        </div>
                        <div
                            class="mn-hero-meta"
                            aria-label="Montre Nova highlights"
                        >
                            <span>INDEPENDENT WATCH RETAILER</span>
                            <span>MANILA · PHILIPPINES</span>
                        </div>
                    </div>
                </div>

                <template v-if="hasBestSellerCarousel">
                    <button
                        type="button"
                        class="hero-full-arrow hero-full-arrow-left"
                        aria-label="Previous featured image"
                        @click.stop="
                            previousBestSeller();
                            resetBestSellerAutoPlay();
                        "
                    >
                        <span aria-hidden="true">←</span>
                    </button>
                    <button
                        type="button"
                        class="hero-full-arrow hero-full-arrow-right mn-hero-next-edge"
                        aria-label="Next featured image"
                        @click.stop="
                            nextBestSeller();
                            resetBestSellerAutoPlay();
                        "
                    >
                        <span aria-hidden="true">→</span>
                    </button>
                </template>

                <div
                    v-if="hasBestSellerCarousel"
                    class="hero-full-dock"
                    aria-label="Featured image selector"
                >
                    <button
                        v-for="(slide, index) in bestSellerPreviewWatches"
                        :key="slide.id"
                        type="button"
                        class="hero-full-thumb-button"
                        :class="{
                            'is-active': activeBestSellerIndex === index,
                        }"
                        :aria-label="`Show featured image ${index + 1}`"
                        :aria-pressed="activeBestSellerIndex === index"
                        @click="
                            setActiveBestSeller(index);
                            resetBestSellerAutoPlay();
                        "
                    >
                        <span class="hero-full-thumb-media">
                            <img
                                :src="watchImage(slide)"
                                :alt="`Featured image thumbnail ${index + 1}`"
                                loading="lazy"
                                @error="handleImageError"
                            />
                        </span>
                    </button>
                </div>
                <div class="mn-hero-counter" aria-hidden="true">
                    {{ String(activeBestSellerIndex + 1).padStart(2, "0") }}
                    <span>/</span>
                    {{
                        String(bestSellerCarouselWatches.length).padStart(
                            2,
                            "0",
                        )
                    }}
                </div>
            </section>

            <!-- COLLECTION -->
            <section id="collection" class="mn-journal-section scroll-mt-28">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mn-collection-head animated-in">
                        <div>
                            <p class="mn-showroom-kicker">AVAILABLE NOW</p>
                            <div class="mn-collection-title-row">
                                <h2>Available Watches</h2>
                                <span>{{ availableCount }} watches</span>
                            </div>
                            <p class="mn-collection-intro">
                                On-hand pieces with actual photos, clear
                                references, and transparent pricing.
                            </p>
                        </div>

                        <p
                            v-if="paginationSummary"
                            class="mn-collection-summary"
                        >
                            {{ paginationSummary }}
                        </p>
                    </div>

                    <div
                        v-if="watches.length"
                        class="mn-filter-bar animated-in"
                    >
                        <div class="mn-filter-scroll">
                            <button
                                v-for="filter in watchFilterOptions"
                                :key="filter"
                                type="button"
                                class="mn-filter-chip"
                                :class="
                                    activeWatchFilter === filter
                                        ? 'is-active'
                                        : ''
                                "
                                @click="activeWatchFilter = filter"
                            >
                                {{ filter }}
                            </button>
                        </div>

                        <div
                            class="mn-sort-panel"
                            role="group"
                            aria-label="Sort available watches"
                        >
                            <span class="mn-sort-caption">SORT BY</span>
                            <div class="mn-sort-options">
                                <button
                                    v-for="option in [
                                        { value: 'newest', label: 'Newest' },
                                        {
                                            value: 'price-low',
                                            label: 'Price ↑',
                                        },
                                        {
                                            value: 'price-high',
                                            label: 'Price ↓',
                                        },
                                        { value: 'name', label: 'Name' },
                                    ]"
                                    :key="option.value"
                                    type="button"
                                    class="mn-sort-choice"
                                    :class="{
                                        'is-active': watchSort === option.value,
                                    }"
                                    :aria-pressed="watchSort === option.value"
                                    @click="watchSort = option.value"
                                >
                                    {{ option.label }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="watches.length" class="mn-results-line">
                        <span>{{ filteredWatchCount }} shown</span>
                        <span class="md:hidden">Tap a watch for details</span>
                    </div>

                    <template v-if="watches.length">
                        <TransitionGroup
                            name="watch-card"
                            tag="div"
                            class="mn-watch-grid"
                        >
                            <Link
                                v-for="(
                                    watch, index
                                ) in filteredAndSortedWatches"
                                :key="watch.id"
                                :href="route('public.watches.show', watch.id)"
                                class="mn-watch-card group"
                                :class="isSold(watch) ? 'opacity-65' : ''"
                                :style="{ animationDelay: `${index * 45}ms` }"
                                :aria-label="`View details for ${watchFullName(watch)}${watchReference(watch)}`"
                                @click="preparePageOpen"
                            >
                                <div class="mn-watch-media">
                                    <img
                                        v-if="watchImage(watch)"
                                        :src="watchImage(watch)"
                                        :alt="`${watch.brand} ${watch.model_name}`"
                                        class="h-full w-full object-cover transition duration-700 group-hover:scale-[1.03]"
                                        loading="lazy"
                                        @error="handleImageError"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full w-full items-center justify-center bg-[#e8e8e4]"
                                    >
                                        <div class="mn-watch-placeholder">
                                            MN
                                        </div>
                                    </div>

                                    <div class="mn-watch-badges">
                                        <span
                                            v-for="badge in productBadges(
                                                watch,
                                            ).slice(0, 1)"
                                            :key="badge.label"
                                            class="mn-watch-badge"
                                            >{{ badge.label }}</span
                                        >
                                    </div>
                                </div>

                                <div class="mn-watch-body">
                                    <div class="mn-watch-heading">
                                        <p class="mn-watch-brand">
                                            {{ watch.brand || "Montre Nova" }}
                                        </p>
                                        <h3>{{ watch.model_name }}</h3>
                                        <p class="mn-watch-reference">
                                            {{
                                                watch.reference_number ||
                                                "Reference upon request"
                                            }}
                                        </p>
                                    </div>

                                    <div class="mn-watch-tags">
                                        <span>{{
                                            watch.condition ||
                                            "Condition upon request"
                                        }}</span>
                                        <span v-if="watch.category">{{
                                            watch.category
                                        }}</span>
                                    </div>

                                    <div class="mn-watch-price-row">
                                        <div>
                                            <p class="mn-watch-price">
                                                {{ peso(finalPrice(watch)) }}
                                            </p>
                                            <p
                                                v-if="isBelowSrp(watch)"
                                                class="mn-watch-original-price"
                                            >
                                                {{ peso(originalPrice(watch)) }}
                                            </p>
                                        </div>
                                        <span class="mn-watch-details"
                                            >View
                                            <span aria-hidden="true"></span
                                        ></span>
                                    </div>
                                </div>
                            </Link>
                        </TransitionGroup>

                        <div
                            v-if="!filteredAndSortedWatches.length"
                            class="mn-filter-empty"
                        >
                            <p>
                                No watches match this filter on the current
                                page.
                            </p>
                            <button
                                type="button"
                                @click="activeWatchFilter = 'All'"
                            >
                                Show all watches
                            </button>
                        </div>

                        <div
                            v-if="hasWatchPagination"
                            class="mn-pagination animated-in"
                        >
                            <p>{{ paginationSummary }}</p>
                            <div
                                class="flex flex-wrap items-center justify-center gap-2"
                            >
                                <template
                                    v-for="link in paginationLinks"
                                    :key="link.label"
                                >
                                    <span
                                        v-if="!link.url"
                                        class="mn-page-button is-disabled"
                                    >
                                        {{ cleanPaginationLabel(link.label) }}
                                    </span>
                                    <Link
                                        v-else
                                        :href="paginationUrl(link.url)"
                                        preserve-scroll
                                        preserve-state
                                        class="mn-page-button"
                                        :class="link.active ? 'is-active' : ''"
                                        >{{
                                            cleanPaginationLabel(link.label)
                                        }}</Link
                                    >
                                </template>
                            </div>
                        </div>
                    </template>

                    <div v-else class="mn-empty-state">
                        <p>AVAILABLE NOW</p>
                        <h3>No available watches yet.</h3>
                        <p>
                            Stocks will appear here once they are marked as
                            available and visible from the admin dashboard.
                        </p>
                        <button
                            type="button"
                            class="mn-solid-button"
                            @click="openInquiry()"
                        >
                            INQUIRE
                        </button>
                    </div>
                </div>
            </section>
            <!-- CATALOG PREVIEW -->
            <section
                v-if="catalogPreviewWatches.length"
                id="catalog"
                class="mn-index-section scroll-mt-28"
            >
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mn-compact-head animated-in">
                        <div>
                            <p class="mn-showroom-kicker">
                                BROWSE THE SHOWROOM
                            </p>
                            <h2>Explore by Style</h2>
                            <p>
                                Start with a category, then open the full
                                catalog when you want to see more references.
                            </p>
                        </div>
                        <Link
                            :href="route('public.catalog')"
                            class="mn-outline-button"
                            @click="preparePageOpen"
                        >
                            Full Catalog
                        </Link>
                    </div>

                    <div class="mn-reference-list">
                        <button
                            v-for="(watch, index) in catalogPreviewWatches"
                            :key="watch.id"
                            type="button"
                            class="mn-reference-row group"
                            :style="{ animationDelay: `${index * 65}ms` }"
                            :aria-label="`Ask availability for ${watchFullName(watch)}${watchReference(watch)}`"
                            @click="openInquiry(watch)"
                        >
                            <span class="mn-reference-number">
                                {{ String(index + 1).padStart(2, "0") }}
                            </span>

                            <div class="mn-reference-thumb">
                                <img
                                    v-if="watchImage(watch)"
                                    :src="watchImage(watch)"
                                    :alt="`${watch.brand} ${watch.model_name}`"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
                                    loading="lazy"
                                    @error="handleImageError"
                                />
                                <div v-else class="mn-placeholder-mark">MN</div>
                            </div>

                            <div class="mn-reference-name">
                                <p>{{ watch.category || "Catalog" }}</p>
                                <h3>{{ watch.model_name }}</h3>
                            </div>

                            <p class="mn-reference-code">
                                {{ watch.brand || "Montre Nova" }}<br />
                                REF /
                                {{ watch.reference_number || "UPON REQUEST" }}
                            </p>

                            <span class="mn-reference-action"
                                >ASK AVAILABILITY</span
                            >
                        </button>
                    </div>
                </div>
            </section>
            <!-- RECENTLY SOLD -->
            <section
                v-if="recentSoldWatches.length"
                id="recently-sold"
                class="mn-archive-section scroll-mt-28"
            >
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mn-compact-head is-dark animated-in">
                        <div>
                            <p class="mn-showroom-kicker">RECENTLY DELIVERED</p>
                            <h2>Real watches. Real Montre Nova deals.</h2>
                            <p>
                                Pieces recently claimed by Montre Nova clients.
                            </p>
                        </div>
                        <Link
                            href="/sold-watches"
                            class="mn-light-outline-button"
                            >Sold Gallery</Link
                        >
                    </div>

                    <p class="mn-archive-hint md:hidden">Swipe archive</p>

                    <TransitionGroup
                        name="watch-card"
                        tag="div"
                        class="mn-archive-grid"
                    >
                        <article
                            v-for="(watch, index) in recentSoldWatches"
                            :key="watch.id"
                            class="mn-archive-piece group"
                            :style="{ animationDelay: `${index * 65}ms` }"
                        >
                            <div class="mn-archive-media">
                                <img
                                    v-if="watchImage(watch)"
                                    :src="watchImage(watch)"
                                    :alt="`${watch.brand} ${watch.model_name}`"
                                    class="h-full w-full object-cover transition duration-700 group-hover:scale-[1.025]"
                                    loading="lazy"
                                    @error="handleImageError"
                                />
                                <div v-else class="mn-archive-placeholder">
                                    MN
                                </div>

                                <span class="mn-archive-count">
                                    {{ String(index + 1).padStart(2, "0") }}
                                </span>
                                <span class="mn-archive-sold">CLAIMED</span>
                            </div>

                            <div class="mn-archive-copy">
                                <p>{{ watch.brand || "Montre Nova" }}</p>
                                <h3>{{ watch.model_name }}</h3>
                                <div class="mn-archive-info">
                                    <span
                                        >REF /
                                        {{
                                            watch.reference_number || "—"
                                        }}</span
                                    >
                                    <span>{{ soldDateLabel(watch) }}</span>
                                </div>
                                <button
                                    type="button"
                                    class="mn-archive-source"
                                    @click="openSimilarInquiry(watch)"
                                >
                                    SOURCE SIMILAR
                                </button>
                            </div>
                        </article>
                    </TransitionGroup>

                    <div class="mn-archive-footerline">
                        <span>{{ soldTotal }} WATCHES SOLD</span>
                        <span v-if="soldThisMonthCount"
                            >{{ soldThisMonthCount }} THIS
                            {{ soldMonthLabel.toUpperCase() }}</span
                        >
                        <span v-else>MONTRE NOVA / TAGUIG, PH</span>
                    </div>
                </div>
            </section>
            <!-- VLOGS -->
            <section
                v-if="hasVlogs"
                id="vlogs"
                class="mn-film-section scroll-mt-28"
            >
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mn-compact-head animated-in">
                        <div>
                            <p class="mn-showroom-kicker">
                                MONTRE NOVA STORIES
                            </p>
                            <h2>Stories from the Wrist</h2>
                            <p>
                                Quick handoffs, client moments, and watch
                                stories from our reels.
                            </p>
                        </div>
                        <a
                            href="https://www.facebook.com/montrenova"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mn-outline-button"
                        >
                            Follow on Facebook
                        </a>
                    </div>

                    <div class="mn-film-grid">
                        <a
                            v-for="(vlog, index) in vlogs.slice(0, 3)"
                            :key="vlog.id"
                            :href="vlog.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mn-film-card group"
                            :style="{ animationDelay: `${index * 75}ms` }"
                            @mouseenter="playVlogPreview"
                            @mouseleave="pauseVlogPreview"
                            @focusin="playVlogPreview"
                            @focusout="pauseVlogPreview"
                        >
                            <div class="mn-film-media">
                                <img
                                    :src="vlog.thumbnail"
                                    :alt="vlog.title"
                                    class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-[1.025]"
                                    loading="lazy"
                                />
                                <video
                                    v-if="vlog.preview"
                                    :src="vlog.preview"
                                    muted
                                    playsinline
                                    preload="metadata"
                                    class="absolute inset-0 h-full w-full object-cover opacity-0 transition duration-500 group-hover:opacity-100 group-focus:opacity-100"
                                ></video>
                                <div class="mn-reel-overlay"></div>
                                <span class="mn-film-stamp"
                                    >REEL
                                    {{
                                        String(index + 1).padStart(2, "0")
                                    }}</span
                                >
                                <span class="mn-film-play">PLAY</span>
                            </div>
                            <div class="mn-film-copy">
                                <h3>{{ vlog.title }}</h3>
                                <span>Watch story</span>
                            </div>
                        </a>
                    </div>
                </div>
            </section>
            <!-- HOW TO ORDER -->
            <section id="process" class="mn-process-section scroll-mt-28">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mn-compact-head animated-in">
                        <div>
                            <p class="mn-showroom-kicker">HOW TO BUY</p>
                            <h2>From selection to wrist</h2>
                            <p>
                                A clear three-step process whether you’re buying
                                on-hand or securing a preorder.
                            </p>
                        </div>
                    </div>

                    <div class="mn-process-table">
                        <div
                            v-for="(step, index) in orderSteps"
                            :key="step.title"
                            class="mn-process-row animated-in"
                            :style="{ animationDelay: `${index * 75}ms` }"
                        >
                            <p>{{ step.number }}</p>
                            <h3>{{ step.title }}</h3>
                            <p>{{ step.description }}</p>
                        </div>
                    </div>

                    <div class="mn-trust-strip">
                        <span>ACTUAL PHOTOS</span>
                        <span>TRANSPARENT PRICING</span>
                        <span>AFTER-SALES SUPPORT</span>
                        <span>METRO MANILA + NATIONWIDE</span>
                    </div>
                </div>
            </section>
            <!-- CONTACT -->
            <section id="contact" class="mn-contact-section scroll-mt-28">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mn-contact-shell animated-in">
                        <p class="mn-contact-signature">
                            MONTRE NOVA / TAGUIG, PH
                        </p>

                        <div class="mn-contact-main">
                            <div class="mn-contact-copy">
                                <p class="mn-contact-kicker">STILL DECIDING?</p>
                                <h2>Tell us what you’re looking for.</h2>
                                <p class="mn-contact-intro">
                                    Budget, model, reference, or preferred style
                                    — send it over and we’ll help narrow down
                                    the right piece.
                                </p>
                            </div>

                            <button
                                type="button"
                                class="mn-contact-button"
                                @click="openInquiry()"
                            >
                                <span class="text-gray-900"
                                    >Message Montre Nova</span
                                >
                                <span aria-hidden="true"></span>
                            </button>
                        </div>

                        <!-- Private watch viewing — by appointment -->
                        <div class="mn-viewing-panel">
                            <div class="mn-viewing-details">
                                <p class="mn-viewing-eyebrow">
                                    VISIT MONTRE NOVA
                                </p>
                                <h3>Viewings by appointment.</h3>
                                <p class="mn-viewing-description">
                                    Prefer to see a timepiece in person?
                                    Schedule a viewing with us in Taguig.
                                    Advance arrangements are required.
                                </p>
                                <address class="mn-viewing-address">
                                    Avida Towers One Union Place<br />
                                    ARCA South, South Union Dr, Taguig
                                </address>
                            </div>
                            <button
                                type="button"
                                class="mn-viewing-action"
                                @click="openViewingInquiry"
                            >
                                <span>INQUIRE TO SCHEDULE</span>
                            </button>
                        </div>

                        <div
                            class="mn-social-inline"
                            aria-label="Montre Nova social links"
                        >
                            <a
                                v-for="link in contactLinks"
                                :key="link.label"
                                :href="link.href"
                                target="_blank"
                                rel="noopener noreferrer"
                                :aria-label="`Open Montre Nova on ${link.label}`"
                            >
                                <span class="mn-social-icon" aria-hidden="true">
                                    <svg
                                        v-if="link.icon === 'messenger'"
                                        viewBox="0 0 24 24"
                                        fill="currentColor"
                                    >
                                        <path
                                            d="M12 2.25C6.49 2.25 2.25 6.27 2.25 11.7c0 2.84 1.17 5.28 3.08 6.95v3.1c0 .48.5.8.94.59l3.2-1.5c.81.22 1.66.34 2.53.34 5.51 0 9.75-4.02 9.75-9.45S17.51 2.25 12 2.25Zm1.03 12.5-2.48-2.64-4.84 2.64 5.32-5.65 2.48 2.64 4.79-2.64-5.27 5.65Z"
                                        />
                                    </svg>

                                    <svg
                                        v-else-if="link.icon === 'facebook'"
                                        viewBox="0 0 24 24"
                                        fill="currentColor"
                                    >
                                        <path
                                            d="M13.62 22v-8.91h2.99l.45-3.47h-3.44V7.4c0-1 .28-1.69 1.72-1.69h1.84V2.62c-.32-.04-1.41-.13-2.68-.13-2.65 0-4.47 1.62-4.47 4.59v2.54H7.03v3.47h3V22h3.59Z"
                                        />
                                    </svg>

                                    <svg
                                        v-else-if="link.icon === 'tiktok'"
                                        viewBox="0 0 24 24"
                                        fill="currentColor"
                                    >
                                        <path
                                            d="M12.53.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.72-.03-.5-.04-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.45 3.98-2.14 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.11-.01 2.18-.66 2.75-1.61.18-.33.38-.67.39-1.05.09-1.79.05-3.57.06-5.36.01-4.03-.01-8.05.02-12.07Z"
                                        />
                                    </svg>

                                    <svg
                                        v-else-if="link.icon === 'instagram'"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.9"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <rect
                                            x="3"
                                            y="3"
                                            width="18"
                                            height="18"
                                            rx="5"
                                        />
                                        <circle cx="12" cy="12" r="4.1" />
                                        <circle
                                            cx="17.35"
                                            cy="6.65"
                                            r="1"
                                            fill="currentColor"
                                            stroke="none"
                                        />
                                    </svg>
                                </span>

                                <span>{{ link.label }}</span>
                                <span
                                    class="mn-social-arrow"
                                    aria-hidden="true"
                                ></span>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="mn-footer relative z-10">
            <div
                class="mx-auto flex max-w-7xl flex-col justify-between gap-3 px-4 py-8 text-sm sm:px-6 md:flex-row md:items-center lg:px-8"
            >
                <p>© {{ new Date().getFullYear() }} MONTRE NOVA</p>
            </div>
        </footer>

        <!-- GLOBAL MONTRE NOVA LOADING (INITIAL HERO + INERTIA VISITS) -->
        <Transition name="mn-loading-fade">
            <div
                v-if="showMontreLoader"
                class="mn-loading-screen"
                role="status"
                aria-live="polite"
                :aria-label="loadingLabel"
            >
                <div class="mn-loading-content">
                    <div class="mn-loading-monogram">MN</div>
                    <div class="mn-loading-rule"><span></span></div>
                    <p class="mn-loading-name">MONTRE NOVA</p>
                    <p class="mn-loading-caption">{{ loadingLabel }}</p>
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
@import url("https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap");
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

/* =========================================================
   MONTRE NOVA — MODERN MAGAZINE / EDITORIAL THEME
   Hero carousel intentionally remains cinematic/dark.
========================================================= */

.editorial-section,
.catalog-editorial-section,
.archive-editorial-section,
.process-editorial-section,
.contact-editorial-section {
    position: relative;
}

.editorial-section {
    padding-top: clamp(5.5rem, 9vw, 8.5rem);
    padding-bottom: clamp(5.5rem, 9vw, 8.5rem);
}

.catalog-editorial-section {
    background: #e7e4dd;
    padding-top: clamp(5.5rem, 9vw, 8.5rem);
    padding-bottom: clamp(5.5rem, 9vw, 8.5rem);
}

.archive-editorial-section {
    overflow: hidden;
    background: #111111;
    padding-top: clamp(5.5rem, 9vw, 8.5rem);
    padding-bottom: clamp(5.5rem, 9vw, 8.5rem);
    color: #f7f5f0;
}

.process-editorial-section {
    background: #f8f6f1;
    padding-top: clamp(5.5rem, 9vw, 8.5rem);
    padding-bottom: clamp(5.5rem, 9vw, 8.5rem);
}

.contact-editorial-section {
    overflow: hidden;
    background: #0d0d0d;
    padding-top: clamp(5.5rem, 9vw, 8rem);
    padding-bottom: clamp(5.5rem, 9vw, 8rem);
    color: white;
}

.editorial-heading {
    margin-bottom: clamp(2.75rem, 6vw, 5rem);
}

.editorial-heading-index {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    color: #68645e;
    font-size: 0.64rem;
    font-weight: 900;
    letter-spacing: 0.24em;
    text-transform: uppercase;
}

.editorial-heading-index-dark {
    color: rgb(212 212 216 / 0.68);
}

.editorial-rule {
    display: inline-block;
    width: 2.8rem;
    height: 1px;
    background: rgb(17 17 17 / 0.28);
}

.editorial-rule-dark {
    background: rgb(255 255 255 / 0.25);
}

.editorial-heading-grid {
    display: grid;
    gap: 2rem;
    margin-top: 1.4rem;
}

.editorial-display-title {
    margin: 0;
    max-width: 9ch;
    color: #171717;
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-size: clamp(3.25rem, 8vw, 7rem);
    font-weight: 400;
    line-height: 0.86;
    letter-spacing: -0.065em;
    text-wrap: balance;
}

.editorial-display-title-dark {
    color: #f7f5f0;
}

.editorial-heading-copy {
    max-width: 32rem;
    align-self: end;
    color: #68645e;
    font-size: 0.92rem;
    line-height: 1.85;
}

.editorial-heading-copy-dark {
    color: rgb(212 212 216 / 0.72);
}

.editorial-summary,
.editorial-swipe-label,
.archive-swipe-label {
    margin-top: 1rem;
    font-size: 0.63rem;
    font-weight: 900;
    letter-spacing: 0.18em;
    text-transform: uppercase;
}

.editorial-summary,
.editorial-swipe-label {
    color: #8b867e;
}

.archive-swipe-label {
    margin-bottom: 1rem;
    color: rgb(212 212 216 / 0.52);
}

.editorial-black-button,
.editorial-white-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.7rem;
    min-height: 2.9rem;
    padding: 0.82rem 1.05rem;
    border: 1px solid currentColor;
    border-radius: 0;
    font-size: 0.68rem;
    font-weight: 950;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    transition:
        transform 250ms ease,
        background-color 250ms ease,
        color 250ms ease;
}

.editorial-black-button {
    background: #111;
    color: #fff;
}

.editorial-black-button:hover {
    background: transparent;
    color: #111;
}

.editorial-white-button {
    background: #f7f5f0;
    color: #111;
    border-color: #f7f5f0;
}

.editorial-white-button:hover {
    background: transparent;
    color: #f7f5f0;
}

.editorial-black-button:active,
.editorial-white-button:active {
    transform: scale(0.98);
}

.editorial-text-link {
    display: inline-block;
    border-bottom: 1px solid rgb(17 17 17 / 0.4);
    padding-bottom: 0.2rem;
    color: #171717;
    font-size: 0.72rem;
    font-weight: 900;
    letter-spacing: 0.13em;
    text-transform: uppercase;
}

/* AVAILABLE WATCHES */
.editorial-watch-grid {
    gap: 1.25rem;
}

.editorial-watch-card {
    display: block;
    min-width: 78vw;
    max-width: 78vw;
    scroll-snap-align: start;
    color: #171717;
    text-decoration: none;
    animation: fadeLift 700ms both;
}

.editorial-watch-media {
    position: relative;
    aspect-ratio: 4 / 5;
    overflow: hidden;
    background: #e8e5de;
}

.editorial-badge-row {
    position: absolute;
    top: 0.9rem;
    left: 0.9rem;
    z-index: 2;
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.editorial-badge {
    border: 1px solid rgb(255 255 255 / 0.7);
    background: rgb(247 245 240 / 0.88);
    padding: 0.42rem 0.55rem;
    color: #171717;
    font-size: 0.54rem;
    font-weight: 950;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    backdrop-filter: blur(10px);
}

.editorial-card-number,
.archive-card-number,
.story-editorial-number {
    position: absolute;
    right: 0.9rem;
    top: 0.9rem;
    z-index: 2;
    font-size: 0.65rem;
    font-weight: 950;
    letter-spacing: 0.14em;
}

.editorial-card-number {
    color: rgb(255 255 255 / 0.9);
    text-shadow: 0 1px 12px rgb(0 0 0 / 0.45);
}

.editorial-placeholder-mark,
.archive-placeholder-mark {
    display: grid;
    place-items: center;
    width: 6rem;
    aspect-ratio: 1;
    border: 1px solid currentColor;
    border-radius: 999px;
    font-size: 1.5rem;
    font-weight: 900;
    letter-spacing: -0.08em;
}

.editorial-placeholder-mark {
    color: #8b867e;
}

.editorial-watch-info {
    border-top: 1px solid rgb(17 17 17 / 0.16);
    padding-top: 1rem;
}

.editorial-brand,
.archive-brand,
.story-editorial-copy > p,
.editorial-price-label {
    font-size: 0.58rem;
    font-weight: 950;
    letter-spacing: 0.22em;
    text-transform: uppercase;
}

.editorial-brand {
    color: #68645e;
}

.editorial-watch-name {
    margin-top: 0.45rem;
    color: #171717;
    font-size: 1.3rem;
    font-weight: 750;
    line-height: 1.1;
    letter-spacing: -0.025em;
}

.editorial-reference {
    margin-top: 0.45rem;
    color: #8b867e;
    font-size: 0.76rem;
}

.editorial-arrow {
    color: #171717;
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-size: 1.3rem;
    transition: transform 250ms ease;
}

.editorial-watch-card:hover .editorial-arrow {
    transform: translate(0.2rem, -0.2rem);
}

.editorial-watch-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 1rem;
    margin-top: 1rem;
    color: #77716a;
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.editorial-price-row {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 1.25rem;
    border-top: 1px solid rgb(17 17 17 / 0.1);
    padding-top: 1rem;
}

.editorial-price-label {
    color: #8b867e;
}

.editorial-price {
    margin-top: 0.2rem;
    font-size: 1.15rem;
    font-weight: 900;
    letter-spacing: -0.03em;
}

.editorial-original-price {
    color: #9a958d;
    font-size: 0.7rem;
    text-decoration: line-through;
}

.editorial-details-link {
    border-bottom: 1px solid #171717;
    padding-bottom: 0.1rem;
    font-size: 0.65rem;
    font-weight: 900;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.editorial-pagination {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    gap: 1.25rem;
    margin-top: 3rem;
    border-top: 1px solid rgb(17 17 17 / 0.14);
    border-bottom: 1px solid rgb(17 17 17 / 0.14);
    padding: 1rem 0;
    color: #77716a;
    font-size: 0.78rem;
}

.editorial-page-button {
    display: inline-flex;
    min-height: 2.35rem;
    align-items: center;
    justify-content: center;
    border: 1px solid rgb(17 17 17 / 0.18);
    padding: 0.55rem 0.8rem;
    color: #55514b;
    font-size: 0.72rem;
    font-weight: 800;
    transition: 220ms ease;
}

.editorial-page-button:hover,
.editorial-page-button.is-active {
    border-color: #111;
    background: #111;
    color: white;
}

.editorial-page-button.is-disabled {
    cursor: not-allowed;
    opacity: 0.35;
}

.editorial-empty-state {
    border-top: 1px solid rgb(17 17 17 / 0.16);
    border-bottom: 1px solid rgb(17 17 17 / 0.16);
    padding: 4rem 1rem;
    text-align: center;
}

.editorial-empty-state h3 {
    margin-top: 1.25rem;
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-size: 2rem;
    font-weight: 400;
}

.editorial-empty-state > p:not(.editorial-heading-index) {
    max-width: 30rem;
    margin: 1rem auto 1.5rem;
    color: #77716a;
    line-height: 1.8;
}

/* CATALOG INDEX */
.catalog-editorial-grid {
    display: grid;
    gap: 2.25rem 1.25rem;
}

.catalog-editorial-card {
    width: 100%;
    border-top: 1px solid rgb(17 17 17 / 0.2);
    padding-top: 0.8rem;
    animation: fadeLift 700ms both;
}

.catalog-editorial-media {
    aspect-ratio: 4 / 3;
    overflow: hidden;
    background: #d9d6cf;
}

.catalog-editorial-copy {
    padding-top: 1rem;
}

.catalog-editorial-number,
.catalog-editorial-action {
    color: #77716a;
    font-size: 0.58rem;
    font-weight: 900;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

.catalog-editorial-category {
    margin-top: 1.2rem;
    color: #171717;
    font-size: 0.65rem;
    font-weight: 950;
    letter-spacing: 0.22em;
    text-transform: uppercase;
}

.catalog-editorial-card h3 {
    margin-top: 0.35rem;
    color: #171717;
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-size: 1.65rem;
    font-weight: 400;
    line-height: 1.05;
    letter-spacing: -0.04em;
}

.catalog-editorial-ref {
    margin-top: 0.55rem;
    color: #77716a;
    font-size: 0.72rem;
}

/* DARK ARCHIVE */
.archive-watch-card {
    min-width: 78vw;
    max-width: 78vw;
    scroll-snap-align: start;
    color: #f7f5f0;
    animation: fadeLift 700ms both;
}

.archive-watch-media {
    position: relative;
    aspect-ratio: 4 / 5;
    overflow: hidden;
    background: #1b1b1b;
}

.archive-sold-label {
    position: absolute;
    left: 0.9rem;
    top: 0.9rem;
    z-index: 2;
    border: 1px solid rgb(255 255 255 / 0.34);
    background: rgb(0 0 0 / 0.35);
    padding: 0.42rem 0.62rem;
    font-size: 0.55rem;
    font-weight: 950;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    backdrop-filter: blur(8px);
}

.archive-card-number {
    color: rgb(255 255 255 / 0.82);
}

.archive-placeholder-mark {
    color: rgb(255 255 255 / 0.42);
}

.archive-watch-copy {
    border-top: 1px solid rgb(255 255 255 / 0.16);
    padding-top: 1rem;
}

.archive-brand {
    color: rgb(212 212 216 / 0.6);
}

.archive-watch-copy h3 {
    margin-top: 0.45rem;
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-size: 1.6rem;
    font-weight: 400;
    line-height: 1.04;
    letter-spacing: -0.035em;
}

.archive-ref {
    margin-top: 0.5rem;
    color: rgb(212 212 216 / 0.55);
    font-size: 0.72rem;
}

.archive-meta-row {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 0.5rem 1rem;
    margin-top: 1rem;
    color: rgb(212 212 216 / 0.55);
    font-size: 0.58rem;
    font-weight: 850;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.archive-source-button {
    display: flex;
    width: 100%;
    align-items: center;
    justify-content: space-between;
    margin-top: 1.15rem;
    border-top: 1px solid rgb(255 255 255 / 0.15);
    padding-top: 0.9rem;
    color: #f7f5f0;
    font-size: 0.65rem;
    font-weight: 900;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

/* STORIES */
.story-editorial-grid {
    gap: 1.25rem;
}

.story-editorial-card {
    display: block;
    min-width: 78vw;
    max-width: 78vw;
    scroll-snap-align: start;
    color: #171717;
    text-decoration: none;
    animation: fadeLift 700ms both;
}

.story-editorial-media {
    position: relative;
    aspect-ratio: 4 / 5;
    overflow: hidden;
    background: #dedbd4;
}

.story-editorial-number {
    color: white;
    text-shadow: 0 1px 12px rgb(0 0 0 / 0.45);
}

.story-editorial-play {
    position: absolute;
    left: 0.9rem;
    bottom: 0.9rem;
    z-index: 2;
    background: rgb(247 245 240 / 0.9);
    padding: 0.48rem 0.62rem;
    color: #171717;
    font-size: 0.57rem;
    font-weight: 950;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    backdrop-filter: blur(8px);
}

.story-editorial-copy {
    border-top: 1px solid rgb(17 17 17 / 0.16);
    padding-top: 1rem;
}

.story-editorial-copy > p {
    color: #77716a;
}

.story-editorial-copy h3 {
    margin-top: 0.45rem;
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-size: 1.75rem;
    font-weight: 400;
    line-height: 1.02;
    letter-spacing: -0.04em;
}

.story-editorial-footer {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 1rem;
    color: #77716a;
    font-size: 0.72rem;
    line-height: 1.55;
}

.story-editorial-footer > span:first-child {
    max-width: 90%;
}

/* HOW TO ORDER */
.process-editorial-list {
    border-top: 1px solid rgb(17 17 17 / 0.2);
}

.process-editorial-row {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 1rem;
    align-items: start;
    border-bottom: 1px solid rgb(17 17 17 / 0.2);
    padding: 1.4rem 0;
}

.process-editorial-number {
    color: #8b867e;
    font-size: 0.65rem;
    font-weight: 950;
    letter-spacing: 0.14em;
}

.process-editorial-row h3 {
    color: #171717;
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-size: 1.8rem;
    font-weight: 400;
    line-height: 1;
    letter-spacing: -0.04em;
}

.process-editorial-description {
    grid-column: 2 / -1;
    max-width: 44rem;
    color: #68645e;
    font-size: 0.84rem;
    line-height: 1.8;
}

.process-editorial-mark {
    color: #171717;
    font-size: 1.1rem;
}

/* CONTACT / BACK COVER */
.contact-editorial-grid {
    display: grid;
    gap: 3.5rem;
}

.contact-editorial-overline {
    margin-top: 2rem;
    color: rgb(212 212 216 / 0.58);
    font-size: 0.65rem;
    font-weight: 900;
    letter-spacing: 0.18em;
    text-transform: uppercase;
}

.contact-editorial-grid h2 {
    margin-top: 0.65rem;
    color: #f7f5f0;
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-size: clamp(3.4rem, 8vw, 7.5rem);
    font-weight: 400;
    line-height: 0.86;
    letter-spacing: -0.07em;
}

.contact-editorial-intro {
    max-width: 35rem;
    margin-top: 1.5rem;
    color: rgb(212 212 216 / 0.66);
    font-size: 0.9rem;
    line-height: 1.85;
}

.contact-editorial-links {
    border-top: 1px solid rgb(255 255 255 / 0.18);
}

.contact-editorial-link {
    display: flex;
    align-items: center;
    gap: 1rem;
    border-bottom: 1px solid rgb(255 255 255 / 0.18);
    padding: 1rem 0;
    color: white;
}

.contact-editorial-icon {
    display: grid;
    place-items: center;
    width: 2.25rem;
    height: 2.25rem;
    border: 1px solid rgb(255 255 255 / 0.25);
    border-radius: 999px;
    font-size: 0.58rem;
    font-weight: 950;
}

.contact-editorial-link strong {
    display: block;
    font-size: 0.9rem;
    font-weight: 850;
}

.contact-editorial-link small {
    display: block;
    margin-top: 0.2rem;
    color: rgb(212 212 216 / 0.52);
    font-size: 0.68rem;
    line-height: 1.45;
}

.contact-editorial-arrow {
    color: rgb(255 255 255 / 0.7);
    font-size: 1.2rem;
    transition: transform 250ms ease;
}

.contact-editorial-link:hover .contact-editorial-arrow {
    transform: translate(0.18rem, -0.18rem);
}

.editorial-footer {
    border-top: 1px solid rgb(17 17 17 / 0.14);
    background: #f3f1ec;
    color: #77716a;
}

/* Make old generic section typography compatible with the light shell
   if it appears in an empty/fallback state. */
.section-kicker,
.brand-kicker {
    color: #77716a;
}

.section-title {
    color: #171717;
}

.empty-state {
    border-color: rgb(17 17 17 / 0.14);
    background: #f8f6f1;
    color: #171717;
    box-shadow: none;
}

@media (min-width: 640px) {
    .editorial-watch-card,
    .story-editorial-card {
        min-width: 360px;
        max-width: 360px;
    }

    .archive-watch-card {
        min-width: 340px;
        max-width: 340px;
    }

    .catalog-editorial-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .editorial-pagination {
        flex-direction: row;
    }
}

@media (min-width: 768px) {
    .editorial-heading-grid {
        grid-template-columns: minmax(0, 1.35fr) minmax(18rem, 0.65fr);
        align-items: end;
    }

    .editorial-watch-card,
    .story-editorial-card {
        min-width: 0;
        max-width: none;
    }

    .process-editorial-row {
        grid-template-columns: 4rem minmax(10rem, 0.65fr) minmax(0, 1.35fr) auto;
        align-items: center;
        gap: 1.5rem;
        padding: 1.7rem 0;
    }

    .process-editorial-description {
        grid-column: auto;
    }
}

@media (min-width: 1024px) {
    .catalog-editorial-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .contact-editorial-grid {
        grid-template-columns: minmax(0, 1.2fr) minmax(22rem, 0.8fr);
        align-items: end;
        gap: 5rem;
    }
}

@media (max-width: 767px) {
    header {
        background: rgb(248 246 241 / 0.94) !important;
        border-bottom-color: rgb(17 17 17 / 0.1) !important;
    }

    .editorial-display-title {
        font-size: clamp(3rem, 17vw, 4.7rem);
    }

    .editorial-watch-card,
    .archive-watch-card,
    .story-editorial-card {
        min-width: 78vw;
        max-width: 78vw;
    }

    .editorial-heading-copy {
        font-size: 0.84rem;
    }
}

/* =========================================================
   MONTRE NOVA — MODERN HOROLOGY JOURNAL
   Distinctive cool-monochrome system for Sections 2 onward.
   HERO rules above remain unchanged.
   ========================================================= */

.mn-journal-section,
.mn-film-section,
.mn-process-section {
    position: relative;
    background: #f5f5f2;
    padding: clamp(5rem, 9vw, 9rem) 0;
}

.mn-index-section {
    position: relative;
    background: #e8e8e4;
    padding: clamp(5rem, 9vw, 9rem) 0;
}

.mn-section-head {
    margin-bottom: clamp(2.8rem, 6vw, 5.5rem);
}

.mn-index-line {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 1rem;
    border-top: 1px solid rgb(17 17 17 / 0.34);
    padding-top: 0.7rem;
    color: #5f5f5b;
    font-family:
        ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,
        "Liberation Mono", monospace;
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

.mn-index-line > span:nth-child(2) {
    text-align: center;
}

.mn-index-line.is-dark {
    border-top-color: rgb(255 255 255 / 0.3);
    color: rgb(255 255 255 / 0.58);
}

.mn-section-title-grid {
    display: grid;
    gap: 2rem;
    margin-top: clamp(2rem, 5vw, 4rem);
}

.mn-display-title {
    margin: 0;
    color: #111;
    font-family: inherit;
    font-size: clamp(3.25rem, 8.4vw, 7.7rem);
    font-weight: 800;
    line-height: 0.83;
    letter-spacing: -0.078em;
}

.mn-display-title.is-dark {
    color: #f5f5f2;
}

.mn-section-copy {
    max-width: 31rem;
    color: #60605c;
    font-size: 0.9rem;
    line-height: 1.85;
}

.mn-section-copy.is-dark {
    color: rgb(255 255 255 / 0.58);
}

.mn-micro-meta,
.mn-mobile-hint,
.mn-archive-hint {
    margin-top: 1.25rem;
    font-family:
        ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.11em;
    text-transform: uppercase;
}

.mn-micro-meta,
.mn-mobile-hint {
    color: #7a7a75;
}

.mn-solid-button,
.mn-outline-button,
.mn-light-outline-button,
.mn-contact-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2.8rem;
    padding: 0.75rem 1rem;
    font-family:
        ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.64rem;
    font-weight: 900;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    transition: 220ms ease;
}

.mn-solid-button {
    background: #111;
    color: white;
}

.mn-outline-button {
    border: 1px solid #111;
    color: #111;
}

.mn-outline-button:hover,
.mn-solid-button:hover {
    background: #2a2a28;
    color: white;
}

.mn-light-outline-button,
.mn-contact-button {
    border: 1px solid rgb(255 255 255 / 0.5);
    color: #f5f5f2;
}

.mn-light-outline-button:hover,
.mn-contact-button:hover {
    background: #f5f5f2;
    color: #111;
}

/* CURRENT SELECTION — photographic plates, not cards */
.mn-selection-list {
    border-top: 1px solid rgb(17 17 17 / 0.28);
}

.mn-selection-item {
    display: grid;
    gap: 0;
    border-bottom: 1px solid rgb(17 17 17 / 0.28);
    padding: clamp(1.1rem, 2vw, 1.5rem) 0;
    color: #111;
    text-decoration: none;
    animation: fadeLift 700ms both;
}

.mn-selection-media {
    position: relative;
    min-height: 28rem;
    overflow: hidden;
    background: #e8e8e4;
}

.mn-selection-copy {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 23rem;
    padding: 1.5rem 0 0;
}

.mn-product-code,
.mn-photo-index,
.mn-price-caption,
.mn-ref-line,
.mn-status-strip,
.mn-spec-line,
.mn-view-piece {
    font-family:
        ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    text-transform: uppercase;
}

.mn-product-code {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    border-bottom: 1px solid rgb(17 17 17 / 0.18);
    padding-bottom: 0.7rem;
    color: #777772;
    font-size: 0.58rem;
    font-weight: 800;
    letter-spacing: 0.11em;
}

.mn-brand-label {
    margin-top: 2rem;
    color: #6f6f6a;
    font-size: 0.66rem;
    font-weight: 900;
    letter-spacing: 0.22em;
    text-transform: uppercase;
}

.mn-selection-copy h3 {
    margin-top: 0.45rem;
    color: #111;
    font-size: clamp(2.35rem, 5vw, 5rem);
    font-weight: 780;
    line-height: 0.9;
    letter-spacing: -0.06em;
}

.mn-ref-line {
    margin-top: 0.75rem;
    color: #777772;
    font-size: 0.64rem;
    font-weight: 800;
    letter-spacing: 0.08em;
}

.mn-spec-line {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 1.4rem;
    margin-top: 1.5rem;
    color: #656560;
    font-size: 0.6rem;
    font-weight: 800;
    letter-spacing: 0.08em;
}

.mn-price-block {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 2rem;
    border-top: 1px solid rgb(17 17 17 / 0.22);
    padding-top: 1rem;
}

.mn-price-caption {
    color: #787873;
    font-size: 0.56rem;
    font-weight: 850;
    letter-spacing: 0.12em;
}

.mn-price-value {
    margin-top: 0.25rem;
    font-size: clamp(1.45rem, 2.4vw, 2.3rem);
    font-weight: 850;
    letter-spacing: -0.045em;
}

.mn-price-original {
    margin-top: 0.2rem;
    color: #8a8a85;
    font-size: 0.66rem;
    text-decoration: line-through;
}

.mn-view-piece {
    border-bottom: 1px solid #111;
    padding-bottom: 0.15rem;
    font-size: 0.62rem;
    font-weight: 900;
    letter-spacing: 0.1em;
}

.mn-photo-index {
    position: absolute;
    left: 1rem;
    top: 1rem;
    z-index: 3;
    background: rgb(245 245 242 / 0.92);
    padding: 0.45rem 0.55rem;
    color: #111;
    font-size: 0.57rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    backdrop-filter: blur(8px);
}

.mn-status-strip {
    position: absolute;
    right: 1rem;
    top: 1rem;
    z-index: 3;
    display: flex;
    gap: 0.4rem;
}

.mn-status-strip span {
    border: 1px solid rgb(255 255 255 / 0.55);
    background: rgb(17 17 17 / 0.54);
    padding: 0.45rem 0.55rem;
    color: white;
    font-size: 0.54rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    backdrop-filter: blur(8px);
}

.mn-placeholder-mark {
    display: grid;
    height: 100%;
    place-items: center;
    color: #aaa9a3;
    font-size: 3rem;
    font-weight: 900;
    letter-spacing: -0.08em;
}

.mn-pagination {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    justify-content: space-between;
    margin-top: 2.5rem;
    border-bottom: 1px solid rgb(17 17 17 / 0.28);
    padding-bottom: 1rem;
    color: #73736e;
    font-family:
        ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.64rem;
    text-transform: uppercase;
}

.mn-page-button {
    display: inline-flex;
    min-height: 2.3rem;
    align-items: center;
    justify-content: center;
    border: 1px solid rgb(17 17 17 / 0.26);
    padding: 0.5rem 0.75rem;
    color: #111;
}

.mn-page-button.is-active,
.mn-page-button:hover {
    background: #111;
    color: white;
}

.mn-page-button.is-disabled {
    opacity: 0.3;
}

.mn-empty-state {
    border-top: 1px solid rgb(17 17 17 / 0.28);
    border-bottom: 1px solid rgb(17 17 17 / 0.28);
    padding: 4rem 0;
    text-align: center;
}

.mn-empty-state > p:first-child {
    color: #777772;
    font-family: ui-monospace, monospace;
    font-size: 0.62rem;
}

.mn-empty-state h3 {
    margin-top: 1rem;
    font-size: clamp(2rem, 5vw, 4rem);
    font-weight: 800;
    letter-spacing: -0.055em;
}

.mn-empty-state > p:nth-of-type(2) {
    max-width: 30rem;
    margin: 1rem auto 1.5rem;
    color: #666661;
}

/* REFERENCE INDEX */
.mn-reference-list {
    border-top: 1px solid rgb(17 17 17 / 0.34);
}

.mn-reference-row {
    display: grid;
    grid-template-columns: 2rem 5.2rem minmax(0, 1fr) auto;
    gap: 1rem;
    align-items: center;
    width: 100%;
    border-bottom: 1px solid rgb(17 17 17 / 0.28);
    padding: 0.85rem 0;
    color: #111;
    text-align: left;
    animation: fadeLift 700ms both;
}

.mn-reference-number,
.mn-reference-code,
.mn-reference-action,
.mn-reference-name > p {
    font-family:
        ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.58rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.mn-reference-number,
.mn-reference-code,
.mn-reference-name > p {
    color: #70706b;
}

.mn-reference-thumb {
    display: grid;
    aspect-ratio: 1 / 1;
    overflow: hidden;
    place-items: center;
    background: #d7d7d2;
}

.mn-reference-name h3 {
    margin-top: 0.2rem;
    font-size: 1.15rem;
    font-weight: 800;
    letter-spacing: -0.035em;
}

.mn-reference-code {
    display: none;
    line-height: 1.55;
}

.mn-reference-action {
    white-space: nowrap;
    border-bottom: 1px solid transparent;
    padding-bottom: 0.1rem;
}

.mn-reference-row:hover .mn-reference-action {
    border-bottom-color: #111;
}

/* CLIENT ARCHIVE */
.mn-archive-section,
.mn-contact-section {
    position: relative;
    background: #101010;
    color: #f5f5f2;
    padding: clamp(5rem, 9vw, 9rem) 0;
}

.mn-archive-hint {
    color: rgb(255 255 255 / 0.48);
}

.mn-archive-grid {
    display: flex;
    gap: 0.8rem;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    padding-bottom: 0.5rem;
    scrollbar-width: none;
}

.mn-archive-grid::-webkit-scrollbar {
    display: none;
}

.mn-archive-piece {
    min-width: 72vw;
    max-width: 72vw;
    scroll-snap-align: start;
    animation: fadeLift 700ms both;
}

.mn-archive-media {
    position: relative;
    aspect-ratio: 4 / 5;
    overflow: hidden;
    background: #1d1d1d;
}

.mn-archive-count,
.mn-archive-sold {
    position: absolute;
    top: 0.8rem;
    z-index: 3;
    background: rgb(16 16 16 / 0.62);
    padding: 0.4rem 0.5rem;
    color: white;
    font-family: ui-monospace, monospace;
    font-size: 0.55rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    backdrop-filter: blur(8px);
}

.mn-archive-count {
    left: 0.8rem;
}
.mn-archive-sold {
    right: 0.8rem;
}

.mn-archive-placeholder {
    display: grid;
    height: 100%;
    place-items: center;
    color: rgb(255 255 255 / 0.25);
    font-size: 2.8rem;
    font-weight: 900;
}

.mn-archive-copy {
    border-top: 1px solid rgb(255 255 255 / 0.24);
    padding-top: 0.85rem;
}

.mn-archive-copy > p:first-child {
    color: rgb(255 255 255 / 0.48);
    font-size: 0.58rem;
    font-weight: 900;
    letter-spacing: 0.18em;
    text-transform: uppercase;
}

.mn-archive-copy h3 {
    margin-top: 0.3rem;
    font-size: 1.45rem;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -0.045em;
}

.mn-archive-info {
    display: flex;
    justify-content: space-between;
    gap: 0.8rem;
    margin-top: 0.7rem;
    color: rgb(255 255 255 / 0.45);
    font-family: ui-monospace, monospace;
    font-size: 0.54rem;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.mn-archive-source {
    margin-top: 0.9rem;
    border-bottom: 1px solid rgb(255 255 255 / 0.55);
    padding-bottom: 0.12rem;
    color: white;
    font-family: ui-monospace, monospace;
    font-size: 0.58rem;
    font-weight: 900;
    letter-spacing: 0.08em;
}

.mn-archive-footerline {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 2.4rem;
    border-top: 1px solid rgb(255 255 255 / 0.3);
    padding-top: 0.7rem;
    color: rgb(255 255 255 / 0.48);
    font-family: ui-monospace, monospace;
    font-size: 0.58rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

/* MN FILM */
.mn-film-grid {
    display: grid;
    gap: 2.5rem 1rem;
}

.mn-film-card {
    color: #111;
    text-decoration: none;
    animation: fadeLift 700ms both;
}

.mn-film-media {
    position: relative;
    aspect-ratio: 4 / 5;
    overflow: hidden;
    background: #e1e1dd;
}

.mn-film-stamp,
.mn-film-play {
    position: absolute;
    z-index: 3;
    bottom: 0.8rem;
    background: rgb(245 245 242 / 0.91);
    padding: 0.42rem 0.5rem;
    color: #111;
    font-family: ui-monospace, monospace;
    font-size: 0.55rem;
    font-weight: 900;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    backdrop-filter: blur(8px);
}

.mn-film-stamp {
    left: 0.8rem;
}
.mn-film-play {
    right: 0.8rem;
}

.mn-film-copy {
    border-top: 1px solid rgb(17 17 17 / 0.28);
    padding-top: 0.8rem;
}

.mn-film-code {
    display: flex;
    justify-content: space-between;
    color: #71716c;
    font-family: ui-monospace, monospace;
    font-size: 0.56rem;
    font-weight: 800;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.mn-film-copy h3 {
    margin-top: 0.65rem;
    font-size: clamp(1.7rem, 3vw, 2.7rem);
    font-weight: 800;
    line-height: 0.98;
    letter-spacing: -0.052em;
}

.mn-film-copy > p {
    margin-top: 0.7rem;
    color: #666661;
    font-size: 0.78rem;
    line-height: 1.6;
}

.mn-text-link {
    display: inline-flex;
    border-bottom: 1px solid #111;
    padding-bottom: 0.1rem;
    color: #111;
    font-family: ui-monospace, monospace;
    font-size: 0.62rem;
    font-weight: 900;
    letter-spacing: 0.08em;
}

/* ORDER TABLE */
.mn-process-table {
    border-top: 1px solid rgb(17 17 17 / 0.32);
}

.mn-process-row {
    display: grid;
    grid-template-columns: 2.5rem minmax(0, 1fr) auto;
    gap: 1rem;
    align-items: start;
    border-bottom: 1px solid rgb(17 17 17 / 0.32);
    padding: 1.3rem 0;
}

.mn-process-row > p:first-child {
    color: #777772;
    font-family: ui-monospace, monospace;
    font-size: 0.62rem;
    font-weight: 900;
}

.mn-process-row h3 {
    font-size: 1.6rem;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -0.04em;
}

.mn-process-row > p:nth-child(3) {
    grid-column: 2 / -1;
    max-width: 48rem;
    color: #62625d;
    font-size: 0.82rem;
    line-height: 1.75;
}

.mn-process-row > span {
    font-family: ui-monospace, monospace;
}

/* CONTACT / BACK COVER */
.mn-contact-grid {
    display: grid;
    gap: 4rem;
}

.mn-contact-kicker,
.mn-directory-label {
    margin-top: 2rem;
    color: rgb(255 255 255 / 0.48);
    font-family: ui-monospace, monospace;
    font-size: 0.6rem;
    font-weight: 850;
    letter-spacing: 0.11em;
    text-transform: uppercase;
}

.mn-contact-grid h2 {
    margin-top: 0.7rem;
    color: #f5f5f2;
    font-size: clamp(3.5rem, 9vw, 8.4rem);
    font-weight: 820;
    line-height: 0.82;
    letter-spacing: -0.085em;
}

.mn-contact-intro {
    max-width: 36rem;
    margin-top: 1.6rem;
    color: rgb(255 255 255 / 0.58);
    font-size: 0.9rem;
    line-height: 1.85;
}

.mn-contact-directory {
    border-top: 1px solid rgb(255 255 255 / 0.3);
}

.mn-directory-label {
    margin: 0;
    padding: 0.7rem 0;
    border-bottom: 1px solid rgb(255 255 255 / 0.2);
}

.mn-contact-row {
    display: grid;
    grid-template-columns: 2rem 1fr auto;
    gap: 1rem;
    align-items: center;
    border-bottom: 1px solid rgb(255 255 255 / 0.2);
    padding: 1rem 0;
    color: white;
}

.mn-contact-row > span:first-child {
    color: rgb(255 255 255 / 0.4);
    font-family: ui-monospace, monospace;
    font-size: 0.58rem;
}

.mn-contact-row strong {
    display: block;
    font-size: 0.92rem;
    font-weight: 800;
}

.mn-contact-row small {
    display: block;
    margin-top: 0.2rem;
    color: rgb(255 255 255 / 0.45);
    font-size: 0.67rem;
    line-height: 1.45;
}

.mn-contact-arrow {
    color: rgb(255 255 255 / 0.7);
    transition: transform 220ms ease;
}

.mn-contact-row:hover .mn-contact-arrow {
    transform: translate(0.15rem, -0.15rem);
}

.mn-footer {
    border-top: 1px solid rgb(17 17 17 / 0.22);
    background: #e8e8e4;
    color: #656560;
    font-family:
        ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.62rem;
    font-weight: 750;
    letter-spacing: 0.07em;
    text-transform: uppercase;
}

@media (min-width: 640px) {
    .mn-pagination {
        flex-direction: row;
        align-items: center;
    }
    .mn-archive-piece {
        min-width: 22rem;
        max-width: 22rem;
    }
    .mn-film-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (min-width: 768px) {
    .mn-section-title-grid {
        grid-template-columns: minmax(0, 1.35fr) minmax(18rem, 0.65fr);
        align-items: end;
    }

    .mn-selection-item {
        grid-template-columns: minmax(0, 1.18fr) minmax(20rem, 0.82fr);
        min-height: 40rem;
    }

    .mn-selection-item.is-reversed {
        grid-template-columns: minmax(20rem, 0.82fr) minmax(0, 1.18fr);
    }

    .mn-selection-item.is-reversed .mn-selection-media {
        order: 2;
    }
    .mn-selection-item.is-reversed .mn-selection-copy {
        order: 1;
        padding-left: 0;
        padding-right: clamp(2rem, 6vw, 5rem);
    }

    .mn-selection-media {
        min-height: 40rem;
    }

    .mn-selection-copy {
        min-height: 40rem;
        padding: 1rem 0 1rem clamp(2rem, 6vw, 5rem);
    }

    .mn-reference-row {
        grid-template-columns:
            2.5rem 6.5rem minmax(12rem, 1fr) minmax(11rem, 0.65fr)
            auto;
    }

    .mn-reference-code {
        display: block;
    }

    .mn-process-row {
        grid-template-columns: 4rem minmax(12rem, 0.65fr) minmax(0, 1.35fr) auto;
        align-items: center;
        gap: 1.5rem;
        padding: 1.65rem 0;
    }

    .mn-process-row > p:nth-child(3) {
        grid-column: auto;
    }
}

@media (min-width: 1024px) {
    .mn-archive-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        overflow: visible;
    }

    .mn-archive-piece {
        min-width: 0;
        max-width: none;
    }

    .mn-film-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .mn-contact-grid {
        grid-template-columns: minmax(0, 1.25fr) minmax(23rem, 0.75fr);
        align-items: end;
        gap: 6rem;
    }
}

@media (max-width: 767px) {
    header {
        background: rgb(248 248 245 / 0.95) !important;
        border-bottom-color: rgb(17 17 17 / 0.1) !important;
    }

    .mn-index-line {
        grid-template-columns: auto 1fr;
    }

    .mn-index-line > span:nth-child(2) {
        text-align: right;
    }
    .mn-index-line > span:nth-child(3) {
        display: none;
    }

    .mn-display-title {
        font-size: clamp(3.15rem, 17vw, 5rem);
    }

    .mn-selection-copy h3 {
        font-size: clamp(2.45rem, 12vw, 3.8rem);
    }

    .mn-reference-row {
        grid-template-columns: 1.7rem 4.7rem minmax(0, 1fr);
    }

    .mn-reference-action,
    .mn-reference-code {
        display: none;
    }
}

/* =========================================================
   UX REVISION — WATCH CARD GRID + RESTRAINED SECTION TYPE
   ========================================================= */

/* Keep the MN indexing system, but make section headlines feel like
   editorial subheads rather than full-screen poster typography. */
.mn-section-head {
    margin-bottom: clamp(2.2rem, 4vw, 3.75rem);
}

.mn-section-title-grid {
    margin-top: clamp(1.5rem, 3vw, 2.5rem);
}

.mn-display-title {
    max-width: 13ch;
    font-size: clamp(2.65rem, 5.2vw, 4.9rem);
    line-height: 0.94;
    letter-spacing: -0.055em;
}

.mn-section-copy {
    font-size: 0.88rem;
    line-height: 1.75;
}

.mn-contact-grid h2 {
    max-width: 10ch;
    font-size: clamp(3rem, 5.8vw, 5.4rem);
    line-height: 0.92;
    letter-spacing: -0.06em;
}

/* AVAILABLE — proper scan-friendly watch cards */
.mn-watch-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.8rem;
}

.mn-watch-card {
    min-width: 0;
    overflow: hidden;
    border: 1px solid rgb(17 17 17 / 0.12);
    background: #fbfbf8;
    color: #111;
    text-decoration: none;
    box-shadow: 0 1px 0 rgb(255 255 255 / 0.8) inset;
    animation: fadeLift 650ms both;
    transition:
        transform 260ms ease,
        border-color 260ms ease,
        box-shadow 260ms ease;
}

.mn-watch-card:hover {
    transform: translateY(-3px);
    border-color: rgb(17 17 17 / 0.28);
    box-shadow: 0 18px 45px rgb(17 17 17 / 0.08);
}

.mn-watch-card:active {
    transform: translateY(-1px) scale(0.995);
}

.mn-watch-card:focus-visible {
    outline: 2px solid #111;
    outline-offset: 3px;
}

.mn-watch-media {
    position: relative;
    aspect-ratio: 4 / 5;
    overflow: hidden;
    background: #e8e8e4;
}

.mn-watch-placeholder {
    display: grid;
    place-items: center;
    width: 4.25rem;
    aspect-ratio: 1;
    border: 1px solid rgb(17 17 17 / 0.35);
    border-radius: 999px;
    color: #777772;
    font-size: 1.15rem;
    font-weight: 900;
    letter-spacing: -0.08em;
}

.mn-watch-badges {
    position: absolute;
    left: 0.55rem;
    top: 0.55rem;
    z-index: 2;
    display: flex;
    max-width: calc(100% - 3.4rem);
    flex-wrap: wrap;
    gap: 0.3rem;
}

.mn-watch-badge {
    border: 1px solid rgb(255 255 255 / 0.72);
    background: rgb(248 248 245 / 0.9);
    padding: 0.34rem 0.45rem;
    color: #222;
    font-family:
        ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.47rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    backdrop-filter: blur(10px);
}

.mn-watch-index {
    position: absolute;
    right: 0.55rem;
    top: 0.6rem;
    z-index: 2;
    color: rgb(255 255 255 / 0.94);
    font-family:
        ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.5rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-shadow: 0 1px 8px rgb(0 0 0 / 0.42);
}

.mn-watch-body {
    display: flex;
    min-height: 12rem;
    flex-direction: column;
    padding: 0.85rem;
}

.mn-watch-brand {
    color: #696965;
    font-size: 0.53rem;
    font-weight: 900;
    letter-spacing: 0.18em;
    text-transform: uppercase;
}

.mn-watch-heading h3 {
    display: -webkit-box;
    overflow: hidden;
    margin-top: 0.35rem;
    color: #111;
    font-size: 1rem;
    font-weight: 800;
    line-height: 1.12;
    letter-spacing: -0.025em;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.mn-watch-reference {
    overflow: hidden;
    margin-top: 0.35rem;
    color: #7b7b76;
    font-family:
        ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.54rem;
    font-weight: 700;
    letter-spacing: 0.035em;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.mn-watch-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    margin-top: 0.75rem;
}

.mn-watch-tags span {
    border: 1px solid rgb(17 17 17 / 0.12);
    padding: 0.28rem 0.4rem;
    color: #686864;
    font-size: 0.5rem;
    font-weight: 800;
    line-height: 1;
    letter-spacing: 0.055em;
    text-transform: uppercase;
}

.mn-watch-price-row {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 0.5rem;
    margin-top: auto;
    border-top: 1px solid rgb(17 17 17 / 0.1);
    padding-top: 0.75rem;
}

.mn-watch-price {
    color: #111;
    font-size: 1rem;
    font-weight: 900;
    letter-spacing: -0.035em;
}

.mn-watch-original-price {
    margin-top: 0.1rem;
    color: #92928d;
    font-size: 0.58rem;
    text-decoration: line-through;
}

.mn-watch-details {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    color: #333330;
    font-family:
        ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.52rem;
    font-weight: 900;
    letter-spacing: 0.045em;
    text-transform: uppercase;
}

.mn-watch-details span {
    transition: transform 220ms ease;
}

.mn-watch-card:hover .mn-watch-details span {
    transform: translateX(0.18rem);
}

@media (min-width: 640px) {
    .mn-watch-grid {
        gap: 1rem;
    }

    .mn-watch-body {
        min-height: 13rem;
        padding: 1rem;
    }

    .mn-watch-heading h3 {
        font-size: 1.18rem;
    }

    .mn-watch-price {
        font-size: 1.1rem;
    }
}

@media (min-width: 768px) {
    .mn-watch-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1.1rem;
    }

    .mn-watch-heading h3 {
        font-size: 1.3rem;
    }

    .mn-watch-badge,
    .mn-watch-index {
        font-size: 0.54rem;
    }
}

@media (min-width: 1280px) {
    .mn-watch-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1.2rem;
    }
}

@media (max-width: 767px) {
    .mn-display-title {
        max-width: 12ch;
        font-size: clamp(2.35rem, 10.8vw, 3.45rem);
        line-height: 0.96;
        letter-spacing: -0.045em;
    }

    .mn-section-copy {
        font-size: 0.82rem;
    }

    .mn-contact-grid h2 {
        font-size: clamp(2.8rem, 13vw, 4.1rem);
        line-height: 0.92;
        letter-spacing: -0.055em;
    }

    .mn-watch-tags span:nth-child(n + 2) {
        display: none;
    }
}

/* =========================================================
   MONTRE NOVA — PREMIUM DIGITAL WATCH SHOWROOM
   Product-first retail system. Hero remains untouched.
   ========================================================= */

.mn-journal-section,
.mn-index-section,
.mn-film-section,
.mn-process-section {
    background: #f2f3f3;
    padding: clamp(4.25rem, 7vw, 6.75rem) 0;
}

.mn-index-section {
    background: #e9ebeb;
    border-top: 1px solid rgb(16 17 18 / 0.08);
    border-bottom: 1px solid rgb(16 17 18 / 0.08);
}

.mn-film-section,
.mn-process-section {
    border-top: 1px solid rgb(16 17 18 / 0.08);
}

.mn-section-head {
    margin-bottom: clamp(1.8rem, 3.2vw, 2.8rem);
}

.mn-index-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    border: 0;
    padding: 0;
    color: #737777;
    font-family: inherit;
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.13em;
    text-transform: uppercase;
}

.mn-index-line > span:nth-child(2) {
    margin-left: auto;
    text-align: right;
}

.mn-index-line > span:nth-child(3) {
    padding-left: 1rem;
    border-left: 1px solid rgb(16 17 18 / 0.14);
}

.mn-section-title-grid {
    grid-template-columns: minmax(0, 1fr) minmax(17rem, 0.62fr);
    align-items: end;
    gap: clamp(1.5rem, 5vw, 5rem);
    margin-top: 1.2rem;
    border-top: 1px solid rgb(16 17 18 / 0.14);
    padding-top: clamp(1.5rem, 2.5vw, 2.2rem);
}

.mn-display-title,
.mn-display-title.is-dark {
    max-width: 16ch;
    color: #101112;
    font-size: clamp(2.15rem, 4.1vw, 3.65rem);
    font-weight: 760;
    line-height: 1;
    letter-spacing: -0.05em;
}

.mn-section-copy,
.mn-section-copy.is-dark {
    max-width: 29rem;
    color: #676b6b;
    font-size: 0.84rem;
    line-height: 1.7;
}

.mn-outline-button,
.mn-light-outline-button,
.mn-solid-button,
.mn-contact-button {
    min-height: 2.75rem;
    border-radius: 999px;
    padding: 0.72rem 1.05rem;
    font-family: inherit;
    font-size: 0.65rem;
    font-weight: 850;
    letter-spacing: 0.08em;
}

.mn-outline-button {
    border-color: rgb(16 17 18 / 0.22);
    background: #fff;
    box-shadow: 0 7px 22px rgb(16 17 18 / 0.05);
}

/* PRODUCT GRID — familiar ecommerce UX with showroom materials */
.mn-watch-grid {
    gap: 0.85rem;
}

.mn-watch-card {
    overflow: hidden;
    border: 1px solid rgb(16 17 18 / 0.09);
    border-radius: 1rem;
    background: #fff;
    box-shadow:
        0 1px 0 rgb(255 255 255 / 0.9) inset,
        0 10px 28px rgb(16 17 18 / 0.045);
}

.mn-watch-card:hover {
    transform: translateY(-4px);
    border-color: rgb(16 17 18 / 0.18);
    box-shadow: 0 22px 48px rgb(16 17 18 / 0.09);
}

.mn-watch-media {
    aspect-ratio: 1 / 1.16;
    background: radial-gradient(
        circle at 50% 36%,
        #fafafa 0%,
        #eef0f0 52%,
        #e6e8e8 100%
    );
}

.mn-watch-media::after {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    box-shadow: inset 0 -1px 0 rgb(16 17 18 / 0.07);
}

.mn-watch-index {
    display: none !important;
}

.mn-watch-badges {
    left: 0.65rem;
    top: 0.65rem;
}

.mn-watch-badge {
    border: 1px solid rgb(16 17 18 / 0.08);
    border-radius: 999px;
    background: rgb(255 255 255 / 0.88);
    color: #252727;
    font-family: inherit;
    font-size: 0.49rem;
    letter-spacing: 0.055em;
    box-shadow: 0 4px 14px rgb(16 17 18 / 0.06);
}

.mn-watch-body {
    min-height: 11.5rem;
    padding: 0.9rem 0.95rem 1rem;
}

.mn-watch-brand {
    color: #777b7b;
    font-size: 0.54rem;
    letter-spacing: 0.14em;
}

.mn-watch-heading h3 {
    margin-top: 0.28rem;
    font-size: 1rem;
    font-weight: 760;
    line-height: 1.13;
}

.mn-watch-reference {
    color: #8a8e8e;
    font-family: inherit;
    font-size: 0.56rem;
    font-weight: 650;
    letter-spacing: 0.025em;
}

.mn-watch-tags {
    gap: 0.3rem;
    margin-top: 0.7rem;
}

.mn-watch-tags span {
    border: 0;
    border-radius: 999px;
    background: #f0f1f1;
    padding: 0.3rem 0.45rem;
    color: #6c7070;
    font-size: 0.49rem;
    letter-spacing: 0.045em;
}

.mn-watch-price-row {
    border-top-color: rgb(16 17 18 / 0.08);
}

.mn-watch-price {
    font-size: 1.05rem;
    font-weight: 820;
}

.mn-watch-details {
    border-radius: 999px;
    background: #111313;
    padding: 0.48rem 0.62rem;
    color: #fff;
    font-family: inherit;
    font-size: 0.51rem;
    letter-spacing: 0.04em;
}

/* CATALOG — showroom category tiles */
.mn-reference-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.8rem;
    border: 0;
}

.mn-reference-row {
    position: relative;
    display: grid;
    grid-template-columns: 1fr;
    gap: 0;
    overflow: hidden;
    border: 1px solid rgb(16 17 18 / 0.09);
    border-radius: 1rem;
    background: #fff;
    padding: 0;
    box-shadow: 0 10px 28px rgb(16 17 18 / 0.04);
    transition:
        transform 240ms ease,
        box-shadow 240ms ease,
        border-color 240ms ease;
}

.mn-reference-row:hover {
    transform: translateY(-3px);
    border-color: rgb(16 17 18 / 0.18);
    box-shadow: 0 20px 42px rgb(16 17 18 / 0.08);
}

.mn-reference-number {
    display: none;
}

.mn-reference-thumb {
    aspect-ratio: 16 / 11;
    background: #e7e9e9;
}

.mn-reference-name {
    padding: 0.9rem 0.95rem 0.35rem;
}

.mn-reference-name > p {
    color: #7a7e7e;
    font-family: inherit;
    font-size: 0.53rem;
    letter-spacing: 0.12em;
}

.mn-reference-name h3 {
    margin-top: 0.28rem;
    font-size: 1rem;
    font-weight: 760;
}

.mn-reference-code {
    display: block;
    padding: 0 0.95rem;
    color: #898d8d;
    font-family: inherit;
    font-size: 0.54rem;
}

.mn-reference-action {
    margin: 0.85rem 0.95rem 0.95rem;
    width: fit-content;
    border: 0;
    border-radius: 999px;
    background: #111313;
    padding: 0.48rem 0.64rem;
    color: #fff;
    font-family: inherit;
    font-size: 0.5rem;
    letter-spacing: 0.04em;
}

/* RECENTLY DELIVERED — one deliberate dark showroom wall */
.mn-archive-section {
    background: #111313;
    color: #fff;
    padding: clamp(4.5rem, 7vw, 6.75rem) 0;
}

.mn-archive-section .mn-section-title-grid {
    border-top-color: rgb(255 255 255 / 0.16);
}

.mn-archive-section .mn-index-line,
.mn-contact-section .mn-index-line {
    color: rgb(255 255 255 / 0.58);
}

.mn-archive-section .mn-index-line > span:nth-child(3),
.mn-contact-section .mn-index-line > span:nth-child(3) {
    border-left-color: rgb(255 255 255 / 0.18);
}

.mn-archive-section .mn-display-title,
.mn-contact-section .mn-display-title,
.mn-archive-section .mn-display-title.is-dark {
    color: #fff;
}

.mn-archive-section .mn-section-copy,
.mn-archive-section .mn-section-copy.is-dark {
    color: rgb(255 255 255 / 0.58);
}

.mn-archive-grid {
    gap: 0.9rem;
}

.mn-archive-piece {
    overflow: hidden;
    border: 1px solid rgb(255 255 255 / 0.1);
    border-radius: 1rem;
    background: #181a1a;
}

.mn-archive-media {
    aspect-ratio: 4 / 5;
    background: #202222;
}

.mn-archive-copy {
    border: 0;
    padding: 0.9rem 0.95rem 1rem;
}

.mn-archive-copy h3 {
    font-size: 1.2rem;
}

.mn-archive-count {
    display: none;
}

.mn-archive-sold {
    left: 0.7rem;
    right: auto;
    top: 0.7rem;
    border: 1px solid rgb(255 255 255 / 0.12);
    border-radius: 999px;
    background: rgb(17 19 19 / 0.7);
}

.mn-archive-source {
    border: 0;
    border-radius: 999px;
    background: #fff;
    padding: 0.48rem 0.62rem;
    color: #111313;
}

.mn-archive-footerline {
    border-top-color: rgb(255 255 255 / 0.13);
}

/* STORIES — content cards, not editorial spreads */
.mn-film-grid {
    gap: 0.9rem;
}

.mn-film-card {
    overflow: hidden;
    border: 1px solid rgb(16 17 18 / 0.09);
    border-radius: 1rem;
    background: #fff;
    box-shadow: 0 10px 28px rgb(16 17 18 / 0.045);
}

.mn-film-media {
    aspect-ratio: 16 / 11;
    background: #e8eaea;
}

.mn-film-stamp,
.mn-film-play {
    bottom: 0.65rem;
    border-radius: 999px;
    background: rgb(255 255 255 / 0.9);
    color: #111313;
    font-family: inherit;
    font-size: 0.51rem;
}

.mn-film-copy {
    border: 0;
    padding: 0.95rem 1rem 1rem;
}

.mn-film-code {
    color: #8a8e8e;
    font-family: inherit;
    font-size: 0.52rem;
}

.mn-film-copy h3 {
    margin-top: 0.45rem;
    font-size: 1.25rem;
    line-height: 1.05;
}

.mn-film-copy > p {
    margin-top: 0.45rem;
    color: #707474;
}

/* HOW TO BUY — three showroom service cards */
.mn-process-table {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.8rem;
    border: 0;
}

.mn-process-row {
    display: flex;
    min-height: 14rem;
    flex-direction: column;
    gap: 0;
    border: 1px solid rgb(16 17 18 / 0.09);
    border-radius: 1rem;
    background: #fff;
    padding: 1.1rem;
    box-shadow: 0 10px 28px rgb(16 17 18 / 0.04);
}

.mn-process-row > p:first-child {
    color: #929696;
    font-family: inherit;
    font-size: 0.62rem;
}

.mn-process-row h3 {
    margin-top: 1.4rem;
    font-size: 1.25rem;
}

.mn-process-row > p:nth-child(3) {
    grid-column: auto;
    margin-top: 0.7rem;
    color: #6f7373;
    font-size: 0.78rem;
    line-height: 1.65;
}

.mn-process-row > span {
    margin-top: auto;
    align-self: flex-end;
}

/* CONTACT — clean dark closing panel */
.mn-contact-section {
    background: #111313;
    padding: clamp(4.5rem, 7vw, 6.75rem) 0;
}

.mn-contact-grid {
    gap: clamp(2.5rem, 6vw, 5rem);
}

.mn-contact-grid h2 {
    max-width: 12ch;
    margin-top: 0.6rem;
    font-size: clamp(2.7rem, 5.3vw, 4.9rem);
    line-height: 0.94;
    letter-spacing: -0.055em;
}

.mn-contact-kicker,
.mn-directory-label {
    font-family: inherit;
    letter-spacing: 0.1em;
}

.mn-contact-directory {
    overflow: hidden;
    border: 1px solid rgb(255 255 255 / 0.1);
    border-radius: 1rem;
    background: rgb(255 255 255 / 0.035);
}

.mn-directory-label {
    padding: 0.9rem 1rem;
}

.mn-contact-row {
    padding: 0.95rem 1rem;
}

.mn-contact-row > span:first-child {
    display: none;
}

.mn-contact-button {
    border-color: #fff;
    background: #fff;
    color: #111313;
}

.mn-contact-button:hover {
    background: #e8eaea;
    color: #111313;
}

.mn-footer {
    background: #eef0f0;
    color: #747878;
    font-family: inherit;
    letter-spacing: 0.045em;
}

@media (min-width: 640px) {
    .mn-watch-grid {
        gap: 1rem;
    }
    .mn-reference-list {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }
    .mn-film-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (min-width: 1024px) {
    .mn-watch-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
    .mn-reference-list {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

@media (max-width: 767px) {
    header {
        background: rgb(247 248 248 / 0.95) !important;
    }

    .mn-section-title-grid {
        grid-template-columns: 1fr;
        gap: 0.8rem;
        padding-top: 1.25rem;
    }

    .mn-display-title,
    .mn-display-title.is-dark {
        max-width: 14ch;
        font-size: clamp(1.9rem, 8.2vw, 2.7rem);
        line-height: 1.02;
        letter-spacing: -0.045em;
    }

    .mn-index-line {
        display: flex;
        font-size: 0.56rem;
    }

    .mn-index-line > span:nth-child(3) {
        display: none;
    }

    .mn-watch-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.65rem;
    }

    .mn-watch-card {
        border-radius: 0.8rem;
    }
    .mn-watch-body {
        min-height: 11.2rem;
        padding: 0.75rem;
    }
    .mn-watch-heading h3 {
        font-size: 0.92rem;
    }
    .mn-watch-price {
        font-size: 0.95rem;
    }
    .mn-watch-details {
        padding: 0.42rem 0.52rem;
    }
    .mn-reference-list {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.65rem;
    }
    .mn-reference-row {
        border-radius: 0.8rem;
    }
    .mn-reference-code {
        display: none;
    }
    .mn-film-grid {
        grid-template-columns: 1fr;
    }
    .mn-process-table {
        grid-template-columns: 1fr;
    }
    .mn-process-row {
        min-height: 11rem;
    }
    .mn-contact-grid h2 {
        font-size: clamp(2.55rem, 12vw, 3.75rem);
    }
}

/* =========================================================
   DARK NAV + REFINED SHOWROOM CONTACT
   ========================================================= */

header {
    background: rgb(8 9 9 / 0.92) !important;
    border-bottom-color: rgb(255 255 255 / 0.09) !important;
}

/* Keep the masthead visually connected to the cinematic hero. */
header :deep(img) {
    max-height: 3rem;
}

.mn-contact-section {
    background: #0b0c0c;
    padding: clamp(4.25rem, 7vw, 6.25rem) 0 0;
}

.mn-contact-shell {
    border-top: 1px solid rgb(255 255 255 / 0.14);
}

.mn-contact-topline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid rgb(255 255 255 / 0.11);
    color: #9fa3a3;
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;
}

.mn-contact-main {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: end;
    gap: clamp(2rem, 6vw, 6rem);
    padding: clamp(3rem, 5vw, 4.75rem) 0;
}

.mn-contact-copy {
    max-width: 47rem;
}

.mn-contact-kicker {
    margin: 0;
    color: #a7aaaa;
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.15em;
    text-transform: uppercase;
}

.mn-contact-main h2 {
    max-width: 13ch;
    margin: 0.8rem 0 0;
    color: white;
    font-size: clamp(2.2rem, 4vw, 3.85rem);
    font-weight: 650;
    line-height: 0.98;
    letter-spacing: -0.05em;
}

.mn-contact-intro {
    max-width: 42rem;
    margin-top: 1.35rem;
    color: #aeb1b1;
    font-size: 0.96rem;
    line-height: 1.75;
}

.mn-contact-button {
    display: inline-flex;
    min-width: 14rem;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
    border: 1px solid #fff;
    border-radius: 0.75rem;
    background: #fff;
    padding: 1rem 1.1rem;
    color: #111313;
    font-size: 0.7rem;
    font-weight: 850;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    transition:
        transform 220ms ease,
        background-color 220ms ease;
}

.mn-contact-button:hover {
    transform: translateY(-2px);
    background: #e9ebeb;
}

.mn-social-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    border-top: 1px solid rgb(255 255 255 / 0.11);
    border-bottom: 1px solid rgb(255 255 255 / 0.11);
}

.mn-social-link {
    display: flex;
    min-width: 0;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.25rem 1.1rem;
    color: white;
    transition: background-color 220ms ease;
}

.mn-social-link + .mn-social-link {
    border-left: 1px solid rgb(255 255 255 / 0.11);
}

.mn-social-link:hover {
    background: rgb(255 255 255 / 0.055);
}

.mn-social-link > div {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 0.75rem;
}

.mn-social-icon {
    display: inline-flex;
    height: 1.9rem;
    min-width: 1.9rem;
    align-items: center;
    justify-content: center;
    border: 1px solid rgb(255 255 255 / 0.16);
    border-radius: 999px;
    color: #aeb1b1;
    font-size: 0.55rem;
    font-weight: 900;
    letter-spacing: 0.05em;
}

.mn-social-link strong {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 0.82rem;
    font-weight: 700;
}

.mn-social-arrow {
    flex: none;
    color: #8f9393;
    transition:
        transform 220ms ease,
        color 220ms ease;
}

.mn-social-link:hover .mn-social-arrow {
    transform: translate(2px, -2px);
    color: white;
}

.mn-footer {
    background: #0b0c0c;
    color: #777b7b;
    border-top: 0;
}

@media (max-width: 767px) {
    header {
        background: rgb(8 9 9 / 0.95) !important;
    }

    .mn-contact-section {
        padding-top: 3.5rem;
    }

    .mn-contact-main {
        grid-template-columns: 1fr;
        align-items: start;
        gap: 2rem;
        padding: 2.6rem 0;
    }

    .mn-contact-main h2 {
        max-width: 12ch;
        font-size: clamp(2.15rem, 10vw, 3rem);
    }

    .mn-contact-button {
        width: 100%;
        min-width: 0;
    }

    .mn-social-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .mn-social-link {
        padding: 1rem 0.8rem;
    }

    .mn-social-link + .mn-social-link {
        border-left: 0;
    }

    .mn-social-link:nth-child(even) {
        border-left: 1px solid rgb(255 255 255 / 0.11);
    }

    .mn-social-link:nth-child(n + 3) {
        border-top: 1px solid rgb(255 255 255 / 0.11);
    }
}

/* =========================================================
   SHOWROOM POLISH PASS
   Dynamic masthead, filtering, tighter retail hierarchy.
   ========================================================= */

/* SEAMLESS NAVBAR — NO DIVIDER */

.mn-nav-top,
.mn-nav-scrolled {
    border: none !important;
    border-bottom: 0 !important;
    box-shadow: none !important;
}

.hero-full-carousel-section {
    border-top: none !important;
    box-shadow: none !important;
}

.hero-full-bg {
    border-top: none !important;
}

header::before,
header::after {
    border-bottom: none !important;
    box-shadow: none !important;
}

.mn-journal-section,
.mn-index-section,
.mn-film-section,
.mn-process-section {
    padding: clamp(3.7rem, 6vw, 5.6rem) 0;
}

.mn-showroom-kicker {
    color: #777b7b;
    font-size: 0.62rem;
    font-weight: 850;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

.mn-collection-head,
.mn-compact-head {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 2rem;
    margin-bottom: 1.75rem;
}

.mn-collection-title-row {
    display: flex;
    align-items: baseline;
    gap: 0.8rem;
    margin-top: 0.45rem;
}

.mn-collection-title-row h2,
.mn-compact-head h2 {
    color: #101112;
    font-size: clamp(1.85rem, 3vw, 2.7rem);
    font-weight: 730;
    line-height: 1;
    letter-spacing: -0.045em;
}

.mn-collection-title-row > span {
    color: #8a8e8e;
    font-size: 0.72rem;
    font-weight: 750;
}

.mn-collection-intro,
.mn-compact-head p:not(.mn-showroom-kicker) {
    max-width: 37rem;
    margin-top: 0.75rem;
    color: #6e7272;
    font-size: 0.82rem;
    line-height: 1.65;
}

.mn-collection-summary {
    flex: none;
    color: #888c8c;
    font-size: 0.68rem;
}

.mn-filter-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.75rem;
    border-top: 1px solid rgb(16 17 18 / 0.1);
    border-bottom: 1px solid rgb(16 17 18 / 0.1);
    padding: 0.75rem 0;
}

.mn-filter-scroll {
    display: flex;
    min-width: 0;
    gap: 0.45rem;
    overflow-x: auto;
    scrollbar-width: none;
}
.mn-filter-scroll::-webkit-scrollbar {
    display: none;
}

.mn-filter-chip {
    flex: none;
    border: 1px solid rgb(16 17 18 / 0.12);
    border-radius: 999px;
    background: transparent;
    padding: 0.5rem 0.72rem;
    color: #646868;
    font-size: 0.61rem;
    font-weight: 800;
    letter-spacing: 0.055em;
    text-transform: uppercase;
    transition: 180ms ease;
}
.mn-filter-chip:hover {
    border-color: rgb(16 17 18 / 0.28);
    color: #111;
}
.mn-filter-chip.is-active {
    border-color: #111313;
    background: #111313;
    color: white;
}

.mn-sort-control {
    display: flex;
    flex: none;
    align-items: center;
    gap: 0.5rem;
    color: #7a7e7e;
    font-size: 0.61rem;
    font-weight: 800;
    text-transform: uppercase;
}
.mn-sort-control select {
    border: 0;
    border-left: 1px solid rgb(16 17 18 / 0.12);
    background: transparent;
    padding: 0.35rem 1.8rem 0.35rem 0.65rem;
    color: #202222;
    font-size: 0.68rem;
    font-weight: 750;
    outline: 0;
}

.mn-results-line {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    color: #979a9a;
    font-size: 0.58rem;
    font-weight: 750;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.mn-watch-card {
    border-radius: 0.8rem;
    box-shadow: 0 8px 24px rgb(16 17 18 / 0.035);
}
.mn-watch-card:hover {
    box-shadow: 0 18px 42px rgb(16 17 18 / 0.075);
}
.mn-watch-media {
    aspect-ratio: 1 / 1.2;
}
.mn-watch-badge {
    padding: 0.33rem 0.5rem;
}
.mn-watch-body {
    min-height: 10.9rem;
}
.mn-watch-heading h3 {
    font-size: 1.04rem;
}
.mn-watch-reference {
    margin-top: 0.3rem;
}
.mn-watch-price {
    font-size: 1.13rem;
}
.mn-watch-details {
    background: transparent;
    padding: 0;
    color: #303333;
    font-size: 0.58rem;
}

.mn-filter-empty {
    margin-top: 1rem;
    border: 1px dashed rgb(16 17 18 / 0.18);
    padding: 1.5rem;
    text-align: center;
    color: #757979;
    font-size: 0.8rem;
}
.mn-filter-empty button {
    margin-top: 0.65rem;
    color: #111313;
    font-size: 0.68rem;
    font-weight: 850;
    text-decoration: underline;
    text-underline-offset: 0.2rem;
}

/* Browse the showroom: less technical, more visual retail. */
.mn-reference-row {
    border-radius: 0.8rem;
}
.mn-reference-thumb {
    aspect-ratio: 4 / 3;
}
.mn-reference-name h3 {
    font-size: 1.05rem;
}
.mn-reference-action {
    background: transparent;
    padding: 0;
    color: #242727;
    font-size: 0.57rem;
}

/* Dark social proof wall. */
.mn-archive-section .mn-compact-head h2 {
    color: white;
    max-width: 24ch;
}
.mn-archive-section .mn-compact-head p:not(.mn-showroom-kicker) {
    color: rgb(255 255 255 / 0.58);
}
.mn-archive-section .mn-showroom-kicker {
    color: rgb(255 255 255 / 0.52);
}
.mn-archive-piece {
    border-radius: 0.8rem;
}

/* Vertical reels, closer to the content's native format. */
.mn-film-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    max-width: 58rem;
}
.mn-film-card {
    border-radius: 0.8rem;
}
.mn-film-media {
    aspect-ratio: 9 / 16;
}
.mn-reel-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        180deg,
        rgb(0 0 0 / 0.05),
        transparent 50%,
        rgb(0 0 0 / 0.35)
    );
    pointer-events: none;
}
.mn-film-copy {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.85rem 0.95rem;
}
.mn-film-copy h3 {
    margin: 0;
    font-size: 0.95rem;
}
.mn-film-copy > span {
    flex: none;
    color: #747878;
    font-size: 0.58rem;
    font-weight: 800;
    text-transform: uppercase;
}

/* Buying flow + trust strip. */
.mn-process-row {
    min-height: 12rem;
    border-radius: 0.8rem;
}
.mn-process-row h3 {
    margin-top: 1.15rem;
    font-size: 1.18rem;
}
.mn-trust-strip {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    margin-top: 1rem;
    border-top: 1px solid rgb(16 17 18 / 0.12);
    border-bottom: 1px solid rgb(16 17 18 / 0.12);
}
.mn-trust-strip span {
    padding: 0.9rem 0.8rem;
    color: #6f7373;
    font-size: 0.58rem;
    font-weight: 850;
    letter-spacing: 0.08em;
    text-align: center;
}
.mn-trust-strip span + span {
    border-left: 1px solid rgb(16 17 18 / 0.1);
}

/* Minimal black showroom close. */
.mn-contact-signature {
    border-bottom: 1px solid rgb(255 255 255 / 0.12);
    padding: 1rem 0;
    color: #858989;
    font-size: 0.6rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}
.mn-contact-main {
    padding: clamp(2.8rem, 5vw, 4.3rem) 0;
}
.mn-contact-main h2 {
    max-width: 15ch;
    font-size: clamp(2.15rem, 4vw, 3.55rem);
}
.mn-contact-intro {
    max-width: 38rem;
}
.mn-social-inline {
    display: flex;
    flex-wrap: wrap;
    gap: 0;
    border-top: 1px solid rgb(255 255 255 / 0.12);
}
.mn-social-inline a {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    border-right: 1px solid rgb(255 255 255 / 0.12);
    padding: 1rem 1.35rem 1rem 0;
    margin-right: 1.35rem;
    color: #a8abab;
    font-size: 0.67rem;
    font-weight: 760;
    transition: color 180ms ease;
}
.mn-social-inline a:hover {
    color: white;
}

@media (max-width: 767px) {
    .mn-nav-top {
        background: rgb(7 8 8 / 0.82) !important;
    }
    .mn-nav-scrolled {
        background: rgb(7 8 8 / 0.97) !important;
    }

    .mn-collection-head,
    .mn-compact-head {
        align-items: start;
        flex-direction: column;
        gap: 1rem;
        margin-bottom: 1.35rem;
    }
    .mn-collection-title-row {
        align-items: end;
        justify-content: space-between;
    }
    .mn-collection-title-row h2,
    .mn-compact-head h2 {
        font-size: clamp(1.65rem, 7.6vw, 2.15rem);
    }
    .mn-collection-summary {
        display: none;
    }

    .mn-filter-bar {
        align-items: stretch;
        flex-direction: column;
        gap: 0.65rem;
    }
    .mn-sort-control {
        justify-content: space-between;
    }
    .mn-sort-control select {
        flex: 1;
        text-align: right;
    }

    .mn-watch-grid {
        gap: 0.6rem;
    }
    .mn-watch-body {
        min-height: 10.4rem;
        padding: 0.72rem;
    }
    .mn-watch-heading h3 {
        font-size: 0.9rem;
    }
    .mn-watch-reference {
        font-size: 0.52rem;
    }
    .mn-watch-price {
        font-size: 0.98rem;
    }
    .mn-watch-details {
        font-size: 0.52rem;
    }

    .mn-film-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.65rem;
    }
    .mn-film-copy {
        align-items: start;
        flex-direction: column;
        gap: 0.35rem;
        padding: 0.7rem;
    }
    .mn-film-copy h3 {
        font-size: 0.82rem;
    }
    .mn-film-copy > span {
        font-size: 0.5rem;
    }

    .mn-process-table {
        grid-template-columns: 1fr;
    }
    .mn-trust-strip {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .mn-trust-strip span:nth-child(even) {
        border-left: 1px solid rgb(16 17 18 / 0.1);
    }
    .mn-trust-strip span:nth-child(n + 3) {
        border-top: 1px solid rgb(16 17 18 / 0.1);
    }

    .mn-contact-main {
        gap: 1.7rem;
    }
    .mn-social-inline a {
        width: 50%;
        margin: 0;
        padding: 0.9rem 0.4rem 0.9rem 0;
    }
    .mn-social-inline a:nth-child(even) {
        padding-left: 0.8rem;
        border-right: 0;
    }
}

/* =========================================================
   SCREEN-BY-SCREEN SHOWROOM + SOCIAL BRAND ICONS
   Each major room fills at least one viewport. Content-heavy
   sections may grow naturally rather than clipping products.
   ========================================================= */

:global(html) {
    scroll-snap-type: y proximity;
    scroll-padding-top: 5.25rem;
}

main > section {
    scroll-snap-align: start;
    scroll-snap-stop: normal;
}

.mn-journal-section,
.mn-index-section,
.mn-archive-section,
.mn-film-section,
.mn-process-section,
.mn-contact-section {
    min-height: calc(100svh - 5.25rem);
    display: flex;
    align-items: center;
}

.mn-journal-section > .mx-auto,
.mn-index-section > .mx-auto,
.mn-archive-section > .mx-auto,
.mn-film-section > .mx-auto,
.mn-process-section > .mx-auto,
.mn-contact-section > .mx-auto {
    width: 100%;
}

/* Keep one-screen rooms feeling deliberate instead of padded-out. */
.mn-index-section,
.mn-archive-section,
.mn-film-section,
.mn-process-section {
    padding-top: clamp(2.6rem, 5vh, 4.5rem);
    padding-bottom: clamp(2.6rem, 5vh, 4.5rem);
}

.mn-contact-section {
    padding-top: 0;
    padding-bottom: 0;
}

/* Three curated stories only, centered as a single showroom wall. */
.mn-film-grid {
    margin-inline: auto;
}

/* Actual brand marks in the final social row. */
.mn-social-inline a {
    min-height: 3.55rem;
}

.mn-social-icon {
    display: inline-flex;
    width: 1.12rem;
    height: 1.12rem;
    flex: 0 0 1.12rem;
    align-items: center;
    justify-content: center;
    color: #f4f5f5;
}

.mn-social-icon svg {
    width: 100%;
    height: 100%;
    display: block;
}

.mn-social-arrow {
    margin-left: 0.15rem;
    color: #777b7b;
    transition:
        transform 180ms ease,
        color 180ms ease;
}

.mn-social-inline a:hover .mn-social-arrow {
    transform: translate(2px, -2px);
    color: #fff;
}

/* Compact vertical rhythm for shorter laptop screens so sections
   still read as one room without making controls too small. */
@media (min-width: 768px) and (max-height: 820px) {
    .mn-journal-section,
    .mn-index-section,
    .mn-archive-section,
    .mn-film-section,
    .mn-process-section {
        padding-top: 2.5rem;
        padding-bottom: 2.5rem;
    }

    .mn-collection-head,
    .mn-compact-head {
        margin-bottom: 1.15rem;
    }

    .mn-film-grid {
        max-width: 48rem;
    }

    .mn-process-row {
        min-height: 10.5rem;
    }
}

@media (max-width: 767px) {
    :global(html) {
        scroll-snap-type: y proximity;
        scroll-padding-top: 7.25rem;
    }

    .hero-full-carousel-section,
    .mn-journal-section,
    .mn-index-section,
    .mn-archive-section,
    .mn-film-section,
    .mn-process-section,
    .mn-contact-section {
        min-height: calc(100svh - 7.25rem);
    }

    .mn-journal-section,
    .mn-index-section,
    .mn-archive-section,
    .mn-film-section,
    .mn-process-section {
        padding-top: 2.75rem;
        padding-bottom: 2.75rem;
    }

    /* Keep the three stories in a usable two-up + one layout on mobile,
       while remaining exactly three curated cards. */
    .mn-film-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        width: 100%;
    }

    .mn-film-card:last-child:nth-child(odd) {
        grid-column: 1 / -1;
        width: calc(50% - 0.325rem);
        justify-self: center;
    }

    .mn-social-inline a {
        gap: 0.55rem;
    }

    .mn-social-icon {
        width: 1.05rem;
        height: 1.05rem;
        flex-basis: 1.05rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    :global(html) {
        scroll-snap-type: none;
        scroll-behavior: auto;
    }
}

/* =========================================================
   MONTRE NOVA / MONOCHROME EDITORIAL REFINEMENT
   Keep this block last so it overrides older hero media rules.
========================================================= */
.hero-full-carousel-section {
    min-height: min(850px, calc(100svh - 5.25rem));
    background: #080808;
    border-bottom: 1px solid #282828;
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
}
.hero-full-bg,
.hero-full-slide {
    background: #080808;
}
.hero-full-backdrop-image {
    opacity: 0.27;
    filter: grayscale(1) blur(30px) brightness(0.55);
    transform: scale(1.06);
}
.hero-full-main-image {
    opacity: 0.88;
    filter: grayscale(1) contrast(1.04) brightness(0.9);
    animation: none;
    transform: none;
    object-position: 64% center;
}
.hero-full-left-gradient {
    background: linear-gradient(
        90deg,
        rgba(0, 0, 0, 0.96) 0%,
        rgba(0, 0, 0, 0.83) 30%,
        rgba(0, 0, 0, 0.38) 64%,
        rgba(0, 0, 0, 0.18) 100%
    );
}
.hero-full-vignette {
    background: radial-gradient(
        circle at 75% 42%,
        transparent 15%,
        rgba(0, 0, 0, 0.3) 100%
    );
}
.hero-full-bottom-gradient {
    background: linear-gradient(to top, rgba(0, 0, 0, 0.77), transparent);
}
.hero-full-top-glass {
    background: linear-gradient(to bottom, rgba(0, 0, 0, 0.24), transparent);
}
.hero-full-content {
    min-height: min(850px, calc(100svh - 5.25rem));
    max-width: 80rem;
    padding: 6rem clamp(1.5rem, 7vw, 7rem) 9rem;
}
.hero-full-copy {
    max-width: 40rem;
}
.mn-hero-eyebrow {
    margin-bottom: 1.65rem;
    font-size: 0.64rem;
    font-weight: 500;
    letter-spacing: 0.27em;
    color: #d4d4d4;
}
.mn-hero-eyebrow span {
    color: #777;
    padding-inline: 0.4rem;
}
.hero-full-title {
    max-width: 38rem;
    color: #fafafa;
    font-size: clamp(3.25rem, 6.7vw, 6.6rem);
    font-weight: 400;
    letter-spacing: -0.065em;
    line-height: 1.04;
    text-shadow: none;
}
.hero-full-description {
    margin-top: 1.65rem;
    max-width: 25rem;
    font-size: clamp(0.85rem, 1vw, 0.96rem);
    font-weight: 400;
    line-height: 1.9;
    color: #c4c4c4;
    text-shadow: none;
}
.hero-full-actions {
    margin-top: 2.4rem;
    width: auto;
}
.mn-hero-explore {
    display: inline-flex;
    width: auto;
    min-width: 16rem;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
    padding: 1rem 1.25rem;
    border: 1px solid #8d8d8d;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
    color: #fff;
    font-size: 0.65rem;
    font-weight: 500;
    letter-spacing: 0.14em;
    text-decoration: none;
    transition:
        background 0.25s ease,
        color 0.25s ease,
        border-color 0.25s ease;
}
.mn-hero-explore:hover {
    background: #f5f5f5;
    color: #111;
    border-color: #f5f5f5;
}
.mn-hero-explore:focus-visible,
.hero-full-arrow:focus-visible,
.hero-full-thumb-button:focus-visible {
    outline: 2px solid #fff;
    outline-offset: 4px;
}
.mn-hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem 2rem;
    margin-top: 2.8rem;
    font-size: 0.6rem;
    font-weight: 400;
    letter-spacing: 0.16em;
    color: #999;
}
.hero-full-arrow {
    height: 2.6rem;
    width: 2.6rem;
    border-radius: 0;
    border-color: rgba(255, 255, 255, 0.27);
    background: rgba(8, 8, 8, 0.35);
    box-shadow: none;
    font-size: 1rem;
    font-weight: 300;
}
.hero-full-arrow-left {
    left: clamp(0.75rem, 2vw, 2rem);
}
.hero-full-arrow-right {
    right: clamp(0.75rem, 2vw, 2rem);
}
.hero-full-dock {
    right: clamp(1rem, 4vw, 4rem);
    bottom: 1.6rem;
    display: flex;
    grid-template-columns: none;
    gap: 0.55rem;
    padding: 0;
    max-width: calc(100% - 2rem);
    overflow: visible;
    border: none;
    border-radius: 0;
    box-shadow: none;
    background: transparent;
    backdrop-filter: none;
}
.hero-full-thumb-button {
    width: clamp(2.7rem, 5vw, 4rem);
    padding: 0;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 0;
    background: #151515;
    opacity: 0.57;
    filter: grayscale(1);
    overflow: hidden;
    transition:
        opacity 0.25s ease,
        border-color 0.25s ease,
        transform 0.25s ease;
}
.hero-full-thumb-button:hover,
.hero-full-thumb-button.is-active {
    opacity: 1;
    border-color: white;
    background: #222;
}
.hero-full-thumb-media {
    aspect-ratio: 1 / 1;
    border-radius: 0;
}
.hero-full-thumb-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    filter: grayscale(1);
}
.mn-hero-counter {
    position: absolute;
    left: clamp(1.5rem, 7vw, 7rem);
    bottom: 2.7rem;
    z-index: 33;
    color: #eee;
    font-size: 0.72rem;
    font-weight: 400;
    letter-spacing: 0.13em;
}
.mn-hero-counter span {
    padding-inline: 0.65rem;
    color: #777;
}
.hero-slide-enter-active,
.hero-slide-leave-active {
    transition: opacity 0.65s ease;
}
.hero-slide-enter-from,
.hero-slide-leave-to {
    opacity: 0;
    transform: none;
    filter: none;
}
/* Preserve product detail hierarchy while reducing aggressive typography. */
.mn-watch-brand,
.mn-showroom-kicker {
    letter-spacing: 0.16em;
}
@media (max-width: 767px) {
    .hero-full-carousel-section,
    .hero-full-content {
        min-height: min(760px, calc(100svh - 7.75rem));
    }
    .hero-full-main-image {
        object-position: 62% center;
        opacity: 0.7;
    }
    .hero-full-left-gradient {
        background: linear-gradient(
            to top,
            rgba(0, 0, 0, 0.94) 4%,
            rgba(0, 0, 0, 0.6) 54%,
            rgba(0, 0, 0, 0.14) 100%
        );
    }
    .hero-full-content {
        align-items: center;
        padding: 4.5rem 1.6rem 9rem;
    }
    .hero-full-copy {
        max-width: 100%;
    }
    .hero-full-title {
        font-size: clamp(2.65rem, 10.5vw, 4.3rem);
        letter-spacing: -0.06em;
        line-height: 1.06;
    }
    .hero-full-description {
        max-width: 20rem;
        font-size: 0.82rem;
        line-height: 1.75;
    }
    .mn-hero-eyebrow {
        font-size: 0.57rem;
        letter-spacing: 0.15em;
    }
    .mn-hero-meta {
        display: none;
    }
    .hero-full-arrow {
        top: auto;
        bottom: 1.4rem;
        transform: none;
        width: 2.15rem;
        height: 2.15rem;
    }
    .hero-full-arrow:hover,
    .hero-full-arrow:active {
        transform: none;
    }
    .hero-full-arrow-left {
        left: 1rem;
    }
    .hero-full-arrow-right {
        left: 3.6rem;
        right: auto;
    }
    .hero-full-dock {
        right: 1rem;
        bottom: 1.5rem;
        gap: 0.3rem;
    }
    .hero-full-thumb-button {
        width: 2.35rem;
    }
    .mn-hero-counter {
        bottom: 5.4rem;
        left: 1.6rem;
    }
}
@media (prefers-reduced-motion: reduce) {
    .hero-slide-enter-active,
    .hero-slide-leave-active {
        transition: none;
    }
}

/* =========================================================
   FULL-COLOR WATCH PHOTOGRAPHY / MONOCHROME INTERFACE
   Keep original dial, bezel, and strap colors visible.
========================================================= */
.hero-full-backdrop-image {
    opacity: 0.35;
    filter: blur(28px) brightness(0.72) saturate(1.05);
}
.hero-full-main-image {
    opacity: 1;
    filter: none;
    object-position: 64% center;
}
.hero-full-left-gradient {
    background: linear-gradient(
        90deg,
        rgba(0, 0, 0, 0.88) 0%,
        rgba(0, 0, 0, 0.7) 27%,
        rgba(0, 0, 0, 0.23) 54%,
        rgba(0, 0, 0, 0) 80%
    );
}
.hero-full-vignette {
    background: radial-gradient(
        circle at 75% 45%,
        transparent 42%,
        rgba(0, 0, 0, 0.18) 100%
    );
}
.hero-full-bottom-gradient {
    background: linear-gradient(to top, rgba(0, 0, 0, 0.5), transparent 32%);
}
.hero-full-top-glass {
    background: linear-gradient(
        to bottom,
        rgba(0, 0, 0, 0.12),
        transparent 20%
    );
}
.hero-full-thumb-button {
    filter: none;
    opacity: 0.65;
}
.hero-full-thumb-button.is-active,
.hero-full-thumb-button:hover {
    opacity: 1;
}
.hero-full-thumb-media img {
    filter: none;
}
@media (max-width: 767px) {
    .hero-full-main-image {
        opacity: 1;
        object-position: 62% center;
    }
    .hero-full-left-gradient {
        background: linear-gradient(
            to top,
            rgba(0, 0, 0, 0.87) 0%,
            rgba(0, 0, 0, 0.56) 31%,
            rgba(0, 0, 0, 0.08) 67%,
            transparent 100%
        );
    }
    .hero-full-vignette {
        background: radial-gradient(
            circle at 60% 35%,
            transparent 45%,
            rgba(0, 0, 0, 0.12) 100%
        );
    }
}

/* ================================================================
   MONTRE NOVA / SIGNATURE EDITORIAL REDESIGN — 2026
   Intentional single visual system: ivory / graphite / original color.
   The fixed nav overlays the hero; no separate spacer or white seam.
   ================================================================ */
:global(html) {
    scroll-behavior: smooth;
}
:global(body) {
    margin: 0;
    background: #0c0c0c;
}
:global(*),
:global(*::before),
:global(*::after) {
    box-sizing: border-box;
}
.mn-site-main,
main.mn-site-main {
    padding-top: 0 !important;
    margin-top: 0 !important;
    background: #0b0b0b;
}
header,
header.mn-nav-top,
header.mn-nav-scrolled {
    border: 0 !important;
    border-top: 0 !important;
    border-bottom: 0 !important;
    box-shadow: none !important;
    outline: none !important;
    background: rgba(12, 12, 12, 0.92) !important;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}
header::before,
header::after {
    content: none !important;
    display: none !important;
}
header .mx-auto {
    border: 0 !important;
    box-shadow: none !important;
}
header nav {
    font-weight: 450 !important;
    letter-spacing: 0.015em;
}
header nav a {
    font-weight: 450 !important;
}
header nav a span.absolute {
    height: 1px !important;
    opacity: 0.5;
}
header :deep(img) {
    max-height: 3rem;
}
.hero-full-carousel-section,
main.mn-site-main > .hero-full-carousel-section {
    margin: 0 !important;
    padding: 0 !important;
    min-height: 100svh !important;
    border: 0 !important;
    border-top: 0 !important;
    box-shadow: none !important;
    outline: 0 !important;
    background: #0b0b0b;
}
.hero-full-carousel-section::before,
.hero-full-carousel-section::after {
    border: 0 !important;
    box-shadow: none !important;
}
.hero-full-bg {
    inset: 0;
    border: 0 !important;
}
.hero-full-content {
    min-height: 100svh !important;
    display: flex;
    align-items: center;
    padding: clamp(10rem, 17vh, 14rem) clamp(2rem, 9vw, 9.5rem)
        clamp(9rem, 15vh, 11rem) !important;
    max-width: 1440px;
    margin: 0 auto;
}
.hero-full-copy {
    max-width: 660px;
}
.hero-full-title {
    max-width: 670px;
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-weight: 400 !important;
    font-size: clamp(3.55rem, 6.4vw, 6.8rem) !important;
    line-height: 0.99 !important;
    letter-spacing: -0.045em !important;
    text-transform: none !important;
    text-wrap: balance;
    color: #f8f5ef;
}
.mn-hero-eyebrow {
    font-size: 0.62rem;
    font-weight: 500;
    letter-spacing: 0.29em;
    color: #e4e0d8;
}
.hero-full-description {
    margin-top: 1.85rem;
    max-width: 370px;
    color: #e4e0d8;
    font-size: 0.93rem;
    font-weight: 400;
    line-height: 1.8;
}
.hero-full-main-image {
    filter: none !important;
    opacity: 1 !important;
    object-position: 66% center;
}
.hero-full-backdrop-image {
    filter: blur(32px) brightness(0.6) !important;
    opacity: 0.24 !important;
}
.hero-full-left-gradient {
    background: linear-gradient(
        90deg,
        rgba(0, 0, 0, 0.84),
        rgba(0, 0, 0, 0.57) 30%,
        rgba(0, 0, 0, 0.12) 62%,
        transparent 87%
    ) !important;
}
.hero-full-top-glass {
    background: linear-gradient(
        to bottom,
        rgba(0, 0, 0, 0.3),
        transparent 25%
    ) !important;
}
.hero-full-vignette {
    background: radial-gradient(
        circle at 70% 45%,
        transparent 38%,
        rgba(0, 0, 0, 0.17) 100%
    ) !important;
}
.hero-full-bottom-gradient {
    background: linear-gradient(
        to top,
        rgba(0, 0, 0, 0.55),
        transparent 34%
    ) !important;
}
.mn-hero-explore {
    min-width: 215px;
    padding: 1.1rem 1.35rem;
    border: 1px solid rgba(255, 255, 255, 0.6);
    background: transparent;
    font-size: 0.66rem;
    font-weight: 500;
    letter-spacing: 0.14em;
}
.mn-hero-explore:hover {
    background: #f6f3ed;
    color: #111;
}
.hero-full-arrow {
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.22);
    border: 1px solid rgba(255, 255, 255, 0.45);
}
.hero-full-thumb-button {
    border-radius: 2px;
    filter: none !important;
}
.hero-full-thumb-media img {
    filter: none !important;
}
.mn-hero-counter {
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-size: 0.9rem;
}
/* Warm off-white showroom, not a washed-out gray background. */
.mn-journal-section,
.mn-index-section,
.mn-film-section,
.mn-process-section {
    position: relative;
    border: 0 !important;
    padding: clamp(5rem, 9vw, 9rem) 0 !important;
    background: #f4f1eb !important;
    color: #171717;
}
.mn-index-section {
    background: #e9e5de !important;
}
.mn-film-section {
    background: #eeeae3 !important;
}
.mn-process-section {
    background: #f4f1eb !important;
}
.mn-collection-head,
.mn-compact-head {
    margin-bottom: clamp(2rem, 4vw, 3.5rem) !important;
}
.mn-showroom-kicker {
    color: #73716c !important;
    font-size: 0.64rem !important;
    font-weight: 500 !important;
    letter-spacing: 0.23em !important;
}
.mn-collection-title-row h2,
.mn-compact-head h2,
.mn-contact-main h2 {
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-weight: 400 !important;
    letter-spacing: -0.045em !important;
    line-height: 1.04 !important;
    text-wrap: balance;
}
.mn-collection-title-row h2,
.mn-compact-head h2 {
    font-size: clamp(2.7rem, 5vw, 5rem) !important;
}
.mn-collection-intro,
.mn-compact-head p:not(.mn-showroom-kicker) {
    font-size: 0.92rem;
    font-weight: 400;
    line-height: 1.75;
    color: #63615d;
}
/* Available products: generous photography, hairline controls, no heavy cards. */
.mn-watch-grid {
    gap: clamp(0.9rem, 1.8vw, 1.6rem) !important;
}
.mn-watch-card {
    border: 1px solid #dedad3 !important;
    background: #fcfaf6 !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    overflow: hidden;
    transition:
        transform 0.35s ease,
        border-color 0.35s ease,
        box-shadow 0.35s ease;
}
.mn-watch-card:hover {
    transform: translateY(-4px);
    border-color: #b9b2a8 !important;
    box-shadow: 0 16px 32px rgba(25, 23, 20, 0.055) !important;
}
.mn-watch-media {
    background: #e6e2dc !important;
}
.mn-watch-media img {
    filter: none !important;
}
.mn-watch-body {
    padding: clamp(1rem, 1.7vw, 1.5rem) !important;
}
.mn-watch-brand {
    font-size: 0.6rem !important;
    font-weight: 600 !important;
    letter-spacing: 0.19em !important;
    color: #76716b !important;
}
.mn-watch-heading h3 {
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-size: clamp(1.1rem, 1.6vw, 1.5rem) !important;
    font-weight: 400 !important;
    letter-spacing: -0.025em !important;
}
.mn-watch-price {
    color: #151515;
    letter-spacing: -0.025em !important;
    font-weight: 500 !important;
}
.mn-filter-bar {
    border-radius: 0 !important;
    background: transparent !important;
    border-top: 1px solid #cfcac2 !important;
    border-bottom: 1px solid #cfcac2 !important;
    padding: 1rem 0 !important;
}
.mn-filter-chip {
    border-radius: 0 !important;
    font-weight: 450 !important;
}
.mn-filter-chip.is-active {
    background: #171717 !important;
    color: #fff !important;
}
.mn-sort-control select {
    border-radius: 0 !important;
}
.mn-page-button {
    border-radius: 0 !important;
}
/* Catalog becomes a compact editorial reference index. */
.mn-reference-list {
    gap: 1px !important;
    background: #d2cdc5;
}
.mn-reference-row {
    border: 0 !important;
    border-radius: 0 !important;
    background: #f4f1eb !important;
    box-shadow: none !important;
}
.mn-reference-row:hover {
    background: #fffdfa !important;
}
.mn-reference-name h3 {
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-size: clamp(1.05rem, 1.6vw, 1.4rem) !important;
    font-weight: 400 !important;
}
.mn-outline-button {
    border-radius: 0 !important;
}
/* Delivered pieces: charcoal gallery with photographic emphasis. */
.mn-archive-section {
    background: #151515 !important;
    color: #f4f1eb !important;
    padding: clamp(5.5rem, 9vw, 9rem) 0 !important;
}
.mn-archive-section .mn-compact-head h2 {
    color: #f4f1eb !important;
}
.mn-archive-section .mn-compact-head p {
    color: #b6b2ac !important;
}
.mn-archive-piece {
    background: #202020 !important;
    border: 1px solid #343434 !important;
    border-radius: 0 !important;
    box-shadow: none !important;
}
.mn-archive-media img {
    filter: none !important;
}
.mn-archive-copy h3 {
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-weight: 400 !important;
}
/* Film cards: clean gallery thumbnails, no rounded tiles. */
.mn-film-card {
    border-radius: 0 !important;
    background: #171717 !important;
    box-shadow: none !important;
}
.mn-film-media,
.mn-film-media img,
.mn-film-media video {
    border-radius: 0 !important;
}
.mn-film-copy h3 {
    font-weight: 500 !important;
    letter-spacing: 0.04em !important;
}
/* Order flow: editorial horizontal rules / elegant row numbers. */
.mn-process-table {
    gap: 1px !important;
    background: #d6d1c9 !important;
}
.mn-process-row {
    border-radius: 0 !important;
    border: 0 !important;
    background: #f4f1eb !important;
    box-shadow: none !important;
}
.mn-process-row > p:first-child {
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-weight: 400 !important;
    font-size: 1.5rem !important;
}
.mn-process-row h3 {
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-weight: 400 !important;
    font-size: clamp(1.4rem, 2vw, 2rem) !important;
}
.mn-trust-strip {
    border-color: #cdc8c0 !important;
}
/* Last panel: black editorial sign-off. */
.mn-contact-section {
    background: #0d0d0d !important;
    padding: clamp(6rem, 10vw, 10rem) 0 2.5rem !important;
}
.mn-contact-shell {
    border-top: 1px solid #3c3c3c !important;
}
.mn-contact-main h2 {
    font-size: clamp(3rem, 5.7vw, 6.2rem) !important;
    color: #f5f2ec !important;
}
.mn-contact-intro {
    color: #bcb8b2 !important;
}
.mn-contact-button {
    border-radius: 0 !important;
    font-weight: 500 !important;
}
.mn-social-inline {
    border-color: #353535 !important;
}
.mn-footer {
    background: #0d0d0d !important;
}
@media (max-width: 767px) {
    .hero-full-content {
        padding: 11rem 1.6rem 9rem !important;
        align-items: end !important;
    }
    .hero-full-copy {
        max-width: 100%;
    }
    .hero-full-title {
        font-size: clamp(3.05rem, 11.7vw, 5rem) !important;
        line-height: 1.02 !important;
    }
    .hero-full-main-image {
        object-position: 62% center;
    }
    .hero-full-left-gradient {
        background: linear-gradient(
            to top,
            rgba(0, 0, 0, 0.87),
            rgba(0, 0, 0, 0.48) 40%,
            transparent 82%
        ) !important;
    }
    .hero-full-description {
        max-width: 20rem;
        font-size: 0.85rem;
    }
    .mn-journal-section,
    .mn-index-section,
    .mn-film-section,
    .mn-process-section,
    .mn-archive-section {
        padding: 4.5rem 0 !important;
    }
    .mn-collection-title-row h2,
    .mn-compact-head h2 {
        font-size: clamp(2.4rem, 9.5vw, 3.5rem) !important;
    }
    .mn-contact-section {
        padding-top: 5rem !important;
    }
    .mn-contact-main h2 {
        font-size: clamp(2.9rem, 11vw, 4.5rem) !important;
    }
}
@media (prefers-reduced-motion: reduce) {
    .mn-watch-card,
    .hero-full-thumb-button {
        transition: none !important;
    }
}

/* By-appointment viewing — quiet editorial panel */
.mn-viewing-panel {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 2rem;
    margin: clamp(2.5rem, 5vw, 4.5rem) 0 2rem;
    padding: clamp(1.75rem, 3.5vw, 3.25rem) 0;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    color: #f5f2ec;
}
.mn-viewing-details {
    max-width: 42rem;
}
.mn-viewing-eyebrow {
    color: #a8a39c;
    font-size: 0.67rem;
    font-weight: 500;
    letter-spacing: 0.23em;
    margin-bottom: 1.1rem;
}
.mn-viewing-details h3 {
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-size: clamp(2rem, 3.5vw, 3.5rem);
    font-weight: 400;
    line-height: 1.1;
    letter-spacing: -0.035em;
    margin-bottom: 1rem;
}
.mn-viewing-description {
    color: #bcb8b2;
    font-size: 0.92rem;
    line-height: 1.75;
    margin-bottom: 1.25rem;
}
.mn-viewing-address {
    font-style: normal;
    color: #e3ded7;
    font-size: 0.88rem;
    line-height: 1.8;
}
.mn-viewing-action {
    display: inline-flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
    min-height: 3.25rem;
    padding: 0.9rem 1.2rem;
    background: transparent;
    border: 1px solid #77716b;
    border-radius: 0;
    color: #f5f2ec;
    font-size: 0.69rem;
    font-weight: 500;
    letter-spacing: 0.13em;
    white-space: nowrap;
    transition:
        background 0.2s ease,
        color 0.2s ease;
}
.mn-viewing-action:hover,
.mn-viewing-action:focus-visible {
    background: #f5f2ec;
    color: #171717;
}
@media (max-width: 640px) {
    .mn-viewing-panel {
        align-items: stretch;
    }
    .mn-viewing-action {
        width: 100%;
    }
}

/* ===============================================================
   MONTRE NOVA — BLACK / GRAYSCALE SHOWROOM
   All interface surfaces are neutral. Product imagery remains color.
   =============================================================== */
:global(html),
:global(body) {
    background: #080808 !important;
}
.mn-site-main {
    background: #080808 !important;
}
.mn-journal-section,
.mn-index-section,
.mn-film-section,
.mn-process-section {
    background: #101010 !important;
    color: #f1f1f1 !important;
}
.mn-index-section {
    background: #181818 !important;
}
.mn-film-section {
    background: #111111 !important;
}
.mn-process-section {
    background: #181818 !important;
}
.mn-archive-section {
    background: #080808 !important;
    color: #f1f1f1 !important;
}
.mn-contact-section,
.mn-footer {
    background: #080808 !important;
}
.mn-collection-title-row h2,
.mn-compact-head h2,
.mn-collection-title-row span,
.mn-collection-head,
.mn-compact-head,
.mn-collection-head p,
.mn-compact-head p,
.mn-showroom-kicker {
    color: #f2f2f2 !important;
}
.mn-showroom-kicker {
    color: #aaa !important;
}
.mn-collection-intro,
.mn-compact-head p:not(.mn-showroom-kicker),
.mn-collection-summary,
.mn-results-line {
    color: #a8a8a8 !important;
}
.mn-filter-bar {
    border-color: #383838 !important;
    background: transparent !important;
}
.mn-filter-chip {
    background: transparent !important;
    border-color: #444 !important;
    color: #c9c9c9 !important;
}
.mn-filter-chip.is-active {
    background: #f1f1f1 !important;
    color: #111 !important;
    border-color: #f1f1f1 !important;
}
.mn-sort-control,
.mn-sort-control span {
    color: #aaa !important;
}
.mn-sort-control select {
    background: #161616 !important;
    color: #f3f3f3 !important;
    border-color: #444 !important;
}
.mn-watch-card {
    background: #1a1a1a !important;
    border-color: #333 !important;
    color: #f0f0f0 !important;
}
.mn-watch-card:hover {
    border-color: #808080 !important;
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.24) !important;
}
.mn-watch-media,
.mn-watch-placeholder {
    background: #252525 !important;
}
.mn-watch-media img,
.mn-reference-thumb img,
.mn-archive-media img,
.mn-film-media img,
.mn-film-media video,
.hero-full-main-image,
.hero-full-thumb-media img {
    filter: none !important;
}
.mn-watch-brand,
.mn-watch-reference,
.mn-watch-tags,
.mn-watch-tags span {
    color: #b5b5b5 !important;
}
.mn-watch-heading h3,
.mn-watch-price,
.mn-watch-details {
    color: #f3f3f3 !important;
}
.mn-watch-price-row,
.mn-watch-body {
    background: #1a1a1a !important;
}
.mn-watch-original-price {
    color: #8c8c8c !important;
}
.mn-watch-badge {
    background: rgba(15, 15, 15, 0.75) !important;
    border-color: #555 !important;
    color: #eee !important;
}
.mn-reference-list {
    background: #353535 !important;
}
.mn-reference-row,
.mn-reference-row:hover {
    background: #1d1d1d !important;
    color: #eaeaea !important;
}
.mn-reference-row:hover {
    background: #292929 !important;
}
.mn-reference-name h3,
.mn-reference-name p,
.mn-reference-number,
.mn-reference-code,
.mn-reference-action {
    color: #e8e8e8 !important;
}
.mn-reference-thumb {
    background: #2a2a2a !important;
}
.mn-outline-button,
.mn-light-outline-button {
    background: transparent !important;
    border-color: #777 !important;
    color: #ededed !important;
}
.mn-outline-button:hover,
.mn-light-outline-button:hover {
    background: #eee !important;
    color: #111 !important;
}
.mn-archive-piece,
.mn-film-card {
    background: #1b1b1b !important;
    border-color: #363636 !important;
}
.mn-archive-copy,
.mn-archive-copy h3,
.mn-archive-info,
.mn-film-copy,
.mn-film-copy h3 {
    color: #eee !important;
}
.mn-process-table {
    background: #3b3b3b !important;
}
.mn-process-row {
    background: #191919 !important;
    color: #f2f2f2 !important;
}
.mn-process-row h3 {
    color: #f0f0f0 !important;
}
.mn-process-row p {
    color: #bdbdbd !important;
}
.mn-trust-strip {
    border-color: #404040 !important;
    color: #b5b5b5 !important;
}
.mn-page-button {
    background: #1b1b1b !important;
    color: #eee !important;
    border-color: #444 !important;
}
.mn-page-button.is-active {
    background: #eee !important;
    color: #111 !important;
}
.mn-filter-empty,
.mn-empty-state {
    background: #1b1b1b !important;
    color: #eee !important;
    border-color: #444 !important;
}
.mn-filter-empty p,
.mn-empty-state p {
    color: #bababa !important;
}
.mn-viewing-panel {
    color: #f4f4f4;
}
.mn-viewing-eyebrow,
.mn-viewing-description {
    color: #aaa;
}
.mn-viewing-address {
    color: #ddd;
}
.mn-viewing-action {
    color: #eee;
    border-color: #666;
}
.mn-viewing-action:hover,
.mn-viewing-action:focus-visible {
    background: #eee;
    color: #111;
}
.hero-full-title,
.mn-contact-main h2 {
    color: #f6f6f6 !important;
}
.mn-hero-eyebrow,
.hero-full-description,
.mn-contact-intro {
    color: #d3d3d3 !important;
}
.mn-hero-explore:hover {
    background: #f0f0f0 !important;
}
/* Keep the navbar-to-hero join seamless; avoid a bright border/seam. */
header.mn-nav-top,
header.mn-nav-scrolled,
.hero-full-carousel-section {
    border: 0 !important;
    border-bottom: 0 !important;
    box-shadow: none !important;
}

/* MONTRE NOVA — TYPOGRAPHIC SYSTEM & LOADING EXPERIENCE */
:global(body) {
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-weight: 400;
    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
}
/* Display typography: elegant contrast; brand character without excessive boldness. */
.hero-full-title,
.mn-collection-title-row h2,
.mn-compact-head h2,
.mn-contact-main h2,
.mn-viewing-details h3 {
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-weight: 400 !important;
    letter-spacing: -0.038em !important;
    text-transform: none !important;
    text-wrap: balance;
}
.hero-full-title {
    font-size: clamp(3.1rem, 6.3vw, 6.4rem) !important;
    line-height: 1.035 !important;
    letter-spacing: -0.048em !important;
}
.mn-collection-title-row h2,
.mn-compact-head h2 {
    font-size: clamp(2.25rem, 4.6vw, 4.5rem) !important;
    line-height: 1.08 !important;
}
.mn-watch-heading h3,
.mn-reference-name h3,
.mn-archive-copy h3,
.mn-film-copy h3,
.mn-process-row h3 {
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-weight: 450 !important;
    letter-spacing: -0.025em !important;
    line-height: 1.3 !important;
}
.mn-showroom-kicker,
.mn-hero-eyebrow,
.mn-watch-brand,
.mn-contact-kicker,
.mn-viewing-eyebrow,
.mn-reference-code,
.mn-trust-strip,
.mn-hero-explore,
header nav,
.mn-filter-chip {
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-weight: 500 !important;
    letter-spacing: 0.13em !important;
}
.hero-full-description,
.mn-collection-intro,
.mn-compact-head p,
.mn-contact-intro,
.mn-viewing-description {
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-size: clamp(0.875rem, 1.15vw, 1rem) !important;
    font-weight: 400 !important;
    letter-spacing: 0.004em !important;
    line-height: 1.8 !important;
}
.mn-watch-price {
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-variant-numeric: tabular-nums;
    font-weight: 500 !important;
    letter-spacing: -0.025em !important;
}
/* Never desaturate products. The UI itself is monochrome. */
.hero-full-main-image,
.hero-full-thumb-media img,
.mn-watch-media img,
.mn-reference-thumb img,
.mn-archive-media img {
    filter: none !important;
}
/* Brand loading splash — works on initial hero media and Inertia requests. */
.mn-loading-screen {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: #080808;
    color: #f5f5f5;
    display: grid;
    place-items: center;
    padding: 1.5rem;
}
.mn-loading-content {
    width: min(78vw, 270px);
    text-align: center;
}
.mn-loading-monogram {
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
    font-size: clamp(4rem, 12vw, 6rem);
    font-weight: 400;
    line-height: 1;
    letter-spacing: -0.075em;
}
.mn-loading-rule {
    width: 100%;
    height: 1px;
    background: #363636;
    overflow: hidden;
    margin: 1.65rem auto 1.15rem;
}
.mn-loading-rule span {
    display: block;
    width: 40%;
    height: 100%;
    background: #efefef;
    animation: mn-loading-sweep 1.35s ease-in-out infinite;
}
.mn-loading-name {
    font-size: 0.66rem;
    font-weight: 600;
    letter-spacing: 0.3em;
}
.mn-loading-caption {
    margin-top: 0.75rem;
    color: #969696;
    font-size: 0.72rem;
    letter-spacing: 0.035em;
}
.mn-loading-fade-enter-active,
.mn-loading-fade-leave-active {
    transition: opacity 0.28s ease;
}
.mn-loading-fade-enter-from,
.mn-loading-fade-leave-to {
    opacity: 0;
}
@keyframes mn-loading-sweep {
    from {
        transform: translateX(-115%);
    }
    to {
        transform: translateX(365%);
    }
}
@media (max-width: 640px) {
    .hero-full-title {
        font-size: clamp(2.8rem, 10.8vw, 4.3rem) !important;
    }
    .mn-loading-caption {
        font-size: 0.7rem;
    }
}
@media (prefers-reduced-motion: reduce) {
    .mn-loading-rule span {
        animation: none;
        width: 100%;
    }
    .mn-loading-fade-enter-active,
    .mn-loading-fade-leave-active {
        transition: none;
    }
}

/* ==============================================================
   MONTRE NOVA / SPOTLIGHT + LAYERED OBSIDIAN — FINAL DESIGN SYSTEM
   No noise, grain, stripes, or textured bitmap overlays.
   ============================================================= */
:global(html),
:global(body) {
    background: #080808 !important;
}
:global(body) {
    overflow-x: hidden;
}
/* Eliminate old decorative background layer, without affecting images. */
.mn-site-main {
    background: #080808 !important;
}
.mn-journal-section,
.mn-index-section,
.mn-archive-section,
.mn-film-section,
.mn-process-section,
.mn-contact-section {
    position: relative;
    isolation: isolate;
}
.mn-journal-section {
    background: radial-gradient(
        ellipse 85% 58% at 49% 13%,
        #303030 0%,
        #191919 53%,
        #101010 100%
    ) !important;
}
.mn-index-section {
    background: linear-gradient(155deg, #0c0c0c, #1b1b1b 57%, #111) !important;
}
.mn-archive-section {
    background: radial-gradient(
        ellipse 80% 70% at 80% 25%,
        #222 0%,
        #101010 68%,
        #080808 100%
    ) !important;
}
.mn-film-section {
    background: linear-gradient(150deg, #191919, #0c0c0c 72%) !important;
}
.mn-process-section {
    background: radial-gradient(
        ellipse 90% 75% at 23% 20%,
        #242424 0%,
        #121212 62%,
        #0c0c0c 100%
    ) !important;
}
.mn-contact-section {
    background: linear-gradient(145deg, #111, #202020 52%, #090909) !important;
}
.mn-journal-section::before,
.mn-index-section::before,
.mn-film-section::before,
.mn-process-section::before,
.mn-contact-section::before {
    background-image: none !important;
    mask-image: none !important;
    opacity: 0 !important;
    pointer-events: none !important;
}
.mn-watch-card {
    background: #181818 !important;
    border: 1px solid rgba(255, 255, 255, 0.09) !important;
    box-shadow: 0 10px 34px rgba(0, 0, 0, 0.13) !important;
}
.mn-watch-card:hover {
    border-color: rgba(255, 255, 255, 0.3) !important;
}
.mn-reference-row {
    background: rgba(25, 25, 25, 0.93) !important;
}
.mn-watch-media img,
.mn-reference-thumb img,
.mn-archive-media img,
.mn-film-media img,
.hero-full-main-image,
.hero-full-thumb-media img {
    filter: none !important;
}
/* Streamlined, seamless desktop and mobile navigation. */
.mn-luxe-nav {
    position: fixed;
    top: 0;
    right: 0;
    left: 0;
    z-index: 90;
    border: 0 !important;
    background: rgba(8, 8, 8, 0.86);
    backdrop-filter: blur(22px);
    -webkit-backdrop-filter: blur(22px);
    box-shadow: none !important;
    transition: background 0.25s;
}
.mn-luxe-nav.is-scrolled {
    background: rgba(9, 9, 9, 0.96);
}
.mn-luxe-nav-inner {
    max-width: 1440px;
    margin: auto;
    min-height: 80px;
    display: flex;
    align-items: center;
    gap: clamp(20px, 3vw, 48px);
    padding: 0 clamp(20px, 4vw, 56px);
}
.mn-luxe-brand {
    display: flex;
    flex: 0 0 auto;
    min-width: 0;
    align-items: center;
    max-width: 195px;
}
.mn-luxe-brand :deep(svg),
.mn-luxe-brand :deep(img) {
    max-width: 100%;
    height: auto;
    max-height: 50px;
}
.mn-desktop-links {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: clamp(14px, 1.65vw, 28px);
    white-space: nowrap;
}
.mn-desktop-links a,
.mn-nav-warranty {
    font-size: 11px;
    letter-spacing: 0.07em;
    font-weight: 500;
    color: #c3c3c3;
    text-decoration: none;
    transition: color 0.2s;
}
.mn-desktop-links a:hover,
.mn-nav-warranty:hover {
    color: white;
}
.mn-desktop-actions {
    display: flex;
    gap: 20px;
    align-items: center;
    white-space: nowrap;
}
.mn-nav-inquire {
    display: inline-flex;
    align-items: center;
    gap: 18px;
    background: #ececec;
    color: #0b0b0b;
    border: 0;
    padding: 12px 16px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.08em;
    transition: background 0.2s;
}
.mn-nav-inquire:hover {
    background: #fff;
}
.mn-mobile-actions {
    display: none;
}
.mn-mobile-panel {
    display: none;
}
/* Keep hero behind header: no bright spacer between navbar and banner. */
.mn-site-main {
    padding-top: 0 !important;
}
.hero-full-carousel-section {
    margin-top: 0 !important;
    border: 0 !important;
    box-shadow: none !important;
}
@media (max-width: 1150px) {
    .mn-desktop-links {
        gap: 12px;
    }
    .mn-desktop-links a {
        font-size: 10px;
        letter-spacing: 0;
    }
    .mn-desktop-actions {
        gap: 12px;
    }
}
@media (max-width: 1023px) {
    .mn-luxe-nav-inner {
        min-height: 68px;
        padding: 0 20px;
        justify-content: space-between;
    }
    .mn-luxe-brand {
        max-width: 156px;
    }
    .mn-desktop-links,
    .mn-desktop-actions {
        display: none;
    }
    .mn-mobile-actions {
        display: flex;
        align-items: center;
        gap: 17px;
    }
    .mn-mobile-inquire {
        background: transparent;
        border: 0;
        color: #eee;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.08em;
    }
    .mn-mobile-toggle {
        width: 42px;
        height: 44px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 7px;
        border: 1px solid rgba(255, 255, 255, 0.22);
        background: rgba(255, 255, 255, 0.035);
        padding: 0;
    }
    .mn-mobile-toggle span {
        display: block;
        width: 19px;
        height: 1px;
        background: #eee;
        transition: transform 0.25s;
    }
    .mn-mobile-toggle .line-open-first {
        transform: translateY(4px) rotate(45deg);
    }
    .mn-mobile-toggle .line-open-last {
        transform: translateY(-4px) rotate(-45deg);
    }
    .mn-mobile-panel {
        display: block;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        max-height: calc(100dvh - 68px);
        overflow-y: auto;
        background: rgba(12, 12, 12, 0.985);
        border: 0;
        padding: 16px 20px 30px;
        box-shadow: 0 22px 34px rgba(0, 0, 0, 0.35);
    }
    .mn-mobile-panel a {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 16px 4px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.09);
        color: #f0f0f0;
        text-decoration: none;
        font-size: clamp(17px, 4.1vw, 22px);
        font-weight: 400;
        letter-spacing: -0.03em;
    }
    .mn-menu-number {
        color: #888;
        font-size: 10px;
        letter-spacing: 0;
        min-width: 22px;
    }
    .mn-menu-arrow {
        margin-left: auto;
        font-size: 17px;
        color: #aaa;
    }
    .mn-menu-footer {
        margin-top: 24px;
        color: #888;
        font-size: 10px;
        letter-spacing: 0.18em;
    }
    .mn-menu-fade-enter-active,
    .mn-menu-fade-leave-active {
        transition:
            opacity 0.2s,
            transform 0.2s;
    }
    .mn-menu-fade-enter-from,
    .mn-menu-fade-leave-to {
        opacity: 0;
        transform: translateY(-8px);
    }
}
@media (max-width: 767px) {
    .mn-luxe-nav-inner {
        min-height: 64px;
        padding: 0 16px;
    }
    .mn-luxe-brand {
        max-width: 140px;
    }
    .hero-full-carousel-section {
        min-height: min(820px, 88svh) !important;
    }
    .hero-full-content {
        padding-left: clamp(20px, 5vw, 28px) !important;
        padding-right: clamp(20px, 5vw, 28px) !important;
    }
    .hero-full-title {
        font-size: clamp(2.5rem, 10.5vw, 4.25rem) !important;
        line-height: 1.04 !important;
        max-width: 95vw !important;
        letter-spacing: -0.038em !important;
        overflow-wrap: break-word;
    }
    .hero-full-description {
        font-size: 13px !important;
        line-height: 1.6 !important;
        max-width: 310px !important;
    }
    .mn-hero-meta {
        gap: 10px;
        flex-wrap: wrap;
    }
    .hero-full-dock {
        max-width: calc(100vw - 90px);
        gap: 7px !important;
    }
    .hero-full-thumb-button {
        min-width: 0 !important;
    }
    .mn-journal-section,
    .mn-index-section,
    .mn-archive-section,
    .mn-film-section,
    .mn-process-section,
    .mn-contact-section {
        padding-top: 65px !important;
        padding-bottom: 70px !important;
    }
    .mn-collection-head,
    .mn-compact-head {
        gap: 18px !important;
    }
    .mn-collection-title-row h2,
    .mn-compact-head h2 {
        font-size: clamp(2.05rem, 8.1vw, 3.2rem) !important;
    }
    .mn-watch-grid {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 11px !important;
    }
    .mn-watch-card {
        min-width: 0 !important;
        overflow: hidden;
    }
    .mn-watch-body {
        padding: 13px 11px !important;
    }
    .mn-watch-heading h3 {
        font-size: 13px !important;
        line-height: 1.32 !important;
    }
    .mn-watch-brand,
    .mn-watch-reference,
    .mn-watch-tags {
        font-size: 10px !important;
    }
    .mn-watch-price {
        font-size: 15px !important;
    }
    .mn-watch-price-row {
        align-items: flex-end;
        gap: 8px;
    }
    .mn-watch-details {
        font-size: 10px !important;
    }
    .mn-filter-bar {
        gap: 12px;
        flex-direction: column;
        align-items: stretch;
    }
    .mn-filter-scroll {
        max-width: 100%;
        overflow-x: auto;
        scrollbar-width: none;
    }
    .mn-sort-control {
        align-self: flex-start;
    }
    .mn-reference-row {
        gap: 12px !important;
    }
    .mn-archive-grid,
    .mn-film-grid {
        gap: 14px !important;
    }
    .mn-contact-main,
    .mn-viewing-panel {
        gap: 20px !important;
    }
    .mn-viewing-action {
        width: 100%;
        justify-content: center;
    }
    .mn-process-row {
        padding: 22px 0 !important;
    }
    .mn-trust-strip {
        gap: 15px !important;
    }
}
@media (max-width: 390px) {
    .mn-watch-grid {
        gap: 8px !important;
    }
    .mn-watch-body {
        padding: 11px 9px !important;
    }
    .mn-watch-price {
        font-size: 13px !important;
    }
    .mn-watch-details {
        font-size: 9px !important;
    }
    .mn-hero-meta {
        display: none !important;
    }
}
@media (prefers-reduced-motion: reduce) {
    .mn-menu-fade-enter-active,
    .mn-menu-fade-leave-active {
        transition: none;
    }
}

/* MANROPE REFINEMENT — CLEAN MOBILE HERO AND MONOCHROME NAVIGATION */
:global(html),
:global(body),
:global(button),
:global(input),
:global(select) {
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif;
}
.mn-luxe-nav,
.mn-luxe-nav button,
.mn-luxe-nav a,
.mn-mobile-panel {
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif !important;
}
.mn-nav-inquire,
.mn-mobile-inquire {
    letter-spacing: 0.025em !important;
    text-transform: uppercase;
    font-size: 10px !important;
    font-weight: 700 !important;
}
.hero-full-title,
.mn-collection-title-row h2,
.mn-compact-head h2,
.mn-contact-main h2,
.mn-viewing-details h3,
.mn-process-row h3 {
    font-family: "Manrope", "Helvetica Neue", Arial, sans-serif !important;
    font-weight: 500 !important;
    letter-spacing: -0.052em !important;
    text-transform: none !important;
}
.hero-full-title {
    text-wrap: balance;
}
.mn-watch-heading h3,
.mn-reference-name h3,
.mn-archive-copy h3,
.mn-film-copy h3 {
    font-family: "Manrope", Arial, sans-serif !important;
}
.mn-loading-monogram {
    font-family: "Manrope", Arial, sans-serif !important;
    font-weight: 500;
    letter-spacing: -0.07em;
}
@media (max-width: 767px) {
    .mn-luxe-nav-inner {
        gap: 10px;
    }
    .mn-mobile-actions {
        gap: 11px;
        flex-shrink: 0;
    }
    .mn-mobile-inquire {
        white-space: nowrap;
        padding: 10px 3px;
    }
    .hero-full-carousel-section {
        min-height: 100svh !important;
    }
    .hero-full-content {
        display: flex !important;
        align-items: flex-end !important;
        justify-content: center !important;
        padding: 112px 20px 170px !important;
        box-sizing: border-box;
    }
    .hero-full-copy {
        width: 100% !important;
        max-width: 440px !important;
        margin: 0 auto !important;
        text-align: left !important;
    }
    .mn-hero-eyebrow {
        font-size: 9px !important;
        letter-spacing: 0.14em !important;
        line-height: 1.5 !important;
        margin-bottom: 14px !important;
    }
    .hero-full-title {
        font-family: "Manrope", Arial, sans-serif !important;
        font-size: clamp(30px, 8.15vw, 43px) !important;
        font-weight: 600 !important;
        line-height: 1.13 !important;
        letter-spacing: -0.055em !important;
        text-align: left !important;
        text-wrap: balance;
        overflow-wrap: normal !important;
        word-break: normal !important;
        max-width: 420px !important;
        margin: 0 0 15px !important;
    }
    .hero-full-description {
        max-width: 330px !important;
        font-size: 12px !important;
        line-height: 1.7 !important;
        text-align: left !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
    .hero-full-actions {
        justify-content: flex-start !important;
    }
    .mn-hero-explore {
        font-size: 10px !important;
        letter-spacing: 0.08em !important;
    }
    .mn-hero-meta {
        display: none !important;
    }
    .hero-full-arrow {
        top: 52% !important;
    }
}
@media (max-width: 390px) {
    .hero-full-content {
        padding: 100px 18px 158px !important;
    }
    .hero-full-title {
        font-size: clamp(28px, 8.1vw, 33px) !important;
    }
    .hero-full-description {
        font-size: 11.5px !important;
    }
}
@media (max-width: 350px) {
    .mn-luxe-brand {
        max-width: 124px !important;
    }
    .mn-mobile-actions {
        gap: 7px;
    }
    .hero-full-title {
        font-size: 27px !important;
    }
}

/* =====================================================================
   MONTRE NOVA — BLACK ARCHITECTURAL CONCRETE / 2026
   Manrope, uninterrupted full-colour watch imagery, mobile layout preserved.
   Architectural, large-scale tonal relief instead of noise/grain/patterns.
   ===================================================================== */
:global(html),
:global(body) {
    background: #090a0b !important;
}
.mn-site-main {
    background: #090a0b !important;
}

/* Architectural concrete: broad tonal planes and distant studio illumination.
   Fine seams are deliberately limited to section backgrounds, not photos. */
.mn-journal-section,
.mn-index-section,
.mn-archive-section,
.mn-film-section,
.mn-process-section,
.mn-contact-section {
    position: relative;
    isolation: isolate;
    background-color: #111315 !important;
    background-attachment: scroll !important;
    overflow: hidden;
}
.mn-journal-section {
    background-image:
        radial-gradient(
            ellipse 82% 55% at 64% 4%,
            rgba(116, 120, 124, 0.19),
            transparent 74%
        ),
        linear-gradient(
            113deg,
            transparent 0 55%,
            rgba(210, 214, 219, 0.027) 55.1% 55.25%,
            transparent 55.4%
        ),
        linear-gradient(163deg, #1e2022 0%, #141618 46%, #0c0e10 100%) !important;
}
.mn-index-section {
    background-image:
        linear-gradient(
            150deg,
            transparent 0 32%,
            rgba(255, 255, 255, 0.027) 32.1% 32.22%,
            transparent 32.34%
        ),
        radial-gradient(
            ellipse 72% 84% at 15% 10%,
            rgba(99, 105, 109, 0.17),
            transparent 75%
        ),
        linear-gradient(138deg, #0d0f11, #202225 52%, #111315) !important;
}
.mn-archive-section {
    background-image:
        radial-gradient(
            ellipse 72% 65% at 82% 25%,
            rgba(108, 114, 118, 0.19),
            transparent 74%
        ),
        linear-gradient(
            102deg,
            transparent 0 74%,
            rgba(255, 255, 255, 0.023) 74.1% 74.24%,
            transparent 74.35%
        ),
        linear-gradient(165deg, #0a0c0e, #17191c 60%, #090a0c) !important;
}
.mn-film-section {
    background-image:
        radial-gradient(
            ellipse 90% 63% at 12% 4%,
            rgba(118, 122, 125, 0.15),
            transparent 76%
        ),
        linear-gradient(134deg, #202225, #141618 52%, #0b0d0f) !important;
}
.mn-process-section {
    background-image:
        linear-gradient(
            110deg,
            rgba(255, 255, 255, 0.022) 0%,
            transparent 24% 76%,
            rgba(255, 255, 255, 0.016) 100%
        ),
        radial-gradient(
            ellipse 67% 80% at 90% 8%,
            rgba(114, 119, 124, 0.16),
            transparent 73%
        ),
        linear-gradient(152deg, #0c0e10, #212326 49%, #0c0e10) !important;
}
.mn-contact-section {
    background-image:
        radial-gradient(
            ellipse 68% 72% at 78% 19%,
            rgba(112, 119, 125, 0.18),
            transparent 74%
        ),
        linear-gradient(
            120deg,
            transparent 0 66%,
            rgba(255, 255, 255, 0.027) 66.1% 66.24%,
            transparent 66.36%
        ),
        linear-gradient(150deg, #17191b 0%, #232528 52%, #0a0c0d 100%) !important;
}
/* Previous design revisions used section pseudo-elements for grain.
   Disable them so the architecture remains clean and non-repeating. */
.mn-journal-section::before,
.mn-journal-section::after,
.mn-index-section::before,
.mn-index-section::after,
.mn-archive-section::before,
.mn-archive-section::after,
.mn-film-section::before,
.mn-film-section::after,
.mn-process-section::before,
.mn-process-section::after,
.mn-contact-section::before,
.mn-contact-section::after {
    background-image: none !important;
    mask-image: none !important;
    box-shadow: none !important;
    opacity: 0 !important;
    pointer-events: none !important;
}
/* Ensure foreground content stays above any legacy decorative layers. */
.mn-journal-section > .mx-auto,
.mn-index-section > .mx-auto,
.mn-archive-section > .mx-auto,
.mn-film-section > .mx-auto,
.mn-process-section > .mx-auto,
.mn-contact-section > .mx-auto {
    position: relative;
    z-index: 1;
}
/* Gallery product surfaces mimic carved dark concrete alcoves, not glass. */
.mn-watch-card {
    background: linear-gradient(156deg, #222426, #151719 75%) !important;
    border: 1px solid rgba(223, 228, 231, 0.105) !important;
    box-shadow: 0 20px 45px rgba(0, 0, 0, 0.2) !important;
    border-radius: 3px !important;
}
.mn-watch-card:hover {
    border-color: rgba(235, 238, 240, 0.29) !important;
    box-shadow: 0 23px 54px rgba(0, 0, 0, 0.3) !important;
}
.mn-reference-row,
.mn-process-row,
.mn-viewing-panel {
    border-color: rgba(228, 230, 233, 0.12) !important;
}
.mn-reference-row {
    background: linear-gradient(
        110deg,
        rgba(34, 36, 39, 0.85),
        rgba(19, 21, 23, 0.86)
    ) !important;
}
.mn-luxe-nav {
    background: rgba(10, 11, 12, 0.91) !important;
    border-bottom: 0 !important;
    box-shadow: none !important;
}
.mn-luxe-nav.is-scrolled {
    background: rgba(9, 10, 11, 0.97) !important;
}
/* Hero/product photographs retain natural colour and are not textured. */
.hero-full-main-image,
.hero-full-backdrop-image,
.hero-full-thumb-media img,
.mn-watch-media img,
.mn-reference-thumb img,
.mn-archive-media img,
.mn-film-media img {
    filter: none !important;
}
/* Nonintrusive mobile lighting, with readable contrast on narrow displays. */
@media (max-width: 767px) {
    .mn-journal-section {
        background-image:
            radial-gradient(
                ellipse 100% 44% at 50% 0%,
                rgba(120, 124, 128, 0.14),
                transparent 80%
            ),
            linear-gradient(164deg, #1c1e20, #101214 76%) !important;
    }
    .mn-index-section {
        background-image: linear-gradient(
            155deg,
            #141618,
            #222427 52%,
            #101214
        ) !important;
    }
    .mn-archive-section {
        background-image:
            radial-gradient(
                ellipse 100% 55% at 77% 8%,
                rgba(109, 114, 119, 0.14),
                transparent 78%
            ),
            linear-gradient(168deg, #101214, #191b1e 60%, #0b0d0f) !important;
    }
    .mn-film-section {
        background-image: linear-gradient(
            145deg,
            #212326,
            #111315 72%
        ) !important;
    }
    .mn-process-section {
        background-image:
            radial-gradient(
                ellipse 100% 65% at 12% 0%,
                rgba(120, 123, 127, 0.13),
                transparent 76%
            ),
            linear-gradient(165deg, #121416, #202225 61%, #0c0e10) !important;
    }
    .mn-contact-section {
        background-image: linear-gradient(
            150deg,
            #202225,
            #111315 75%
        ) !important;
    }
    .mn-watch-card {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.17) !important;
    }
}

/* ========================================================================
   MONTRE NOVA / DEEP NOIR MARBLE — FINAL THEME
   Midnight nero-marquina-inspired surfaces, soft white mineral veins.
   Self-contained SVG pathwork; no asset file or plugin necessary.
   Low-contrast veins stay BEHIND content; photographs stay full-colour.
   ======================================================================== */
:global(html),
:global(body) {
    background: #050505 !important;
}

/* The marble is embedded in CSS to keep this a single-file Vue component. */
.mn-journal-section,
.mn-index-section,
.mn-archive-section,
.mn-film-section,
.mn-process-section,
.mn-contact-section {
    position: relative;
    isolation: isolate;
    background-color: #070708 !important;
    background-image:
        linear-gradient(180deg, rgba(4, 4, 5, 0.79), rgba(3, 3, 4, 0.88)),
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 900'%3E%3Cg fill='none' stroke='%23c0c0c4' stroke-linecap='round'%3E%3Cpath d='M-90 310 C130 292 124 85 345 150 S620 495 792 335 S1045 98 1280 175' opacity='.34' stroke-width='1.7'/%3E%3Cpath d='M-110 317 C129 301 128 100 348 159 S610 514 793 348 S1038 106 1280 188' opacity='.16' stroke-width='5'/%3E%3Cpath d='M65 955 C185 770 411 815 514 594 S791 556 900 420 S1063 352 1230 210' opacity='.20' stroke-width='1.3'/%3E%3Cpath d='M74 950 C190 765 408 820 523 600 S787 553 908 424 S1070 345 1219 205' opacity='.10' stroke-width='4.5'/%3E%3Cpath d='M-38 690 C230 598 347 716 495 783 S860 716 1280 860' opacity='.12' stroke-width='1.1'/%3E%3Cpath d='M915 -50 C856 152 1060 230 980 398 S1060 650 1270 735' opacity='.17' stroke-width='1.3'/%3E%3C/g%3E%3C/svg%3E"),
        radial-gradient(
            ellipse 90% 70% at 75% 8%,
            #202023 0%,
            #0c0c0e 50%,
            #040405 100%
        ) !important;
    background-size:
        auto,
        1300px auto,
        cover !important;
    background-position:
        center,
        center top,
        center !important;
    background-attachment: scroll !important;
}
/* Alternating panels are quieter so the marble isn't repetitive. */
.mn-index-section,
.mn-film-section,
.mn-contact-section {
    background-image:
        linear-gradient(145deg, rgba(3, 3, 4, 0.82), rgba(4, 4, 5, 0.93)),
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 900'%3E%3Cg fill='none' stroke='%23ceced1'%3E%3Cpath d='M-60 790 C200 690 130 405 365 470 S650 670 835 405 S1040 345 1240 40' opacity='.26' stroke-width='1.4'/%3E%3Cpath d='M-65 796 C200 701 148 413 372 479 S659 667 844 414 S1050 355 1260 45' opacity='.13' stroke-width='4'/%3E%3Cpath d='M330 -50 C490 190 540 114 622 320 S910 620 1210 545' opacity='.16' stroke-width='1.2'/%3E%3C/g%3E%3C/svg%3E"),
        linear-gradient(155deg, #171719, #070709 70%) !important;
}
.mn-journal-section::before,
.mn-journal-section::after,
.mn-index-section::before,
.mn-index-section::after,
.mn-archive-section::before,
.mn-archive-section::after,
.mn-film-section::before,
.mn-film-section::after,
.mn-process-section::before,
.mn-process-section::after,
.mn-contact-section::before,
.mn-contact-section::after {
    opacity: 0 !important;
    background-image: none !important;
    pointer-events: none !important;
}
.mn-journal-section > .mx-auto,
.mn-index-section > .mx-auto,
.mn-archive-section > .mx-auto,
.mn-film-section > .mx-auto,
.mn-process-section > .mx-auto,
.mn-contact-section > .mx-auto {
    position: relative;
    z-index: 1;
}
.mn-watch-card,
.mn-reference-row,
.mn-film-card,
.mn-viewing-panel,
.mn-process-row {
    background: linear-gradient(
        155deg,
        rgba(23, 23, 25, 0.96),
        rgba(9, 9, 11, 0.96)
    ) !important;
    border-color: rgba(235, 235, 237, 0.11) !important;
    box-shadow: 0 18px 44px rgba(0, 0, 0, 0.27) !important;
}
.mn-luxe-nav,
.mn-luxe-nav.is-scrolled {
    background: rgba(4, 4, 5, 0.94) !important;
    border: none !important;
    box-shadow: none !important;
}
/* No recoloring or desaturation of hero, stock, archive or video imagery. */
.hero-full-main-image,
.hero-full-backdrop-image,
.hero-full-thumb-media img,
.mn-watch-media img,
.mn-reference-thumb img,
.mn-archive-media img,
.mn-film-media img {
    filter: none !important;
}
/* No blue arrows / accents, including inherited link or icon styles. */
.mn-menu-arrow {
    display: none !important;
}
.mn-luxe-nav a,
.mn-mobile-panel a,
.mn-hero-explore,
.mn-reference-action,
.mn-viewing-action,
.mn-film-play,
.mn-contact-button,
.mn-nav-inquire,
.mn-mobile-inquire {
    color: #f2f2f2;
    accent-color: #e2e2e2;
}
.mn-mobile-inquire,
.mn-nav-inquire {
    background: #eeeeee !important;
    color: #101010 !important;
}
/* Keep the actual carousel navigation but make it monochrome. */
.hero-full-arrow {
    color: #fff !important;
}
@media (max-width: 767px) {
    .mn-journal-section,
    .mn-index-section,
    .mn-archive-section,
    .mn-film-section,
    .mn-process-section,
    .mn-contact-section {
        background-size:
            auto,
            980px auto,
            cover !important;
        background-position:
            center,
            45% top,
            center !important;
    }
    .mn-watch-card {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.24) !important;
    }
}

/* ========================================================================
   MONTRE NOVA / FINISHING PASS 2026
   Consistent design language for desktop, tablet, small mobile; subtle marble
   detail belongs in section edges, never directly beneath product data.
   ======================================================================== */
:global(html) {
    scroll-behavior: smooth;
    background: #050505;
}
:global(body) {
    font-family: "Manrope", sans-serif;
    -webkit-font-smoothing: antialiased;
    background: #050505;
    color: #eee;
}
:global(*:focus-visible) {
    outline: 2px solid #dedede;
    outline-offset: 4px;
}
.mn-site-main {
    background: #050505 !important;
}
/* Navigation: no intrusive border, identical optical padding */
.mn-luxe-nav,
.mn-luxe-nav.is-scrolled {
    background: rgba(7, 7, 8, 0.95) !important;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    box-shadow: 0 8px 28px rgba(0, 0, 0, 0.18) !important;
}
.mn-luxe-nav-inner {
    max-width: 1380px;
    min-height: 76px;
    gap: clamp(16px, 2.5vw, 38px);
}
.mn-luxe-brand {
    max-width: 185px;
}
.mn-desktop-links {
    gap: clamp(16px, 1.7vw, 27px);
}
.mn-desktop-links a,
.mn-nav-warranty {
    font-size: 11px;
    letter-spacing: 0.025em;
    font-weight: 600;
    color: #b4b4b6;
}
.mn-desktop-links a:hover,
.mn-nav-warranty:hover {
    color: #fff;
}
.mn-nav-inquire {
    border: 1px solid #ededed;
    background: #eaeaea;
    color: #080808;
    border-radius: 2px;
    letter-spacing: 0.08em;
    padding: 12px 20px;
}
.mn-nav-inquire:hover {
    background: #fff;
}
.mn-mobile-inquire {
    font-size: 11px;
    letter-spacing: 0.03em;
    font-weight: 650;
}
.mn-mobile-toggle {
    border-radius: 3px;
}
.mn-mobile-panel {
    background: rgba(10, 10, 11, 0.99) !important;
    backdrop-filter: blur(22px);
}
.mn-mobile-panel a {
    font-weight: 550 !important;
}
/* Hero: preserve photographic color, cleaner readability and control placement */
.hero-full-carousel-section {
    background: #080808 !important;
    isolation: isolate;
}
.hero-full-main-image,
.hero-full-backdrop-image,
.hero-full-thumb-media img {
    filter: none !important;
}
.hero-full-title {
    font-family: "Manrope", sans-serif !important;
    font-weight: 600 !important;
    letter-spacing: -0.055em !important;
    text-wrap: balance;
    line-height: 1.055 !important;
}
.hero-full-description {
    font-weight: 450;
    letter-spacing: -0.01em;
}
.hero-full-copy {
    isolation: isolate;
}
.mn-hero-eyebrow {
    font-weight: 650;
    letter-spacing: 0.19em;
}
.hero-full-cta {
    border-radius: 3px !important;
    letter-spacing: 0.1em !important;
    font-weight: 700 !important;
    min-height: 51px;
}
.hero-full-arrow {
    border: 1px solid rgba(255, 255, 255, 0.22) !important;
    background: rgba(10, 10, 10, 0.44) !important;
    backdrop-filter: blur(12px);
    color: #fff !important;
}
.hero-full-arrow:hover {
    background: rgba(255, 255, 255, 0.18) !important;
}
.hero-full-thumb-button {
    opacity: 0.76;
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}
.hero-full-thumb-button.is-active,
.hero-full-thumb-button:hover {
    opacity: 1;
}
/* Subtle marble, not printed wallpaper: top and bottom of each section darker */
.mn-journal-section,
.mn-index-section,
.mn-archive-section,
.mn-film-section,
.mn-process-section,
.mn-contact-section {
    color: #f1f1f1;
    overflow: clip;
}
.mn-journal-section,
.mn-film-section {
    background-color: #0c0c0e !important;
}
.mn-index-section,
.mn-process-section {
    background-color: #070708 !important;
}
.mn-archive-section,
.mn-contact-section {
    background-color: #050506 !important;
}
.mn-journal-section,
.mn-index-section,
.mn-archive-section,
.mn-film-section,
.mn-process-section,
.mn-contact-section {
    background-blend-mode: normal, soft-light, normal !important;
    border-top: 1px solid rgba(255, 255, 255, 0.045);
}
/* consistent section rhythm */
.mn-collection-head,
.mn-compact-head {
    margin-bottom: clamp(28px, 4vw, 54px) !important;
}
.mn-collection-title-row h2,
.mn-compact-head h2,
.mn-contact-copy h2 {
    font-family: "Manrope", sans-serif !important;
    font-weight: 600 !important;
    letter-spacing: -0.048em !important;
    line-height: 1.13 !important;
    text-wrap: balance;
}
.mn-showroom-kicker,
.mn-contact-kicker,
.mn-viewing-eyebrow {
    color: #a9a9ae !important;
    font-size: 10px !important;
    letter-spacing: 0.19em !important;
    font-weight: 750 !important;
}
.mn-collection-intro,
.mn-compact-head p:not(.mn-showroom-kicker),
.mn-contact-intro {
    color: #b7b7ba !important;
    line-height: 1.75 !important;
    max-width: 650px;
}
/* Inventory: one coherent card style, consistent product metadata and pricing */
.mn-watch-grid {
    align-items: stretch;
}
.mn-watch-card {
    background: #121214 !important;
    border: 1px solid rgba(255, 255, 255, 0.085) !important;
    border-radius: 6px !important;
    box-shadow: 0 12px 36px rgba(0, 0, 0, 0.16) !important;
    transition:
        transform 0.25s ease,
        border-color 0.25s ease,
        box-shadow 0.25s ease;
}
.mn-watch-card:hover {
    transform: translateY(-3px);
    border-color: rgba(255, 255, 255, 0.21) !important;
    box-shadow: 0 20px 48px rgba(0, 0, 0, 0.28) !important;
}
.mn-watch-media {
    overflow: hidden;
    background: #171719 !important;
}
.mn-watch-media img,
.mn-reference-thumb img,
.mn-archive-media img,
.mn-film-media img {
    filter: none !important;
}
.mn-watch-body {
    display: flex;
    flex-direction: column;
    gap: 14px;
    min-width: 0;
}
.mn-watch-heading h3 {
    font-family: "Manrope", sans-serif !important;
    letter-spacing: -0.028em !important;
    font-weight: 650 !important;
    overflow-wrap: anywhere;
}
.mn-watch-brand {
    font-weight: 700 !important;
    letter-spacing: 0.13em !important;
}
.mn-watch-reference {
    color: #99999d !important;
    overflow-wrap: anywhere;
}
.mn-watch-price-row {
    margin-top: auto !important;
    border-top: 1px solid rgba(255, 255, 255, 0.075);
    padding-top: 13px;
}
.mn-watch-price {
    font-weight: 750 !important;
    letter-spacing: -0.035em !important;
    white-space: nowrap;
}
.mn-watch-badge {
    background: rgba(12, 12, 12, 0.82) !important;
    color: #eee !important;
    border: 1px solid rgba(255, 255, 255, 0.16) !important;
    backdrop-filter: blur(8px);
}
.mn-filter-bar {
    gap: 16px;
}
.mn-filter-chip {
    transition:
        background 0.2s,
        border-color 0.2s,
        color 0.2s;
}
.mn-sort-control select {
    max-width: 100%;
}
/* Catalog: high density product rows, less unnecessary visual clutter */
.mn-reference-list {
    border-top: 1px solid rgba(255, 255, 255, 0.13);
}
.mn-reference-row {
    box-shadow: none !important;
    background: rgba(14, 14, 16, 0.68) !important;
    border-color: rgba(255, 255, 255, 0.09) !important;
    transition:
        background 0.2s ease,
        border-color 0.2s ease;
}
.mn-reference-row:hover {
    background: #1c1c1e !important;
    border-color: rgba(255, 255, 255, 0.21) !important;
}
.mn-reference-name h3,
.mn-archive-copy h3 {
    font-family: "Manrope", sans-serif !important;
    font-weight: 620 !important;
    letter-spacing: -0.035em !important;
}
.mn-reference-action,
.mn-archive-source {
    letter-spacing: 0.09em !important;
    font-weight: 700 !important;
}
/* Sold: authentic full-color image proof rather than washed-out grayscale */
.mn-archive-piece {
    min-width: 0;
}
.mn-archive-media {
    background: #171719 !important;
}
.mn-archive-copy {
    background: rgba(12, 12, 13, 0.72) !important;
}
.mn-archive-footerline {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}
/* Vlogs: quieter title and image treatment */
.mn-film-card {
    border-radius: 5px !important;
    overflow: hidden;
    background: #101012 !important;
    box-shadow: 0 18px 38px rgba(0, 0, 0, 0.16) !important;
}
.mn-film-copy h3 {
    font-weight: 650 !important;
    letter-spacing: -0.025em !important;
}
/* Ordering: readable three-step progression */
.mn-process-row {
    box-shadow: none !important;
    background: rgba(14, 14, 16, 0.73) !important;
    border-color: rgba(255, 255, 255, 0.095) !important;
}
.mn-process-row h3 {
    font-family: "Manrope", sans-serif !important;
    font-weight: 650 !important;
    letter-spacing: -0.035em !important;
}
.mn-process-row p {
    line-height: 1.72;
}
.mn-trust-strip {
    color: #b0b0b4 !important;
}
/* Contact and appointment: contact CTA is visually primary */
.mn-contact-shell {
    border-radius: 6px;
    overflow: hidden;
}
.mn-contact-button,
.mn-viewing-action {
    font-family: "Manrope", sans-serif !important;
    font-weight: 700 !important;
    letter-spacing: 0.025em !important;
    border-radius: 3px !important;
}
.mn-viewing-panel {
    background: linear-gradient(135deg, #161619, #0b0b0d) !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    border-radius: 4px !important;
    box-shadow: none !important;
}
.mn-viewing-details h3 {
    font-family: "Manrope", sans-serif !important;
    font-weight: 600 !important;
    letter-spacing: -0.045em !important;
    line-height: 1.18 !important;
}
.mn-viewing-address {
    font-style: normal !important;
    line-height: 1.7 !important;
}
/* consistent hover, no blue native color */
a,
a:visited {
    color: inherit;
}
.mn-menu-arrow {
    display: none !important;
}
.mn-mobile-inquire::after,
.mn-nav-inquire::after,
.hero-full-cta::after {
    content: none !important;
    background: none !important;
}
@media (max-width: 1023px) {
    .mn-luxe-nav-inner {
        min-height: 66px !important;
    }
    .mn-mobile-toggle {
        width: 42px;
        height: 42px;
    }
    .mn-mobile-actions {
        gap: 13px;
    }
    .mn-compact-head {
        align-items: flex-start;
    }
}
@media (max-width: 767px) {
    .mn-luxe-nav-inner {
        min-height: 64px !important;
        padding: 0 15px !important;
    }
    .mn-luxe-brand {
        max-width: min(43vw, 154px) !important;
    }
    .mn-mobile-panel {
        padding: 8px 20px 26px !important;
    }
    .mn-mobile-panel a {
        font-size: 16px !important;
        padding: 16px 3px !important;
    }
    .hero-full-carousel-section {
        min-height: calc(100svh - 0px) !important;
        max-height: none !important;
    }
    .hero-full-content {
        padding-left: 22px !important;
        padding-right: 22px !important;
    }
    .hero-full-copy {
        max-width: 100% !important;
    }
    .hero-full-title {
        font-size: clamp(2.15rem, 8.2vw, 3.35rem) !important;
        line-height: 1.12 !important;
        letter-spacing: -0.055em !important;
        max-width: 335px !important;
        margin-left: auto !important;
        margin-right: auto !important;
        text-align: center !important;
        overflow-wrap: normal !important;
        word-break: normal !important;
    }
    .hero-full-description {
        font-size: 12.5px !important;
        line-height: 1.65 !important;
        max-width: 300px !important;
        margin-left: auto !important;
        margin-right: auto !important;
        text-align: center !important;
    }
    .mn-hero-eyebrow {
        font-size: 9px !important;
        letter-spacing: 0.14em !important;
        text-align: center !important;
    }
    .hero-full-cta {
        min-height: 48px !important;
        font-size: 10px !important;
        width: min(100%, 350px) !important;
        justify-content: center !important;
    }
    .hero-full-actions {
        width: 100%;
        display: flex;
        justify-content: center;
    }
    .mn-hero-meta {
        justify-content: center;
        text-align: center;
        font-size: 9px !important;
        gap: 9px !important;
    }
    .hero-full-arrow {
        width: 34px !important;
        height: 34px !important;
    }
    .hero-full-dock {
        gap: 7px !important;
    }
    .mn-journal-section,
    .mn-index-section,
    .mn-archive-section,
    .mn-film-section,
    .mn-process-section,
    .mn-contact-section {
        padding-top: 58px !important;
        padding-bottom: 64px !important;
    }
    .mn-collection-head,
    .mn-compact-head {
        margin-bottom: 23px !important;
    }
    .mn-collection-title-row h2,
    .mn-compact-head h2 {
        font-size: clamp(1.75rem, 7vw, 2.55rem) !important;
        line-height: 1.16 !important;
    }
    .mn-showroom-kicker,
    .mn-contact-kicker,
    .mn-viewing-eyebrow {
        font-size: 9px !important;
    }
    .mn-watch-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 10px !important;
    }
    .mn-watch-body {
        padding: 13px 11px !important;
        gap: 10px !important;
    }
    .mn-watch-heading h3 {
        font-size: 12.5px !important;
        line-height: 1.35 !important;
    }
    .mn-watch-brand,
    .mn-watch-reference {
        font-size: 9px !important;
    }
    .mn-watch-tags {
        gap: 4px !important;
    }
    .mn-watch-tags span {
        font-size: 9px !important;
    }
    .mn-watch-price-row {
        padding-top: 10px !important;
        gap: 6px !important;
    }
    .mn-watch-price {
        font-size: clamp(12px, 3.7vw, 15px) !important;
    }
    .mn-watch-details {
        font-size: 9px !important;
    }
    .mn-reference-row {
        padding: 12px 8px !important;
    }
    .mn-film-grid {
        gap: 16px !important;
    }
    .mn-process-row {
        padding: 22px 18px !important;
    }
    .mn-contact-copy h2 {
        font-size: clamp(2rem, 8vw, 3rem) !important;
    }
    .mn-viewing-panel {
        padding: 22px 18px !important;
    }
}
@media (max-width: 359px) {
    .mn-luxe-brand {
        max-width: 38vw !important;
    }
    .mn-mobile-inquire {
        font-size: 10px !important;
    }
    .hero-full-title {
        font-size: clamp(2rem, 8vw, 2.5rem) !important;
    }
    .mn-watch-grid {
        gap: 8px !important;
    }
    .mn-watch-body {
        padding: 10px 9px !important;
    }
    .mn-watch-price {
        font-size: 12px !important;
    }
}
@media (prefers-reduced-motion: reduce) {
    .mn-watch-card,
    .hero-full-thumb-button,
    .mn-reference-row {
        transition: none !important;
    }
    .mn-watch-card:hover {
        transform: none !important;
    }
}

/* MONTRE NOVA / MOBILE SWIPE + SORT + VISIBLE MARBLE CORRECTIONS */
.mn-site-main {
    background-color: #030304 !important;
}
.mn-journal-section,
.mn-index-section,
.mn-archive-section,
.mn-film-section,
.mn-process-section,
.mn-contact-section {
    /* Clear, finely veined marble beneath a translucent smoky glaze. */
    background-color: #040405 !important;
    background-image:
        linear-gradient(
            160deg,
            rgba(2, 2, 3, 0.78) 0%,
            rgba(4, 4, 5, 0.72) 55%,
            rgba(2, 2, 3, 0.82) 100%
        ),
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1280 960'%3E%3Cg fill='none' stroke='%23d0d0d6' stroke-linecap='round'%3E%3Cpath d='M-120 440 C90 360 115 190 310 235 S545 525 755 382 S1060 155 1400 265' stroke-width='2' opacity='.48'/%3E%3Cpath d='M-115 452 C96 368 119 199 317 246 S551 538 770 392 S1076 166 1400 279' stroke-width='6' opacity='.13'/%3E%3Cpath d='M20 1020 C225 740 420 834 575 625 S780 550 960 310 S1170 295 1310 80' stroke-width='1.6' opacity='.36'/%3E%3Cpath d='M36 1030 C230 751 430 839 586 638 S796 550 970 313 S1179 299 1320 90' stroke-width='5' opacity='.10'/%3E%3Cpath d='M-110 830 C206 637 356 770 520 804 S955 710 1380 872' stroke-width='1.6' opacity='.24'/%3E%3Cpath d='M835 -90 C785 230 1050 305 975 535 S1030 765 1330 850' stroke-width='1.3' opacity='.3'/%3E%3C/g%3E%3C/svg%3E"),
        radial-gradient(
            ellipse at 72% 23%,
            #151518 0%,
            #09090b 46%,
            #020203 100%
        ) !important;
    background-size:
        auto,
        1280px auto,
        cover !important;
    background-position:
        center,
        center top,
        center !important;
    background-repeat: no-repeat, repeat-y, no-repeat !important;
    background-blend-mode: normal !important;
}
/* Do not hide the marble with opaque pseudo elements. */
.mn-archive-grid {
    display: flex !important;
    flex-wrap: nowrap !important;
    width: 100%;
    max-width: 100%;
    overflow-x: auto !important;
    overflow-y: hidden !important;
    overscroll-behavior-inline: contain;
    scroll-snap-type: x mandatory;
    scroll-padding-inline: 4px;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    gap: 14px !important;
    padding: 4px 4px 16px;
    touch-action: pan-x pan-y;
}
.mn-archive-grid::-webkit-scrollbar {
    display: none;
}
.mn-archive-piece {
    flex: 0 0 clamp(215px, 70vw, 310px) !important;
    width: clamp(215px, 70vw, 310px) !important;
    min-width: clamp(215px, 70vw, 310px) !important;
    max-width: clamp(215px, 70vw, 310px) !important;
    scroll-snap-align: start;
    box-sizing: border-box;
    overflow: hidden;
}
.mn-archive-copy,
.mn-archive-copy h3,
.mn-archive-info {
    min-width: 0;
    overflow-wrap: break-word;
}
.mn-archive-source {
    max-width: 100%;
    white-space: normal;
}
@media (min-width: 1024px) {
    .mn-archive-grid {
        display: grid !important;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        overflow: visible !important;
    }
    .mn-archive-piece {
        width: auto !important;
        min-width: 0 !important;
        max-width: none !important;
        flex: auto !important;
    }
}
.mn-sort-panel {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    min-width: 0;
}
.mn-sort-caption {
    font-size: 10px;
    font-weight: 750;
    letter-spacing: 0.15em;
    color: #a4a4aa;
    white-space: nowrap;
}
.mn-sort-options {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 5px;
}
.mn-sort-choice {
    font-family: "Manrope", sans-serif;
    background: rgba(21, 21, 24, 0.9);
    color: #bcbcc1;
    border: 1px solid rgba(255, 255, 255, 0.16);
    border-radius: 3px;
    padding: 10px 12px;
    font-size: 11px;
    line-height: 1.2;
    font-weight: 650;
    cursor: pointer;
    transition:
        background 0.2s,
        color 0.2s,
        border-color 0.2s;
}
.mn-sort-choice.is-active {
    background: #e9e9eb;
    color: #0b0b0d;
    border-color: #e9e9eb;
}
.mn-sort-choice:focus-visible {
    outline: 2px solid white;
    outline-offset: 3px;
}
@media (max-width: 767px) {
    .mn-filter-bar {
        align-items: stretch !important;
    }
    .mn-sort-panel {
        width: 100%;
        display: block;
    }
    .mn-sort-caption {
        display: block;
        margin-bottom: 9px;
    }
    .mn-sort-options {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 5px;
        width: 100%;
    }
    .mn-sort-choice {
        padding: 11px 3px;
        min-width: 0;
        font-size: 10px;
        white-space: nowrap;
    }
    .mn-archive-grid {
        margin-right: 0;
    }
}
@media (max-width: 359px) {
    .mn-sort-choice {
        font-size: 9px;
    }
}

/* FINAL VIEWING PANEL / DESKTOP AND MOBILE ALIGNMENT */
.mn-contact-shell {
    overflow: visible !important;
}
.mn-viewing-panel {
    display: grid !important;
    grid-template-columns: minmax(0, 1fr) auto !important;
    align-items: center !important;
    justify-content: stretch !important;
    column-gap: clamp(28px, 5vw, 100px) !important;
    row-gap: 24px !important;
    width: 100% !important;
    box-sizing: border-box !important;
    margin: clamp(36px, 5vw, 64px) 0 28px !important;
    padding: clamp(32px, 4.2vw, 64px) clamp(26px, 4.2vw, 72px) !important;
    overflow: hidden !important;
    background: linear-gradient(
        125deg,
        rgba(30, 30, 34, 0.96),
        rgba(15, 15, 18, 0.98)
    ) !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    border-radius: 5px !important;
}
.mn-viewing-details {
    min-width: 0 !important;
    max-width: 760px !important;
    margin: 0 !important;
    padding: 0 !important;
}
.mn-viewing-eyebrow {
    margin: 0 0 16px !important;
    font-size: 11px !important;
    letter-spacing: 0.17em !important;
}
.mn-viewing-details h3 {
    font-size: clamp(30px, 3.15vw, 52px) !important;
    line-height: 1.15 !important;
    letter-spacing: -0.045em !important;
    max-width: 17ch !important;
    margin: 0 0 18px !important;
    overflow-wrap: normal !important;
}
.mn-viewing-description {
    max-width: 62ch !important;
    margin: 0 0 22px !important;
    font-size: clamp(13px, 1.1vw, 16px) !important;
    line-height: 1.75 !important;
}
.mn-viewing-address {
    font-size: clamp(13px, 1.05vw, 15px) !important;
    line-height: 1.75 !important;
}
.mn-viewing-action {
    justify-self: end !important;
    align-self: center !important;
    width: auto !important;
    min-width: 208px !important;
    min-height: 54px !important;
    padding: 15px 22px !important;
    justify-content: center !important;
    font-size: 12px !important;
    white-space: nowrap !important;
    text-align: center !important;
}
@media (max-width: 900px) {
    .mn-viewing-panel {
        grid-template-columns: minmax(0, 1fr) !important;
        padding: 30px 26px !important;
    }
    .mn-viewing-action {
        justify-self: start !important;
    }
}
@media (max-width: 640px) {
    .mn-viewing-panel {
        padding: 26px 20px !important;
        margin: 32px 0 24px !important;
        row-gap: 26px !important;
    }
    .mn-viewing-eyebrow {
        font-size: 10px !important;
        margin-bottom: 12px !important;
    }
    .mn-viewing-details h3 {
        font-size: clamp(25px, 7vw, 34px) !important;
        line-height: 1.17 !important;
    }
    .mn-viewing-description {
        font-size: 13px !important;
        margin-bottom: 18px !important;
    }
    .mn-viewing-address {
        font-size: 12.5px !important;
    }
    .mn-viewing-action {
        width: 100% !important;
        min-width: 0 !important;
        justify-self: stretch !important;
    }
}

/* MONTRE NOVA / DEEP NOIR MARBLE BODY FINISH */
:global(html),
:global(body) {
    background-color: #030304 !important;
}
.mn-site-main {
    background: #030304 !important;
}
.mn-journal-section,
.mn-index-section,
.mn-archive-section,
.mn-film-section,
.mn-process-section,
.mn-contact-section {
    border-top: 1px solid rgba(255, 255, 255, 0.045) !important;
}
/* Charcoal surfaces float above the darker marble without heavy gray blocks. */
.mn-watch-card,
.mn-reference-row,
.mn-archive-piece,
.mn-film-card,
.mn-process-row {
    border-color: rgba(255, 255, 255, 0.105) !important;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.21);
}
.mn-filter-bar,
.mn-contact-shell {
    border-color: rgba(255, 255, 255, 0.09) !important;
}
/* Preserve the full-color product imagery and editorial hero. */
.mn-watch-media img,
.mn-archive-media img,
.mn-reference-thumb img,
.hero-full-main-image,
.hero-full-backdrop-image {
    filter: none !important;
}

/* MONTRE NOVA | INVENTORY & CATALOG FINAL RESPONSIVE REFINEMENT */
.mn-watch-grid {
    align-items: stretch !important;
}
.mn-watch-card {
    display: flex !important;
    flex-direction: column !important;
    min-width: 0 !important;
    height: 100% !important;
    overflow: hidden !important;
}
.mn-watch-media {
    flex: none !important;
    width: 100% !important;
    aspect-ratio: 1 / 1 !important;
}
.mn-watch-media img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.mn-watch-body {
    flex: 1 1 auto !important;
    display: flex !important;
    flex-direction: column !important;
    background: #18181a !important;
    padding: 19px 17px !important;
    gap: 0 !important;
}
.mn-watch-heading {
    min-width: 0 !important;
}
.mn-watch-heading h3 {
    font-size: clamp(14px, 1.4vw, 18px) !important;
    line-height: 1.4 !important;
    font-weight: 630 !important;
    letter-spacing: -0.03em !important;
    overflow-wrap: break-word !important;
    word-break: normal !important;
}
.mn-watch-brand {
    color: #b8b8bc !important;
    font-size: 10px !important;
    margin-bottom: 7px !important;
}
.mn-watch-reference {
    color: #98989d !important;
    font-size: 10px !important;
    line-height: 1.35 !important;
    margin-top: 8px !important;
    overflow-wrap: anywhere !important;
}
.mn-watch-tags {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 7px !important;
    margin: auto 0 0 !important;
    padding: 17px 0 15px !important;
    align-content: start !important;
}
.mn-watch-tags span {
    display: inline-flex !important;
    align-items: center !important;
    width: max-content !important;
    max-width: 100% !important;
    border-radius: 3px !important;
    background: #2a2a2d !important;
    color: #e8e8eb !important;
    border: 1px solid #454549 !important;
    font-size: 9px !important;
    font-weight: 750 !important;
    letter-spacing: 0.06em !important;
    line-height: 1.3 !important;
    padding: 6px 9px !important;
    white-space: normal !important;
    overflow-wrap: anywhere !important;
}
.mn-watch-price-row {
    margin-top: 0 !important;
    padding-top: 14px !important;
    border-top: 1px solid #38383c !important;
    align-items: baseline !important;
    gap: 7px !important;
}
.mn-watch-price {
    font-size: clamp(15px, 1.6vw, 22px) !important;
    line-height: 1.25 !important;
}
.mn-watch-details {
    color: #e4e4e7 !important;
    font-size: 10px !important;
    letter-spacing: 0.09em !important;
    white-space: nowrap !important;
}
/* Reference/catalog tiles: equal images, shared padding and text rhythm */
.mn-reference-list {
    background: transparent !important;
    border-top: 0 !important;
}
.mn-reference-row {
    display: flex !important;
    flex-direction: column !important;
    min-width: 0 !important;
    padding: 0 !important;
    border-radius: 5px !important;
    overflow: hidden !important;
    text-align: left !important;
    background: #18181a !important;
}
.mn-reference-thumb {
    display: block !important;
    width: 100% !important;
    aspect-ratio: 5/4 !important;
    height: auto !important;
    overflow: hidden !important;
    background: #161618 !important;
}
.mn-reference-thumb img {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    display: block !important;
}
.mn-reference-name {
    min-height: 83px !important;
    padding: 16px 17px 3px !important;
}
.mn-reference-name > p {
    font-size: 10px !important;
    color: #b5b5b9 !important;
    line-height: 1.35 !important;
    letter-spacing: 0.12em !important;
}
.mn-reference-name h3 {
    font-size: clamp(14px, 1.55vw, 19px) !important;
    line-height: 1.35 !important;
    margin-top: 6px !important;
    overflow-wrap: break-word !important;
}
.mn-reference-code {
    display: block !important;
    padding: 8px 17px 0 !important;
    color: #a4a4a9 !important;
    font-size: 10px !important;
    line-height: 1.5 !important;
    overflow-wrap: anywhere !important;
}
.mn-reference-action {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    align-self: flex-start !important;
    margin: auto 17px 17px !important;
    margin-top: 16px !important;
    padding: 9px 12px !important;
    background: #252528 !important;
    border: 1px solid #48484d !important;
    border-radius: 3px !important;
    color: #f0f0f2 !important;
    font-size: 9px !important;
    letter-spacing: 0.065em !important;
}
@media (max-width: 767px) {
    .mn-watch-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 11px !important;
    }
    .mn-watch-media {
        aspect-ratio: 1/1 !important;
    }
    .mn-watch-body {
        padding: 14px 12px !important;
    }
    .mn-watch-heading h3 {
        font-size: clamp(12px, 3.25vw, 15px) !important;
        line-height: 1.38 !important;
    }
    .mn-watch-brand {
        font-size: 9px !important;
        margin-bottom: 6px !important;
    }
    .mn-watch-reference {
        font-size: 9px !important;
        margin-top: 6px !important;
    }
    .mn-watch-tags {
        padding: 13px 0 12px !important;
        gap: 5px !important;
    }
    .mn-watch-tags span {
        font-size: 8px !important;
        padding: 5px 7px !important;
    }
    .mn-watch-price-row {
        padding-top: 11px !important;
    }
    .mn-watch-price {
        font-size: clamp(14px, 3.65vw, 17px) !important;
    }
    .mn-watch-details {
        font-size: 9px !important;
    }
    .mn-reference-list {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 12px !important;
    }
    .mn-reference-row {
        height: 100% !important;
    }
    .mn-reference-name {
        padding: 12px 12px 2px !important;
        min-height: 70px !important;
    }
    .mn-reference-name > p {
        font-size: 9px !important;
    }
    .mn-reference-name h3 {
        font-size: clamp(13px, 3.6vw, 16px) !important;
    }
    .mn-reference-code {
        padding: 8px 12px 0 !important;
        font-size: 9px !important;
    }
    .mn-reference-action {
        margin: auto 12px 12px !important;
        margin-top: 13px !important;
        padding: 8px 9px !important;
        font-size: 8px !important;
    }
}
@media (max-width: 359px) {
    .mn-watch-body {
        padding: 11px 9px !important;
    }
    .mn-watch-tags span {
        font-size: 7.5px !important;
    }
    .mn-reference-list {
        gap: 8px !important;
    }
    .mn-reference-name {
        padding: 10px 9px 2px !important;
    }
    .mn-reference-code {
        padding-left: 9px !important;
        padding-right: 9px !important;
    }
    .mn-reference-action {
        margin-left: 9px !important;
        margin-right: 9px !important;
    }
}

/* HERO CAROUSEL — FINAL RESPONSIVE ARROW POSITION FIX
   Explicitly cancel older left/right positioning rules. */
.hero-full-carousel-section .hero-full-arrow {
    position: absolute !important;
    top: 50% !important;
    bottom: auto !important;
    transform: translateY(-50%) !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 42px !important;
    height: 42px !important;
    padding: 0 !important;
    border-radius: 50% !important;
    border: 1px solid rgba(255, 255, 255, 0.38) !important;
    background: rgba(8, 8, 10, 0.64) !important;
    color: #f5f5f5 !important;
    box-shadow: 0 5px 24px rgba(0, 0, 0, 0.25) !important;
    -webkit-backdrop-filter: blur(9px);
    backdrop-filter: blur(9px);
    z-index: 42 !important;
}
.hero-full-carousel-section .hero-full-arrow-left {
    left: clamp(12px, 2.2vw, 30px) !important;
    right: auto !important;
}
.hero-full-carousel-section .hero-full-arrow-right {
    right: clamp(12px, 2.2vw, 30px) !important;
    left: auto !important;
}
.hero-full-carousel-section .hero-full-arrow span {
    display: block !important;
    font-family: Arial, sans-serif !important;
    font-size: 21px !important;
    font-weight: 400 !important;
    line-height: 1 !important;
    color: inherit !important;
    opacity: 1 !important;
}
.hero-full-carousel-section .hero-full-arrow:focus-visible {
    outline: 2px solid white !important;
    outline-offset: 3px;
}
@media (max-width: 767px) {
    .hero-full-carousel-section .hero-full-arrow {
        top: 48% !important;
        width: 35px !important;
        height: 35px !important;
        background: rgba(8, 8, 10, 0.66) !important;
    }
    .hero-full-carousel-section .hero-full-arrow-left {
        left: 12px !important;
        right: auto !important;
    }
    .hero-full-carousel-section .hero-full-arrow-right {
        right: 12px !important;
        left: auto !important;
    }
    .hero-full-carousel-section .hero-full-arrow span {
        font-size: 18px !important;
    }
}

/* MOBILE HERO — symmetric edge navigation; keep actions out of the title area. */
.hero-full-carousel-section {
    position: relative;
}
.hero-full-carousel-section > button.mn-hero-next-edge {
    position: absolute !important;
    inset: 46% 12px auto auto !important;
    margin: 0 !important;
    transform: translateY(-50%) !important;
}
@media (max-width: 767px) {
    .hero-full-carousel-section > button.hero-full-arrow-left {
        inset: 46% auto auto 12px !important;
        margin: 0 !important;
        transform: translateY(-50%) !important;
    }
    .hero-full-carousel-section > button.mn-hero-next-edge {
        inset: 46% 12px auto auto !important;
        margin: 0 !important;
        transform: translateY(-50%) !important;
    }
}

/* CATALOG PREVIEW — COMPACT, CONSISTENT CARD RHYTHM (DESKTOP + MOBILE)
   Overrides legacy oversized grid-row / flex spacing rules. */
.mn-reference-list {
    display: grid !important;
    grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    gap: 16px !important;
    align-items: start !important;
    background: transparent !important;
}
.mn-reference-row {
    position: relative !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: flex-start !important;
    align-items: stretch !important;
    align-self: start !important;
    height: auto !important;
    min-height: 0 !important;
    max-height: none !important;
    grid-template-columns: none !important;
    grid-template-rows: none !important;
    gap: 0 !important;
    padding: 0 !important;
    overflow: hidden !important;
    border-radius: 8px !important;
    background: #18181b !important;
    border: 1px solid rgba(255, 255, 255, 0.09) !important;
    color: #f2f2f3 !important;
    text-align: left !important;
}
.mn-reference-number {
    display: none !important;
}
.mn-reference-thumb {
    order: 0 !important;
    position: relative !important;
    display: block !important;
    width: 100% !important;
    height: auto !important;
    aspect-ratio: 1 / 1 !important;
    flex: none !important;
    margin: 0 !important;
    overflow: hidden !important;
}
.mn-reference-thumb img {
    display: block !important;
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    filter: none !important;
}
.mn-reference-name {
    order: 1 !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: flex-start !important;
    flex: none !important;
    width: 100% !important;
    min-height: 0 !important;
    height: auto !important;
    margin: 0 !important;
    padding: 14px 15px 0 !important;
}
.mn-reference-name > p {
    margin: 0 !important;
    font-size: 10px !important;
    line-height: 1.5 !important;
    color: #a7a7ae !important;
    letter-spacing: 0.11em !important;
}
.mn-reference-name h3 {
    margin: 5px 0 0 !important;
    min-height: 0 !important;
    font-size: clamp(14px, 1.5vw, 19px) !important;
    font-weight: 600 !important;
    line-height: 1.35 !important;
    letter-spacing: -0.035em !important;
    overflow-wrap: break-word !important;
}
.mn-reference-code {
    order: 2 !important;
    display: block !important;
    flex: none !important;
    width: 100% !important;
    height: auto !important;
    min-height: 0 !important;
    margin: 0 !important;
    padding: 10px 15px 0 !important;
    color: #a9a9af !important;
    font-size: 10px !important;
    line-height: 1.55 !important;
    letter-spacing: 0.035em !important;
    overflow-wrap: anywhere !important;
}
.mn-reference-action {
    order: 3 !important;
    display: inline-flex !important;
    align-self: flex-start !important;
    flex: none !important;
    width: auto !important;
    height: auto !important;
    min-height: 0 !important;
    margin: 14px 15px 15px !important;
    padding: 9px 11px !important;
    border: 1px solid #48484c !important;
    border-radius: 4px !important;
    background: #242427 !important;
    color: #f1f1f2 !important;
    font-size: 10px !important;
    font-weight: 650 !important;
    letter-spacing: 0.035em !important;
    text-align: center !important;
    white-space: normal !important;
}
@media (max-width: 1023px) {
    .mn-reference-list {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 13px !important;
    }
}
@media (max-width: 639px) {
    .mn-reference-list {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 10px !important;
    }
    .mn-reference-row {
        border-radius: 7px !important;
    }
    .mn-reference-thumb {
        aspect-ratio: 1 / 1 !important;
    }
    .mn-reference-name {
        padding: 12px 11px 0 !important;
    }
    .mn-reference-name > p {
        font-size: 9px !important;
    }
    .mn-reference-name h3 {
        font-size: clamp(13px, 3.6vw, 16px) !important;
        line-height: 1.35 !important;
    }
    .mn-reference-code {
        padding: 9px 11px 0 !important;
        font-size: 9px !important;
    }
    .mn-reference-action {
        margin: 12px 11px 13px !important;
        padding: 8px 9px !important;
        font-size: 9px !important;
    }
}
</style>
