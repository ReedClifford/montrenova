import { onMounted, onBeforeUnmount } from "vue";
import { router } from "@inertiajs/vue3";
import { requestDiscardChanges } from "@/Utils/discardChanges";

export function useUnsavedChanges({
    dirty,
    busy = () => false,
    beforeUnload = true,
}) {
    const confirmDiscard = async () =>
        !dirty() || (await requestDiscardChanges());
    const onUnload = (event) => {
        if (!dirty() || busy()) return;
        event.preventDefault();
        event.returnValue = "";
    };
    let removeNavigationGuard;
    let allowNextNavigation = false;
    onMounted(() => {
        if (beforeUnload) window.addEventListener("beforeunload", onUnload);
        removeNavigationGuard = router.on("before", (event) => {
            const visit = event.detail.visit;
            if (allowNextNavigation) {
                allowNextNavigation = false;
                return;
            }
            if (
                visit.method.toLowerCase() !== "get" ||
                visit.preserveState === true
            )
                return;
            if (!dirty()) return;
            event.preventDefault();
            if (busy()) return;
            confirmDiscard().then((discard) => {
                if (!discard) return;
                allowNextNavigation = true;
                router.visit(visit.url, {
                    method: "get",
                    data: visit.data,
                    replace: visit.replace,
                    preserveState: visit.preserveState,
                    preserveScroll: visit.preserveScroll,
                    only: visit.only,
                    except: visit.except,
                });
            });
        });
    });
    onBeforeUnmount(() => {
        if (beforeUnload) window.removeEventListener("beforeunload", onUnload);
        removeNavigationGuard?.();
    });
    return { confirmDiscard };
}
