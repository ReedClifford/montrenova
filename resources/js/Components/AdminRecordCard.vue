<script setup>
defineProps({
    title: String,
    subtitle: String,
    image: String,
    selected: Boolean,
    detailsLabel: { type: String, default: "Details & more actions" },
});
</script>
<template>
    <article class="admin-record" :class="{ selected }">
        <div class="record-heading">
            <div v-if="image" class="record-image">
                <img :src="image" alt="" loading="lazy" />
            </div>
            <div class="record-title">
                <h3>{{ title }}</h3>
                <p v-if="subtitle">{{ subtitle }}</p>
                <div v-if="$slots.badges" class="record-badges">
                    <slot name="badges" />
                </div>
            </div>
            <slot name="selection" />
        </div>
        <div v-if="$slots.metrics" class="record-metrics">
            <slot name="metrics" />
        </div>
        <slot />
        <div v-if="$slots.actions" class="record-actions">
            <slot name="actions" />
        </div>
        <details v-if="$slots.details" class="record-details">
            <summary>{{ detailsLabel }}</summary>
            <div class="record-detail-body"><slot name="details" /></div>
        </details>
    </article>
</template>
<style scoped>
.admin-record {
    min-width: 0;
    border: 1px solid #ffffff1a;
    border-radius: 14px;
    background: #101012;
    padding: 16px;
    color: #e4e4e7;
}
.admin-record.selected {
    border-color: #dfc99f;
    box-shadow: 0 0 0 1px #dfc99f55;
}
.record-heading {
    display: flex;
    gap: 12px;
    align-items: flex-start;
}
.record-image {
    width: 64px;
    height: 76px;
    flex: none;
    overflow: hidden;
    background: #08080a;
    border-radius: 8px;
}
.record-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
.record-title {
    min-width: 0;
    flex: 1;
}
.record-title h3 {
    font-size: 14px;
    font-weight: 650;
    line-height: 1.6;
    overflow-wrap: anywhere;
}
.record-title > p {
    font-size: 11px;
    color: #a1a1aa;
    line-height: 1.7;
    margin-top: 3px;
    overflow-wrap: anywhere;
}
.record-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 9px;
}
.record-badges :deep(span) {
    font-size: 10px;
    border-radius: 6px;
    padding: 4px 7px;
    max-width: 100%;
    overflow-wrap: anywhere;
}
.record-metrics {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    border-top: 1px solid #ffffff12;
    margin-top: 14px;
    padding-top: 12px;
}
.record-metrics :deep(dt) {
    color: #a1a1aa;
    font-size: 11px;
    line-height: 1.6;
}
.record-metrics :deep(dd) {
    font-size: 14px;
    font-weight: 650;
    line-height: 1.6;
    overflow-wrap: anywhere;
    margin-top: 3px;
}
.record-actions {
    display: flex;
    gap: 8px;
    margin-top: 14px;
    flex-wrap: wrap;
}
.record-actions :deep(button),
.record-actions :deep(a),
.record-detail-body :deep(button),
.record-detail-body :deep(a) {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    padding: 10px 12px;
    border: 1px solid #ffffff26;
    border-radius: 8px;
    color: #e4e4e7;
    font-size: 12px;
    font-weight: 600;
}
.record-actions :deep(button),
.record-actions :deep(a) {
    flex: 1;
}
.record-actions :deep(.primary) {
    background: #dfc99f;
    color: #211a10;
    border-color: #dfc99f;
}
.record-detail-body :deep(.danger) {
    color: #fda4af;
    border-color: #fb718540;
}
.record-details {
    margin-top: 12px;
    border-top: 1px solid #ffffff12;
}
.record-details summary {
    padding: 14px 0 2px;
    font-size: 12px;
    color: #b8b8c0;
    cursor: pointer;
    min-height: 44px;
}
.record-detail-body {
    padding-top: 12px;
    font-size: 12px;
    line-height: 1.8;
    overflow-wrap: anywhere;
}
.admin-record :deep(button:focus-visible),
summary:focus-visible {
    outline: 2px solid #dfc99f;
    outline-offset: 3px;
}
@media (max-width: 359px) {
    .admin-record {
        padding: 12px;
    }
    .record-image {
        width: 52px;
        height: 64px;
    }
}
</style>
