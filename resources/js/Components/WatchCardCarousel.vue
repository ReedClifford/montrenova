<script setup>
import WatchCard from "@/Components/WatchCard.vue";
const props = defineProps({
    items: { type: Array, default: () => [] },
    eyebrow: { type: String, default: "More Available Watches" },
    title: { type: String, default: "Continue browsing" },
    description: {
        type: String,
        default: "Other available pieces you can tap to view next.",
    },
    statusLabel: { type: String, default: "AVAILABLE" },
    label: { type: String, default: "More available watches, swipe to browse" },
});
function onKey(event) {
    if (
        event.target !== event.currentTarget ||
        !["ArrowLeft", "ArrowRight"].includes(event.key)
    )
        return;
    event.preventDefault();
    const el = event.currentTarget;
    const card = el.querySelector(".carousel-card");
    const gap = parseFloat(getComputedStyle(el).gap) || 14;
    el.scrollBy({
        left:
            (event.key === "ArrowRight" ? 1 : -1) *
            ((card?.getBoundingClientRect().width || 240) + gap),
        behavior: window.matchMedia("(prefers-reduced-motion: reduce)").matches
            ? "auto"
            : "smooth",
    });
}
</script>
<template>
    <div class="watch-card-carousel">
        <div class="shine-line"></div>

        <div
            class="flex flex-col justify-between gap-4 border-white/10 p-5 sm:p-6 lg:flex-row lg:items-end"
        >
            <div>
                <p class="micro-label">{{ eyebrow }}</p>

                <h2
                    class="mn-related-title mt-3 text-2xl font-semibold tracking-[-0.035em] text-white sm:text-3xl"
                >
                    {{ title }}
                </h2>

                <p class="mt-3 max-w-xl text-sm leading-6 text-zinc-500">
                    {{ description }}
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
                :aria-label="label"
                role="region"
                tabindex="0"
                @keydown="onKey"
            >
                <WatchCard
                    v-for="watchItem in items"
                    :key="watchItem.id"
                    :item="watchItem"
                    :status-label="statusLabel"
                    class="carousel-card mn-more-card"
                />
            </div>
        </div>
    </div>
</template>
<style scoped>
.watch-card-carousel {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.11);
    border-radius: 7px;
    background: linear-gradient(
        145deg,
        rgba(28, 28, 31, 0.95),
        rgba(13, 13, 15, 0.98)
    );
    box-shadow: 0 16px 56px rgba(0, 0, 0, 0.28);
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
        rgba(255, 255, 255, 0.45),
        transparent
    );
    opacity: 0.35;
}
.micro-label {
    color: #a9a9af;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.17em;
    text-transform: uppercase;
}
.carousel-card {
    position: relative;
    transition:
        border-color 0.32s,
        box-shadow 0.32s;
}
.thin-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
}
.thin-scrollbar::-webkit-scrollbar {
    height: 5px;
}
.thin-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.18);
    border-radius: 999px;
}
.carousel-row:focus-visible,
.carousel-card:focus-visible {
    outline: 2px solid #d5bd93;
    outline-offset: 3px;
}
/* Continue browsing — matches the refined catalog's product-card system. */
.watch-card-carousel {
    overflow: hidden;
}
.carousel-row {
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
.carousel-card.mn-more-card {
    flex: 0 0 clamp(215px, 23vw, 282px);
    width: clamp(215px, 23vw, 282px);
    height: auto;
    scroll-snap-align: start;
}
@media (max-width: 639px) {
    .carousel-card.mn-more-card {
        flex-basis: min(68vw, 255px);
        width: min(68vw, 255px);
    }
    .carousel-row {
        gap: 12px;
        padding: 8px 14px 18px;
        scroll-padding-inline: 14px;
    }
}
</style>
