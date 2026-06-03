<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import MontreLogo from "@/Components/MontreLogo.vue";

const props = defineProps({
    title: {
        type: String,
        default: "Dashboard",
    },
});

const showingMobileSidebar = ref(false);

const page = usePage();

const user = computed(() => page.props.auth?.user);

const sidebarLinks = [
    {
        label: "Dashboard",
        href: "/dashboard",
        icon: "M3.75 13.5l10.5-10.5 10.5 10.5M6.75 10.5v9.75h5.25v-6h4.5v6h5.25V10.5",
        active: page.url.startsWith("/dashboard"),
    },
    {
        label: "Watch Stocks",
        href: route("admin.watches.index"),
        icon: "M12 6v12m6-6H6",
        active: page.url.startsWith("/admin/watches"),
    },
    // {
    //     label: "HD Photo Manager",
    //     href: "#",
    //     icon: "M3.75 6.75A2.25 2.25 0 016 4.5h12a2.25 2.25 0 012.25 2.25v10.5A2.25 2.25 0 0118 19.5H6a2.25 2.25 0 01-2.25-2.25V6.75z M8.25 10.5a1.5 1.5 0 100-3 1.5 1.5 0 000 3z M3.75 16.5l4.5-4.5 3 3 3.75-3.75 5.25 5.25",
    //     active: false,
    // },
    // {
    //     label: "Reservations",
    //     href: "#",
    //     icon: "M8.25 6.75h12M8.25 12h12M8.25 17.25h12M3.75 6.75h.008v.008H3.75V6.75zm0 5.25h.008v.008H3.75V12zm0 5.25h.008v.008H3.75v-.008z",
    //     active: false,
    // },
    {
        label: "Sales",
        href: route("admin.sales.index"),
        icon: "M8.25 6.75h12M8.25 12h12M8.25 17.25h12M3.75 6.75h.008v.008H3.75V6.75zm0 5.25h.008v.008H3.75V12zm0 5.25h.008v.008H3.75v-.008z",

        active: page.url.startsWith("/admin/sales"),
    },

    {
        label: "Expenses",
        href: route("admin.expenses.index"),
        icon: "M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.768 0-1.536-.219-2.121-.659-1.172-.879-1.172-2.303 0-3.182 1.171-.879 3.07-.879 4.242 0l.879.659M21 12a9 9 0 11-18 0 9 9 0 0118 0z",
        active: page.url.startsWith("/admin/expenses"),
    },
    // {
    //     label: "Website Settings",
    //     href: "#",
    //     icon: "M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.34.78.725.97.385.19.838.166 1.203-.062l.774-.484a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.484.774c-.228.365-.252.818-.062 1.203.19.385.546.655.97.725l.894.149c.542.09.94.56.94 1.11v1.093c0 .55-.398 1.02-.94 1.11l-.894.149c-.424.07-.78.34-.97.725-.19.385-.166.838.062 1.203l.484.774c.32.448.27 1.061-.12 1.45l-.773.774a1.125 1.125 0 01-1.45.12l-.774-.484c-.365-.228-.818-.252-1.203-.062-.385.19-.655.546-.725.97l-.149.894c-.09.542-.56.94-1.11.94h-1.093c-.55 0-1.02-.398-1.11-.94l-.149-.894a1.125 1.125 0 00-.725-.97 1.125 1.125 0 00-1.203.062l-.774.484a1.125 1.125 0 01-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.484-.774c.228-.365.252-.818.062-1.203a1.125 1.125 0 00-.97-.725l-.894-.149A1.125 1.125 0 013 12.547v-1.093c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.78-.34.97-.725.19-.385.166-.838-.062-1.203l-.484-.774a1.125 1.125 0 01.12-1.45l.773-.774a1.125 1.125 0 011.45-.12l.774.484c.365.228.818.252 1.203.062.385-.19.655-.546.725-.97l.149-.894z M15 12a3 3 0 11-6 0 3 3 0 016 0z",
    //     active: false,
    // },
];

const initials = computed(() => {
    if (!user.value?.name) return "MN";

    return user.value.name
        .split(" ")
        .map((part) => part[0])
        .join("")
        .slice(0, 2)
        .toUpperCase();
});
</script>

<template>
    <div class="min-h-screen bg-[#050505] text-white">
        <!-- DESKTOP SIDEBAR -->
        <aside
            class="fixed inset-y-0 left-0 z-40 hidden w-72 border-r border-white/10 bg-[#080808] lg:block"
        >
            <div class="flex h-full flex-col">
                <div class="border-b border-white/10 px-6 py-6">
                    <MontreLogo />
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-6">
                    <a
                        v-for="item in sidebarLinks"
                        :key="item.label"
                        :href="item.href"
                        class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition"
                        :class="
                            item.active
                                ? 'bg-white text-black'
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
                    </a>
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
                                    {{ user?.name }}
                                </p>
                                <p class="truncate text-xs text-zinc-500">
                                    {{ user?.email }}
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
        <div v-if="showingMobileSidebar" class="fixed inset-0 z-50 lg:hidden">
            <div
                class="absolute inset-0 bg-black/70 backdrop-blur-sm"
                @click="showingMobileSidebar = false"
            ></div>

            <aside
                class="relative h-full w-80 max-w-[85vw] border-r border-white/10 bg-[#080808]"
            >
                <div
                    class="flex items-center justify-between border-b border-white/10 px-5 py-5"
                >
                    <MontreLogo />

                    <button
                        type="button"
                        class="rounded-xl border border-white/10 p-2 text-zinc-400 hover:text-white"
                        @click="showingMobileSidebar = false"
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

                <nav class="space-y-1 px-4 py-5">
                    <a
                        v-for="item in sidebarLinks"
                        :key="item.label"
                        :href="item.href"
                        class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition"
                        :class="
                            item.active
                                ? 'bg-white text-black'
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
                    </a>
                </nav>
            </aside>
        </div>

        <!-- MAIN AREA -->
        <div class="lg:pl-72">
            <!-- TOPBAR -->
            <header
                class="sticky top-0 z-30 border-b border-white/10 bg-[#050505]/85 backdrop-blur-xl"
            >
                <div
                    class="flex h-20 items-center justify-between px-5 sm:px-8"
                >
                    <div class="flex items-center gap-4">
                        <button
                            type="button"
                            class="rounded-2xl border border-white/10 p-3 text-zinc-400 transition hover:border-white/30 hover:text-white lg:hidden"
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

                        <div>
                            <p
                                class="text-xs uppercase tracking-[0.28em] text-zinc-600"
                            >
                                Montre Nova Admin
                            </p>
                            <h1
                                class="mt-1 text-xl font-semibold tracking-tight text-white sm:text-2xl"
                            >
                                {{ title }}
                            </h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <Link
                            href="/"
                            class="hidden rounded-2xl border border-white/10 px-4 py-2 text-sm font-medium text-zinc-300 transition hover:border-white/30 hover:text-white sm:inline-flex"
                        >
                            View Website
                        </Link>

                        <Link
                            :href="route('admin.watches.create')"
                            class="rounded-2xl bg-white px-4 py-2 text-sm font-semibold text-black transition hover:bg-zinc-200"
                        >
                            Add Watch
                        </Link>
                    </div>
                </div>
            </header>

            <!-- PAGE CONTENT -->
            <main class="px-5 py-8 sm:px-8">
                <slot />
            </main>
        </div>
    </div>
</template>
