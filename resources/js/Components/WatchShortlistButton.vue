<script setup>
import { computed } from "vue";
import { useWatchShortlist } from "@/Composables/useWatchShortlist";

const props = defineProps({ watchId: [String, Number], watchName: String });
const { count, has, toggle } = useWatchShortlist();
const saved = computed(() => has(props.watchId));
</script>

<template>
    <button
        type="button"
        class="shortlist-button"
        :aria-label="`Compare ${watchName}`"
        :aria-pressed="saved"
        :disabled="!saved && count >= 3"
        @click="toggle(watchId)"
    >
        {{ saved ? "✓ Saved" : "Compare +" }}
    </button>
</template>

<style scoped>
.shortlist-button {
    border: 1px solid #ffffff45;
    border-radius: 5px;
    padding: 7px 9px;
    background: #18181a;
    color: #fff;
    font-size: 11px;
    line-height: 1.3;
}
.shortlist-button[aria-pressed="true"] {
    color: #f1d7a9;
    border-color: #f1d7a9;
}
.shortlist-button:disabled {
    opacity: .65;
    cursor: not-allowed;
}
</style>
