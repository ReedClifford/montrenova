<script setup>
import MontreLogo from "@/Components/MontreLogo.vue";
import { Head, Link } from "@inertiajs/vue3";

defineProps({
    canLogin: {
        type: Boolean,
        default: true,
    },
    canRegister: {
        type: Boolean,
        default: false,
    },
    featuredWatch: {
        type: Object,
        default: null,
    },
    watches: {
        type: Array,
        default: () => [],
    },
});

const peso = (value) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 0,
    }).format(Number(value || 0));
};
</script>

<template>
    <Head title="Montre Nova | Curated Timepieces" />

    <div class="min-h-screen overflow-hidden bg-[#050505] text-white">
        <div class="pointer-events-none fixed inset-0">
            <div
                class="absolute left-[-14rem] top-[-14rem] h-[36rem] w-[36rem] rounded-full bg-white/[0.04] blur-3xl"
            ></div>
            <div
                class="absolute right-[-16rem] top-[18rem] h-[34rem] w-[34rem] rounded-full bg-zinc-700/10 blur-3xl"
            ></div>
            <div
                class="absolute bottom-[-16rem] left-[28%] h-[36rem] w-[36rem] rounded-full bg-white/[0.025] blur-3xl"
            ></div>
        </div>

        <!-- NAVBAR -->
        <header class="relative z-10 border-b border-white/10">
            <div
                class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5 lg:px-8"
            >
                <a href="/" class="flex items-center">
                    <MontreLogo />
                </a>

                <nav
                    class="hidden items-center gap-8 text-sm text-zinc-500 md:flex"
                >
                    <a href="#collection" class="transition hover:text-white">
                        Collection
                    </a>
                    <a href="#process" class="transition hover:text-white">
                        Process
                    </a>
                    <a href="#warranty" class="transition hover:text-white">
                        Warranty
                    </a>
                    <a href="#contact" class="transition hover:text-white">
                        Contact
                    </a>
                </nav>

                <div v-if="canLogin" class="flex items-center gap-3">
                    <Link
                        :href="route('login')"
                        class="rounded-full border border-white/10 px-5 py-2 text-sm font-medium text-zinc-300 transition hover:border-white/30 hover:text-white"
                    >
                        Admin Login
                    </Link>
                </div>
            </div>
        </header>

        <main class="relative z-10">
            <!-- HERO -->
            <section
                class="mx-auto grid max-w-7xl items-center gap-14 px-6 py-20 lg:grid-cols-[1fr_0.9fr] lg:px-8 lg:py-28"
            >
                <div>
                    <div
                        class="mb-6 inline-flex items-center gap-3 rounded-full border border-white/10 bg-white/[0.03] px-4 py-2"
                    >
                        <span class="h-2 w-2 rounded-full bg-white"></span>
                        <span
                            class="text-xs font-medium uppercase tracking-[0.28em] text-zinc-500"
                        >
                            Brand-new & Preowned Watches
                        </span>
                    </div>

                    <h2
                        class="max-w-4xl text-5xl font-semibold leading-[1.05] tracking-tight text-white sm:text-6xl lg:text-7xl"
                    >
                        Curated watches for your next signature timepiece.
                    </h2>

                    <p
                        class="mt-7 max-w-2xl text-base leading-8 text-zinc-400 sm:text-lg"
                    >
                        Explore brand new and pre-owned watches selected with
                        care, presented with actual HD photos, clear pricing,
                        and a smooth inquiry process.
                    </p>

                    <div class="mt-10 flex flex-col gap-4 sm:flex-row">
                        <a
                            href="#collection"
                            class="inline-flex items-center justify-center rounded-2xl bg-white px-7 py-4 text-sm font-semibold text-black transition hover:bg-zinc-200"
                        >
                            View Collection
                        </a>

                        <a
                            href="#contact"
                            class="inline-flex items-center justify-center rounded-2xl border border-white/10 bg-white/[0.03] px-7 py-4 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/[0.06]"
                        >
                            Message Us
                        </a>
                    </div>

                    <div class="mt-12 grid max-w-2xl grid-cols-3 gap-4">
                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.03] p-5"
                        >
                            <p class="text-2xl font-semibold text-white">HD</p>
                            <p
                                class="mt-1 text-xs uppercase tracking-widest text-zinc-600"
                            >
                                Actual Photos
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.03] p-5"
                        >
                            <p class="text-2xl font-semibold text-white">1Y</p>
                            <p
                                class="mt-1 text-xs uppercase tracking-widest text-zinc-600"
                            >
                                Service Warranty
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.03] p-5"
                        >
                            <p class="text-2xl font-semibold text-white">
                                {{ watches.length || 0 }}
                            </p>
                            <p
                                class="mt-1 text-xs uppercase tracking-widest text-zinc-600"
                            >
                                Available
                            </p>
                        </div>
                    </div>
                </div>

                <!-- FEATURED WATCH -->
                <div class="relative">
                    <div
                        class="absolute inset-0 rounded-[2.5rem] bg-white/[0.04] blur-3xl"
                    ></div>

                    <div
                        class="relative overflow-hidden rounded-[2.5rem] border border-white/10 bg-[#0B0B0D]/90 p-5 shadow-2xl shadow-black/50"
                    >
                        <div
                            class="aspect-[4/5] overflow-hidden rounded-[2rem] border border-white/10 bg-[#050505]"
                        >
                            <img
                                v-if="
                                    featuredWatch?.primary_hd_url ||
                                    featuredWatch?.primary_image_url
                                "
                                :src="
                                    featuredWatch.primary_hd_url ||
                                    featuredWatch.primary_image_url
                                "
                                :alt="`${featuredWatch.brand} ${featuredWatch.model_name}`"
                                class="h-full w-full object-cover"
                            />

                            <div
                                v-else
                                class="flex h-full items-center justify-center bg-[radial-gradient(circle_at_center,rgba(255,255,255,0.10),transparent_38%)]"
                            >
                                <img
                                    src="/images/montre-nova-logo.png"
                                    alt="Montre Nova"
                                    class="h-72 w-72 object-contain opacity-90"
                                />
                            </div>
                        </div>

                        <div class="p-3 pt-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p
                                        class="text-xs uppercase tracking-[0.28em] text-zinc-500"
                                    >
                                        Featured Drop
                                    </p>

                                    <h3
                                        class="mt-2 text-2xl font-semibold tracking-tight text-white"
                                    >
                                        <template v-if="featuredWatch">
                                            {{ featuredWatch.brand }}
                                            {{ featuredWatch.model_name }}
                                        </template>
                                        <template v-else>
                                            Premium Timepiece
                                        </template>
                                    </h3>

                                    <p class="mt-2 text-sm text-zinc-500">
                                        <template v-if="featuredWatch">
                                            Ref.
                                            {{
                                                featuredWatch.reference_number ||
                                                "No reference"
                                            }}
                                            |
                                            {{
                                                featuredWatch.condition ||
                                                "Condition available upon request"
                                            }}
                                        </template>
                                        <template v-else>
                                            Brand New | Complete Set | Available
                                        </template>
                                    </p>
                                </div>

                                <div
                                    class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-300"
                                >
                                    Available
                                </div>
                            </div>

                            <div
                                class="mt-6 flex items-center justify-between border-t border-white/10 pt-5"
                            >
                                <p class="text-sm text-zinc-500">
                                    Starting from
                                </p>

                                <p class="text-2xl font-semibold text-white">
                                    {{
                                        featuredWatch
                                            ? peso(featuredWatch.price)
                                            : "₱XX,XXX"
                                    }}
                                </p>
                            </div>

                            <Link
                                v-if="featuredWatch"
                                :href="
                                    route(
                                        'public.watches.show',
                                        featuredWatch.id,
                                    )
                                "
                                class="mt-5 inline-flex w-full items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200"
                            >
                                View Details
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- COLLECTION -->
            <section
                id="collection"
                class="mx-auto max-w-7xl px-6 py-20 lg:px-8"
            >
                <div
                    class="mb-10 flex flex-col justify-between gap-5 md:flex-row md:items-end"
                >
                    <div>
                        <p
                            class="text-xs font-medium uppercase tracking-[0.32em] text-zinc-500"
                        >
                            Collection
                        </p>

                        <h2
                            class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl"
                        >
                            Available Watches
                        </h2>

                        <p
                            class="mt-4 max-w-2xl text-sm leading-7 text-zinc-400"
                        >
                            Browse real-time available watch stocks from Montre
                            Nova.
                        </p>
                    </div>

                    <a
                        href="#contact"
                        class="inline-flex rounded-2xl border border-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/[0.04]"
                    >
                        Ask for Latest Stocks
                    </a>
                </div>

                <div
                    v-if="watches.length"
                    class="grid gap-5 md:grid-cols-2 xl:grid-cols-3"
                >
                    <div
                        v-for="watch in watches"
                        :key="watch.id"
                        class="group overflow-hidden rounded-[2rem] border border-white/10 bg-[#0B0B0D]/90 p-4 transition hover:border-white/30"
                    >
                        <div
                            class="aspect-square overflow-hidden rounded-[1.5rem] border border-white/10 bg-[#050505]"
                        >
                            <img
                                v-if="watch.primary_image_url"
                                :src="watch.primary_image_url"
                                :alt="`${watch.brand} ${watch.model_name}`"
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                            />

                            <div
                                v-else
                                class="flex h-full items-center justify-center bg-[radial-gradient(circle_at_center,rgba(255,255,255,0.08),transparent_40%)]"
                            >
                                <img
                                    src="/images/montre-nova-logo.png"
                                    alt="Montre Nova"
                                    class="h-40 w-40 object-contain opacity-70"
                                />
                            </div>
                        </div>

                        <div class="p-2 pt-5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p
                                        class="text-xs font-medium uppercase tracking-[0.26em] text-zinc-500"
                                    >
                                        {{ watch.brand }}
                                    </p>

                                    <h3
                                        class="mt-2 text-lg font-semibold text-white"
                                    >
                                        {{ watch.model_name }}
                                    </h3>

                                    <p class="mt-1 text-sm text-zinc-500">
                                        Ref.
                                        {{
                                            watch.reference_number ||
                                            "No reference"
                                        }}
                                    </p>
                                </div>

                                <span
                                    class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-300"
                                >
                                    Available
                                </span>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <span
                                    class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-xs text-zinc-400"
                                >
                                    {{
                                        watch.condition ||
                                        "Condition upon request"
                                    }}
                                </span>

                                <span
                                    v-if="watch.category"
                                    class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-xs text-zinc-400"
                                >
                                    {{ watch.category }}
                                </span>
                            </div>

                            <div
                                class="mt-6 flex items-center justify-between border-t border-white/10 pt-5"
                            >
                                <p class="text-xl font-semibold text-white">
                                    {{ peso(watch.price) }}
                                </p>

                                <Link
                                    :href="
                                        route('public.watches.show', watch.id)
                                    "
                                    class="text-sm font-medium text-zinc-300 transition group-hover:text-white"
                                >
                                    View Details
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="rounded-[2rem] border border-white/10 bg-[#0B0B0D] p-10 text-center"
                >
                    <img
                        src="/images/montre-nova-logo.png"
                        alt="Montre Nova"
                        class="mx-auto h-24 w-24 object-contain opacity-70"
                    />

                    <h3 class="mt-6 text-xl font-semibold text-white">
                        No available watches yet.
                    </h3>

                    <p class="mt-3 text-sm leading-7 text-zinc-500">
                        Stocks will appear here once they are marked as
                        available and visible from the admin dashboard.
                    </p>

                    <a
                        href="#contact"
                        class="mt-6 inline-flex rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200"
                    >
                        Message Us for Availability
                    </a>
                </div>
            </section>

            <!-- PROCESS -->
            <section id="process" class="mx-auto max-w-7xl px-6 py-20 lg:px-8">
                <div class="grid gap-5 lg:grid-cols-3">
                    <div
                        class="rounded-[2rem] border border-white/10 bg-[#0B0B0D]/90 p-8"
                    >
                        <p
                            class="text-xs font-medium uppercase tracking-[0.28em] text-zinc-600"
                        >
                            01
                        </p>

                        <h3 class="mt-4 text-xl font-semibold text-white">
                            Order & Purchase Process
                        </h3>

                        <p class="mt-4 text-sm leading-7 text-zinc-400">
                            Message us through our official channels to confirm
                            availability, request details, or reserve a watch.
                        </p>
                    </div>

                    <div
                        id="warranty"
                        class="rounded-[2rem] border border-white/10 bg-[#0B0B0D]/90 p-8"
                    >
                        <p
                            class="text-xs font-medium uppercase tracking-[0.28em] text-zinc-600"
                        >
                            02
                        </p>

                        <h3 class="mt-4 text-xl font-semibold text-white">
                            Service Warranty
                        </h3>

                        <p class="mt-4 text-sm leading-7 text-zinc-400">
                            Montre Card warranty coverage is valid for one year
                            from the date of purchase for movement and internal
                            mechanism defects.
                        </p>
                    </div>

                    <div
                        class="rounded-[2rem] border border-white/10 bg-[#0B0B0D]/90 p-8"
                    >
                        <p
                            class="text-xs font-medium uppercase tracking-[0.28em] text-zinc-600"
                        >
                            03
                        </p>

                        <h3 class="mt-4 text-xl font-semibold text-white">
                            Payment Methods
                        </h3>

                        <p class="mt-4 text-sm leading-7 text-zinc-400">
                            Cash, GCash, bank transfer, QR code payments, and
                            selected trade-ins may be accepted subject to
                            evaluation.
                        </p>
                    </div>
                </div>
            </section>

            <!-- CONTACT -->
            <section id="contact" class="mx-auto max-w-7xl px-6 py-20 lg:px-8">
                <div
                    class="overflow-hidden rounded-[2.5rem] border border-white/10 bg-[#0B0B0D] p-8 sm:p-12"
                >
                    <div
                        class="grid gap-10 lg:grid-cols-[1fr_0.8fr] lg:items-center"
                    >
                        <div>
                            <p
                                class="text-xs font-medium uppercase tracking-[0.32em] text-zinc-500"
                            >
                                Get in Touch
                            </p>

                            <h2
                                class="mt-4 max-w-2xl text-3xl font-semibold tracking-tight text-white sm:text-5xl"
                            >
                                Looking for your next timepiece?
                            </h2>

                            <p
                                class="mt-5 max-w-2xl text-sm leading-7 text-zinc-400 sm:text-base"
                            >
                                Message Montre Nova for available stocks,
                                reservations, and curated recommendations.
                            </p>
                        </div>

                        <div class="space-y-3">
                            <a
                                href="#"
                                class="flex items-center justify-between rounded-2xl border border-white/10 bg-[#050505] px-5 py-4 text-sm font-semibold text-white transition hover:border-white/30"
                            >
                                Messenger
                                <span class="text-zinc-500">→</span>
                            </a>

                            <a
                                href="#"
                                class="flex items-center justify-between rounded-2xl border border-white/10 bg-[#050505] px-5 py-4 text-sm font-semibold text-white transition hover:border-white/30"
                            >
                                Viber Community
                                <span class="text-zinc-500">→</span>
                            </a>

                            <a
                                href="#"
                                class="flex items-center justify-between rounded-2xl border border-white/10 bg-[#050505] px-5 py-4 text-sm font-semibold text-white transition hover:border-white/30"
                            >
                                Instagram
                                <span class="text-zinc-500">→</span>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="relative z-10 border-t border-white/10">
            <div
                class="mx-auto flex max-w-7xl flex-col justify-between gap-4 px-6 py-8 text-sm text-zinc-600 md:flex-row md:items-center lg:px-8"
            >
                <p>
                    © {{ new Date().getFullYear() }} Montre Nova. Curated
                    timepieces.
                </p>

                <p>Minimalist luxury watch boutique.</p>
            </div>
        </footer>
    </div>
</template>
