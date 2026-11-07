<script setup>
import { ref, watch, onBeforeUnmount } from "vue";
const props = defineProps({ file: { default: null } });
const url = ref(null);
const isImage = ref(false);
function release() {
    if (url.value) URL.revokeObjectURL(url.value);
    url.value = null;
}
watch(
    () => props.file,
    (file) => {
        release();
        isImage.value = file instanceof File && file.type.startsWith("image/");
        if (isImage.value) url.value = URL.createObjectURL(file);
    },
    { immediate: true },
);
onBeforeUnmount(release);
</script>
<template>
    <div v-if="file" class="attachment-preview">
        <img v-if="url" :src="url" alt="Selected proof preview" />
        <div>
            <p>{{ file.name }}</p>
            <small
                >{{ (file.size / 1024 / 1024).toFixed(2) }} MB · Not uploaded
                until you save</small
            >
        </div>
    </div>
</template>
<style scoped>
.attachment-preview {
    display: flex;
    gap: 12px;
    align-items: center;
    border: 1px solid #ffffff26;
    border-radius: 10px;
    padding: 12px;
    margin-top: 12px;
    min-width: 0;
}
.attachment-preview img {
    height: 90px;
    width: 90px;
    object-fit: contain;
    border-radius: 6px;
    background: #09090b;
}
.attachment-preview p {
    font-size: 12px;
    overflow-wrap: anywhere;
}
.attachment-preview small {
    font-size: 11px;
    color: #a1a1aa;
}
</style>
