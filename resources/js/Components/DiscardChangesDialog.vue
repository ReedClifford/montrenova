<script setup>
import Modal from "@/Components/Modal.vue";
import {
    discardDialogOpen,
    resolveDiscardChanges,
} from "@/Utils/discardChanges";
</script>
<template>
    <Modal
        :show="discardDialogOpen"
        max-width="sm"
        label="Unsaved changes"
        @close="resolveDiscardChanges(false)"
        ><section class="discard-panel">
            <h2>Keep your changes?</h2>
            <p>
                You have details that haven’t been saved. Keep editing, or
                discard them to continue.
            </p>
            <div>
                <button
                    type="button"
                    autofocus
                    @click="resolveDiscardChanges(false)"
                >
                    Keep editing</button
                ><button
                    type="button"
                    class="discard-action"
                    @click="resolveDiscardChanges(true)"
                >
                    Discard changes
                </button>
            </div>
        </section></Modal
    >
</template>
<style scoped>
.discard-panel {
    background: #18181b;
    color: #f4f4f5;
    padding: 24px;
}
.discard-panel h2 {
    font-size: 20px;
    font-weight: 650;
}
.discard-panel p {
    font-size: 13px;
    color: #b8b8bf;
    line-height: 1.8;
    margin: 12px 0 22px;
}
.discard-panel > div {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.discard-panel button {
    flex: 1;
    min-height: 44px;
    padding: 10px 12px;
    border: 1px solid #ffffff26;
    border-radius: 8px;
    font-size: 12px;
    white-space: nowrap;
}
.discard-panel .discard-action {
    color: #fda4af;
    border-color: #fb71854d;
}
.discard-panel button:focus-visible {
    outline: 2px solid #dfc99f;
    outline-offset: 3px;
}
</style>
