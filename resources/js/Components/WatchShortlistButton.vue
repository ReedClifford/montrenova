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
        <span aria-hidden="true">{{ saved ? "✓" : "+" }}</span>
        {{ saved ? "Added" : "Compare" }}
    </button>
</template>

<style scoped>
.shortlist-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    border: 1px solid #ffffff30;
    border-radius: 5px;
    padding: 7px 9px;
    background: transparent;
    color: #d4d4d8;
    font-size: 11px;
    line-height: 1.3;
}
.shortlist-button:hover:not(:disabled) {
    border-color: #ffffff75;
}
.shortlist-button:focus-visible {
    outline: 2px solid #e5cda5;
    outline-offset: 2px;
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
