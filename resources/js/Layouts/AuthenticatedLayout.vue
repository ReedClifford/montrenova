<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, watch, ref } from "vue";

const props = defineProps({
    title: { type: String, default: "Dashboard" },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const showingMobileSidebar = ref(false);

const navigation = computed(() => [
    {
        label: "Dashboard",
        shortLabel: "Home",
        href: "/dashboard",
        path: "/dashboard",
        icon: "home",
    },
    {
        label: "Watch Stocks",
        shortLabel: "Stocks",
        href: route("admin.watches.index"),
        path: "/admin/watches",
        icon: "watch",
    },
    {
        label: "Catalog",
        shortLabel: "Catalog",
        href: route("admin.catalog.index"),
        path: "/admin/catalog",
        icon: "catalog",
    },
    {
        label: "Sales",
        shortLabel: "Sales",
        href: route("admin.sales.index"),
        path: "/admin/sales",
        icon: "sales",
    },
    {
        label: "Expenses",
        shortLabel: "Expenses",
        href: route("admin.expenses.index"),
        path: "/admin/expenses",
        icon: "expenses",
    },
]);

// Inertia page.url can include query parameters. Match the route path only.
const currentPath = computed(
    () =>
        String(page.url || "")
            .split("?")[0]
            .replace(/\/$/, "") || "/",
);
const isActive = (item) =>
    currentPath.value === item.path ||
    currentPath.value.startsWith(`${item.path}/`);
const currentPageTitle = computed(
    () => props.title || navigation.value.find(isActive)?.label || "Dashboard",
);
const initials = computed(() => {
    const parts = String(user.value?.name || "Montre Nova")
        .trim()
        .split(/\s+/)
        .filter(Boolean);
    return (
        parts.length > 1
            ? `${parts[0][0]}${parts[parts.length - 1][0]}`
            : (parts[0] || "MN").slice(0, 2)
    ).toUpperCase();
});

const closeMobileSidebar = () => {
    showingMobileSidebar.value = false;
};
const toggleMobileSidebar = () => {
    showingMobileSidebar.value = !showingMobileSidebar.value;
};
const handleKeydown = (event) => {
    if (event.key === "Escape" && showingMobileSidebar.value)
        closeMobileSidebar();
};

let previousOverflow = "";
watch(showingMobileSidebar, (open) => {
    if (typeof document === "undefined") return;
    if (open) {
        previousOverflow = document.body.style.overflow;
        document.body.style.overflow = "hidden";
        document.addEventListener("keydown", handleKeydown);
    } else {
        document.body.style.overflow = previousOverflow;
        document.removeEventListener("keydown", handleKeydown);
    }
});
watch(() => page.url, closeMobileSidebar);
onBeforeUnmount(() => {
    if (typeof document !== "undefined") {
        if (showingMobileSidebar.value)
            document.body.style.overflow = previousOverflow;
        document.removeEventListener("keydown", handleKeydown);
    }
});
</script>

<template>
    <div class="mn-shell min-h-screen bg-[#070709] text-zinc-100 antialiased">
        <!-- DESKTOP SIDEBAR -->
        <aside
            class="mn-sidebar fixed inset-y-0 left-0 z-40 hidden w-[260px] flex-col border-r border-white/[0.08] bg-[#0c0c0f] lg:flex"
        >
            <div
                class="flex min-h-[86px] items-center border-b border-white/[0.07] px-6"
            >
                <Link
                    href="/dashboard"
                    class="min-w-0"
                    aria-label="Montre Nova dashboard"
                >
                    <div
                        class="mn-brand text-[13px] font-bold uppercase tracking-[.23em] text-white"
                    >
                        Montre Nova
                    </div>
                    <div
                        class="mt-1 text-[10px] uppercase tracking-[.2em] text-zinc-500"
                    >
                        Administration
                    </div>
                </Link>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-3 py-6 mn-scrollbar">
                <p
                    class="mb-3 px-3 text-[10px] font-semibold uppercase tracking-[.2em] text-zinc-600"
                >
                    Workspace
                </p>
                <nav class="space-y-1" aria-label="Main navigation">
                    <Link
                        v-for="item in navigation"
                        :key="item.label"
                        :href="item.href"
                        class="mn-sidebar-link group flex min-h-12 items-center gap-3 rounded-xl px-3.5 py-3 text-[13px] font-medium transition-colors"
                        :class="
                            isActive(item)
                                ? 'border border-white/10 bg-white/[0.10] text-white'
                                : 'border border-transparent text-zinc-400 hover:bg-white/[0.05] hover:text-white'
                        "
                        :aria-current="isActive(item) ? 'page' : undefined"
                    >
                        <span
                            class="mn-nav-icon flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                            :class="
                                isActive(item)
                                    ? 'bg-white text-black'
                                    : 'bg-white/[0.04] text-zinc-400 group-hover:text-white'
                            "
                        >
                            <svg
                                v-if="item.icon === 'home'"
                                class="mn-icon"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path
                                    d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"
                                />
                            </svg>
                            <svg
                                v-else-if="item.icon === 'watch'"
                                class="mn-icon"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <rect
                                    x="6.5"
                                    y="6.5"
                                    width="11"
                                    height="11"
                                    rx="5.5"
                                />
                                <path
                                    d="M9 6.5 10 2h4l1 4.5M9 17.5 10 22h4l1-4.5M12 9v3l2 1"
                                />
                            </svg>
                            <svg
                                v-else-if="item.icon === 'catalog'"
                                class="mn-icon"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <rect
                                    x="3"
                                    y="3"
                                    width="8"
                                    height="8"
                                    rx="1.5"
                                />
                                <rect
                                    x="13"
                                    y="3"
                                    width="8"
                                    height="8"
                                    rx="1.5"
                                />
                                <rect
                                    x="3"
                                    y="13"
                                    width="8"
                                    height="8"
                                    rx="1.5"
                                />
                                <rect
                                    x="13"
                                    y="13"
                                    width="8"
                                    height="8"
                                    rx="1.5"
                                />
                            </svg>
                            <svg
                                v-else-if="item.icon === 'sales'"
                                class="mn-icon"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M3 19V5M3 19h18M7 14l4-4 3 2 6-7" />
                                <path d="M16 5h4v4" />
                            </svg>
                            <svg
                                v-else
                                class="mn-icon"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <rect
                                    x="4"
                                    y="3"
                                    width="16"
                                    height="18"
                                    rx="2"
                                />
                                <path d="M8 8h8M8 12h8M8 16h5" />
                            </svg>
                        </span>
                        <span class="truncate">{{ item.label }}</span>
                        <span
                            v-if="isActive(item)"
                            class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-white"
                            aria-hidden="true"
                        ></span>
                    </Link>
                </nav>
            </div>

            <div class="border-t border-white/[0.07] p-4">
                <div
                    class="rounded-xl border border-white/[0.08] bg-white/[0.025] p-3.5"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-xs font-bold text-zinc-900"
                        >
                            {{ initials }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-xs font-semibold text-zinc-100"
                            >
                                {{ user?.name || "Montre Nova" }}
                            </p>
                            <p
                                class="mt-0.5 truncate text-[11px] text-zinc-500"
                            >
                                {{ user?.email || "Administrator" }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <Link
                            :href="route('profile.edit')"
                            class="mn-account-action border border-white/10 text-zinc-300 hover:bg-white/[0.06]"
                            >Profile</Link
                        >
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="mn-account-action border border-white bg-white text-black hover:bg-zinc-200"
                            >Log out</Link
                        >
                    </div>
                </div>
            </div>
        </aside>

        <!-- MOBILE/TABLET SIDEBAR -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showingMobileSidebar"
                    class="mn-drawer fixed inset-0 z-[200] lg:hidden"
                >
                    <button
                        type="button"
                        class="absolute inset-0 w-full bg-black/75 backdrop-blur-[3px]"
                        aria-label="Close navigation"
                        @click="closeMobileSidebar"
                    ></button>
                    <aside
                        class="relative flex h-[100dvh] w-[min(86vw,330px)] flex-col border-r border-white/10 bg-[#101013] shadow-2xl"
                        role="dialog"
                        aria-modal="true"
                        aria-label="Navigation menu"
                    >
                        <div
                            class="flex shrink-0 items-center justify-between border-b border-white/[0.08] px-5 py-4"
                        >
                            <div class="min-w-0">
                                <p
                                    class="text-[13px] font-bold uppercase tracking-[.18em] text-white"
                                >
                                    Montre Nova
                                </p>
                                <p
                                    class="mt-1 text-[10px] uppercase tracking-[.18em] text-zinc-500"
                                >
                                    Admin workspace
                                </p>
                            </div>
                            <button
                                type="button"
                                aria-label="Close menu"
                                class="mn-icon-button"
                                @click="closeMobileSidebar"
                            >
                                <span
                                    aria-hidden="true"
                                    class="text-2xl leading-none"
                                    >×</span
                                >
                            </button>
                        </div>
                        <div
                            class="min-h-0 flex-1 overflow-y-auto px-3 py-4 mn-scrollbar"
                        >
                            <p
                                class="mb-3 px-3 text-[10px] font-semibold uppercase tracking-[.2em] text-zinc-600"
                            >
                                Navigation
                            </p>
                            <nav
                                class="space-y-1"
                                aria-label="Mobile navigation"
                            >
                                <Link
                                    v-for="item in navigation"
                                    :key="item.label"
                                    :href="item.href"
                                    @click="closeMobileSidebar"
                                    class="flex min-h-[52px] items-center justify-between rounded-xl px-4 text-sm font-medium transition-colors"
                                    :class="
                                        isActive(item)
                                            ? 'bg-white text-black'
                                            : 'text-zinc-300 hover:bg-white/[0.07]'
                                    "
                                    :aria-current="
                                        isActive(item) ? 'page' : undefined
                                    "
                                >
                                    <span>{{ item.label }}</span
                                    ><span
                                        aria-hidden="true"
                                        class="text-lg opacity-50"
                                        >›</span
                                    >
                                </Link>
                            </nav>
                        </div>
                        <div
                            class="shrink-0 border-t border-white/[0.08] px-4 pt-4 mn-safe-bottom"
                        >
                            <div class="mb-4 flex items-center gap-3">
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-xs font-bold text-black"
                                >
                                    {{ initials }}
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold">
                                        {{ user?.name || "Montre Nova" }}
                                    </p>
                                    <p class="truncate text-xs text-zinc-500">
                                        {{ user?.email || "Administrator" }}
                                    </p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <Link
                                    :href="route('profile.edit')"
                                    class="mn-account-action border border-white/10 text-zinc-300"
                                    @click="closeMobileSidebar"
                                    >Profile</Link
                                >
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="mn-account-action border border-white bg-white text-black"
                                    >Log out</Link
                                >
                            </div>
                        </div>
                    </aside>
                </div>
            </Transition>
        </Teleport>

        <!-- CONTENT -->
        <div class="min-w-0 lg:pl-[260px]">
            <header
                class="sticky top-0 z-30 border-b border-white/[0.08] bg-[#0a0a0d]/95 backdrop-blur-xl"
            >
                <div
                    class="mx-auto flex h-[62px] max-w-[1720px] items-center justify-between gap-3 px-4 sm:h-[74px] sm:px-6 xl:px-9"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <button
                            type="button"
                            class="mn-icon-button shrink-0 lg:hidden"
                            aria-label="Open navigation"
                            :aria-expanded="showingMobileSidebar"
                            @click="toggleMobileSidebar"
                        >
                            <span class="mn-menu-lines" aria-hidden="true"
                                ><i></i><i></i><i></i
                            ></span>
                        </button>
                        <div class="min-w-0">
                            <p
                                class="hidden text-[10px] font-semibold uppercase tracking-[.2em] text-zinc-600 sm:block"
                            >
                                Montre Nova / Workspace
                            </p>
                            <h1
                                class="truncate text-base font-semibold tracking-[-.02em] text-zinc-100 sm:mt-1 sm:text-xl"
                            >
                                {{ currentPageTitle }}
                            </h1>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <Link
                            :href="route('admin.watches.create')"
                            class="mn-add-button"
                            aria-label="Add Watch"
                            ><span
                                class="text-xl leading-none"
                                aria-hidden="true"
                                >+</span
                            ><span class="hidden sm:inline"
                                >Add watch</span
                            ></Link
                        >
                        <button
                            type="button"
                            class="mn-header-avatar hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/[0.04] text-xs font-semibold text-zinc-200 sm:flex lg:hidden"
                            aria-label="Open account menu"
                            @click="toggleMobileSidebar"
                        >
                            {{ initials }}
                        </button>
                    </div>
                </div>
            </header>

            <main
                class="mn-main mx-auto min-w-0 max-w-[1720px] px-3 pb-28 pt-4 sm:px-6 sm:pb-32 sm:pt-7 lg:px-7 lg:pb-10 xl:px-9"
                role="main"
            >
                <slot />
            </main>
        </div>

        <!-- MOBILE/TABLET BOTTOM NAVIGATION -->
        <nav
            class="mn-bottom-nav fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-[#0b0b0e]/95 px-1.5 pt-2 backdrop-blur-xl lg:hidden"
            aria-label="Quick navigation"
        >
            <div class="mx-auto grid max-w-xl grid-cols-5 gap-1">
                <Link
                    v-for="item in navigation"
                    :key="item.label"
                    :href="item.href"
                    class="mn-bottom-link flex min-w-0 flex-col items-center justify-center gap-1 rounded-xl px-1 py-2 text-center transition-colors"
                    :class="
                        isActive(item)
                            ? 'bg-white/[0.10] text-white'
                            : 'text-zinc-500 hover:bg-white/[0.04] hover:text-zinc-200'
                    "
                    :aria-current="isActive(item) ? 'page' : undefined"
                >
                    <span
                        class="mn-bottom-symbol"
                        :class="isActive(item) ? 'text-white' : 'text-zinc-500'"
                        aria-hidden="true"
                    >
                        <svg
                            v-if="item.icon === 'home'"
                            class="mn-icon"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"
                            />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'watch'"
                            class="mn-icon"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <rect
                                x="6.5"
                                y="6.5"
                                width="11"
                                height="11"
                                rx="5.5"
                            />
                            <path
                                d="M9 6.5 10 2h4l1 4.5M9 17.5 10 22h4l1-4.5M12 9v3l2 1"
                            />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'catalog'"
                            class="mn-icon"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <rect x="3" y="3" width="8" height="8" rx="1.5" />
                            <rect x="13" y="3" width="8" height="8" rx="1.5" />
                            <rect x="3" y="13" width="8" height="8" rx="1.5" />
                            <rect x="13" y="13" width="8" height="8" rx="1.5" />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'sales'"
                            class="mn-icon"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M3 19V5M3 19h18M7 14l4-4 3 2 6-7" />
                            <path d="M16 5h4v4" />
                        </svg>
                        <svg
                            v-else
                            class="mn-icon"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <rect x="4" y="3" width="16" height="18" rx="2" />
                            <path d="M8 8h8M8 12h8M8 16h5" />
                        </svg>
                    </span>
                    <span
                        class="max-w-full truncate text-[10px] font-semibold leading-tight"
                        >{{ item.shortLabel }}</span
                    >
                </Link>
            </div>
        </nav>
    </div>
</template>

<style scoped>
.mn-shell {
    color-scheme: dark;
}
.mn-brand {
    letter-spacing: 0.19em;
}
.mn-main {
    overflow-wrap: anywhere;
}
.mn-account-action {
    display: inline-flex;
    min-height: 40px;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    padding: 8px 10px;
    font-size: 12px;
    font-weight: 650;
    transition: background 0.16s ease;
}
.mn-icon-button {
    display: flex;
    height: 43px;
    width: 43px;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255, 255, 255, 0.11);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.035);
    color: #e4e4e7;
}
.mn-icon-button:hover {
    background: rgba(255, 255, 255, 0.09);
}
.mn-menu-lines {
    display: flex;
    width: 18px;
    flex-direction: column;
    gap: 4px;
}
.mn-menu-lines i {
    display: block;
    height: 1.6px;
    width: 100%;
    border-radius: 4px;
    background: currentColor;
}
.mn-add-button {
    display: inline-flex;
    height: 42px;
    min-width: 42px;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 11px;
    background: #fafafa;
    padding: 0 12px;
    font-size: 12px;
    font-weight: 700;
    color: #09090b;
    transition: background 0.16s ease;
}
.mn-add-button:hover {
    background: #e4e4e7;
}
.mn-bottom-link {
    min-height: 53px;
}
.mn-bottom-symbol {
    display: flex;
    height: 23px;
    width: 23px;
    align-items: center;
    justify-content: center;
}
/* A strict icon dimension protects against global SVG selectors from other admin pages. */
.mn-shell svg.mn-icon {
    display: block !important;
    width: 20px !important;
    height: 20px !important;
    max-width: 20px !important;
    max-height: 20px !important;
    min-width: 20px !important;
    min-height: 20px !important;
    flex: none !important;
}
.mn-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: #35353c transparent;
}
.mn-safe-bottom {
    padding-bottom: max(18px, env(safe-area-inset-bottom));
}
.mn-bottom-nav {
    padding-bottom: max(6px, env(safe-area-inset-bottom));
}
@media (max-width: 360px) {
    .mn-bottom-link {
        padding-inline: 0;
    }
    .mn-bottom-link span:last-child {
        font-size: 9px;
    }
}
@media (prefers-reduced-motion: reduce) {
    .mn-shell *,
    .mn-drawer * {
        transition-duration: 0.01ms !important;
    }
}
</style>
