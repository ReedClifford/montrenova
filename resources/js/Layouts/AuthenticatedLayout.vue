<script setup>
import MontreLogo from "@/Components/MontreLogo.vue";
import { Link, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
    title: {
        type: String,
        default: "Dashboard",
    },
});

const showingMobileSidebar = ref(false);

const page = usePage();

const user = computed(() => page.props.auth?.user);

const isActive = (path) => {
    return page.url.startsWith(path);
};

const sidebarLinks = computed(() => [
    {
        label: "Dashboard",
        shortLabel: "Home",
        href: "/dashboard",
        icon: "M3.75 13.5l10.5-10.5 10.5 10.5M6.75 10.5v9.75h5.25v-6h4.5v6h5.25V10.5",
        active: isActive("/dashboard"),
    },
    {
        label: "Watch Stocks",
        shortLabel: "Stocks",
        href: route("admin.watches.index"),
        icon: "M12 6v12m6-6H6",
        active: isActive("/admin/watches"),
    },
    {
        label: "Catalog",
        shortLabel: "Catalog",
        href: route("admin.catalog.index"),
        icon: "M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5",
        active: isActive("/admin/catalog"),
    },
    {
        label: "Sales",
        shortLabel: "Sales",
        href: route("admin.sales.index"),
        icon: "M8.25 6.75h12M8.25 12h12M8.25 17.25h12M3.75 6.75h.008v.008H3.75V6.75zm0 5.25h.008v.008H3.75V12zm0 5.25h.008v.008H3.75v-.008z",
        active: isActive("/admin/sales"),
    },
    {
        label: "Expenses",
        shortLabel: "Expenses",
        href: route("admin.expenses.index"),
        icon: "M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.768 0-1.536-.219-2.121-.659-1.172-.879-1.172-2.303 0-3.182 1.171-.879 3.07-.879 4.242 0l.879.659M21 12a9 9 0 11-18 0 9 9 0 0118 0z",
        active: isActive("/admin/expenses"),
    },
]);

const mobileBottomLinks = computed(() => sidebarLinks.value);

const initials = computed(() => {
    if (!user.value?.name) return "MN";

    return user.value.name
        .split(" ")
        .map((part) => part[0])
        .join("")
        .slice(0, 2)
        .toUpperCase();
});

const closeMobileSidebar = () => {
    showingMobileSidebar.value = false;
};
</script>

