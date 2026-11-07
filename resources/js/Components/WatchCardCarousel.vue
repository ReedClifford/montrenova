<script setup>
import { Link } from "@inertiajs/vue3";
import { ref, watch } from "vue";
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
const brokenImages = ref({});
watch(
    () => props.items,
    () => (brokenImages.value = {}),
);
const money = (value) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 0,
    }).format(Number(value || 0));
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
                <Link
                    v-for="watchItem in items"
                    :key="watchItem.id"
                    :href="watchItem.url"
                    class="carousel-card mn-more-card group"
                >
                    <div class="mn-more-photo">
                        <img
                            v-if="
                                watchItem.card_image &&
                                !brokenImages[watchItem.id]
                            "
                            :src="watchItem.card_image"
                            :alt="`${watchItem.brand} ${watchItem.model_name}`"
                            loading="lazy"
                            class="mn-more-image"
                            @error="
                                brokenImages = {
                                    ...brokenImages,
                                    [watchItem.id]: true,
                                }
                            "
                        />
                        <div v-else class="mn-more-placeholder">MN</div>
                        <span class="mn-more-status">{{
                            watchItem.status_label || statusLabel
                        }}</span>
                    </div>
                    <div class="mn-more-content">
                        <div class="mn-more-heading">
                            <p class="mn-more-brand">
                                {{ watchItem.brand || "Montre Nova" }}
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
                                    {{ money(watchItem.card_price) }}
                                </p>
                                <p
                                    v-if="watchItem.card_original_price != null"
                                    class="mn-more-original"
                                >
                                    {{ money(watchItem.card_original_price) }}
                                </p>
                            </div>
                            <span class="mn-more-view">VIEW</span>
                        </div>
                    </div>
                </Link>
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
.carousel-card.mn-more-card::before {
    display: none;
}
.carousel-card.mn-more-card:hover {
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
    .carousel-row {
        gap: 12px;
        padding: 8px 14px 18px;
        scroll-padding-inline: 14px;
    }
    .carousel-card.mn-more-card {
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
