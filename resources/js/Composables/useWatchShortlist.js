import { computed, ref } from "vue";

const key = "montre-nova-watch-shortlist";
const ids = ref([]);
let loaded = false;

export function useWatchShortlist() {
    if (!loaded && typeof window !== "undefined") {
        loaded = true;
        try {
            const saved = JSON.parse(window.localStorage.getItem(key) || "[]");
            if (Array.isArray(saved)) {
                ids.value = [...new Set(saved.filter(
                    (id) => typeof id === "string" && /^(?:preorder-)?[1-9][0-9]{0,17}$/.test(id),
                ))].slice(0, 3);
            }
        } catch {
            // The shortlist remains usable in memory when storage is unavailable.
        }
    }

    function save(next) {
        ids.value = next;
        try {
            window.localStorage.setItem(key, JSON.stringify(next));
        } catch {
            // Keep the current tab's selection.
        }
    }

    const has = (id) => ids.value.includes(String(id));
    const remove = (id) => save(ids.value.filter((item) => item !== String(id)));
    function toggle(id) {
        if (has(id)) remove(id);
        else if (ids.value.length < 3) save([...ids.value, String(id)]);
    }

    return {
        count: computed(() => ids.value.length),
        compareUrl: computed(() => route("public.watches.compare", { items: ids.value })),
        has,
        remove,
        toggle,
        clear: () => save([]),
    };
}
