<script setup>
import UiIcon from "@/Components/UiIcon.vue";
import {
    collectionHref,
    requestCollectionRestore,
} from "@/Utils/collectionMemory";
import { Link, usePage } from "@inertiajs/vue3";
import { computed, onMounted, onBeforeUnmount, ref, watch } from "vue";
defineProps({ position: { type: String, default: "fixed" } });
const page = usePage();
const open = ref(false);
const root = ref(null);
const toggle = ref(null);
const links = computed(() => [
    {
        label: "Collection",
        href: collectionHref(),
        active: ["Welcome", "Public/WatchShow", "Public/PreorderShow"].includes(
            page.component,
        ),
    },
    {
        label: "Catalog",
        href: route("public.catalog"),
        active: page.component === "Public/Catalog",
    },
    {
        label: "Sold archive",
        href: route("public.sold-watches.index"),
        active: page.component === "Public/SoldGallery",
    },
    {
        label: "Warranty",
        href: route("public.warranty-check.index"),
        active: page.component === "Public/WarrantyCheck",
    },
]);
function close(event) {
    if (event.type === "keydown") {
        if (event.key !== "Escape" || !open.value) return;
        open.value = false;
        toggle.value?.focus();
    } else if (!root.value?.contains(event.target)) open.value = false;
}
watch(
    () => page.url,
    () => (open.value = false),
);
onMounted(() => {
    document.addEventListener("pointerdown", close);
    document.addEventListener("keydown", close);
});
onBeforeUnmount(() => {
    document.removeEventListener("pointerdown", close);
    document.removeEventListener("keydown", close);
});
</script>
<template>
    <nav
        ref="root"
        class="public-header"
        :class="position"
        aria-label="Primary navigation"
    >
        <div class="public-header-inner">
            <Link
                :href="route('welcome')"
                class="public-brand"
                aria-label="Montre Nova home"
                ><img
                    src="/images/montre-nova-logo.jpg"
                    alt=""
                    width="40"
                    height="40"
                /><span
                    >Montre Nova<small
                        >Your next signature timepiece</small
                    ></span
                ></Link
            >
            <div class="public-desktop-links">
                <Link
                    v-for="link in links"
                    :key="link.label"
                    :href="link.href"
                    @click="
                        link.label === 'Collection' &&
                        requestCollectionRestore()
                    "
                    :class="{ active: link.active }"
                    :aria-current="link.active ? 'page' : undefined"
                    >{{ link.label }}</Link
                >
            </div>
            <button
                ref="toggle"
                class="public-menu-toggle"
                type="button"
                :aria-expanded="open"
                aria-label="Browse pages"
                @click="open = !open"
            >
                <span>{{ open ? "Close" : "Menu" }}</span
                ><svg
                    viewBox="0 0 24 24"
                    width="18"
                    height="18"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                    aria-hidden="true"
                >
                    <path
                        :d="
                            open
                                ? 'M6 6l12 12M6 18L18 6'
                                : 'M4 7h16M4 12h16M4 17h16'
                        "
                    />
                </svg>
            </button>
        </div>
        <div v-if="open" class="public-mobile-links">
            <Link
                v-for="link in links"
                :key="link.label"
                :href="link.href"
                :class="{ active: link.active }"
                :aria-current="link.active ? 'page' : undefined"
                @click="
                    open = false;
                    link.label === 'Collection' && requestCollectionRestore();
                "
                >{{ link.label }}<span aria-hidden="true"><UiIcon name="chevron-right" /></span></Link
            >
        </div>
    </nav>
</template>
<style scoped>
.public-header {
    z-index: 90;
    border-bottom: 1px solid #ffffff12;
    background: rgba(9, 9, 11, 0.95);
    color: #e4e4e7;
    backdrop-filter: blur(20px);
    width: 100%;
}
.public-header.fixed {
    position: fixed;
    inset: 0 0 auto;
}
.public-header.sticky {
    position: sticky;
    top: 0;
}
.public-header.static {
    position: relative;
    border: 1px solid #ffffff12;
    border-radius: 14px;
}
.public-header-inner {
    height: 76px;
    max-width: 1280px;
    padding: 0 28px;
    margin: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
}
.public-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    text-decoration: none;
    color: #fafafa;
}
.public-brand img {
    width: 40px;
    height: 40px;
    object-fit: contain;
    border: 1px solid #ffffff15;
    border-radius: 10px;
}
.public-brand > span {
    text-transform: uppercase;
    font-size: 14px;
    font-weight: 600;
    letter-spacing: 0.24em;
    white-space: nowrap;
}
.public-brand small {
    display: block;
    letter-spacing: 0.13em;
    font-size: 7px;
    color: #8d8d98;
    margin-top: 6px;
}
.public-desktop-links {
    display: flex;
    align-items: center;
    gap: 6px;
}
.public-desktop-links a {
    color: #a1a1aa;
    font-size: 12px;
    padding: 12px 16px;
    border-radius: 8px;
    text-decoration: none;
    transition:
        color 0.15s,
        background 0.15s;
}
.public-desktop-links a:hover,
.public-desktop-links a.active {
    color: #e4d1ac;
    background: #e4d1ac0a;
}
.public-menu-toggle {
    display: none;
    align-items: center;
    gap: 10px;
    min-height: 44px;
    padding: 8px 12px;
    border: 1px solid #ffffff20;
    border-radius: 9px;
    font-size: 12px;
}
.public-mobile-links {
    position: absolute;
    inset: 100% 0 auto;
    background: #101013;
    border-bottom: 1px solid #ffffff20;
    padding: 12px 20px 20px;
    box-shadow: 0 24px 40px #0008;
}
.public-mobile-links a {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 12px;
    border-radius: 8px;
    font-size: 14px;
    color: #a1a1aa;
    text-decoration: none;
}
.public-mobile-links a.active {
    background: #e4d1ac0c;
    color: #e4d1ac;
}
.public-header a:focus-visible,
.public-header button:focus-visible {
    outline: 2px solid #e4d1ac;
    outline-offset: 3px;
}
@media (min-width: 761px) {
    .public-mobile-links {
        display: none;
    }
}
@media (max-width: 760px) {
    .public-header-inner {
        height: 68px;
        padding: 0 16px;
        gap: 12px;
    }
    .public-brand > span {
        font-size: 11px;
        letter-spacing: 0.19em;
    }
    .public-brand small {
        font-size: 6px;
        letter-spacing: 0.07em;
    }
    .public-brand img {
        width: 34px;
        height: 34px;
    }
    .public-brand {
        gap: 9px;
    }
    .public-desktop-links {
        display: none;
    }
    .public-menu-toggle {
        display: flex;
    }
}
@media (prefers-reduced-motion: reduce) {
    .public-header * {
        transition: none;
    }
}
</style>
