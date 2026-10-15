<script setup>
import MontreLogo from "@/Components/MontreLogo.vue";

import { Head, Link } from "@inertiajs/vue3";

import { computed, ref, onBeforeUnmount, watch } from "vue";

const props = defineProps({
    watch: {
        type: Object,

        required: true,
    },

    availableWatches: {
        type: [Array, Object],

        default: () => [],
    },

    relatedWatches: {
        type: [Array, Object],

        default: () => [],
    },

    canLogin: {
        type: Boolean,

        default: true,
    },
});

const selectedImageIndex = ref(0);

const copied = ref(false);

let copyResetTimer = null;

const showImagePreview = ref(false);

const touchStartX = ref(0);

const touchStartY = ref(0);

const swipeThreshold = 45;

const messengerUsername = "montrenova";

const images = computed(() => props.watch.images || []);

const displayName = computed(() => {
    return `${props.watch.brand || ""} ${props.watch.model_name || ""}`.trim();
});

const selectedImageObject = computed(() => {
    if (!images.value.length) return null;

    return images.value[selectedImageIndex.value] || images.value[0];
});

const selectedImage = computed(() => {
    if (!selectedImageObject.value) return null;

    return (
        selectedImageObject.value.hd_url ||
        selectedImageObject.value.image_url ||
        selectedImageObject.value.thumbnail_url ||
        null
    );
});

const hasMultipleImages = computed(() => images.value.length > 1);

const hasDiscount = computed(() => {
    return (
        Number(props.watch.discounted_price || 0) > 0 &&
        Number(props.watch.selling_price || 0) >
            Number(props.watch.discounted_price || 0)
    );
});

const finalPrice = computed(() => {
    if (hasDiscount.value) return props.watch.discounted_price;

    return props.watch.price || props.watch.selling_price || 0;
});

const originalPrice = computed(() => {
    return props.watch.selling_price || props.watch.price || 0;
});

const statusLabel = computed(() => {
    const status = String(props.watch.status || "available").toLowerCase();

    const labels = {
        available: "Available",

        reserved: "Reserved",

        sold: "Sold",

        hidden: "Hidden",

        draft: "Draft",
    };

    return labels[status] || "Available";
});

const statusClass = computed(() => {
    const status = String(props.watch.status || "available").toLowerCase();

    const classes = {
        available: "border-emerald-400/20 bg-emerald-400/10 text-emerald-200",

        reserved: "border-amber-400/20 bg-amber-400/10 text-amber-200",

        sold: "border-zinc-400/20 bg-zinc-400/10 text-zinc-300",

        hidden: "border-red-400/20 bg-red-400/10 text-red-200",

        draft: "border-white/10 bg-white/[0.05] text-zinc-400",
    };

    return classes[status] || classes.available;
});

const isAvailable = computed(() => {
    return (
        String(props.watch.status || "available").toLowerCase() === "available"
    );
});

const peso = (value) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",

        currency: "PHP",

        minimumFractionDigits: 0,
    }).format(Number(value || 0));
};

const productDescription = computed(() => {
    return String(props.watch.description || "").trim();
});

const metaDescription = computed(() => {
    const reference = props.watch.reference_number
        ? ` ${props.watch.reference_number}`
        : "";

    return `${displayName.value}${reference} at Montre Nova. View actual photos, price, specifications, warranty details, and inquiry options.`;
});

const productImageUrls = computed(() => {
    return images.value

        .map((image) => image.hd_url || image.image_url || image.thumbnail_url)

        .filter(Boolean);
});

const productJsonLd = computed(() => {
    return {
        "@context": "https://schema.org",

        "@type": "Product",

        name: displayName.value || "Montre Nova Watch",

        brand: {
            "@type": "Brand",

            name: props.watch.brand || "Montre Nova",
        },

        sku: props.watch.reference_number || String(props.watch.id),

        image: productImageUrls.value,

        description: productDescription.value || metaDescription.value,

        category: props.watch.category || "Watch",

        offers: {
            "@type": "Offer",

            priceCurrency: "PHP",

            price: Number(finalPrice.value || 0),

            availability: isAvailable.value
                ? "https://schema.org/InStock"
                : "https://schema.org/SoldOut",

            itemCondition: String(props.watch.condition || "")
                .toLowerCase()

                .includes("brand")
                ? "https://schema.org/NewCondition"
                : "https://schema.org/UsedCondition",

            url: typeof window !== "undefined" ? window.location.href : "",
        },
    };
});

const heroHighlights = computed(() => {
    return [
        {
            label: "Condition",

            value: props.watch.condition,
        },

        {
            label: "Reference",

            value: props.watch.reference_number,
        },

        {
            label: "Warranty",

            value: props.watch.warranty_type,
        },

        {
            label: "Box / Papers",

            value: props.watch.box_papers,
        },
    ].filter((item) => item.value);
});

const specGroups = computed(() => [
    {
        title: "Core Details",

        items: [
            { label: "Reference", value: props.watch.reference_number },

            { label: "Condition", value: props.watch.condition },

            { label: "Category", value: props.watch.category },

            { label: "Movement", value: props.watch.movement },
        ],
    },

    {
        title: "Case & Build",

        items: [
            { label: "Case Size", value: props.watch.case_size },

            { label: "Case Material", value: props.watch.case_material },

            { label: "Dial Color", value: props.watch.dial_color },

            { label: "Crystal", value: props.watch.crystal },
        ],
    },

    {
        title: "Strap & Resistance",

        items: [
            { label: "Bracelet / Strap", value: props.watch.bracelet_or_strap },

            { label: "Water Resistance", value: props.watch.water_resistance },
        ],
    },

    {
        title: "Inclusions",

        items: [
            { label: "Box / Papers", value: props.watch.box_papers },

            { label: "Warranty", value: props.watch.warranty_type },
        ],
    },
]);

const availableSpecGroups = computed(() => {
    return specGroups.value

        .map((group) => ({
            ...group,

            items: group.items.filter((item) => item.value),
        }))

        .filter((group) => group.items.length);
});

