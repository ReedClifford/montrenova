<script setup>
import { Link } from "@inertiajs/vue3";
import { useWatchShortlist } from "@/Composables/useWatchShortlist";

const { count, compareUrl, clear } = useWatchShortlist();
</script>

<template>
    <aside
        v-if="count"
        aria-label="Watch shortlist"
        class="fixed bottom-[max(1rem,env(safe-area-inset-bottom))] left-4 z-[85] flex max-w-[calc(100%-2rem)] flex-wrap items-center gap-x-3 gap-y-2 rounded-lg border border-white/25 bg-zinc-950 px-3 py-2 text-sm text-white shadow-xl"
    >
        <p role="status">{{ count }} of 3 saved<span v-if="count === 1"> · Add one more</span></p>
        <Link v-if="count > 1" :href="compareUrl" class="rounded bg-[#e5cda5] px-3 py-2 font-semibold text-black">Compare watches</Link>
        <button type="button" class="px-2 py-2 text-zinc-300 underline" @click="clear">Clear</button>
    </aside>
</template>
