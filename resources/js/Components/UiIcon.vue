<script setup>
defineProps({ name: { type: String, default: "chevron-right" } });
const paths = {
    eye: [
        "M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z",
        "M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z",
    ],
    chat: [
        "M21 11.5a8.5 8.5 0 0 1-8.5 8.5 9 9 0 0 1-4-.9L3 21l1.9-5.5a9 9 0 0 1-.9-4A8.5 8.5 0 0 1 12.5 3h.5a8.5 8.5 0 0 1 8 8v.5Z",
        "M8 11h9M8 15h5",
    ],
    "chevron-right": ["m9 5 7 7-7 7"],
    "chevron-left": ["m15 5-7 7 7 7"],
};
</script>
<template>
    <svg
        class="mn-ui-icon"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.7"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
        focusable="false"
    >
        <path
            v-for="path in paths[name] || paths['chevron-right']"
            :key="path"
            :d="path"
        />
    </svg>
</template>
<style scoped>
.mn-ui-icon {
    display: inline-block !important;
    width: 16px !important;
    height: 16px !important;
    min-width: 16px !important;
    max-width: 16px !important;
    min-height: 16px !important;
    max-height: 16px !important;
    flex: none !important;
    vertical-align: middle;
    fill: none !important;
    stroke: currentColor !important;
}
</style>