const compactSpecs = computed(() => {
    return availableSpecGroups.value

        .flatMap((group) => group.items)

        .filter((item) => item.value)

        .slice(0, 12);
});

const shortSpecLabel = (label) => {
    const labels = {
        Reference: "Reference",

        Condition: "Condition",

        Category: "Category",

        Movement: "Movement",

        "Case Size": "Case Size",

        "Case Material": "Case",

        "Dial Color": "Dial",

        Crystal: "Crystal",

        "Bracelet / Strap": "Bracelet",

        "Water Resistance": "Resistance",

        "Box / Papers": "Box / Papers",

        Warranty: "Warranty",
    };

    return labels[label] || label;
};

const buyingNotes = computed(() => {
    return [
        {
            title: "Actual Photos",

            description:
                "Review the gallery photos before reserving the watch.",
        },

        {
            title: "Payment",

            description:
                "Payment details and reservation instructions are sent only through official Montre Nova channels.",
        },

        {
            title: "Shipping",

            description:
                "Metro Manila orders may be delivered through Lalamove. Nationwide orders may be shipped through LBC.",
        },

        {
            title: "Warranty",

            description:
                props.watch.warranty_type ||
                "Selected pieces include Montre Card service warranty support.",
        },
    ];
});

const defaultInquiryMessage = computed(() => {
    return `Hi Montre Nova, I'm interested in this watch:







${displayName.value}



${props.watch.reference_number ? `Ref. ${props.watch.reference_number}` : ""}



Price: ${peso(finalPrice.value || props.watch.price)}







Is this still available?`;
});

