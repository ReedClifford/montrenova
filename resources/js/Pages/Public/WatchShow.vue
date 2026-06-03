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
const activeTab = ref("specs");

const touchStartX = ref(0);
const touchStartY = ref(0);
const swipeThreshold = 45;

const images = computed(() => props.watch.images || []);

const displayName = computed(() => {
    return `${props.watch.brand || ""} ${props.watch.model_name || ""}`.trim();
});

const selectedImage = computed(() => {
    if (!images.value.length) return null;

    const image = images.value[selectedImageIndex.value];

    return image?.hd_url || image?.image_url || image?.thumbnail_url || null;
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

const peso = (value) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 0,
    }).format(Number(value || 0));
};

const specs = computed(() => [
    { label: "Reference", value: props.watch.reference_number },
    { label: "Condition", value: props.watch.condition },
    { label: "Category", value: props.watch.category },
    { label: "Movement", value: props.watch.movement },
    { label: "Case Size", value: props.watch.case_size },
    { label: "Case Material", value: props.watch.case_material },
    { label: "Dial Color", value: props.watch.dial_color },
    { label: "Crystal", value: props.watch.crystal },
    { label: "Bracelet / Strap", value: props.watch.bracelet_or_strap },
    { label: "Water Resistance", value: props.watch.water_resistance },
    { label: "Box / Papers", value: props.watch.box_papers },
    { label: "Warranty", value: props.watch.warranty_type },
]);

const availableSpecs = computed(() => specs.value.filter((item) => item.value));

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
        label: "Case Size",
        value: props.watch.case_size || "Upon request",
    },
    {
        label: "Box / Papers",
        value: props.watch.box_papers || "Upon request",
    },
    {
        label: "Warranty",
        value: props.watch.warranty_type || "Montre Card",
    },
]);

const contactLinks = computed(() => [
    {
        label: "Messenger",
        description: "Fastest way to inquire or reserve",
        href: props.watch.messenger_url || "#",
    },
    {
        label: "Viber",
        description: "Request more photos or payment details",
        href: props.watch.viber_url || "#",
    },
    {
        label: "Instagram",
        description: "View latest drops and curated stocks",
        href: props.watch.instagram_url || "#",
    },
]);

