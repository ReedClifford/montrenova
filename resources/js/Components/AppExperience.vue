<script setup>
import { router, usePage } from "@inertiajs/vue3";
import {
    computed,
    nextTick,
    onMounted,
    onBeforeUnmount,
    ref,
    watch,
} from "vue";
const page = usePage();
const offline = ref(false);
const toast = ref("");
const errorMessage = ref("");
const hiddenErrors = ref(false);
const light = computed(() => /^Investor2?\//.test(page.component));
const errors = computed(() =>
    [...new Set(Object.values(page.props.errors || {}).flat())].filter(
        (v) => typeof v === "string",
    ),
);
const publicPage = computed(
    () => page.component === "Welcome" || page.component.startsWith("Public/"),
);
const showTop = ref(false);
let timer;
const cleanups = [];
function updateConnection() {
    offline.value = !navigator.onLine;
}
function updateScroll() {
    showTop.value = window.scrollY > 700;
}
function goTop() {
    window.scrollTo({ top: 0, behavior: "smooth" });
}
function focusContent() {
    const target = document.querySelector('main,[role="main"],#collection');
    if (!target) return;
    target.setAttribute("tabindex", "-1");
    target.focus({ preventScroll: true });
    target.scrollIntoView({ block: "start" });
}
watch(
    () => page.props.errors,
    async () => {
        hiddenErrors.value = false;
        if (!errors.value.length) return;
        await nextTick();
        const activeDialog = document.querySelector("dialog[open]");
        const scope = activeDialog || document;
        const field = scope.querySelector(
            '[aria-invalid="true"],input:invalid,select:invalid,textarea:invalid',
        );
        field?.focus({ preventScroll: true });
        field?.scrollIntoView({ block: "center", behavior: "smooth" });
    },
);
watch(
    () => page.props.flash?.success,
    async (message) => {
        clearTimeout(timer);
        toast.value = "";
        if (!message || /^Investor2?\//.test(page.component)) return;
        await nextTick();
        if (
            [...document.querySelectorAll('[role="status"],.success')].some(
                (el) => el.textContent?.includes(message),
            )
        )
            return;
        toast.value = message;
        timer = setTimeout(() => (toast.value = ""), 6500);
    },
);
onMounted(() => {
    updateConnection();
    updateScroll();
    window.addEventListener("online", updateConnection);
    window.addEventListener("offline", updateConnection);
    window.addEventListener("scroll", updateScroll, { passive: true });
    cleanups.push(
        router.on("exception", () => {
            errorMessage.value =
                "The request could not finish. Check your connection and try again.";
        }),
    );
    cleanups.push(
        router.on("start", () => {
            errorMessage.value = "";
        }),
    );
});
onBeforeUnmount(() => {
    clearTimeout(timer);
    cleanups.forEach((fn) => fn());
    window.removeEventListener("online", updateConnection);
    window.removeEventListener("offline", updateConnection);
    window.removeEventListener("scroll", updateScroll);
});
</script>
<template>
    <a href="#main-content" class="ux-skip" @click.prevent="focusContent"
        >Skip to content</a
    >
    <div class="ux-feedback" :class="{ light }">
        <div v-if="offline" class="ux-message warning" role="status">
            <strong>You’re offline</strong>
            <p>Reconnect before submitting changes or payments.</p>
        </div>
        <div v-if="toast" class="ux-message success" role="status">
            <span>{{ toast }}</span
            ><button
                type="button"
                aria-label="Dismiss notification"
                @click="toast = ''"
            >
                ×
            </button>
        </div>
        <div v-if="errorMessage" class="ux-message warning" role="alert">
            <span>{{ errorMessage }}</span
            ><button
                type="button"
                aria-label="Dismiss connection message"
                @click="errorMessage = ''"
            >
                ×
            </button>
        </div>
        <div
            v-if="errors.length && !hiddenErrors"
            class="ux-message warning"
            role="alert"
        >
            <div>
                <strong
                    >Please check
                    {{
                        errors.length === 1 ? "this detail" : "these details"
                    }}</strong
                >
                <ul>
                    <li v-for="message in errors.slice(0, 4)" :key="message">
                        {{ message }}
                    </li>
                </ul>
                <p v-if="errors.length > 4">
                    {{ errors.length - 4 }} more fields need attention.
                </p>
            </div>
            <button
                type="button"
                aria-label="Dismiss validation summary"
                @click="hiddenErrors = true"
            >
                ×
            </button>
        </div>
    </div>
    <button
        v-if="publicPage && showTop"
        type="button"
        class="ux-back-top"
        aria-label="Back to top"
        @click="goTop"
    >
        ↑ <span>Top</span>
    </button>
</template>
<style scoped>
.ux-skip {
    position: fixed;
    top: 12px;
    left: 12px;
    z-index: 10000;
    background: #e4d1ac;
    color: #21190e;
    padding: 14px 20px;
    border-radius: 10px;
    font-size: 14px;
    transform: translateY(-180%);
    font-weight: 600;
}
.ux-skip:focus {
    transform: none;
}
.ux-feedback {
    position: fixed;
    top: 88px;
    right: 20px;
    z-index: 1200;
    width: min(390px, calc(100vw - 40px));
    pointer-events: none;
    display: grid;
    gap: 10px;
}
.ux-message {
    pointer-events: auto;
    display: flex;
    align-items: start;
    justify-content: space-between;
    gap: 12px;
    border: 1px solid #ffffff25;
    background: #17171bf5;
    box-shadow: 0 12px 38px #0005;
    color: #e4e4e7;
    border-radius: 14px;
    padding: 16px 18px;
    font-size: 13px;
    line-height: 1.65;
    max-height: 40dvh;
    overflow: auto;
    backdrop-filter: blur(16px);
}
.ux-message.warning {
    border-color: #e4b98455;
}
.ux-message strong {
    display: block;
    font-size: 13px;
    font-weight: 600;
}
.ux-message button {
    flex-shrink: 0;
    display: grid;
    place-items: center;
    min-width: 32px;
    min-height: 32px;
    border-radius: 6px;
    color: inherit;
    font-size: 22px;
    line-height: 1;
}
.ux-message ul {
    padding-left: 16px;
    margin-top: 6px;
    list-style: disc;
}
.ux-message p {
    font-size: 12px;
}
.ux-feedback.light .ux-message {
    background: #fffffff5;
    color: #334155;
    border-color: #e2e8f0;
    box-shadow: 0 12px 38px #0f172a12;
}
.ux-feedback.light .warning {
    border-color: #fbbf2470;
}
.ux-back-top {
    position: fixed;
    left: 16px;
    bottom: max(20px, env(safe-area-inset-bottom));
    z-index: 75;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 15px;
    border-radius: 30px;
    min-height: 44px;
    background: #141417eb;
    color: #e4d1ac;
    border: 1px solid #e4d1ac30;
    font-size: 14px;
    backdrop-filter: blur(10px);
}
.ux-back-top span {
    font-size: 11px;
}
.ux-feedback button:focus-visible,
.ux-back-top:focus-visible {
    outline: 2px solid #d5bd93;
    outline-offset: 3px;
}
@media (max-width: 640px) {
    .ux-feedback {
        top: 76px;
        right: 12px;
        width: calc(100vw - 24px);
    }
    .ux-message {
        padding: 13px 15px;
    }
    .ux-back-top {
        padding: 8px 12px;
    }
    .ux-back-top span {
        display: none;
    }
}
</style>