const messengerInquiryUrl = computed(() => {
    const message = encodeURIComponent(defaultInquiryMessage.value);

    return `https://m.me/${messengerUsername}?text=${message}`;
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

const availableWatchSource = computed(() => {
    const sources = [
        props.availableWatches,

        props.relatedWatches,

        props.watch?.available_watches,

        props.watch?.availableWatches,

        props.watch?.related_watches,

        props.watch?.relatedWatches,
    ];

    for (const source of sources) {
        const items = toCollectionArray(source);

        if (items.length) {
            return items;
        }
    }

    return [];
});

const carouselWatches = computed(() => {
    return availableWatchSource.value

        .filter((item) => {
            if (!item?.id || item.id === props.watch.id) return false;

            const status = String(item.status || "available").toLowerCase();

            return status === "available";
        })

        .slice(0, 12);
});

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

const watchCardImage = (watch) => {
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

const itemHasDiscount = (watch) => {
    return (
        Number(watch?.discounted_price || 0) > 0 &&
        Number(watch?.selling_price || 0) > Number(watch?.discounted_price || 0)
    );
};

const itemFinalPrice = (watch) => {
    if (!watch) return 0;

    if (itemHasDiscount(watch)) {
        return watch.discounted_price;
    }

    return watch.price || watch.selling_price || watch.discounted_price || 0;
};

const itemOriginalPrice = (watch) => {
    return Number(watch?.selling_price || watch?.price || 0);
};

const selectImage = (index) => {
    selectedImageIndex.value = index;
};

const previousImage = () => {
    if (!images.value.length) return;

    selectedImageIndex.value =
        selectedImageIndex.value === 0
            ? images.value.length - 1
            : selectedImageIndex.value - 1;
};

const nextImage = () => {
    if (!images.value.length) return;

    selectedImageIndex.value =
        selectedImageIndex.value === images.value.length - 1
            ? 0
            : selectedImageIndex.value + 1;
};

const handleTouchStart = (event) => {
    const touch = event.changedTouches[0];

    touchStartX.value = touch.clientX;

    touchStartY.value = touch.clientY;
};

const handleTouchEnd = (event) => {
    const touch = event.changedTouches[0];

    const deltaX = touch.clientX - touchStartX.value;

    const deltaY = touch.clientY - touchStartY.value;

    const isHorizontalSwipe = Math.abs(deltaX) > Math.abs(deltaY);

    const isEnoughSwipe = Math.abs(deltaX) > swipeThreshold;

    if (!isHorizontalSwipe || !isEnoughSwipe) return;

    if (deltaX < 0) {
        nextImage();
    } else {
        previousImage();
    }
};

const openImagePreview = () => {
    if (!selectedImage.value) return;

    showImagePreview.value = true;
};

const closeImagePreview = () => {
    showImagePreview.value = false;
};

const openMessengerInquiry = () => {
    window.open(messengerInquiryUrl.value, "_blank", "noopener,noreferrer");
};

const copyLink = async () => {
    try {
        if (!navigator?.clipboard) return;

        await navigator.clipboard.writeText(window.location.href);

        copied.value = true;

        setTimeout(() => {
            copied.value = false;
        }, 1800);
    } catch (error) {
        console.warn("Unable to copy link:", error);
    }
};

watch(images, (next) => {
    if (selectedImageIndex.value >= next.length) selectedImageIndex.value = 0;
});

onBeforeUnmount(() => {
    if (copyResetTimer) clearTimeout(copyResetTimer);
});
</script>

<template>
    <Head :title="`${displayName} | Montre Nova`">
        <meta name="description" :content="metaDescription" />

        <meta property="og:title" :content="`${displayName} | Montre Nova`" />

        <meta property="og:description" :content="metaDescription" />

        <meta property="og:type" content="product" />

        <meta
            v-if="selectedImage"
            property="og:image"
            :content="selectedImage"
        />

        <component
            :is="'script'"
            type="application/ld+json"
            v-html="JSON.stringify(productJsonLd)"
        />
    </Head>

    <div
        class="mn-product-page relative min-h-screen overflow-x-hidden pb-10 text-white antialiased"
    >
        <!-- Dark marble surface shared with the storefront. Fixed behind content. -->

        <div class="mn-product-marble" aria-hidden="true"></div>

        <!-- HEADER -->

        <header
            class="mn-product-header fixed inset-x-0 top-0 z-[90] backdrop-blur-2xl"
        >
            <div
                class="mn-product-nav-inner mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8"
            >
                <Link
                    :href="route('welcome')"
                    class="flex min-w-0 items-center rounded-xl transition duration-300 hover:opacity-80 active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-white/40"
                >
                    <MontreLogo />
                </Link>

                <Link
                    :href="route('welcome')"
                    class="mn-product-home group inline-flex h-10 w-10 items-center justify-center border border-white/15 text-zinc-200 transition duration-300 hover:bg-white hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white/50 sm:h-auto sm:w-auto sm:gap-2 sm:px-4 sm:py-2.5"
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

        <main class="relative z-10 pt-[4.75rem] pb-8 lg:pb-14">
            <!-- PRODUCT HERO -->

            <section
                class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8 lg:py-10"
            >
                <div
                    class="mb-5 flex items-center gap-2 overflow-hidden text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600"
                >
                    <Link
                        :href="route('welcome')"
                        class="shrink-0 transition hover:text-white"
                    >
                        Home
                    </Link>

                    <span class="shrink-0 text-zinc-700">/</span>

                    <Link
                        :href="route('welcome') + '#collection'"
                        class="shrink-0 transition hover:text-white"
                    >
                        Collection
                    </Link>

                    <span class="shrink-0 text-zinc-700">/</span>

                    <span class="truncate text-zinc-400">
                        {{ displayName || watch.brand || "Watch" }}
                    </span>
                </div>

                <div
                    class="product-hero-grid grid gap-5 lg:grid-cols-[minmax(0,58%)_minmax(390px,1fr)] lg:items-stretch xl:grid-cols-[minmax(0,60%)_minmax(410px,1fr)]"
                >
                    <!-- GALLERY -->

                    <section
                        class="fade-up relative lg:h-full lg:sticky lg:top-24"
                    >
                        <div
                            class="absolute -inset-3 rounded-[1.6rem] bg-white/[0.035] blur-2xl"
                        ></div>

                        <div
                            class="premium-panel gallery-panel relative overflow-hidden"
                        >
                            <div class="shine-line"></div>

                            <div
                                class="flex items-center justify-between gap-3 border-b border-white/10 px-4 py-3 sm:px-5"
                            >
                                <div>
                                    <p class="micro-label">Product Gallery</p>

                                    <p class="mt-1 text-xs text-zinc-500">
                                        Swipe photos · tap to zoom
                                    </p>
                                </div>

                                <div
                                    v-if="images.length"
                                    class="shrink-0 rounded-xl border border-white/10 bg-white/[0.04] px-3 py-2 text-[10px] font-black uppercase tracking-[0.16em] text-zinc-300"
                                >
                                    {{ selectedImageIndex + 1 }} /

                                    {{ images.length }}
                                </div>
                            </div>

                            <div
                                class="gallery-stage relative flex h-[350px] touch-pan-y select-none items-center justify-center overflow-hidden bg-[radial-gradient(circle_at_center,rgba(255,255,255,0.08),transparent_44%)] sm:h-[510px] lg:h-auto lg:min-h-0"
                                @touchstart.passive="handleTouchStart"
                                @touchend.passive="handleTouchEnd"
                            >
                                <button
                                    type="button"
                                    class="absolute inset-0 z-[1] cursor-zoom-in"
                                    aria-label="Open image preview"
                                    @click="openImagePreview"
                                ></button>

                                <img
                                    v-if="selectedImage"
                                    :key="selectedImage"
                                    :src="selectedImage"
                                    :alt="displayName"
                                    draggable="false"
                                    class="pointer-events-none h-full w-full object-contain p-4 transition duration-500 sm:p-7 lg:p-8"
                                />

                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center"
                                >
                                    <div
                                        class="flex flex-col items-center text-center"
                                    >
                                        <div class="mn-placeholder">
                                            <img
                                                src="/images/montre-nova-logo.png"
                                                alt="Montre Nova"
                                                class="h-20 w-20 object-contain opacity-70"
                                            />
                                        </div>

                                        <p class="mt-4 micro-label">
                                            Montre Nova
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="pointer-events-none absolute left-4 top-4 z-10 flex max-w-[70%] flex-wrap gap-2"
                                >
                                    <span
                                        class="rounded-lg border px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.12em] backdrop-blur"
                                        :class="statusClass"
                                    >
                                        {{ statusLabel }}
                                    </span>

                                    <span
                                        v-if="hasDiscount"
                                        class="rounded-sm border border-white/25 bg-black/65 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-zinc-200 backdrop-blur"
                                    >
                                        Below SRP
                                    </span>
                                </div>

                                <div
                                    v-if="hasMultipleImages"
                                    class="absolute bottom-4 left-1/2 z-10 flex -translate-x-1/2 items-center gap-1.5 rounded-xl border border-white/10 bg-black/70 px-3 py-2 backdrop-blur"
                                >
                                    <button
                                        v-for="(_, index) in images"
                                        :key="index"
                                        type="button"
                                        class="relative z-20 h-1.5 rounded-md transition"
                                        :class="
                                            selectedImageIndex === index
                                                ? 'w-6 bg-white'
                                                : 'w-1.5 bg-white/35 hover:bg-white/60'
                                        "
                                        @click.stop="selectImage(index)"
                                    ></button>
                                </div>

                                <button
                                    v-if="hasMultipleImages"
                                    type="button"
                                    class="gallery-arrow left-3 sm:left-5"
                                    aria-label="Previous image"
                                    @click.stop="previousImage"
                                >
                                    ‹
                                </button>

                                <button
                                    v-if="hasMultipleImages"
                                    type="button"
                                    class="gallery-arrow right-3 sm:right-5"
                                    aria-label="Next image"
                                    @click.stop="nextImage"
                                >
                                    ›
                                </button>
                            </div>

                            <div
                                v-if="hasMultipleImages"
                                class="border-t border-white/10 bg-[#080808]/80 p-3 lg:p-3"
                            >
                                <div
                                    class="thin-scrollbar flex gap-2 overflow-x-auto pb-1"
                                >
                                    <button
                                        v-for="(image, index) in images"
                                        :key="image.id || index"
                                        type="button"
                                        class="h-16 w-16 shrink-0 overflow-hidden rounded-xl border bg-[#050505] p-1 transition sm:h-20 sm:w-20 lg:h-[4.5rem] lg:w-[4.5rem]"
                                        :class="
                                            selectedImageIndex === index
                                                ? 'border-white bg-white/[0.06]'
                                                : 'border-white/10 hover:border-white/40'
                                        "
                                        @click="selectImage(index)"
                                    >
                                        <img
                                            :src="
                                                image.thumbnail_url ||
                                                image.image_url ||
                                                image.hd_url
                                            "
                                            alt=""
                                            class="h-full w-full rounded-lg object-cover"
                                            loading="lazy"
                                        />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- PRODUCT INFO -->

                    <section
                        class="fade-up lg:h-full lg:[animation-delay:80ms]"
                    >
                        <div
                            class="premium-panel product-panel flex min-h-0 flex-col overflow-hidden"
                        >
                            <div class="shine-line"></div>

                            <!-- PRODUCT DETAILS + PRICE + CTA -->

                            <div class="bg-white/[0.025] p-5 sm:p-6 lg:p-6">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="rounded-lg border px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.12em]"
                                        :class="statusClass"
                                    >
                                        {{ statusLabel }}
                                    </span>

                                    <span
                                        v-if="watch.category"
                                        class="soft-chip"
                                    >
                                        {{ watch.category }}
                                    </span>

                                    <span
                                        v-if="watch.condition"
                                        class="soft-chip"
                                    >
                                        {{ watch.condition }}
                                    </span>

                                    <span
                                        v-if="hasDiscount"
                                        class="rounded-sm border border-white/25 bg-white/[0.06] px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-zinc-200"
                                    >
                                        Below SRP
                                    </span>
                                </div>

                                <div class="mt-7">
                                    <p class="micro-label">
                                        {{ watch.brand || "Montre Nova" }}
                                    </p>

                                    <h1
                                        class="mn-product-title mt-3 text-3xl font-semibold leading-[1.12] tracking-[-0.04em] text-white sm:text-4xl xl:text-[2.85rem]"
                                    >
                                        {{ watch.model_name }}
                                    </h1>
                                </div>

                                <div
                                    class="mt-7 rounded-2xl border border-white/10 bg-black/35 p-5 shadow-xl shadow-black/20"
                                >
                                    <p class="micro-label">Asking Price</p>

                                    <div
                                        class="mt-3 flex flex-wrap items-end gap-3"
                                    >
                                        <p
                                            class="mn-product-price text-3xl font-semibold tracking-[-0.045em] text-white sm:text-[2.5rem]"
                                        >
                                            {{ peso(finalPrice) }}
                                        </p>

                                        <p
                                            v-if="hasDiscount"
                                            class="pb-1 text-sm font-semibold text-zinc-500 line-through"
                                        >
                                            {{ peso(originalPrice) }}
                                        </p>
                                    </div>
                                </div>

                                <p
                                    v-if="productDescription"
                                    class="mt-5 text-sm leading-7 text-zinc-400 sm:text-base"
                                >
                                    {{ productDescription }}
                                </p>

                                <button
                                    type="button"
                                    class="primary-action mt-6 w-full"
                                    @click="openMessengerInquiry()"
                                >
                                    Inquire on Messenger
                                </button>
                            </div>

                            <!-- INLINE SPECS -->

                            <div
                                v-if="compactSpecs.length"
                                class="inline-specs border-t border-white/10 bg-black/20 p-4 sm:p-5 lg:p-5"
                            >
                                <div class="inline-spec-head">
                                    <div>
                                        <p class="micro-label">
                                            Specifications
                                        </p>

                                        <h2
                                            class="mt-1.5 text-base font-semibold tracking-[-0.02em] text-white sm:text-lg"
                                        >
                                            Watch details
                                        </h2>
                                    </div>
                                </div>

                                <div class="inline-spec-grid">
                                    <div
                                        v-for="spec in compactSpecs"
                                        :key="spec.label"
                                        class="inline-spec-row"
                                    >
                                        <span>{{
                                            shortSpecLabel(spec.label)
                                        }}</span>

                                        <p>{{ spec.value }}</p>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-else
                                class="inline-specs border-t border-white/10 bg-black/20 p-4 sm:p-5 lg:p-5"
                            >
                                <div class="inline-spec-empty">
                                    Full specifications available upon request.
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </section>

            <!-- AVAILABLE WATCHES CAROUSEL -->

            <section
                v-if="carouselWatches.length"
                class="mx-auto max-w-7xl px-4 pb-10 sm:px-6 lg:px-8"
            >
                <div class="carousel-shell fade-up">
                    <div class="shine-line"></div>

                    <div
                        class="flex flex-col justify-between gap-4 border-white/10 p-5 sm:p-6 lg:flex-row lg:items-end"
                    >
                        <div>
                            <p class="micro-label">More Available Watches</p>

                            <h2
                                class="mn-related-title mt-3 text-2xl font-semibold tracking-[-0.035em] text-white sm:text-3xl"
                            >
                                Continue browsing
                            </h2>

                            <p
                                class="mt-3 max-w-xl text-sm leading-6 text-zinc-500"
                            >
                                Other available pieces you can tap to view next.
                            </p>
                        </div>
                    </div>

                    <div class="relative">
                        <div
                            class="pointer-events-none absolute inset-y-0 left-0 z-10 hidden w-16 bg-gradient-to-r from-[#0A0A0B] to-transparent sm:block"
                        ></div>

                        <div
                            class="pointer-events-none absolute inset-y-0 right-0 z-10 hidden w-16 bg-gradient-to-l from-[#0A0A0B] to-transparent sm:block"
                        ></div>

                        <div
                            class="carousel-row thin-scrollbar"
                            aria-label="More available watches, swipe to browse"
                        >
                            <Link
                                v-for="watchItem in carouselWatches"
                                :key="watchItem.id"
                                :href="
                                    route('public.watches.show', watchItem.id)
                                "
                                class="carousel-card mn-more-card group"
                            >
                                <div class="mn-more-photo">
                                    <img
                                        v-if="watchCardImage(watchItem)"
                                        :src="watchCardImage(watchItem)"
                                        :alt="`${watchItem.brand} ${watchItem.model_name}`"
                                        loading="lazy"
                                        class="mn-more-image"
                                    />
                                    <div v-else class="mn-more-placeholder">
                                        MN
                                    </div>
                                    <span class="mn-more-status"
                                        >AVAILABLE</span
                                    >
                                </div>
                                <div class="mn-more-content">
                                    <div class="mn-more-heading">
                                        <p class="mn-more-brand">
                                            {{
                                                watchItem.brand || "Montre Nova"
                                            }}
                                        </p>
                                        <h3 class="mn-more-title">
                                            {{ watchItem.model_name }}
                                        </h3>
                                        <p class="mn-more-ref">
                                            {{
                                                watchItem.reference_number ||
                                                "Reference on request"
                                            }}
                                        </p>
                                    </div>
                                    <div class="mn-more-price-row">
                                        <div class="mn-more-price-block">
                                            <p class="mn-more-price">
                                                {{
                                                    peso(
                                                        itemFinalPrice(
                                                            watchItem,
                                                        ),
                                                    )
                                                }}
                                            </p>
                                            <p
                                                v-if="
                                                    itemHasDiscount(watchItem)
                                                "
                                                class="mn-more-original"
                                            >
                                                {{
                                                    peso(
                                                        itemOriginalPrice(
                                                            watchItem,
                                                        ),
                                                    )
                                                }}
                                            </p>
                                        </div>
                                        <span class="mn-more-view">VIEW</span>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- FULLSCREEN IMAGE PREVIEW -->

        <Teleport to="body">
            <div
                v-if="showImagePreview"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/95 p-4"
                @touchstart.passive="handleTouchStart"
                @touchend.passive="handleTouchEnd"
            >
                <button
                    type="button"
                    class="absolute right-4 top-4 z-20 rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-bold text-white backdrop-blur transition hover:bg-white hover:text-black"
                    @click="closeImagePreview"
                >
                    Close
                </button>

                <button
                    v-if="hasMultipleImages"
                    type="button"
                    class="absolute left-4 top-1/2 z-20 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-xl border border-white/10 bg-white/10 text-3xl text-white backdrop-blur transition hover:bg-white hover:text-black"
                    @click.stop="previousImage"
                >
                    ‹
                </button>

                <img
                    v-if="selectedImage"
                    :src="selectedImage"
                    :alt="displayName"
                    class="max-h-[88vh] max-w-full rounded-xl object-contain"
                />

                <button
                    v-if="hasMultipleImages"
                    type="button"
                    class="absolute right-4 top-1/2 z-20 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-xl border border-white/10 bg-white/10 text-3xl text-white backdrop-blur transition hover:bg-white hover:text-black"
                    @click.stop="nextImage"
                >
                    ›
                </button>

                <div
                    v-if="images.length"
                    class="absolute bottom-5 left-1/2 z-20 flex -translate-x-1/2 items-center gap-3 rounded-xl border border-white/10 bg-black/70 px-4 py-2 text-xs font-bold text-white backdrop-blur"
                >
                    {{ selectedImageIndex + 1 }} / {{ images.length }}
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
/* Montre Nova | Product detail design aligned with the dark marble storefront */

@import url("https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap");

.mn-product-page {
    --mn-ink: #09090b;

    --mn-surface: #141416;

    --mn-stroke: rgba(255, 255, 255, 0.11);

    position: relative;

    isolation: isolate;

    background: #070708;

    color: #f3f3f4;

    font-family: "Manrope", ui-sans-serif, system-ui, sans-serif;

    font-weight: 500;

    -webkit-font-smoothing: antialiased;
}

.mn-product-marble {
    position: fixed;

    inset: 0;

    pointer-events: none;

    z-index: 0;

    background-color: #070708;

    background-image:
        radial-gradient(
            ellipse at 78% 4%,

            rgba(92, 93, 99, 0.15) 0%,

            transparent 43%
        ),
        radial-gradient(
            ellipse at 8% 70%,

            rgba(71, 72, 79, 0.14) 0%,

            transparent 49%
        ),
        linear-gradient(
            114deg,
            transparent 0 24.82%,

            rgba(176, 178, 184, 0.075) 24.93%,

            transparent 25.08% 55.7%,

            rgba(172, 173, 180, 0.045) 55.85%,

            transparent 55.98%
        ),
        linear-gradient(
            150deg,
            transparent 0 37.85%,

            rgba(208, 209, 212, 0.045) 37.98%,

            transparent 38.12% 72.1%,

            rgba(183, 185, 192, 0.055) 72.22%,

            transparent 72.35%
        ),
        linear-gradient(180deg, #09090b 0%, #111114 49%, #080809 100%);

    background-size:
        auto,
        auto,
        125% 140%,
        140% 150%,
        auto;
}

.mn-product-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.11);

    background: rgba(9, 9, 11, 0.91);

    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.22);
}

.mn-product-home {
    border-radius: 3px;

    font-size: 11px;

    letter-spacing: 0.13em;
}

.mn-product-page .premium-panel,
.mn-product-page .carousel-shell {
    border: 1px solid var(--mn-stroke);

    border-radius: 7px;

    background: linear-gradient(
        145deg,
        rgba(28, 28, 31, 0.95),
        rgba(13, 13, 15, 0.98)
    );

    box-shadow: 0 16px 56px rgba(0, 0, 0, 0.28);
}

.mn-product-page .shine-line {
    opacity: 0.35;
}

.mn-product-page .micro-label {
    color: #a9a9af;

    font-size: 10px;

    font-weight: 700;

    letter-spacing: 0.17em;
}

.mn-product-page .soft-chip {
    border-radius: 3px;

    font-weight: 600;

    color: #d3d3d7;
}

.mn-product-page .inline-spec-row {
    border-radius: 4px;

    background: rgba(255, 255, 255, 0.025);

    box-shadow: none;
}

.mn-product-page .inline-spec-row span {
    color: #a7a7ad;

    font-weight: 650;

    letter-spacing: 0.1em;
}

.mn-product-page .inline-spec-row p {
    color: #f1f1f2;

    font-weight: 600;
}

.mn-product-page .primary-action,
.mn-product-page .secondary-action {
    border-radius: 3px;

    font-weight: 700;

    letter-spacing: 0.1em;

    box-shadow: none;
}

.mn-product-page .primary-action {
    background: #f0f0f0;

    color: #111;
}

.mn-product-page .primary-action:hover {
    background: #d4d4d5;

    box-shadow: none;
}

.mn-product-page .gallery-arrow {
    border-radius: 50%;
}

.mn-product-page .gallery-stage {
    background: radial-gradient(
        ellipse at center,

        rgba(255, 255, 255, 0.055),
        transparent 66%
    );
}

.mn-product-page .gallery-panel img {
    color: white;
}

.mn-product-page .carousel-card {
    border-radius: 5px;
}

.mn-product-page .carousel-meta-pill,
.mn-product-page .view-chip {
    border-radius: 3px;
}

.mn-product-page .mn-product-title {
    overflow-wrap: anywhere;
}

@media (max-width: 639px) {
    .mn-product-page .product-hero-grid {
        gap: 14px;
    }

    .mn-product-page .mn-product-title {
        font-size: clamp(1.65rem, 7.4vw, 2.25rem);

        line-height: 1.15;

        letter-spacing: -0.035em;
    }

    .mn-product-page .mn-product-price {
        font-size: clamp(1.65rem, 7.2vw, 2.25rem);
    }

    .mn-product-page .gallery-stage {
        height: min(87vw, 410px);

        min-height: 285px;
    }

    .mn-product-page .carousel-row {
        scroll-snap-type: x mandatory;

        -webkit-overflow-scrolling: touch;

        overscroll-behavior-x: contain;
    }

    .mn-product-page .carousel-card {
        scroll-snap-align: start;

        flex: 0 0 min(76vw, 280px);

        min-width: min(76vw, 280px);

        max-width: min(76vw, 280px);
    }

    .mn-product-page .inline-spec-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));

        grid-auto-rows: auto;
    }

    .mn-product-page .inline-spec-row {
        min-width: 0;

        overflow-wrap: anywhere;
    }
}

