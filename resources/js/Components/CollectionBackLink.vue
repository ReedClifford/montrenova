<script setup>
import { Link } from "@inertiajs/vue3";
import {
    collectionHref,
    requestCollectionRestore,
} from "@/Utils/collectionMemory";
</script>
<template>
    <Link
        :href="collectionHref()"
        @click="requestCollectionRestore"
        class="collection-back"
        >← Back to collection</Link
    >
</template>
<style scoped>
.collection-back {
    display: inline-flex;
    align-items: center;
    min-height: 44px;
    color: #c8c8cf;
    font-size: 12px;
    gap: 8px;
}
.collection-back:hover {
    color: #e2cba5;
}
.collection-back:focus-visible {
    outline: 2px solid #e2cba5;
    outline-offset: 4px;
}
</style>
