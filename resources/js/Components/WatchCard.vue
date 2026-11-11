<script setup>
import WatchShortlistButton from "@/Components/WatchShortlistButton.vue";
import UiIcon from "@/Components/UiIcon.vue";
import { Link } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
const props = defineProps({
    item: { type: Object, required: true },
    statusLabel: { type: String, default: "AVAILABLE" },
});
const broken = ref(false);
watch(
    () => props.item.card_image,
    () => (broken.value = false),
);
const name = computed(() =>
    [props.item.brand, props.item.model_name].filter(Boolean).join(" "),
);
const preorder = computed(
    () =>
        props.item.status === "preorder" ||
        props.item.status_label === "PREORDER",
);
const money = (value) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 0,
    }).format(Number(value || 0));
</script>
<template>
    <article class="watch-tile">
        <div class="tile-photo">
            <img
                v-if="item.card_image && !broken"
                :src="item.card_image"
                :alt="name"
                loading="lazy"
                @error="broken = true"
            />
            <div v-else class="tile-placeholder">MN</div>
            <span class="tile-status">{{ item.status_label || statusLabel }}</span>
        </div>
        <div class="tile-body">
            <p class="tile-brand">{{ item.brand || "Montre Nova" }}</p>
            <h3 :title="item.model_name">{{ item.model_name }}</h3>
            <p class="tile-reference" :title="item.reference_number">
                {{ item.reference_number || "Reference upon request" }}
            </p>
            <div class="tile-terms">
                <p v-if="preorder">20% DP required to confirm</p>
            </div>
            <div class="tile-footer">
                <div class="tile-prices">
                    <p class="tile-price">
                        {{ Number(item.card_price) > 0 ? money(item.card_price) : "Price upon request" }}
                    </p>
                    <p v-if="Number(item.card_original_price) > Number(item.card_price)" class="tile-original">
                        {{ money(item.card_original_price) }}
                    </p>
                </div>
                <div class="tile-actions">
                    <Link :href="item.url" class="tile-view" :aria-label="`View ${name}`">
                        View watch <UiIcon name="eye" aria-hidden="true" />
                    </Link>
                    <WatchShortlistButton class="tile-compare" :watch-id="item.id" :watch-name="name" />
                </div>
            </div>
        </div>
    </article>
</template>
<style scoped>
.watch-tile {
    position: relative;
    container-type: inline-size;
    display: flex;
    flex-direction: column;
    min-width: 0;
    width: 100%;
    overflow: hidden;
    border: 1px solid #ffffff21;
    border-radius: 7px;
    background: #18181a;
    color: #f4f4f5;
    text-decoration: none;
    transition: border-color 0.2s;
}
.watch-tile:hover {
    border-color: #ffffff59;
}
.watch-tile:has(.tile-view:focus-visible) {
    outline: 2px solid #d6bd8f;
    outline-offset: 3px;
}
.tile-photo {
    position: relative;
    aspect-ratio: 1;
    width: 100%;
    flex: none;
    overflow: hidden;
    background: #242427;
}
.tile-photo img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}
.watch-tile:hover img {
    transform: scale(1.025);
}
.tile-placeholder {
    display: grid;
    place-items: center;
    height: 100%;
    letter-spacing: 0.18em;
    color: #a1a1aa;
}
.tile-status {
    position: absolute;
    top: 10px;
    left: 10px;
    max-width: calc(100% - 20px);
    padding: 6px 8px;
    border: 1px solid #ffffff38;
    border-radius: 20px;
    background: #0e0e10e8;
    font-size: 8px;
    letter-spacing: 0.08em;
}
.tile-body {
    display: flex;
    flex: 1;
    flex-direction: column;
    min-width: 0;
    padding: 16px;
}
.tile-brand {
    font-size: 10px;
    color: #bdbdc2;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin: 0 0 7px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.tile-body h3 {
    font-size: 15px;
    line-height: 1.4;
    height: 2.8em;
    font-weight: 650;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    overflow-wrap: anywhere;
    margin: 0;
}
.tile-reference {
    font-size: 10px;
    line-height: 1.5;
    margin: 8px 0 0;
    color: #a4a4ab;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.tile-terms {
    height: 46px;
    flex: none;
    padding-top: 10px;
    font-size: 10px;
    line-height: 1.65;
    color: #d6bd8f;
}
.tile-footer {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-items: start;
    gap: 10px;
    margin-top: auto;
    padding-top: 12px;
    border-top: 1px solid #ffffff1f;
}
.tile-prices {
    min-height: 44px;
    min-width: 0;
}
.tile-price {
    font-size: 17px;
    line-height: 1.3;
    font-weight: 750;
    overflow-wrap: anywhere;
}
.tile-original {
    font-size: 10px;
    line-height: 1.4;
    color: #95959b;
    text-decoration: line-through;
    margin-top: 5px;
    overflow-wrap: anywhere;
}
.tile-actions {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 6px;
}
.tile-view {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 44px;
    padding: 6px 8px;
    border: 1px solid #ffffff2e;
    border-radius: 5px;
    background: #ffffff0a;
    color: inherit;
    font-size: 11px;
    text-decoration: none;
    white-space: nowrap;
}
.tile-view::after {
    content: "";
    position: absolute;
    inset: 0;
}
.tile-view:hover {
    background: #ffffff12;
}
.tile-compare {
    position: relative;
    z-index: 1;
    min-height: 44px;
}
@container (min-width: 195px) {
    .tile-actions {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (min-width: 640px) {
    @container (min-width: 310px) {
        .tile-footer {
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
        }
        .tile-actions {
            grid-template-columns: auto auto;
        }
        .tile-prices {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
    }
}
@media (max-width: 639px) {
    .tile-body {
        padding: 12px;
    }
    .tile-body h3 {
        font-size: 13px;
    }
    .tile-brand,
    .tile-reference {
        font-size: 9px;
    }
    .tile-price {
        font-size: 15px;
    }
    .tile-status {
        top: 8px;
        left: 8px;
        font-size: 7px;
        padding: 5px 7px;
    }
}
@media (max-width: 359px) {
    .tile-body {
        padding: 10px;
    }
    .tile-body h3 {
        font-size: 12px;
    }
    .tile-price {
        font-size: 14px;
    }
}
@media (prefers-reduced-motion: reduce) {
    .watch-tile,
    .tile-photo img {
        transition: none;
    }
}
</style>