@media (max-width: 360px) {
    .mn-product-page .inline-spec-grid {
        grid-template-columns: 1fr;
    }
}

.thin-scrollbar {
    scrollbar-width: thin;

    scrollbar-color: rgb(255 255 255 / 0.2) transparent;
}

.thin-scrollbar::-webkit-scrollbar {
    height: 5px;
}

.thin-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.thin-scrollbar::-webkit-scrollbar-thumb {
    background: rgb(255 255 255 / 0.18);

    border-radius: 999px;
}

.thin-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgb(255 255 255 / 0.35);
}

.premium-panel {
    position: relative;

    border-radius: 1.35rem;

    border: 1px solid rgb(255 255 255 / 0.1);

    background: rgb(10 10 11 / 0.95);

    box-shadow: 0 28px 90px rgb(0 0 0 / 0.42);
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

.micro-label {
    font-size: 0.62rem;

    font-weight: 900;

    text-transform: uppercase;

    letter-spacing: 0.28em;

    color: rgb(113 113 122);
}

.soft-chip {
    border-radius: 0.65rem;

    border: 1px solid rgb(255 255 255 / 0.1);

    background: rgb(255 255 255 / 0.04);

    padding: 0.375rem 0.75rem;

    font-size: 0.625rem;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: 0.12em;

    color: rgb(161 161 170);
}

.mn-placeholder {
    display: flex;

    height: 7rem;

    width: 7rem;

    align-items: center;

    justify-content: center;

    border-radius: 1rem;

    border: 1px solid rgb(255 255 255 / 0.1);

    background: rgb(255 255 255 / 0.04);
}

.gallery-arrow {
    position: absolute;

    top: 50%;

    z-index: 20;

    display: flex;

    height: 2.75rem;

    width: 2.75rem;

    transform: translateY(-50%);

    align-items: center;

    justify-content: center;

    border-radius: 0.85rem;

    border: 1px solid rgb(255 255 255 / 0.1);

    background: rgb(0 0 0 / 0.6);

    font-size: 1.75rem;

    font-weight: 700;

    color: white;

    backdrop-filter: blur(16px);

    transition:
        background-color 260ms ease,
        color 260ms ease,
        transform 260ms ease,
        border-color 260ms ease;
}

.gallery-arrow:hover {
    transform: translateY(-50%) scale(1.04);

    border-color: rgb(255 255 255 / 0.25);

    background: white;

    color: black;
}

.price-panel {
    border-radius: 1.1rem;

    border: 1px solid rgb(255 255 255 / 0.1);

    background: linear-gradient(
        135deg,
        rgb(255 255 255 / 0.08),
        rgb(255 255 255 / 0.025)
    );

    padding: 1rem;

    box-shadow: inset 0 1px 0 rgb(255 255 255 / 0.035);
}

.primary-action,
.secondary-action {
    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 0.5rem;

    border-radius: 0.95rem;

    font-size: 0.75rem;

    font-weight: 900;

    text-transform: uppercase;

    letter-spacing: 0.16em;

    transition:
        transform 260ms ease,
        background-color 260ms ease,
        border-color 260ms ease,
        color 260ms ease,
        box-shadow 260ms ease;
}

.primary-action {
    background: white;

    padding: 1rem 1.25rem;

    color: black;

    box-shadow: 0 16px 45px rgb(255 255 255 / 0.1);
}

.primary-action:hover {
    transform: translateY(-2px);

    background: rgb(228 228 231);

    box-shadow: 0 22px 60px rgb(255 255 255 / 0.16);
}

.secondary-action {
    border: 1px solid rgb(255 255 255 / 0.1);

    background: rgb(255 255 255 / 0.04);

    padding: 1rem 1.25rem;

    color: rgb(228 228 231);
}

.secondary-action:hover {
    transform: translateY(-2px);

    border-color: rgb(255 255 255 / 0.3);

    background: rgb(255 255 255 / 0.075);

    color: white;
}

.primary-action:active,
.secondary-action:active {
    transform: scale(0.985);
}

/* INLINE SPECS */

.inline-specs {
    flex: 1 1 auto;

    display: flex;

    min-height: 0;

    flex-direction: column;
}

.inline-spec-head {
    display: flex;

    flex-shrink: 0;

    align-items: center;

    justify-content: space-between;

    gap: 1rem;
}

.spec-warranty-link {
    display: inline-flex;

    flex-shrink: 0;

    align-items: center;

    justify-content: center;

    border-radius: 9999px;

    border: 1px solid rgb(255 255 255 / 0.1);

    background: rgb(255 255 255 / 0.04);

    padding: 0.45rem 0.75rem;

    font-size: 0.58rem;

    font-weight: 900;

    text-transform: uppercase;

    letter-spacing: 0.14em;

    color: rgb(113 113 122);

    transition:
        border-color 240ms ease,
        background-color 240ms ease,
        color 240ms ease;
}

.spec-warranty-link:hover {
    border-color: rgb(255 255 255 / 0.3);

    background: rgb(255 255 255 / 0.075);

    color: white;
}

.inline-spec-grid {
    margin-top: 0.9rem;

    display: grid;

    flex: 1 1 auto;

    min-height: 0;

    grid-template-columns: repeat(2, minmax(0, 1fr));

    grid-auto-rows: minmax(3.05rem, 1fr);

    gap: 0.55rem;
}

.inline-spec-row {
    display: flex;

    min-height: 0;

    flex-direction: column;

    justify-content: center;

    border-radius: 0.8rem;

    border: 1px solid rgb(255 255 255 / 0.08);

    background: linear-gradient(
        135deg,
        rgb(255 255 255 / 0.045),
        rgb(255 255 255 / 0.02)
    );

    padding: 0.62rem 0.72rem;

    box-shadow: inset 0 1px 0 rgb(255 255 255 / 0.03);

    transition:
        transform 240ms ease,
        border-color 240ms ease,
        background-color 240ms ease;
}

.inline-spec-row span {
    font-size: 0.55rem;

    font-weight: 900;

    line-height: 1.15;

    text-transform: uppercase;

    letter-spacing: 0.14em;

    color: rgb(113 113 122);
}

.inline-spec-row p {
    margin-top: 0.28rem;

    display: -webkit-box;

    overflow: hidden;

    -webkit-box-orient: vertical;

    -webkit-line-clamp: 2;

    font-size: 0.8rem;

    font-weight: 850;

    line-height: 1.22;

    color: rgb(244 244 245);
}

.inline-spec-empty {
    border-radius: 0.95rem;

    border: 1px solid rgb(255 255 255 / 0.1);

    background: rgb(255 255 255 / 0.03);

    padding: 1rem;

    font-size: 0.82rem;

    line-height: 1.6;

    color: rgb(113 113 122);
}

/* CAROUSEL */

.carousel-shell {
    position: relative;

    overflow: hidden;

    border-radius: 1.5rem;

    border: 1px solid rgb(255 255 255 / 0.1);

    background: linear-gradient(
        180deg,
        rgb(10 10 11 / 0.98),
        rgb(5 5 5 / 0.96)
    );

    box-shadow: 0 28px 90px rgb(0 0 0 / 0.4);
}

.carousel-row {
    display: flex;

    gap: 1rem;

    overflow-x: auto;

    scroll-snap-type: x mandatory;

    padding: 1.25rem;

    padding-bottom: 1.5rem;
}

.carousel-card {
    position: relative;

    min-width: 74vw;

    max-width: 74vw;

    scroll-snap-align: start;

    overflow: hidden;

    border-radius: 1.35rem;

    border: 1px solid rgb(255 255 255 / 0.1);

    background: black;

    box-shadow: 0 25px 80px rgb(0 0 0 / 0.35);

    transition:
        transform 420ms cubic-bezier(0.2, 0.8, 0.2, 1),
        border-color 320ms ease,
        box-shadow 320ms ease;
}

.carousel-card::before {
    position: absolute;

    inset: 0;

    z-index: 20;

    pointer-events: none;

    content: "";

    border-radius: inherit;

    box-shadow: inset 0 1px 0 rgb(255 255 255 / 0.16);
}

.carousel-card:hover {
    transform: translateY(-4px);

    border-color: rgb(255 255 255 / 0.25);

    box-shadow: 0 35px 110px rgb(0 0 0 / 0.62);
}

.carousel-top-row {
    position: absolute;

    top: 1rem;

    left: 1rem;

    z-index: 10;

    display: flex;

    max-width: calc(100% - 2rem);

    flex-wrap: wrap;

    gap: 0.4rem;
}

.carousel-meta-pill {
    border-radius: 0.7rem;

    border: 1px solid rgb(52 211 153 / 0.2);

    background: rgb(52 211 153 / 0.1);

    padding: 0.38rem 0.7rem;

    font-size: 0.56rem;

    font-weight: 900;

    text-transform: uppercase;

    letter-spacing: 0.14em;

    color: rgb(167 243 208);

    backdrop-filter: blur(16px);
}

.carousel-footer {
    margin-top: 1rem;

    display: flex;

    align-items: flex-end;

    justify-content: space-between;

    gap: 1rem;

    border-top: 1px solid rgb(255 255 255 / 0.1);

    padding-top: 1rem;
}

.view-chip {
    display: inline-flex;

    flex-shrink: 0;

    align-items: center;

    gap: 0.35rem;

    border-radius: 9999px;

    background: white;

    padding: 0.7rem 0.9rem;

    font-size: 0.68rem;

    font-weight: 900;

    text-transform: uppercase;

    letter-spacing: 0.12em;

    color: black;

    box-shadow: 0 14px 34px rgb(255 255 255 / 0.12);

    transition: transform 260ms ease;
}

.carousel-card:hover .view-chip {
    transform: translateX(3px);
}

.fade-up {
    animation: fadeUp 620ms cubic-bezier(0.2, 0.8, 0.2, 1) both;
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

/* DESKTOP LAYOUT */

@media (min-width: 1024px) {
    .product-hero-grid {
        align-items: stretch;
    }

    .product-hero-grid > section {
        display: flex;

        min-height: 0;
    }

    .gallery-panel,
    .product-panel {
        display: flex;

        width: 100%;

        height: 100%;

        min-height: 0;

        flex-direction: column;
    }

    .product-panel {
        overflow: hidden;
    }

    .gallery-stage {
        flex: 1 1 auto;

        height: auto;

        min-height: 0;
    }

    .gallery-stage img {
        max-height: 100%;
    }

    .inline-specs {
        padding-bottom: 1.15rem;
    }

    .inline-spec-grid {
        grid-auto-rows: minmax(2.85rem, 1fr);
    }

    .carousel-row {
        padding: 1.5rem;
    }

    .carousel-card {
        min-width: 300px;

        max-width: 300px;
    }
}

@media (min-width: 640px) {
    .carousel-card {
        min-width: 285px;

        max-width: 285px;
    }
}

@media (max-width: 480px) {
    .inline-spec-grid {
        grid-template-columns: 1fr;

        grid-auto-rows: auto;
    }
}

@keyframes fadeUp {
    from {
        opacity: 0;

        transform: translateY(16px);
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

@media (min-width: 1024px) {
    .messenger-float-button {
        display: none !important;
    }
}

/* Continue browsing — matches the refined catalog's product-card system. */
.mn-product-page .carousel-shell {
    overflow: hidden;
}
.mn-product-page .carousel-row {
    display: flex;
    gap: 14px;
    overflow-x: auto;
    overflow-y: hidden;
    padding: 10px 18px 22px;
    scroll-snap-type: x mandatory;
    scroll-padding-inline: 18px;
    overscroll-behavior-x: contain;
    -webkit-overflow-scrolling: touch;
    align-items: stretch;
}
.mn-product-page .carousel-card.mn-more-card {
    display: flex;
    flex-direction: column;
    flex: 0 0 clamp(215px, 23vw, 282px);
    width: clamp(215px, 23vw, 282px);
    min-width: 0;
    max-width: none;
    height: auto;
    overflow: hidden;
    scroll-snap-align: start;
    border: 1px solid rgba(255, 255, 255, 0.13);
    border-radius: 7px;
    background: #18181a;
    box-shadow: none;
    text-decoration: none;
    transform: none;
}
.mn-product-page .carousel-card.mn-more-card::before {
    display: none;
}
.mn-product-page .carousel-card.mn-more-card:hover {
    transform: none;
    border-color: rgba(255, 255, 255, 0.35);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.22);
}
.mn-more-photo {
    position: relative;
    width: 100%;
    aspect-ratio: 1 / 1;
    overflow: hidden;
    background: #242427;
}
.mn-more-image {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.35s ease;
}
.mn-more-card:hover .mn-more-image {
    transform: scale(1.025);
}
.mn-more-placeholder {
    display: grid;
    place-items: center;
    height: 100%;
    color: #a0a0a6;
    letter-spacing: 0.18em;
    font-weight: 700;
}
.mn-more-status {
    position: absolute;
    top: 10px;
    left: 10px;
    padding: 7px 10px;
    border: 1px solid rgba(255, 255, 255, 0.22);
    border-radius: 999px;
    background: rgba(14, 14, 16, 0.86);
    color: #f2f2f3;
    font-size: 9px;
    font-weight: 750;
    letter-spacing: 0.1em;
}
.mn-more-content {
    display: flex;
    flex: 1;
    flex-direction: column;
    justify-content: space-between;
    min-height: 176px;
    gap: 17px;
    padding: 16px;
}
.mn-more-heading {
    min-width: 0;
}
.mn-more-brand {
    margin: 0 0 7px;
    color: #bdbdc2;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.15em;
    text-transform: uppercase;
}
.mn-more-title {
    margin: 0;
    color: #f5f5f6;
    font-size: 15px;
    font-weight: 650;
    line-height: 1.4;
    letter-spacing: -0.02em;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    overflow-wrap: anywhere;
}
.mn-more-ref {
    margin: 8px 0 0;
    color: #a4a4ab;
    font-size: 10px;
    line-height: 1.5;
    overflow-wrap: anywhere;
}
.mn-more-price-row {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 8px;
    border-top: 1px solid rgba(255, 255, 255, 0.12);
    padding-top: 12px;
}
.mn-more-price-block {
    min-width: 0;
}
.mn-more-price {
    color: #f5f5f5;
    font-size: 17px;
    font-weight: 750;
    line-height: 1.2;
    margin: 0;
    white-space: nowrap;
}
.mn-more-original {
    color: #95959b;
    font-size: 10px;
    margin: 5px 0 0;
    text-decoration: line-through;
}
.mn-more-view {
    flex-shrink: 0;
    color: #e4e4e6;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.08em;
}
@media (max-width: 639px) {
    .mn-product-page .carousel-row {
        gap: 12px;
        padding: 8px 14px 18px;
        scroll-padding-inline: 14px;
    }
    .mn-product-page .carousel-card.mn-more-card {
        flex: 0 0 min(68vw, 255px);
        width: min(68vw, 255px);
    }
    .mn-more-content {
        min-height: 171px;
        padding: 14px;
        gap: 12px;
    }
    .mn-more-title {
        font-size: 14px;
    }
    .mn-more-price {
        font-size: 16px;
    }
}
@media (prefers-reduced-motion: reduce) {
    .mn-more-image {
        transition: none;
    }
}
</style>