const tabs = [
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

const copyLink = async () => {
    if (!navigator.clipboard) return;

    await navigator.clipboard.writeText(window.location.href);

    copied.value = true;

    setTimeout(() => {
        copied.value = false;
    }, 1800);
};
</script>

<template>
    <Head :title="`${displayName} | Montre Nova`" />

    <div class="min-h-screen bg-[#050505] text-white antialiased">
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

                    <button
                        type="button"
                        class="hidden rounded-full border border-white/10 bg-white/[0.03] px-4 py-2 text-xs font-semibold text-zinc-300 transition hover:border-white/30 hover:bg-white/[0.06] hover:text-white sm:inline-flex"
                        @click="copyLink"
                    >
                        {{ copied ? "Copied" : "Share" }}
                    </button>

                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="rounded-full bg-white px-4 py-2 text-xs font-bold text-black transition hover:bg-zinc-200"
                    >
                        Admin
                    </Link>
                </div>
            </div>
        </header>

        <main class="pb-24 lg:pb-10">
            <!-- PRODUCT HERO -->
            <section
                class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8 lg:py-7"
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
                        {{ watch.brand }}
                    </span>
                </div>

                <div
                    class="grid gap-4 lg:grid-cols-[minmax(0,500px)_minmax(0,1fr)] lg:items-start xl:grid-cols-[minmax(0,540px)_minmax(0,1fr)]"
                >
                    <!-- IMAGE CARD -->
                    <section
                        class="overflow-hidden rounded-[1.5rem] border border-white/10 bg-[#0A0A0B] shadow-2xl shadow-black/40"
                    >
                        <div
                            class="relative flex h-[245px] touch-pan-y select-none items-center justify-center overflow-hidden bg-[#101011] sm:h-[340px] lg:h-[430px]"
                            @touchstart.passive="handleTouchStart"
                            @touchend.passive="handleTouchEnd"
                        >
                            <img
                                v-if="selectedImage"
                                :key="selectedImage"
                                :src="selectedImage"
                                :alt="displayName"
                                draggable="false"
                                class="pointer-events-none h-full w-full object-contain p-3 sm:p-5"
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
                        </div>

                        <div class="border-t border-white/10 p-3 sm:p-4">
                            <div
                                v-if="hasMultipleImages"
                                class="flex items-center gap-3"
                            >
                                <button
                                    type="button"
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.03] text-xl font-semibold text-white transition hover:border-white/30 hover:bg-white hover:text-black"
                                    aria-label="Previous image"
                                    @click="previousImage"
                                >
                                    ‹
                                </button>

                                <div
                                    class="thin-scrollbar flex flex-1 gap-2 overflow-x-auto pb-1"
                                >
                                    <button
                                        v-for="(image, index) in images"
                                        :key="image.id || index"
                                        type="button"
                                        class="h-14 w-14 shrink-0 overflow-hidden rounded-2xl border bg-[#050505] p-1 transition sm:h-16 sm:w-16"
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
                                            class="h-full w-full rounded-xl object-cover"
                                        />
                                    </button>
                                </div>

                                <button
                                    type="button"
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.03] text-xl font-semibold text-white transition hover:border-white/30 hover:bg-white hover:text-black"
                                    aria-label="Next image"
                                    @click="nextImage"
                                >
                                    ›
                                </button>
                            </div>

                            <div
                                v-if="images.length"
                                class="mt-3 flex items-center justify-between gap-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-zinc-500"
                            >
                                <span>
                                    Photo {{ selectedImageIndex + 1 }} of
                                    {{ images.length }}
                                </span>

                                <span class="hidden sm:inline">
                                    Swipe or tap thumbnails
                                </span>
                            </div>
                        </div>
                    </section>

                    <!-- DETAILS CARD -->
                    <section
                        class="rounded-[1.5rem] border border-white/10 bg-[#0A0A0B] p-4 shadow-2xl shadow-black/40 sm:p-6 lg:min-h-[430px]"
                    >
                        <div class="flex h-full flex-col">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-[11px] font-bold text-emerald-300"
                                    >
                                        Available
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
                                    class="mt-5 text-[11px] font-bold uppercase tracking-[0.34em] text-zinc-500"
                                >
                                    {{ watch.brand || "Montre Nova" }}
                                </p>

                                <h1
                                    class="mt-2 text-2xl font-bold leading-tight tracking-[-0.04em] text-white sm:text-4xl lg:text-5xl"
                                >
                                    {{ watch.model_name }}
                                </h1>

                                <p
                                    v-if="watch.reference_number"
                                    class="mt-2 text-sm font-medium text-zinc-500"
                                >
                                    Ref. {{ watch.reference_number }}
                                </p>

                                <div
                                    class="mt-5 rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.28em] text-zinc-500"
                                    >
                                        Price
                                    </p>

                                    <div
                                        class="mt-2 flex flex-wrap items-end gap-3"
                                    >
                                        <p
                                            class="text-3xl font-black tracking-[-0.05em] text-white sm:text-4xl"
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
                                    v-if="watch.description"
                                    class="description-clamp mt-4 text-sm leading-6 text-zinc-400"
                                >
                                    {{ watch.description }}
                                </p>
                            </div>

                            <!-- QUICK SPECS -->
                            <div class="mt-5 grid grid-cols-2 gap-2">
                                <div
                                    v-for="item in quickSpecs"
                                    :key="item.label"
                                    class="rounded-2xl border border-white/10 bg-white/[0.03] p-3"
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

                            <!-- DESKTOP CTA -->
                            <div class="mt-auto hidden pt-5 sm:block">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <a
                                        href="#inquire"
                                        class="inline-flex items-center justify-center rounded-2xl bg-white px-5 py-4 text-sm font-black text-black transition hover:bg-zinc-200"
                                    >
                                        Inquire Now
                                    </a>

                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-2xl border border-white/10 bg-white/[0.03] px-5 py-4 text-sm font-bold text-white transition hover:border-white/30 hover:bg-white/[0.06]"
                                        @click="copyLink"
                                    >
                                        {{
                                            copied
                                                ? "Link Copied"
                                                : "Share Watch"
                                        }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </section>

            <!-- TABBED CONTENT -->
            <section
                id="inquire"
                class="mx-auto max-w-7xl px-4 pb-8 sm:px-6 lg:px-8"
            >
                <div
                    class="rounded-[1.5rem] border border-white/10 bg-[#0A0A0B] p-4 sm:p-6"
                >
                    <div
                        class="thin-scrollbar flex gap-2 overflow-x-auto rounded-2xl border border-white/10 bg-[#050505] p-1"
                    >
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            type="button"
                            class="min-w-24 flex-1 rounded-xl px-4 py-3 text-xs font-black uppercase tracking-[0.16em] transition"
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

                    <!-- SPECS TAB -->
                    <div v-if="activeTab === 'specs'" class="mt-5">
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
                                Check the important details before reservation.
                            </p>
                        </div>

                        <div
                            v-if="availableSpecs.length"
                            class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
                        >
                            <div
                                v-for="spec in availableSpecs"
                                :key="spec.label"
                                class="rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                            >
                                <p
                                    class="text-[10px] font-semibold uppercase tracking-[0.16em] text-zinc-500"
                                >
                                    {{ spec.label }}
                                </p>

                                <p class="mt-1 text-sm font-bold text-white">
                                    {{ spec.value }}
                                </p>
                            </div>
                        </div>

                        <p v-else class="mt-5 text-sm text-zinc-500">
                            Full specifications available upon request.
                        </p>
                    </div>

                    <!-- WARRANTY TAB -->
                    <div v-if="activeTab === 'warranty'" class="mt-5">
                        <p
                            class="text-[11px] font-bold uppercase tracking-[0.32em] text-zinc-500"
                        >
                            Warranty
                        </p>

                        <h2
                            class="mt-2 text-2xl font-bold tracking-[-0.04em] text-white"
                        >
                            Montre Card
                        </h2>

                        <div
                            class="mt-5 grid gap-3 text-sm leading-6 text-zinc-400 lg:grid-cols-3"
                        >
                            <div
                                class="rounded-2xl border border-white/10 bg-white/[0.03] p-4"
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
                                class="rounded-2xl border border-white/10 bg-white/[0.03] p-4"
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
                                class="rounded-2xl border border-white/10 bg-white/[0.03] p-4"
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
                    </div>

                    <!-- INQUIRY TAB -->
                    <div v-if="activeTab === 'inquiry'" class="mt-5">
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
                            Message Montre Nova to confirm availability, request
                            more photos, ask for payment details, or reserve
                            this timepiece.
                        </p>

                        <div class="mt-5 grid gap-3 sm:grid-cols-3">
                            <a
                                v-for="link in contactLinks"
                                :key="link.label"
                                :href="link.href"
                                class="group rounded-2xl border border-white/10 bg-white/[0.03] p-4 transition hover:border-white/30 hover:bg-white/[0.06]"
                            >
                                <div
                                    class="flex items-center justify-between gap-4"
                                >
                                    <p class="text-sm font-bold text-white">
                                        {{ link.label }}
                                    </p>

                                    <span
                                        class="text-zinc-500 transition group-hover:text-white"
                                    >
                                        →
                                    </span>
                                </div>

                                <p class="mt-2 text-xs leading-5 text-zinc-500">
                                    {{ link.description }}
                                </p>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- MOBILE STICKY CTA -->
        <div
            class="fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-[#050505]/95 p-3 backdrop-blur-xl sm:hidden"
        >
            <div class="grid grid-cols-[1fr_auto] gap-2">
                <a
                    href="#inquire"
                    class="inline-flex items-center justify-center rounded-2xl bg-white px-4 py-3 text-sm font-black text-black"
                    @click="activeTab = 'inquiry'"
                >
                    Inquire Now
                </a>

                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3 text-sm font-bold text-white"
                    @click="copyLink"
                >
                    {{ copied ? "Copied" : "Share" }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.description-clamp {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
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
</style>
