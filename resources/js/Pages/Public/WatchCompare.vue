<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import PublicHeader from "@/Components/PublicHeader.vue";
import { useWatchShortlist } from "@/Composables/useWatchShortlist";

const props = defineProps({ watches: Array, requestedItems: Array });
const shortlist = useWatchShortlist();
const differencesOnly = ref(false);
const updating = ref(false);
const failedPhotos = ref(new Set());
const unavailable = computed(() => props.requestedItems.filter(
    (id) => !props.watches.some((watch) => String(watch.id) === id),
));
const statusLabels = { available: "Available", preorder: "Preorder", sold: "Sold" };
const peso = new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" });

function photoUrl(watch) {
    return [
        watch.primary_image_url,
        watch.primary_hd_url,
        watch.thumbnail_url,
        watch.comparison_fallback_url,
        ...(watch.images || []).map((image) => image.image_url),
    ].find((url) => url && !failedPhotos.value.has(url));
}

function remove(ids) {
    router.get(route("public.watches.compare"), {
        items: props.requestedItems.filter((id) => !ids.includes(id)),
    }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onStart: () => (updating.value = true),
        onSuccess: () => {
            ids.forEach(shortlist.remove);
            if (props.watches.length < 2) differencesOnly.value = false;
        },
        onFinish: () => (updating.value = false),
    });
}

const fields = [
    ["Status", (watch) => statusLabels[watch.status]],
    ["Price", (watch) => {
        const price = watch.status === "sold" ? watch.sold_price : watch.price;
        return Number(price) > 0 ? peso.format(price) : "Upon request";
    }],
    ["Reference", (watch) => watch.reference_number],
    ["Case size", (watch) => watch.case_size],
    ["Movement", (watch) => watch.movement],
    ["Condition", (watch) => watch.condition],
    ["Warranty", (watch) => watch.warranty_years === 1 ? "1 year" : `${watch.warranty_years} years`],
];

const rows = computed(() => fields.map(([label, getValue]) => {
    const values = props.watches.map((watch) => String(getValue(watch) ?? "").trim());
    const differs = new Set(values.map((value) => value.toLowerCase())).size > 1;

    return { label, values, differs };
}).filter((row) => !differencesOnly.value || row.differs));
</script>

