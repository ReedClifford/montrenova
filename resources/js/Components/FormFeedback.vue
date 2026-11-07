<script setup>
import { computed, nextTick, ref, watch } from "vue";
const props = defineProps({ errors: { type: Object, default: () => ({}) } });
const messages = computed(() => [
    ...new Set(Object.values(props.errors).filter(Boolean)),
]);
const summary = ref(null);
watch(
    () => props.errors,
    async () => {
        if (messages.value.length) {
            await nextTick();
            summary.value?.focus();
        }
    },
    { deep: true },
);
</script>
<template>
    <div
        v-if="messages.length"
        ref="summary"
        class="form-feedback"
        role="alert"
        tabindex="-1"
    >
        <strong>Please check the following before saving:</strong>
        <ul>
            <li v-for="message in messages" :key="message">{{ message }}</li>
        </ul>
    </div>
</template>
<style scoped>
.form-feedback {
    padding: 14px 16px;
    margin: 16px 0;
    border: 1px solid #fb71854d;
    border-radius: 10px;
    background: #fb71850a;
    color: #fda4af;
    font-size: 13px;
    line-height: 1.7;
}
.form-feedback ul {
    list-style: disc;
    padding-left: 18px;
    margin-top: 7px;
}
.form-feedback:focus {
    outline: 2px solid #fb718580;
    outline-offset: 3px;
}
</style>
