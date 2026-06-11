<script setup>
import MontreLogo from "@/Components/MontreLogo.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";

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
        class="relative min-h-screen overflow-hidden bg-[#050505] pb-28 text-white antialiased lg:pb-0"
    >
        <!-- AMBIENT BACKGROUND -->
        <div class="pointer-events-none fixed inset-0 z-0">
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_18%_8%,rgba(255,255,255,0.08),transparent_30%),radial-gradient(circle_at_84%_18%,rgba(161,161,170,0.10),transparent_32%),linear-gradient(180deg,#080808_0%,#050505_44%,#0a0a0a_100%)]"
            ></div>
            <div
                class="absolute left-1/2 top-0 h-px w-[72rem] -translate-x-1/2 bg-gradient-to-r from-transparent via-white/25 to-transparent"
            ></div>
            <div
                class="absolute -right-40 top-36 h-96 w-96 rounded-[2rem] bg-white/[0.045] blur-3xl"
            ></div>
            <div
                class="absolute -left-44 top-[32rem] h-[30rem] w-[30rem] rounded-[2rem] bg-zinc-500/[0.055] blur-3xl"
            ></div>
            <div
                class="absolute bottom-0 left-1/2 h-72 w-[54rem] -translate-x-1/2 rounded-[2rem] bg-white/[0.025] blur-3xl"
            ></div>
        </div>

        <!-- HEADER -->
        <header
            class="sticky top-0 z-50 border-b border-white/10 bg-[#050505]/82 backdrop-blur-2xl"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8"
            >
                <Link
                    :href="route('welcome')"
                    class="flex min-w-0 items-center transition duration-300 hover:opacity-80"
                >
                    <MontreLogo />
                </Link>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('welcome') + '#collection'"
                        class="hidden rounded-xl border border-white/10 bg-white/[0.035] px-4 py-2.5 text-xs font-bold uppercase tracking-[0.12em] text-zinc-300 transition hover:border-white/30 hover:bg-white/[0.07] hover:text-white sm:inline-flex"
                    >
                        Collection
                    </Link>

                    <button
                        type="button"
                        class="rounded-xl border border-white/10 bg-white/[0.035] px-4 py-2.5 text-xs font-bold uppercase tracking-[0.12em] text-zinc-300 transition hover:border-white/30 hover:bg-white/[0.07] hover:text-white"
                        @click="copyLink"
                    >
                        {{ copied ? "Copied" : "Share" }}
                    </button>
                </div>
            </div>
        </header>

        <main class="relative z-10 pb-8 lg:pb-14">
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
                                        class="rounded-lg border border-violet-400/20 bg-violet-400/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.12em] text-violet-300 backdrop-blur"
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

                            <!-- MAIN PRODUCT DETAILS -->
                            <div
                                class="border-b border-white/10 bg-white/[0.025] p-5 sm:p-6 lg:p-6"
                            >
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
                                </div>

                                <p class="mt-7 micro-label">
                                    {{ watch.brand || "Montre Nova" }}
                                </p>

                                <h1
                                    class="mt-3 text-4xl font-black leading-[0.95] tracking-[-0.065em] text-white sm:text-5xl xl:text-[3.35rem]"
                                >
                                    {{ watch.model_name }}
                                </h1>
                            </div>

                            <!-- PRICE + CTA -->
                            <div class="p-5 sm:p-6 lg:p-6">
                                <div class="price-panel">
                                    <div
                                        class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between"
                                    >
                                        <div>
                                            <p class="micro-label">
                                                Asking Price
                                            </p>

                                            <div
                                                class="mt-3 flex flex-wrap items-end gap-3"
                                            >
                                                <p
                                                    class="text-4xl font-black tracking-[-0.065em] text-white sm:text-[2.85rem]"
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

                                        <span
                                            v-if="hasDiscount"
                                            class="w-fit rounded-lg border border-violet-400/20 bg-violet-400/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.12em] text-violet-300"
                                        >
                                            Below SRP
                                        </span>
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
                                    class="primary-action mt-5 w-full"
                                    @click="openMessengerInquiry()"
                                >
                                    Inquire on Messenger
                                    <span aria-hidden="true">→</span>
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
                                            class="mt-1.5 text-base font-black tracking-[-0.03em] text-white sm:text-lg"
                                        >
                                            Watch details
                                        </h2>
                                    </div>

                                    <Link
                                        href="/warranty-check"
                                        class="spec-warranty-link"
                                    >
                                        Warranty
                                    </Link>
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
                        class="flex flex-col justify-between gap-4 border-b border-white/10 p-5 sm:p-6 lg:flex-row lg:items-end"
                    >
                        <div>
                            <p class="micro-label">More Available Watches</p>

                            <h2
                                class="mt-3 text-2xl font-black tracking-[-0.04em] text-white sm:text-3xl"
                            >
                                Continue browsing
                            </h2>

                            <p
                                class="mt-3 max-w-xl text-sm leading-6 text-zinc-500"
                            >
                                Other available pieces you can tap to view next.
                            </p>
                        </div>

                        <div
                            class="inline-flex w-fit items-center gap-2 rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-[10px] font-black uppercase tracking-[0.16em] text-zinc-500"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-white/70"
                            ></span>
                            Swipe / Scroll
                        </div>
                    </div>

                    <div class="relative">
                        <div
                            class="pointer-events-none absolute inset-y-0 left-0 z-10 hidden w-16 bg-gradient-to-r from-[#0A0A0B] to-transparent sm:block"
                        ></div>

                        <div
                            class="pointer-events-none absolute inset-y-0 right-0 z-10 hidden w-16 bg-gradient-to-l from-[#0A0A0B] to-transparent sm:block"
                        ></div>

                        <div class="carousel-row thin-scrollbar">
                            <Link
                                v-for="watchItem in carouselWatches"
                                :key="watchItem.id"
                                :href="
                                    route('public.watches.show', watchItem.id)
                                "
                                class="carousel-card group"
                            >
                                <div
                                    class="relative aspect-[4/5] overflow-hidden"
                                >
                                    <img
                                        v-if="watchCardImage(watchItem)"
                                        :src="watchCardImage(watchItem)"
                                        :alt="`${watchItem.brand} ${watchItem.model_name}`"
                                        class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-[1.055]"
                                        loading="lazy"
                                    />

                                    <div
                                        v-else
                                        class="absolute inset-0 flex items-center justify-center bg-[#050505]"
                                    >
                                        <div class="text-center">
                                            <div
                                                class="mx-auto flex h-20 w-20 items-center justify-center rounded-full border border-white/10 bg-white/[0.04]"
                                            >
                                                <span
                                                    class="text-2xl font-black tracking-[-0.1em] text-white"
                                                >
                                                    MN
                                                </span>
                                            </div>

                                            <p class="mt-3 micro-label">
                                                Montre Nova
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="absolute inset-0 bg-gradient-to-t from-black/92 via-black/28 to-black/10"
                                    ></div>

                                    <div class="carousel-top-row">
                                        <span class="carousel-meta-pill">
                                            Available
                                        </span>

                                        <span
                                            v-if="itemHasDiscount(watchItem)"
                                            class="carousel-meta-pill border-violet-400/20 bg-violet-400/10 text-violet-200"
                                        >
                                            Below SRP
                                        </span>
                                    </div>

                                    <div
                                        class="absolute inset-x-0 bottom-0 z-10 p-5"
                                    >
                                        <p
                                            class="truncate text-[10px] font-black uppercase tracking-[0.3em] text-zinc-400"
                                        >
                                            {{
                                                watchItem.brand || "Montre Nova"
                                            }}
                                        </p>

                                        <h3
                                            class="mt-2 line-clamp-2 text-xl font-black leading-tight tracking-[-0.03em] text-white"
                                        >
                                            {{ watchItem.model_name }}
                                        </h3>

                                        <p
                                            class="mt-2 truncate text-xs text-zinc-400"
                                        >
                                            Ref.
                                            {{
                                                watchItem.reference_number ||
                                                "No reference"
                                            }}
                                        </p>

                                        <div class="carousel-footer">
                                            <div class="min-w-0">
                                                <p
                                                    class="text-[9px] font-black uppercase tracking-[0.2em] text-zinc-600"
                                                >
                                                    Price
                                                </p>

                                                <p
                                                    class="mt-1 truncate text-lg font-black text-white"
                                                >
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
                                                        itemHasDiscount(
                                                            watchItem,
                                                        )
                                                    "
                                                    class="text-[11px] text-zinc-500 line-through"
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

                                            <span class="view-chip">
                                                View
                                                <span aria-hidden="true"
                                                    >→</span
                                                >
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- MOBILE STICKY CTA -->
        <div
            class="fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-[#050505]/95 p-3 backdrop-blur-2xl sm:hidden"
        >
            <div class="grid grid-cols-[1fr_auto_auto] items-center gap-2">
                <div class="min-w-0">
                    <p
                        class="truncate text-[10px] font-black uppercase tracking-[0.18em] text-zinc-500"
                    >
                        {{ statusLabel }}
                    </p>

                    <p class="truncate text-base font-black text-white">
                        {{ peso(finalPrice) }}
                    </p>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-3 text-sm font-black text-black"
                    @click="openMessengerInquiry"
                >
                    Inquire
                </button>

                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/[0.04] px-4 py-3 text-sm font-bold text-white"
                    @click="copyLink"
                >
                    {{ copied ? "Copied" : "Share" }}
                </button>
            </div>
        </div>

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

.inline-spec-row:hover {
    transform: translateY(-1px);
    border-color: rgb(255 255 255 / 0.18);
    background: rgb(255 255 255 / 0.055);
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
</style>