<template>
    <Head title="Compare watches | Montre Nova" />
    <div class="min-h-screen bg-[#0b0b0d] text-zinc-100">
        <PublicHeader position="sticky" />
        <main class="mx-auto max-w-6xl px-4 pb-16 pt-8 sm:px-8 sm:pt-12">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <Link href="/#collection" class="text-sm text-[#e5cda5] hover:underline">← Back to collection</Link>
                    <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Compare watches</h1>
                </div>
                <Link
                    v-if="watches.length && requestedItems.length < 3"
                    href="/#collection"
                    class="rounded-md border border-white/20 px-4 py-2.5 text-sm font-medium hover:border-[#e5cda5]"
                >
                    + Add a watch
                </Link>
            </div>

            <div v-if="unavailable.length" class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-400/20 bg-amber-400/5 p-4 text-sm">
                <p>{{ unavailable.length }} saved {{ unavailable.length === 1 ? "watch is" : "watches are" }} no longer public.</p>
                <button type="button" class="text-[#e5cda5] underline" :disabled="updating" @click="remove(unavailable)">Remove from shortlist</button>
            </div>

            <section v-if="!watches.length" class="mt-6 rounded-xl border border-white/10 bg-white/[0.035] p-8">
                <h2 class="text-lg font-semibold">Your shortlist is empty</h2>
                <p class="mt-2 text-sm text-zinc-400">Tap Compare on a watch card to add up to three watches.</p>
                <Link href="/#collection" class="mt-5 inline-flex rounded-md bg-[#e5cda5] px-4 py-2.5 text-sm font-semibold text-black">Browse watches</Link>
            </section>

            <section v-else class="mt-6" aria-label="Comparison details">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-3 text-sm">
                    <p class="text-zinc-400" role="status">
                        {{ watches.length === 1 ? "Add another watch to compare side by side." : `Comparing ${watches.length} watches` }}
                    </p>
                    <label v-if="watches.length > 1" class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-md border border-white/15 px-3">
                        <input v-model="differencesOnly" type="checkbox" class="rounded accent-[#e5cda5]" />
                        Show differences only
                    </label>
                </div>
                <p v-if="watches.length === 3" class="mb-2 text-xs text-zinc-400 sm:hidden">Swipe to see all three watches.</p>
                <div
                    tabindex="0"
                    role="region"
                    aria-label="Watch comparison table"
                    class="overflow-x-auto rounded-xl border border-white/15 focus-visible:outline focus-visible:outline-[#e5cda5]"
                    :aria-busy="updating"
                >
                    <table class="comparison-table" :style="{ '--watch-count': watches.length }">
                        <thead>
                            <tr>
                                <th scope="col" class="row-label align-bottom">Watch</th>
                                <th v-for="watch in watches" :key="watch.id" scope="col" class="align-top">
                                    <Link :href="watch.url" class="block break-words hover:text-[#e5cda5]">
                                        <div class="watch-photo">
                                            <img
                                                v-if="photoUrl(watch)"
                                                :key="photoUrl(watch)"
                                                :src="photoUrl(watch)"
                                                alt=""
                                                @error="failedPhotos.add($event.currentTarget.getAttribute('src'))"
                                            />
                                            <span v-else aria-hidden="true">MN</span>
                                        </div>
                                        <span class="block text-xs font-medium text-zinc-400">{{ watch.brand }}</span>
                                        <span class="mt-1 block font-semibold leading-snug">{{ watch.model_name }}</span>
                                    </Link>
                                    <div class="mt-2 flex flex-wrap items-center gap-x-3 text-xs">
                                        <Link :href="watch.url" class="py-2 font-medium text-[#e5cda5] underline underline-offset-4">View watch</Link>
                                        <button
                                            type="button"
                                            :aria-label="`Remove ${watch.brand} ${watch.model_name}`"
                                            class="py-2 font-normal text-zinc-400 underline underline-offset-4 disabled:opacity-50"
                                            :disabled="updating"
                                            @click="remove([String(watch.id)])"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in rows" :key="row.label">
                                <th scope="row" class="row-label">{{ row.label }}</th>
                                <td v-for="(value, index) in row.values" :key="watches[index].id" :class="{ 'price-cell': row.label === 'Price' }">
                                    <span v-if="row.label === 'Status'" class="status" :data-status="watches[index].status">{{ value }}</span>
                                    <template v-else>{{ value || "—" }}</template>
                                </td>
                            </tr>
                            <tr v-if="!rows.length">
                                <td :colspan="watches.length + 1" class="text-zinc-400">The listed details are the same for these watches.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</template>

<style scoped>
.comparison-table {
    width: 100%;
    min-width: calc(76px + var(--watch-count) * 102px);
    table-layout: fixed;
    border-spacing: 0;
    border-collapse: separate;
    text-align: left;
    font-size: 14px;
}
.comparison-table th,
.comparison-table td {
    padding: 14px;
    overflow-wrap: anywhere;
}
.comparison-table thead th {
    background: #19191c;
}
.comparison-table tbody > tr > * {
    border-top: 1px solid #ffffff14;
    background: #121214;
}
.comparison-table tbody > tr:nth-child(even) > * {
    background: #171719;
}
.comparison-table .row-label {
    position: sticky;
    left: 0;
    z-index: 1;
    width: clamp(76px, 16vw, 155px);
    border-right: 1px solid #ffffff14;
    color: #b4b4bc;
    font-size: 13px;
    font-weight: 550;
}
.watch-photo {
    position: relative;
    display: grid;
    width: 100%;
    max-width: 280px;
    aspect-ratio: 1;
    place-items: center;
    margin: 0 auto 12px;
    overflow: hidden;
    border-radius: 6px;
    background: #222225;
    color: #a1a1aa;
}
.watch-photo img {
    position: absolute;
    inset: 0;
    display: block;
    width: 100%;
    height: 100%;
    padding: clamp(3px, 1vw, 10px);
    object-fit: contain;
    object-position: center;
}
.price-cell {
    color: #f2ddb8;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}
.status {
    display: inline-block;
    border-radius: 4px;
    padding: 3px 7px;
    background: #ffffff12;
    color: #ddd;
    font-size: 11px;
    font-weight: 600;
}
.status[data-status="available"] {
    background: #34d39920;
    color: #a7f3d0;
}
.status[data-status="preorder"] {
    background: #fbbf2420;
    color: #fde68a;
}
@media (max-width: 639px) {
    .comparison-table th,
    .comparison-table td {
        padding: 9px 7px;
        font-size: 12px;
    }
    .comparison-table .row-label {
        font-size: 12px;
    }
}
</style>