<template>
    <div class="min-h-screen bg-[#050505] text-white antialiased">
        <!-- DESKTOP SIDEBAR -->
        <aside
            class="fixed inset-y-0 left-0 z-40 hidden w-72 border-r border-white/10 bg-[#080808] lg:block"
        >
            <div class="flex h-full flex-col">
                <div class="border-b border-white/10 px-6 py-6">
                    <MontreLogo />
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-6">
                    <Link
                        v-for="item in sidebarLinks"
                        :key="item.label"
                        :href="item.href"
                        class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition"
                        :class="
                            item.active
                                ? 'bg-white text-black shadow-lg shadow-white/5'
                                : 'text-zinc-400 hover:bg-white/[0.05] hover:text-white'
                        "
                    >
                        <svg
                            class="h-5 w-5 shrink-0"
                            :class="
                                item.active
                                    ? 'text-black'
                                    : 'text-zinc-500 group-hover:text-white'
                            "
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.7"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                :d="item.icon"
                            />
                        </svg>

                        <span>{{ item.label }}</span>
                    </Link>
                </nav>

                <div class="border-t border-white/10 p-4">
                    <div
                        class="rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                    >
                        <p
                            class="text-xs uppercase tracking-[0.24em] text-zinc-600"
                        >
                            Admin Account
                        </p>

                        <div class="mt-4 flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-sm font-semibold text-black"
                            >
                                {{ initials }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-sm font-semibold text-white"
                                >
                                    {{ user?.name || "Montre Nova" }}
                                </p>

                                <p class="truncate text-xs text-zinc-500">
                                    {{ user?.email || "Admin" }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <Link
                                :href="route('profile.edit')"
                                class="rounded-xl border border-white/10 px-3 py-2 text-center text-xs font-medium text-zinc-300 transition hover:border-white/30 hover:text-white"
                            >
                                Profile
                            </Link>

                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="rounded-xl bg-white px-3 py-2 text-center text-xs font-semibold text-black transition hover:bg-zinc-200"
                            >
                                Logout
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- MOBILE SIDEBAR OVERLAY -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showingMobileSidebar"
                class="fixed inset-0 z-50 lg:hidden"
            >
                <div
                    class="absolute inset-0 bg-black/75 backdrop-blur-sm"
                    @click="closeMobileSidebar"
                ></div>

                <aside
                    class="relative flex h-full w-[86vw] max-w-sm flex-col border-r border-white/10 bg-[#080808] shadow-2xl shadow-black"
                >
                    <div
                        class="flex items-center justify-between border-b border-white/10 px-5 py-5"
                    >
                        <MontreLogo />

                        <button
                            type="button"
                            class="rounded-2xl border border-white/10 bg-white/[0.03] p-3 text-zinc-400 transition hover:border-white/30 hover:text-white"
                            @click="closeMobileSidebar"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.7"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <div class="border-b border-white/10 p-4">
                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white text-sm font-bold text-black"
                                >
                                    {{ initials }}
                                </div>

                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-semibold text-white"
                                    >
                                        {{ user?.name || "Montre Nova" }}
                                    </p>

                                    <p class="truncate text-xs text-zinc-500">
                                        {{ user?.email || "Admin" }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <Link
                                    :href="route('profile.edit')"
                                    class="rounded-xl border border-white/10 px-3 py-2 text-center text-xs font-medium text-zinc-300"
                                    @click="closeMobileSidebar"
                                >
                                    Profile
                                </Link>

                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="rounded-xl bg-white px-3 py-2 text-center text-xs font-semibold text-black"
                                >
                                    Logout
                                </Link>
                            </div>
                        </div>
                    </div>

                    <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-5">
                        <Link
                            v-for="item in sidebarLinks"
                            :key="item.label"
                            :href="item.href"
                            class="group flex items-center gap-3 rounded-2xl px-4 py-4 text-sm font-medium transition"
                            :class="
                                item.active
                                    ? 'bg-white text-black'
                                    : 'text-zinc-400 hover:bg-white/[0.05] hover:text-white'
                            "
                            @click="closeMobileSidebar"
                        >
                            <svg
                                class="h-5 w-5 shrink-0"
                                :class="
                                    item.active
                                        ? 'text-black'
                                        : 'text-zinc-500 group-hover:text-white'
                                "
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.7"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    :d="item.icon"
                                />
                            </svg>

                            <span>{{ item.label }}</span>
                        </Link>
                    </nav>
                </aside>
            </div>
        </Transition>

        <!-- MAIN AREA -->
        <div class="lg:pl-72">
            <!-- TOPBAR -->
            <header
                class="sticky top-0 z-30 border-b border-white/10 bg-[#050505]/90 backdrop-blur-xl"
            >
                <div
                    class="flex h-16 items-center justify-between gap-3 px-3 sm:h-20 sm:px-8"
                >
                    <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                        <button
                            type="button"
                            class="rounded-2xl border border-white/10 bg-white/[0.03] p-3 text-zinc-400 transition hover:border-white/30 hover:text-white lg:hidden"
                            @click="showingMobileSidebar = true"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.7"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"
                                />
                            </svg>
                        </button>

                        <div class="min-w-0">
                            <p
                                class="hidden text-xs uppercase tracking-[0.28em] text-zinc-600 sm:block"
                            >
                                Montre Nova Admin
                            </p>

                            <h1
                                class="truncate text-lg font-semibold tracking-tight text-white sm:mt-1 sm:text-2xl"
                            >
                                {{ title }}
                            </h1>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                        <Link
                            :href="route('admin.watches.create')"
                            class="hidden rounded-2xl bg-white px-4 py-2 text-sm font-semibold text-black transition hover:bg-zinc-200 sm:inline-flex"
                        >
                            Add Watch
                        </Link>

                        <Link
                            :href="route('admin.watches.create')"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-black transition hover:bg-zinc-200 sm:hidden"
                            aria-label="Add Watch"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 4.5v15m7.5-7.5h-15"
                                />
                            </svg>
                        </Link>
                    </div>
                </div>
            </header>

            <!-- PAGE CONTENT -->
            <main class="px-3 pb-28 pt-5 sm:px-8 sm:py-8 lg:pb-8">
                <slot />
            </main>
        </div>

        <!-- MOBILE BOTTOM NAV -->
        <nav
            class="safe-bottom fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-[#050505]/95 px-2 pt-2 backdrop-blur-xl lg:hidden"
        >
            <div class="grid grid-cols-5 gap-1">
                <Link
                    v-for="item in mobileBottomLinks"
                    :key="item.label"
                    :href="item.href"
                    class="flex min-w-0 flex-col items-center justify-center gap-1 rounded-2xl px-2 py-2 text-[10px] font-semibold transition"
                    :class="
                        item.primary
                            ? 'bg-white text-black'
                            : item.active
                              ? 'bg-white/[0.10] text-white'
                              : 'text-zinc-500 hover:bg-white/[0.05] hover:text-white'
                    "
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.8"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            :d="item.icon"
                        />
                    </svg>

                    <span class="truncate">
                        {{ item.shortLabel }}
                    </span>
                </Link>
            </div>
        </nav>
    </div>
</template>

<style scoped>
.safe-bottom {
    padding-bottom: max(0.75rem, env(safe-area-inset-bottom));
}
</style>
