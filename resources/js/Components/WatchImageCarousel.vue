<script setup>
import { computed, ref } from "vue";

const props = defineProps({
    images: {
        type: Array,
        default: () => [],
    },
    watchName: {
        type: String,
        default: "Watch",
    },
    fallbackImage: {
        type: String,
        default: "/images/montre-nova-logo.png",
    },
});

const activeIndex = ref(0);
const showPreview = ref(false);
const touchStartX = ref(null);

const normalizedImages = computed(() => {
    if (!props.images?.length) {
        return [
            {
                id: "fallback",
                image_url: props.fallbackImage,
                hd_url: props.fallbackImage,
            },
        ];
    }

    return props.images.map((image, index) => ({
        id: image.id || index,
        image_url:
            image.hd_url ||
            image.image_url ||
            image.primary_hd_url ||
            image.primary_image_url ||
            image.thumbnail_url ||
            props.fallbackImage,
        hd_url:
            image.hd_url ||
            image.image_url ||
            image.primary_hd_url ||
            image.primary_image_url ||
            image.thumbnail_url ||
            props.fallbackImage,
    }));
});

const activeImage = computed(() => {
    return (
        normalizedImages.value[activeIndex.value] || normalizedImages.value[0]
    );
});

const goToImage = (index) => {
    activeIndex.value = index;
};

const nextImage = () => {
    activeIndex.value =
        activeIndex.value >= normalizedImages.value.length - 1
            ? 0
            : activeIndex.value + 1;
};

const previousImage = () => {
    activeIndex.value =
        activeIndex.value <= 0
            ? normalizedImages.value.length - 1
            : activeIndex.value - 1;
};

const onTouchStart = (event) => {
    touchStartX.value = event.touches[0].clientX;
};

const onTouchEnd = (event) => {
    if (touchStartX.value === null) return;

    const touchEndX = event.changedTouches[0].clientX;
    const diff = touchStartX.value - touchEndX;

    if (Math.abs(diff) > 40) {
        if (diff > 0) {
            nextImage();
        } else {
            previousImage();
        }
    }

    touchStartX.value = null;
};
</script>

<template>
    <div class="space-y-4">
        <div
            class="group relative overflow-hidden rounded-[2rem] border border-white/10 bg-[#050505]"
            @touchstart="onTouchStart"
            @touchend="onTouchEnd"
        >
            <div class="aspect-square sm:aspect-[4/5]">
                <img
                    :src="activeImage.hd_url || activeImage.image_url"
                    :alt="watchName"
                    class="h-full w-full object-cover"
                    loading="eager"
                />
            </div>

            <button
                type="button"
                class="absolute inset-0 cursor-zoom-in"
                aria-label="Open image preview"
                @click="showPreview = true"
            ></button>

            <div
                class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent p-4"
            >
                <p
                    class="text-xs font-bold uppercase tracking-[0.22em] text-zinc-300"
                >
                    Actual Photo
                </p>

                <p class="mt-1 text-xs text-zinc-500">
                    Tap to zoom • Swipe to browse
                </p>
            </div>

            <button
                v-if="normalizedImages.length > 1"
                type="button"
                class="absolute left-3 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/10 bg-black/60 text-white backdrop-blur transition hover:bg-black"
                @click.stop="previousImage"
            >
                ‹
            </button>

            <button
                v-if="normalizedImages.length > 1"
                type="button"
                class="absolute right-3 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/10 bg-black/60 text-white backdrop-blur transition hover:bg-black"
                @click.stop="nextImage"
            >
                ›
            </button>

            <div
                v-if="normalizedImages.length > 1"
                class="absolute right-4 top-4 rounded-full border border-white/10 bg-black/70 px-3 py-1 text-xs font-bold text-white backdrop-blur"
            >
                {{ activeIndex + 1 }} / {{ normalizedImages.length }}
            </div>
        </div>

        <div
            v-if="normalizedImages.length > 1"
            class="flex gap-3 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
        >
            <button
                v-for="(image, index) in normalizedImages"
                :key="image.id"
                type="button"
                class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl border bg-[#050505] transition"
                :class="
                    activeIndex === index
                        ? 'border-white'
                        : 'border-white/10 opacity-60 hover:opacity-100'
                "
                @click="goToImage(index)"
            >
                <img
                    :src="image.image_url"
                    :alt="`${watchName} photo ${index + 1}`"
                    class="h-full w-full object-cover"
                    loading="lazy"
                />
            </button>
        </div>

        <Teleport to="body">
            <div
                v-if="showPreview"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/95 p-4"
            >
                <button
                    type="button"
                    class="absolute right-4 top-4 rounded-full border border-white/10 bg-white/10 px-4 py-2 text-sm font-bold text-white"
                    @click="showPreview = false"
                >
                    Close
                </button>

                <button
                    v-if="normalizedImages.length > 1"
                    type="button"
                    class="absolute left-4 top-1/2 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full border border-white/10 bg-white/10 text-2xl text-white"
                    @click="previousImage"
                >
                    ‹
                </button>

                <img
                    :src="activeImage.hd_url || activeImage.image_url"
                    :alt="watchName"
                    class="max-h-[88vh] max-w-full rounded-2xl object-contain"
                />

                <button
                    v-if="normalizedImages.length > 1"
                    type="button"
                    class="absolute right-4 top-1/2 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full border border-white/10 bg-white/10 text-2xl text-white"
                    @click="nextImage"
                >
                    ›
                </button>
            </div>
        </Teleport>
    </div>
</template>
