<script setup>
import MontreLogo from "@/Components/MontreLogo.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
    watch: {
        type: Object,
        required: true,
    },
    canLogin: {
        type: Boolean,
        default: true,
    },
});

const selectedImageIndex = ref(0);
const copied = ref(false);
const inquiryCopied = ref(false);
const activeTab = ref("overview");
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

const selectedThumbnail = computed(() => {
    if (!selectedImageObject.value) return null;

    return (
        selectedImageObject.value.thumbnail_url ||
        selectedImageObject.value.image_url ||
        selectedImageObject.value.hd_url ||
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
        available: "border-emerald-400/20 bg-emerald-400/10 text-emerald-300",
        reserved: "border-amber-400/20 bg-amber-400/10 text-amber-300",
        sold: "border-zinc-400/20 bg-zinc-400/10 text-zinc-300",
        hidden: "border-red-400/20 bg-red-400/10 text-red-300",
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
    return (
        props.watch.description ||
        "A curated Montre Nova timepiece. Message us for availability confirmation, actual photos, payment options, and reservation details."
    );
});

const metaDescription = computed(() => {
    return `${displayName.value}${
        props.watch.reference_number ? ` ${props.watch.reference_number}` : ""
    } available at Montre Nova. View actual photos, pricing, specs, warranty details, and inquiry options.`;
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
        description: productDescription.value,
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

const quickSpecs = computed(() => [
    {
        label: "Condition",
        value: props.watch.condition || "Upon request",
    },
    {
        label: "Reference",
        value: props.watch.reference_number || "No reference",
    },
    {
        label: "Movement",
        value: props.watch.movement || "Upon request",
    },
    {
        label: "Warranty",
        value: props.watch.warranty_type || "Montre Card",
    },
]);

const trustBadges = computed(() => [
    {
        title: "Actual HD Photos",
        description: "View real product photos before inquiring.",
    },
    {
        title: "Montre Card Warranty",
        description: "Selected watches include service warranty support.",
    },
    {
        title: "Clear Pricing",
        description: "Price is shown upfront for easy decision-making.",
    },
    {
        title: "Curated Stock",
        description: "Handpicked pieces from Montre Nova.",
    },
]);

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

const defaultInquiryMessage = computed(() => {
    return `Hi Montre Nova, I'm interested in this watch:

${displayName.value}
${props.watch.reference_number ? `Ref. ${props.watch.reference_number}` : ""}
Price: ${peso(finalPrice.value || props.watch.price)}

Is this still available?`;
});

const inquiryMessage = ref(defaultInquiryMessage.value);

const resetInquiryMessage = () => {
    inquiryMessage.value = defaultInquiryMessage.value;
};

const contactLinks = computed(() => [
    {
        label: "Messenger",
        description: "Fastest way to inquire or reserve this watch",
        href: `https://m.me/${messengerUsername}`,
        primary: true,
    },
    {
        label: "Viber",
        description: "Ask for more photos or payment details",
        href: "viber://chat?number=%2B6399084161980",
        primary: false,
    },
    {
        label: "Instagram",
        description: "View latest drops and curated stocks",
        href: "https://instagram.com/montrenova",
        primary: false,
    },
]);

const tabs = [
    { key: "overview", label: "Overview" },
    { key: "specs", label: "Specs" },
    { key: "warranty", label: "Warranty" },
    { key: "inquiry", label: "Inquiry" },
];

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

const copyInquiryMessage = async () => {
    if (!navigator.clipboard) return;

    await navigator.clipboard.writeText(inquiryMessage.value);

    inquiryCopied.value = true;

    setTimeout(() => {
        inquiryCopied.value = false;
    }, 1800);
};

const openInquiryChannel = async (href) => {
    await copyInquiryMessage();

    window.open(href, "_blank", "noopener,noreferrer");
};

const openMessengerInquiry = async () => {
    await copyInquiryMessage();

    window.open(
        `https://m.me/${messengerUsername}`,
        "_blank",
        "noopener,noreferrer",
    );
};

const copyLink = async () => {
    if (!navigator.clipboard) return;

    await navigator.clipboard.writeText(window.location.href);

    copied.value = true;

    setTimeout(() => {
        copied.value = false;
    }, 1800);
};

const goToInquiry = () => {
    activeTab.value = "inquiry";

    const target = document.getElementById("details");

    if (target) {
        target.scrollIntoView({
            behavior: "smooth",
            block: "start",
        });
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

    <div class="min-h-screen bg-[#050505] pb-28 text-white antialiased lg:pb-0">
        <!-- HEADER -->
        <header
            class="sticky top-0 z-50 border-b border-white/10 bg-[#050505]/90 backdrop-blur-xl"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8"
            >
                <Link :href="route('welcome')" class="flex items-center">
                    <MontreLogo />
                </Link>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('welcome') + '#collection'"
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
                        class="rounded-full border border-white/10 bg-white/[0.03] px-4 py-2 text-xs font-semibold text-zinc-300 transition hover:border-white/30 hover:bg-white/[0.06] hover:text-white"
                        @click="copyLink"
                    >
                        {{ copied ? "Copied" : "Share" }}
                    </button>
                </div>
            </div>
        </header>

        <main class="pb-8 lg:pb-12">
            <!-- HERO -->
            <section
                class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8 lg:py-8"
            >
                <!-- BREADCRUMB -->
                <div
                    class="mb-4 flex items-center gap-2 overflow-hidden text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-600"
                >
                    <Link
                        :href="route('welcome')"
                        class="shrink-0 transition hover:text-white"
                    >
                        Home
                    </Link>

                    <span class="shrink-0">/</span>

                    <Link
                        :href="route('welcome') + '#collection'"
                        class="shrink-0 transition hover:text-white"
                    >
                        Collection
                    </Link>

                    <span class="shrink-0">/</span>

                    <span class="truncate text-zinc-400">
                        {{ watch.brand || "Watch" }}
                    </span>
                </div>

                <div
                    class="grid gap-4 lg:grid-cols-[minmax(0,560px)_minmax(0,1fr)] lg:items-start"
                >
                    <!-- IMAGE CARD -->
                    <section
                        class="overflow-hidden rounded-md border border-white/10 bg-[#0A0A0B] shadow-2xl shadow-black/40 lg:sticky lg:top-24"
                    >
                        <div
                            class="relative flex h-[310px] touch-pan-y select-none items-center justify-center overflow-hidden bg-[#101011] sm:h-[430px] lg:h-[580px]"
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
                                class="pointer-events-none h-full w-full object-contain p-3 sm:p-6"
                            />

                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center"
                            >
                                <img
                                    src="/images/montre-nova-logo.png"
                                    alt="Montre Nova"
                                    class="h-24 w-24 object-contain opacity-60"
                                />
                            </div>

                            <!-- IMAGE BADGES -->
                            <div
                                class="pointer-events-none absolute left-4 top-4 z-10 flex flex-wrap gap-2"
                            >
                                <span
                                    class="rounded-full border px-3 py-1 text-[11px] font-bold backdrop-blur"
                                    :class="statusClass"
                                >
                                    {{ statusLabel }}
                                </span>

                                <span
                                    v-if="hasDiscount"
                                    class="rounded-full border border-violet-400/20 bg-violet-400/10 px-3 py-1 text-[11px] font-bold text-violet-300 backdrop-blur"
                                >
                                    Below SRP
                                </span>
                            </div>

                            <!-- TAP TO ZOOM -->
                            <div
                                v-if="selectedImage"
                                class="pointer-events-none absolute right-4 top-4 z-10 rounded-full border border-white/10 bg-black/60 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-zinc-300 backdrop-blur"
                            >
                                Tap to zoom
                            </div>

                            <!-- IMAGE COUNTER -->
                            <div
                                v-if="images.length"
                                class="absolute bottom-4 left-1/2 z-10 flex -translate-x-1/2 items-center gap-3 rounded-full border border-white/10 bg-black/70 px-4 py-2 backdrop-blur"
                            >
                                <span
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-zinc-300"
                                >
                                    {{ selectedImageIndex + 1 }} /
                                    {{ images.length }}
                                </span>

                                <div
                                    v-if="hasMultipleImages"
                                    class="flex items-center gap-1.5"
                                >
                                    <button
                                        v-for="(_, index) in images"
                                        :key="index"
                                        type="button"
                                        class="relative z-20 h-1.5 rounded-full transition"
                                        :class="
                                            selectedImageIndex === index
                                                ? 'w-5 bg-white'
                                                : 'w-1.5 bg-white/30'
                                        "
                                        @click.stop="selectImage(index)"
                                    ></button>
                                </div>
                            </div>

                            <!-- ARROWS -->
                            <button
                                v-if="hasMultipleImages"
                                type="button"
                                class="absolute left-3 top-1/2 z-20 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/10 bg-black/60 text-2xl font-semibold text-white backdrop-blur transition hover:bg-white hover:text-black"
                                aria-label="Previous image"
                                @click.stop="previousImage"
                            >
                                ‹
                            </button>

                            <button
                                v-if="hasMultipleImages"
                                type="button"
                                class="absolute right-3 top-1/2 z-20 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/10 bg-black/60 text-2xl font-semibold text-white backdrop-blur transition hover:bg-white hover:text-black"
                                aria-label="Next image"
                                @click.stop="nextImage"
                            >
                                ›
                            </button>
                        </div>

                        <!-- THUMBNAILS -->
                        <div class="border-t border-white/10 p-3 sm:p-4">
                            <div
                                v-if="hasMultipleImages"
                                class="thin-scrollbar flex gap-2 overflow-x-auto pb-1"
                            >
                                <button
                                    v-for="(image, index) in images"
                                    :key="image.id || index"
                                    type="button"
                                    class="h-16 w-16 shrink-0 overflow-hidden rounded-md border bg-[#050505] p-1 transition sm:h-20 sm:w-20"
                                    :class="
                                        selectedImageIndex === index
                                            ? 'border-white'
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
                                        class="h-full w-full rounded-md object-cover"
                                        loading="lazy"
                                    />
                                </button>
                            </div>

                            <div
                                v-if="images.length"
                                class="mt-3 flex items-center justify-between gap-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-zinc-500"
                            >
                                <span> Actual HD Photos </span>

                                <span class="hidden sm:inline">
                                    Swipe, tap, or zoom
                                </span>
                            </div>
                        </div>
                    </section>

                    <!-- PRODUCT DETAILS -->
                    <section class="space-y-4">
                        <div
                            class="rounded-md border border-white/10 bg-[#0A0A0B] p-4 shadow-2xl shadow-black/40 sm:p-6 lg:p-7"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="rounded-full border px-3 py-1 text-[11px] font-bold"
                                    :class="statusClass"
                                >
                                    {{ statusLabel }}
                                </span>

                                <span
                                    v-if="watch.category"
                                    class="rounded-full border border-white/10 bg-white/[0.04] px-3 py-1 text-[11px] font-semibold text-zinc-400"
                                >
                                    {{ watch.category }}
                                </span>

                                <span
                                    v-if="watch.condition"
                                    class="rounded-full border border-white/10 bg-white/[0.04] px-3 py-1 text-[11px] font-semibold text-zinc-400"
                                >
                                    {{ watch.condition }}
                                </span>
                            </div>

                            <p
                                class="mt-6 text-[11px] font-bold uppercase tracking-[0.34em] text-zinc-500"
                            >
                                {{ watch.brand || "Montre Nova" }}
                            </p>

                            <h1
                                class="mt-2 text-3xl font-black leading-[0.95] tracking-[-0.06em] text-white sm:text-5xl lg:text-6xl"
                            >
                                {{ watch.model_name }}
                            </h1>

                            <p
                                v-if="watch.reference_number"
                                class="mt-3 text-sm font-medium text-zinc-500"
                            >
                                Ref. {{ watch.reference_number }}
                            </p>

                            <!-- PRICE -->
                            <div
                                class="mt-6 rounded-md border border-white/10 bg-white/[0.03] p-5"
                            >
                                <div
                                    class="flex items-start justify-between gap-4"
                                >
                                    <div>
                                        <p
                                            class="text-[10px] font-bold uppercase tracking-[0.28em] text-zinc-500"
                                        >
                                            Price
                                        </p>

                                        <div
                                            class="mt-2 flex flex-wrap items-end gap-3"
                                        >
                                            <p
                                                class="text-4xl font-black tracking-[-0.06em] text-white sm:text-5xl"
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
                                        class="rounded-full border border-violet-400/20 bg-violet-400/10 px-3 py-1 text-[11px] font-bold text-violet-300"
                                    >
                                        Below SRP
                                    </span>
                                </div>
                            </div>

                            <p class="mt-5 text-sm leading-7 text-zinc-400">
                                {{ productDescription }}
                            </p>

                            <!-- CTA -->
                            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-md bg-white px-5 py-4 text-sm font-black text-black transition hover:bg-zinc-200"
                                    @click="openMessengerInquiry"
                                >
                                    Ask via Messenger
                                </button>

                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-md border border-white/10 bg-white/[0.03] px-5 py-4 text-sm font-bold text-white transition hover:border-white/30 hover:bg-white/[0.06]"
                                    @click="goToInquiry"
                                >
                                    Edit Inquiry Message
                                </button>
                            </div>

                            <p class="mt-3 text-xs leading-5 text-zinc-500">
                                Your inquiry message will be copied before
                                opening Messenger.
                            </p>
                        </div>

                        <!-- QUICK SPECS -->
                        <div
                            class="grid grid-cols-2 gap-3 rounded-md border border-white/10 bg-[#0A0A0B] p-4 sm:p-5"
                        >
                            <div
                                v-for="item in quickSpecs"
                                :key="item.label"
                                class="rounded-md border border-white/10 bg-white/[0.03] p-4"
                            >
                                <p
                                    class="text-[10px] font-semibold uppercase tracking-[0.16em] text-zinc-500"
                                >
                                    {{ item.label }}
                                </p>

                                <p
                                    class="mt-1 truncate text-xs font-bold text-white sm:text-sm"
                                >
                                    {{ item.value }}
                                </p>
                            </div>
                        </div>
                    </section>
                </div>
            </section>

            <!-- DETAILS -->
            <section
                id="details"
                class="mx-auto max-w-7xl px-4 pb-8 sm:px-6 lg:px-8"
            >
                <div
                    class="rounded-md border border-white/10 bg-[#0A0A0B] p-4 sm:p-6"
                >
                    <div
                        class="thin-scrollbar flex gap-2 overflow-x-auto rounded-md border border-white/10 bg-[#050505] p-1"
                    >
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            type="button"
                            class="min-w-28 flex-1 rounded-md px-4 py-3 text-xs font-black uppercase tracking-[0.16em] transition"
                            :class="
                                activeTab === tab.key
                                    ? 'bg-white text-black'
                                    : 'text-zinc-500 hover:bg-white/[0.06] hover:text-white'
                            "
                            @click="activeTab = tab.key"
                        >
                            {{ tab.label }}
                        </button>
                    </div>

                    <!-- OVERVIEW TAB -->
                    <div v-if="activeTab === 'overview'" class="mt-6">
                        <div class="grid gap-5 lg:grid-cols-[1.2fr_0.8fr]">
                            <div
                                class="rounded-md border border-white/10 bg-white/[0.03] p-5"
                            >
                                <p
                                    class="text-[11px] font-bold uppercase tracking-[0.32em] text-zinc-500"
                                >
                                    Overview
                                </p>

                                <h2
                                    class="mt-2 text-2xl font-bold tracking-[-0.04em] text-white"
                                >
                                    About this timepiece
                                </h2>

                                <p class="mt-4 text-sm leading-7 text-zinc-400">
                                    {{ productDescription }}
                                </p>
                            </div>

                            <div
                                class="rounded-md border border-white/10 bg-white/[0.03] p-5"
                            >
                                <p
                                    class="text-[11px] font-bold uppercase tracking-[0.32em] text-zinc-500"
                                >
                                    What to check
                                </p>

                                <div class="mt-4 space-y-3">
                                    <div
                                        class="flex items-center justify-between gap-4 border-b border-white/10 pb-3"
                                    >
                                        <span class="text-sm text-zinc-500">
                                            Box / Papers
                                        </span>
                                        <span
                                            class="text-right text-sm font-semibold text-white"
                                        >
                                            {{
                                                watch.box_papers ||
                                                "Upon request"
                                            }}
                                        </span>
                                    </div>

                                    <div
                                        class="flex items-center justify-between gap-4 border-b border-white/10 pb-3"
                                    >
                                        <span class="text-sm text-zinc-500">
                                            Warranty
                                        </span>
                                        <span
                                            class="text-right text-sm font-semibold text-white"
                                        >
                                            {{
                                                watch.warranty_type ||
                                                "Montre Card"
                                            }}
                                        </span>
                                    </div>

                                    <div
                                        class="flex items-center justify-between gap-4"
                                    >
                                        <span class="text-sm text-zinc-500">
                                            Inquiry
                                        </span>
                                        <span
                                            class="text-right text-sm font-semibold text-emerald-300"
                                        >
                                            Message us to confirm availability
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SPECS TAB -->
                    <div v-if="activeTab === 'specs'" class="mt-6">
                        <div
                            class="flex flex-col justify-between gap-2 sm:flex-row sm:items-end"
                        >
                            <div>
                                <p
                                    class="text-[11px] font-bold uppercase tracking-[0.32em] text-zinc-500"
                                >
                                    Full Specifications
                                </p>

                                <h2
                                    class="mt-2 text-2xl font-bold tracking-[-0.04em] text-white"
                                >
                                    Watch details
                                </h2>
                            </div>

                            <p class="max-w-xl text-sm leading-6 text-zinc-500">
                                Grouped for easier comparison before
                                reservation.
                            </p>
                        </div>

                        <div
                            v-if="availableSpecGroups.length"
                            class="mt-6 grid gap-4 lg:grid-cols-2"
                        >
                            <div
                                v-for="group in availableSpecGroups"
                                :key="group.title"
                                class="rounded-md border border-white/10 bg-white/[0.03] p-5"
                            >
                                <h3 class="text-sm font-bold text-white">
                                    {{ group.title }}
                                </h3>

                                <div class="mt-4 divide-y divide-white/10">
                                    <div
                                        v-for="spec in group.items"
                                        :key="spec.label"
                                        class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0"
                                    >
                                        <p
                                            class="text-xs font-semibold uppercase tracking-[0.14em] text-zinc-500"
                                        >
                                            {{ spec.label }}
                                        </p>

                                        <p
                                            class="max-w-[60%] text-right text-sm font-bold text-white"
                                        >
                                            {{ spec.value }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p v-else class="mt-5 text-sm text-zinc-500">
                            Full specifications available upon request.
                        </p>
                    </div>

                    <!-- WARRANTY TAB -->
                    <div v-if="activeTab === 'warranty'" class="mt-6">
                        <p
                            class="text-[11px] font-bold uppercase tracking-[0.32em] text-zinc-500"
                        >
                            Warranty
                        </p>

                        <h2
                            class="mt-2 text-2xl font-bold tracking-[-0.04em] text-white"
                        >
                            Montre Card Warranty
                        </h2>

                        <div
                            class="mt-5 grid gap-3 text-sm leading-6 text-zinc-400 lg:grid-cols-3"
                        >
                            <div
                                class="rounded-md border border-white/10 bg-white/[0.03] p-5"
                            >
                                <p class="font-bold text-white">
                                    1 Year Coverage
                                </p>

                                <p class="mt-2">
                                    The Montre Card warranty coverage is valid
                                    for one year from the date of purchase.
                                </p>
                            </div>

                            <div
                                class="rounded-md border border-white/10 bg-white/[0.03] p-5"
                            >
                                <p class="font-bold text-white">
                                    Movement Defects
                                </p>

                                <p class="mt-2">
                                    Covers movement and internal mechanism
                                    defects, including abnormal timekeeping,
                                    significant gain or loss of time, and
                                    movement stoppage.
                                </p>
                            </div>

                            <div
                                class="rounded-md border border-white/10 bg-white/[0.03] p-5"
                            >
                                <p class="font-bold text-white">Not Covered</p>

                                <p class="mt-2">
                                    Excludes scratches, dents, broken glass,
                                    water damage, misuse, unauthorized repairs,
                                    battery replacement for quartz models, and
                                    cosmetic damage.
                                </p>
                            </div>
                        </div>

                        <Link
                            href="/warranty-check"
                            class="mt-5 inline-flex rounded-md border border-white/10 bg-white/[0.03] px-5 py-3 text-sm font-bold text-white transition hover:border-white/30 hover:bg-white/[0.06]"
                        >
                            Check Existing Warranty
                        </Link>
                    </div>

                    <!-- INQUIRY TAB -->
                    <div v-if="activeTab === 'inquiry'" class="mt-6">
                        <div class="grid gap-5 lg:grid-cols-[0.9fr_1.1fr]">
                            <div>
                                <p
                                    class="text-[11px] font-bold uppercase tracking-[0.32em] text-zinc-500"
                                >
                                    Inquiry
                                </p>

                                <h2
                                    class="mt-2 text-2xl font-bold tracking-[-0.04em] text-white"
                                >
                                    Ready to reserve this watch?
                                </h2>

                                <p
                                    class="mt-3 max-w-2xl text-sm leading-6 text-zinc-400"
                                >
                                    Edit the message preview if needed, then
                                    copy it or open your preferred channel. We
                                    recommend Messenger for the fastest
                                    response.
                                </p>

                                <div class="mt-5 grid gap-3">
                                    <button
                                        type="button"
                                        class="rounded-md bg-white px-5 py-4 text-sm font-black text-black transition hover:bg-zinc-200"
                                        @click="openMessengerInquiry"
                                    >
                                        Ask via Messenger
                                    </button>

                                    <button
                                        type="button"
                                        class="rounded-md border border-white/10 bg-white/[0.03] px-5 py-4 text-sm font-bold text-white transition hover:border-white/30 hover:bg-white/[0.06]"
                                        @click="copyInquiryMessage"
                                    >
                                        {{
                                            inquiryCopied
                                                ? "Inquiry Message Copied"
                                                : "Copy Inquiry Message"
                                        }}
                                    </button>

                                    <button
                                        v-for="link in contactLinks"
                                        :key="link.label"
                                        type="button"
                                        class="group rounded-md border p-4 text-left transition"
                                        :class="
                                            link.primary
                                                ? 'border-emerald-400/20 bg-emerald-400/10 hover:border-emerald-400/40'
                                                : 'border-white/10 bg-[#050505] hover:border-white/30'
                                        "
                                        @click="openInquiryChannel(link.href)"
                                    >
                                        <div
                                            class="flex items-center justify-between gap-4"
                                        >
                                            <p
                                                class="text-sm font-semibold text-white"
                                            >
                                                {{ link.label }}
                                            </p>

                                            <span
                                                class="text-zinc-500 transition group-hover:text-white"
                                            >
                                                →
                                            </span>
                                        </div>

                                        <p
                                            class="mt-2 text-xs leading-5 text-zinc-500"
                                        >
                                            {{ link.description }}
                                        </p>
                                    </button>
                                </div>
                            </div>

                            <div
                                class="rounded-md border border-white/10 bg-[#050505] p-5"
                            >
                                <div
                                    class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center"
                                >
                                    <div>
                                        <p
                                            class="text-[11px] font-bold uppercase tracking-[0.24em] text-zinc-500"
                                        >
                                            Message Preview
                                        </p>

                                        <p
                                            class="mt-1 text-xs leading-5 text-zinc-500"
                                        >
                                            Editable before copying or sending.
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-full border border-white/10 bg-white/[0.03] px-4 py-2 text-xs font-bold text-zinc-300 transition hover:border-white/30 hover:bg-white/[0.06] hover:text-white"
                                        @click="resetInquiryMessage"
                                    >
                                        Reset Message
                                    </button>
                                </div>

                                <textarea
                                    v-model="inquiryMessage"
                                    rows="9"
                                    class="mt-4 w-full resize-none rounded-md border border-white/10 bg-white/[0.03] p-4 text-sm leading-7 text-zinc-300 outline-none transition placeholder:text-zinc-600 focus:border-white/30 focus:bg-white/[0.05]"
                                ></textarea>

                                <p class="mt-4 text-xs leading-5 text-zinc-500">
                                    The exact message above will be copied
                                    before opening Messenger, Viber, or
                                    Instagram.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- MORE CTA -->
            <section class="mx-auto max-w-7xl px-4 pb-8 sm:px-6 lg:px-8">
                <div
                    class="flex flex-col justify-between gap-4 rounded-md border border-white/10 bg-[#0A0A0B] p-5 sm:flex-row sm:items-center sm:p-6"
                >
                    <div>
                        <p
                            class="text-[11px] font-bold uppercase tracking-[0.28em] text-zinc-500"
                        >
                            Still browsing?
                        </p>

                        <h2 class="mt-2 text-xl font-bold text-white">
                            View more curated Montre Nova watches
                        </h2>
                    </div>

                    <Link
                        :href="route('welcome') + '#collection'"
                        class="inline-flex items-center justify-center rounded-md border border-white/10 bg-white/[0.03] px-5 py-3 text-sm font-bold text-white transition hover:border-white/30 hover:bg-white/[0.06]"
                    >
                        Back to Collection
                    </Link>
                </div>
            </section>
        </main>

        <!-- MOBILE STICKY CTA -->
        <div
            class="fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-[#050505]/95 p-3 backdrop-blur-xl sm:hidden"
        >
            <div class="grid grid-cols-[1fr_auto_auto] items-center gap-2">
                <div class="min-w-0">
                    <p
                        class="truncate text-[10px] font-bold uppercase tracking-[0.18em] text-zinc-500"
                    >
                        {{ statusLabel }}
                    </p>

                    <p class="truncate text-base font-black text-white">
                        {{ peso(finalPrice) }}
                    </p>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-md bg-white px-4 py-3 text-sm font-black text-black"
                    @click="openMessengerInquiry"
                >
                    Ask
                </button>

                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-md border border-white/10 bg-white/[0.04] px-4 py-3 text-sm font-bold text-white"
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
                    class="absolute right-4 top-4 z-20 rounded-full border border-white/10 bg-white/10 px-4 py-2 text-sm font-bold text-white backdrop-blur transition hover:bg-white hover:text-black"
                    @click="closeImagePreview"
                >
                    Close
                </button>

                <button
                    v-if="hasMultipleImages"
                    type="button"
                    class="absolute left-4 top-1/2 z-20 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full border border-white/10 bg-white/10 text-3xl text-white backdrop-blur transition hover:bg-white hover:text-black"
                    @click.stop="previousImage"
                >
                    ‹
                </button>

                <img
                    v-if="selectedImage"
                    :src="selectedImage"
                    :alt="displayName"
                    class="max-h-[88vh] max-w-full rounded-md object-contain"
                />

                <button
                    v-if="hasMultipleImages"
                    type="button"
                    class="absolute right-4 top-1/2 z-20 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full border border-white/10 bg-white/10 text-3xl text-white backdrop-blur transition hover:bg-white hover:text-black"
                    @click.stop="nextImage"
                >
                    ›
                </button>

                <div
                    v-if="images.length"
                    class="absolute bottom-5 left-1/2 z-20 flex -translate-x-1/2 items-center gap-3 rounded-full border border-white/10 bg-black/70 px-4 py-2 text-xs font-bold text-white backdrop-blur"
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
</style>
