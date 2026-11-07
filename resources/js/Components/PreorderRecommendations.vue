<script setup>
import { computed } from "vue";
import CollectionBackLink from "@/Components/CollectionBackLink.vue";
import WatchCardCarousel from "@/Components/WatchCardCarousel.vue";
const props = defineProps({ items: { type: Array, default: () => [] } });
const cards = computed(() =>
    props.items.map((item) => ({
        ...item,
        card_image:
            item.primary_hd_url || item.primary_image_url || item.image_url,
        card_price: item.price || item.selling_price,
        card_original_price: null,
        status_label: "PREORDER",
    })),
);
</script>
<template>
    <section
        class="preorder-recommendations"
        aria-label="More preorder watches"
    >
        <WatchCardCarousel
            v-if="cards.length"
            :items="cards"
            eyebrow="More Preorder Watches"
            description="Other preorder pieces you can tap to view next."
            status-label="PREORDER"
            label="More preorder watches, swipe to browse"
        />
        <div v-else class="recommendations-empty">
            <p>More preorder models are on their way.</p>
            <CollectionBackLink />
        </div>
    </section>
</template>
<style scoped>
.preorder-recommendations {
    margin-top: 40px;
    min-width: 0;
}
.recommendations-empty {
    border: 1px dashed #ffffff20;
    border-radius: 7px;
    padding: 24px;
    color: #a1a1aa;
    font-size: 13px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}
.recommendations-empty a {
    display: inline-flex;
    align-items: center;
    min-height: 44px;
    color: #d5bd93;
}
@media (max-width: 639px) {
    .preorder-recommendations {
        margin-top: 28px;
    }
}
</style>
