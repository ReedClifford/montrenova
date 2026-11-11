<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { computed } from "vue";
import PublicHeader from "@/Components/PublicHeader.vue";
import { useWatchShortlist } from "@/Composables/useWatchShortlist";

const props = defineProps({ watches: Array, requestedItems: Array });
const shortlist = useWatchShortlist();
const unavailable = computed(() => props.requestedItems.filter(
    (id) => !props.watches.some((watch) => String(watch.id) === id),
));
const statuses = { available: "Available", preorder: "Preorder", sold: "Sold" };
const peso = new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" });

function remove(ids) {
    ids.forEach(shortlist.remove);
    router.get(route("public.watches.compare"), {
        items: props.requestedItems.filter((id) => !ids.includes(id)),
    }, { preserveScroll: true, replace: true });
}

const rows = [
    ["Status", (watch) => statuses[watch.status]],
    ["Price", (watch) => {
        const price = watch.status === "sold" ? watch.sold_price : watch.price;
        return Number(price) > 0 ? peso.format(price) : "Price upon request";
    }],
    ["Reference", (watch) => watch.reference_number],
    ["Case size", (watch) => watch.case_size],
    ["Movement", (watch) => watch.movement],
    ["Condition", (watch) => watch.condition],
    ["Warranty", (watch) => watch.warranty_years === 1 ? "1 year" : `${watch.warranty_years} years`],
];
</script>

<template>
    <Head title="Compare watches | Montre Nova" />
    <div class="min-h-screen bg-[#0b0b0d] text-zinc-100">
        <PublicHeader position="sticky" />
        <main class="mx-auto max-w-6xl px-4 py-10 sm:px-8">
            <Link href="/#collection" class="text-sm text-[#e5cda5] underline">Back to collection</Link>
            <h1 class="mt-4 text-3xl font-semibold sm:text-4xl">Compare watches</h1>
            <p v-if="!watches.length" class="mt-4 text-zinc-400">Save up to three watches from the collection to compare them here.</p>
            <div v-if="unavailable.length" class="my-4 flex flex-wrap items-center gap-3 text-sm">
                <p>{{ unavailable.length }} saved {{ unavailable.length === 1 ? "watch is" : "watches are" }} no longer public.</p>
                <button type="button" class="underline" @click="remove(unavailable)">Remove from shortlist</button>
            </div>
            <div v-if="watches.length" tabindex="0" role="region" aria-label="Watch comparison table" class="mt-6 overflow-x-auto rounded-lg border border-white/15">
                <table class="w-full table-fixed border-collapse text-left text-sm">
                    <thead class="bg-white/5">
                        <tr>
                            <th scope="col" class="w-24 p-3 align-bottom sm:w-36">Details</th>
                            <th v-for="watch in watches" :key="watch.id" scope="col" class="w-40 p-3 align-top sm:w-auto">
                                <Link :href="watch.url" class="block break-words underline decoration-white/40 underline-offset-4">
                                    <img v-if="watch.primary_image_url" :src="watch.primary_image_url" alt="" class="mb-3 aspect-square w-full rounded bg-zinc-900 object-cover" />
                                    {{ watch.brand }} {{ watch.model_name }}
                                </Link>
                                <button type="button" :aria-label="`Remove ${watch.brand} ${watch.model_name}`" class="mt-2 text-xs font-normal text-zinc-400 underline" @click="remove([String(watch.id)])">Remove</button>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="[label, value] in rows" :key="label" class="border-t border-white/10">
                            <th scope="row" class="p-3 font-medium text-zinc-400">{{ label }}</th>
                            <td v-for="watch in watches" :key="watch.id" class="break-words p-3">{{ value(watch) || "—" }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</template>
