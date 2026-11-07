<script setup>
import CollectionBackLink from "@/Components/CollectionBackLink.vue";
import PublicHeader from "@/Components/PublicHeader.vue";
import { Head } from "@inertiajs/vue3";
import { computed } from "vue";
import PreorderPhotoGallery from "@/Components/PreorderPhotoGallery.vue";
import PreorderRecommendations from "@/Components/PreorderRecommendations.vue";
const props = defineProps({
    watch: Object,
    relatedPreorders: { type: Array, default: () => [] },
});
const name = computed(() =>
    [props.watch.brand, props.watch.model_name].filter(Boolean).join(" "),
);
const money = (n) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(Number(n || 0));
const deposit = computed(
    () => Math.round(Number(props.watch.price) * 20) / 100,
);
const messenger = computed(
    () =>
        "https://m.me/montrenova?text=" +
        encodeURIComponent(
            `Hi Montre Nova! I would like to preorder ${name.value}${props.watch.reference_number ? " (" + props.watch.reference_number + ")" : ""}.\nListed price: ${money(props.watch.price)}\n20% down payment: ${money(deposit.value)}\nI understand a 20% down payment is required to confirm my order. May I confirm the lead time and payment details?\n${window.location.href}`,
        ),
);
const specs = computed(() =>
    [
        ["Movement", "movement"],
        ["Case size", "case_size"],
        ["Case material", "case_material"],
        ["Dial", "dial_color"],
        ["Crystal", "crystal"],
        ["Bracelet / strap", "bracelet_or_strap"],
        ["Water resistance", "water_resistance"],
        ["Box & papers", "box_papers"],
    ].filter(([, key]) => props.watch[key]),
);
</script>
<template>
    <Head :title="`${name} · Preorder | Montre Nova`"
        ><meta
            name="description"
            :content="`Preorder ${name} at Montre Nova. A 20% down payment is required to confirm your order.`"
    /></Head>
    <div class="preorder-public">
        <PublicHeader position="sticky" />
        <main>
            <div style="max-width:1240px;margin:0 auto;padding:12px 20px 0"><CollectionBackLink /></div>
            <div class="product">
                <section aria-label="Watch photos">
                    <PreorderPhotoGallery
                        :images="watch.images || []"
                        :primary-image="watch.primary_image_url"
                        :title="name"
                    />
                    <p class="caption">
                        Reference photos from a previously sold piece. Confirm
                        the incoming watch’s condition and details with us.
                    </p>
                </section>
                <section class="information">
                    <p class="eyebrow">The collection · By request</p>
                    <h1>{{ name }}</h1>
                    <p class="reference">{{ watch.reference_number }}</p>
                    <div class="price">{{ money(watch.price) }}</div>
                    <div class="deposit">
                        <p class="eyebrow">Reserve your next timepiece</p>
                        <h2>20% down payment to confirm</h2>
                        <p>
                            A 20% down payment of
                            <strong>{{ money(deposit) }}</strong> is required to
                            confirm your preorder. The remaining balance is due
                            before handover.
                        </p>
                        <p v-if="watch.estimated_arrival">
                            <strong>Estimated arrival:</strong>
                            {{ watch.estimated_arrival }}
                        </p>
                    </div>
                    <a
                        class="cta"
                        :href="messenger"
                        target="_blank"
                        rel="noopener noreferrer"
                        >Preorder via Messenger
                        <span aria-hidden="true">↗</span></a
                    >
                    <p class="caption">
                        Confirm availability, the final price, lead time and
                        payment instructions with our team on Messenger.
                    </p>
                    <p v-if="watch.description" class="description">
                        {{ watch.description }}
                    </p>
                    <dl v-if="specs.length">
                        <div v-for="[label, key] in specs" :key="key">
                            <dt>{{ label }}</dt>
                            <dd>{{ watch[key] }}</dd>
                        </div>
                    </dl>
                    <section
                        v-for="(section, i) in watch.sections || []"
                        :key="i"
                        class="description"
                    >
                        <h2>{{ section.title }}</h2>
                        <p>{{ section.content }}</p>
                    </section>
                </section>
            </div>
            <PreorderRecommendations :items="relatedPreorders" />
        </main>
        <footer>Montre Nova · Thoughtfully selected timepieces</footer>
    </div>
</template>
<style scoped>
.preorder-public {
    min-height: 100dvh;
    background: #0b0b0d;
    color: #eee;
    font-family: inherit;
}

.preorder-public main {
    max-width: 1240px;
    padding: 55px 28px 80px;
    margin: auto;
}
.product {
    display: grid;
    grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr);
    gap: 64px;
    align-items: start;
}
@media (min-width: 1024px) {
    .product > section:first-child {
        position: sticky;
        top: 100px;
    }
}

.eyebrow {
    text-transform: uppercase;
    letter-spacing: 0.18em;
    font-size: 10px;
    color: #d2b980;
}
.information h1 {
    font-size: clamp(30px, 4vw, 46px);
    line-height: 1.12;
    letter-spacing: -0.04em;
    margin: 17px 0 10px;
    font-weight: 500;
    overflow-wrap: anywhere;
}
.reference {
    color: #8c8c98;
    font-size: 14px;
}
.price {
    font-size: 32px;
    margin: 28px 0;
    letter-spacing: -0.04em;
}
.deposit {
    border: 1px solid #d3b78038;
    background: #d3b78009;
    padding: 23px;
    border-radius: 14px;
}
.deposit h2 {
    font-size: 20px;
    font-weight: 500;
    margin: 10px 0;
}
.deposit p {
    font-size: 13px;
    line-height: 1.9;
    color: #c4c0b7;
    margin-top: 10px;
}
.cta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 23px;
    background: #e2cfaa;
    color: #241c11;
    font-size: 14px;
    font-weight: 600;
    border-radius: 10px;
    margin-top: 24px;
    min-height: 54px;
}
.cta:hover {
    background: #f0e2c9;
}
.caption {
    font-size: 11px;
    line-height: 1.8;
    color: #898991;
    margin-top: 14px;
}

.description {
    font-size: 14px;
    line-height: 1.9;
    color: #a8a8b0;
    margin-top: 28px;
    white-space: pre-wrap;
}
.description h2 {
    color: #e5e5e7;
}
.information dl {
    margin-top: 28px;
    border-top: 1px solid #ffffff12;
}
.information dl div {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    padding: 13px 0;
    border-bottom: 1px solid #ffffff12;
    font-size: 12px;
}
.information dt {
    color: #8e8e98;
}
.information dd {
    text-align: right;
    overflow-wrap: anywhere;
}
.preorder-public footer {
    padding: 25px;
    text-align: center;
    color: #686873;
    border-top: 1px solid #ffffff10;
    font-size: 11px;
}

.preorder-public a:focus-visible,
.preorder-public button:focus-visible {
    outline: 2px solid #d3b780;
    outline-offset: 4px;
}
@media (max-width: 760px) {
    .preorder-public main {
        padding: 25px 20px 50px;
    }
    .product {
        grid-template-columns: 1fr;
        gap: 28px;
    }

    .price {
        font-size: 29px;
        margin: 20px 0;
    }
    .deposit {
        padding: 19px;
    }
}
</style>
