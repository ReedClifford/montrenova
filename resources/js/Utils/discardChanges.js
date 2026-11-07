import { ref } from "vue";
export const discardDialogOpen = ref(false);
let pending;
let resolvePending;
export function requestDiscardChanges() {
    if (pending) return pending;
    discardDialogOpen.value = true;
    pending = new Promise((resolve) => {
        resolvePending = resolve;
    });
    return pending;
}
export function resolveDiscardChanges(discard) {
    discardDialogOpen.value = false;
    resolvePending?.(discard);
    pending = null;
    resolvePending = null;
}
