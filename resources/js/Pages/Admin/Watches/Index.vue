<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import CreateWatchModal from "./CreateWatchModal.vue";
import EditWatchModal from "./EditWatchModal.vue";
import DeleteWatchModal from "./DeleteWatchModal.vue";
import MarkSoldModal from "./MarkSoldModal.vue";
import ReserveWatchModal from "./ReserveWatchModal.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, onMounted, ref, watch } from "vue";

const props = defineProps({
    watches: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
        }),
    },
    filters: {
        type: Object,
        default: () => ({
            search: "",
            status: "",
        }),
    },
    summary: {
        type: Object,
        default: () => ({
            total_watches: 0,
            available_watches: 0,
            reserved_watches: 0,
            sold_watches: 0,
            draft_hidden_watches: 0,
            inventory_capital: 0,
            expected_sales_value: 0,
            expected_profit: 0,
        }),
    },
});

const search = ref(props.filters.search || "");
const status = ref(props.filters.status || "");

const viewMode = ref(localStorage.getItem("watch_stock_view") || "table");

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const showMarkSoldModal = ref(false);
const showReserveModal = ref(false);

const selectedWatch = ref(null);

let timeout = null;

const peso = (value) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
};

const formatDate = (value) => {
    if (!value) return "";

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "2-digit",
    });
};

onMounted(() => {
    const params = new URLSearchParams(window.location.search);

    if (params.get("create") === "1") {
        showCreateModal.value = true;
    }
});

watch([search, status], () => {
    clearTimeout(timeout);

    timeout = setTimeout(() => {
        router.get(
            route("admin.watches.index"),
            {
                search: search.value,
                status: status.value,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, 350);
});

const setStatusFilter = (value) => {
    status.value = value;
};

const setViewMode = (mode) => {
    viewMode.value = mode;
    localStorage.setItem("watch_stock_view", mode);
};

const openCreateModal = () => {
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;

    router.reload({
        only: ["watches", "summary"],
        preserveScroll: true,
    });
};

const openEditModal = (watch) => {
    selectedWatch.value = watch;
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    selectedWatch.value = null;

    router.reload({
        only: ["watches", "summary"],
        preserveScroll: true,
    });
};

const openDeleteModal = (watch) => {
    selectedWatch.value = watch;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedWatch.value = null;

    router.reload({
        only: ["watches", "summary"],
        preserveScroll: true,
    });
};

const openMarkSoldModal = (watch) => {
    selectedWatch.value = watch;
    showMarkSoldModal.value = true;
};

const closeMarkSoldModal = () => {
    showMarkSoldModal.value = false;
    selectedWatch.value = null;

    router.reload({
        only: ["watches", "summary"],
        preserveScroll: true,
    });
};

const openReserveModal = (watch) => {
    selectedWatch.value = watch;
    showReserveModal.value = true;
};

const closeReserveModal = () => {
    showReserveModal.value = false;
    selectedWatch.value = null;

    router.reload({
        only: ["watches", "summary"],
        preserveScroll: true,
    });
};

const clearReservation = (watch) => {
    if (!confirm(`Clear reservation for ${watch.brand} ${watch.model_name}?`)) {
        return;
    }

    router.patch(
        route("admin.watches.clear-reservation", watch.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({
                    only: ["watches", "summary"],
                    preserveScroll: true,
                });
            },
        },
    );
};

const isReservationOverdue = (watch) => {
    if (watch.status !== "reserved" || !watch.reservation_deadline) {
        return false;
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const deadline = new Date(watch.reservation_deadline);
    deadline.setHours(0, 0, 0, 0);

    return deadline < today;
};

const statusClass = (value) => {
    const classes = {
        available: "border-emerald-500/20 bg-emerald-500/10 text-emerald-300",
        reserved: "border-amber-500/20 bg-amber-500/10 text-amber-300",
        sold: "border-zinc-500/20 bg-zinc-500/10 text-zinc-300",
        hidden: "border-red-500/20 bg-red-500/10 text-red-300",
        draft: "border-white/10 bg-white/[0.05] text-zinc-400",
    };

    return classes[value] || classes.draft;
};

const visibilityClass = (watch) => {
    if (watch.status === "sold") {
        return "border-zinc-500/20 bg-zinc-500/10 text-zinc-400";
    }

    return watch.is_visible
        ? "border-emerald-500/20 bg-emerald-500/10 text-emerald-300"
        : "border-red-500/20 bg-red-500/10 text-red-300";
};

const listedPrice = (watch) => {
    return Number(watch.discounted_price || 0) > 0
        ? Number(watch.discounted_price)
        : Number(watch.selling_price || 0);
};

const expectedProfit = (watch) => {
    return listedPrice(watch) - Number(watch.capital_price || 0);
};

const profitMargin = (watch) => {
    const price = listedPrice(watch);

    if (price <= 0) return 0;

    return (expectedProfit(watch) / price) * 100;
};

const profitBadgeClass = (watch) => {
    const margin = profitMargin(watch);
    const profit = expectedProfit(watch);

    if (profit <= 0) {
        return "border-red-500/20 bg-red-500/10 text-red-300";
    }

    if (margin >= 20) {
        return "border-emerald-500/20 bg-emerald-500/10 text-emerald-300";
    }

    if (margin >= 10) {
        return "border-amber-500/20 bg-amber-500/10 text-amber-300";
    }

    return "border-red-500/20 bg-red-500/10 text-red-300";
};

const inventoryCards = computed(() => [
    {
        label: "Total Watches",
        value: props.summary.total_watches,
        helper: "All encoded stocks",
        valueClass: "text-white",
    },
    {
        label: "Available",
        value: props.summary.available_watches,
        helper: "Ready to sell",
        valueClass: "text-emerald-300",
    },
    {
        label: "Reserved",
        value: props.summary.reserved_watches,
        helper: "Pending buyer confirmation",
        valueClass: "text-amber-300",
    },
    {
        label: "Sold",
        value: props.summary.sold_watches,
        helper: "Completed sales",
        valueClass: "text-zinc-300",
    },
]);

const moneyCards = computed(() => [
    {
        label: "Inventory Capital",
        value: peso(props.summary.inventory_capital),
        helper: "Capital tied to unsold watches",
        valueClass: "text-white",
    },
    {
        label: "Expected Sales Value",
        value: peso(props.summary.expected_sales_value),
        helper: "Estimated value if all unsold watches sell",
        valueClass: "text-white",
    },
    {
        label: "Expected Profit",
        value: peso(props.summary.expected_profit),
        helper: "Expected sales minus active inventory capital",
        valueClass:
            Number(props.summary.expected_profit || 0) >= 0
                ? "text-emerald-300"
                : "text-red-300",
    },
    {
        label: "Draft / Hidden",
        value: props.summary.draft_hidden_watches,
        helper: "Not publicly active",
        valueClass: "text-zinc-300",
    },
]);

const statusTabs = computed(() => [
    { label: "All", value: "", count: props.summary.total_watches },
    {
        label: "Available",
        value: "available",
        count: props.summary.available_watches,
    },
    {
        label: "Reserved",
        value: "reserved",
        count: props.summary.reserved_watches,
    },
    { label: "Sold", value: "sold", count: props.summary.sold_watches },
    { label: "Draft", value: "draft", count: null },
    { label: "Hidden", value: "hidden", count: null },
]);
</script>

<template>
    <Head title="Watch Stocks | Montre Nova" />

    <AuthenticatedLayout title="Watch Stocks">
        <div class="space-y-7">
            <!-- HEADER -->
            <section
                class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-[#0B0B0D] p-6 shadow-2xl shadow-black/30 sm:p-8"
            >
                <div class="pointer-events-none absolute inset-0">
                    <div
                        class="absolute right-[-12rem] top-[-12rem] h-[30rem] w-[30rem] rounded-full bg-white/[0.04] blur-3xl"
                    ></div>
                </div>

                <div
                    class="relative flex flex-col justify-between gap-6 lg:flex-row lg:items-end"
                >
                    <div>
                        <p
                            class="text-xs uppercase tracking-[0.34em] text-zinc-600"
                        >
                            Inventory Command Center
                        </p>

                        <h2
                            class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-5xl"
                        >
                            Watch Stocks
                        </h2>

                        <p
                            class="mt-4 max-w-2xl text-sm leading-7 text-zinc-400"
                        >
                            Manage photos, pricing, visibility, status,
                            reservations, and sales conversion for each Montre
                            Nova watch.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200"
                        @click="openCreateModal"
                    >
                        Add Watch
                    </button>
                </div>
            </section>

            <!-- INVENTORY SUMMARY -->
            <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="card in inventoryCards"
                    :key="card.label"
                    class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-6"
                >
                    <p
                        class="text-xs uppercase tracking-[0.26em] text-zinc-600"
                    >
                        {{ card.label }}
                    </p>

                    <p
                        class="mt-4 text-4xl font-semibold tracking-tight"
                        :class="card.valueClass"
                    >
                        {{ card.value }}
                    </p>

                    <p
                        class="mt-4 border-t border-white/10 pt-4 text-sm text-zinc-500"
                    >
                        {{ card.helper }}
                    </p>
                </div>
            </section>

            <!-- MONEY SUMMARY -->
            <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="card in moneyCards"
                    :key="card.label"
                    class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-6"
                >
                    <p
                        class="text-xs uppercase tracking-[0.26em] text-zinc-600"
                    >
                        {{ card.label }}
                    </p>

                    <p
                        class="mt-4 text-2xl font-semibold tracking-tight"
                        :class="card.valueClass"
                    >
                        {{ card.value }}
                    </p>

                    <p
                        class="mt-4 border-t border-white/10 pt-4 text-sm text-zinc-500"
                    >
                        {{ card.helper }}
                    </p>
                </div>
            </section>

            <!-- FILTERS -->
            <section
                class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5"
            >
                <div class="flex flex-col gap-5">
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="tab in statusTabs"
                            :key="tab.value || 'all'"
                            type="button"
                            class="rounded-2xl border px-4 py-2 text-sm font-medium transition"
                            :class="
                                status === tab.value
                                    ? 'border-white bg-white text-black'
                                    : 'border-white/10 bg-white/[0.03] text-zinc-400 hover:border-white/30 hover:text-white'
                            "
                            @click="setStatusFilter(tab.value)"
                        >
                            {{ tab.label }}
                            <span
                                v-if="tab.count !== null"
                                class="ml-1 opacity-70"
                            >
                                {{ tab.count }}
                            </span>
                        </button>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="rounded-2xl border px-4 py-2 text-sm font-medium transition"
                            :class="
                                viewMode === 'table'
                                    ? 'border-white bg-white text-black'
                                    : 'border-white/10 bg-white/[0.03] text-zinc-400 hover:border-white/30 hover:text-white'
                            "
                            @click="setViewMode('table')"
                        >
                            Table View
                        </button>

                        <button
                            type="button"
                            class="rounded-2xl border px-4 py-2 text-sm font-medium transition"
                            :class="
                                viewMode === 'gallery'
                                    ? 'border-white bg-white text-black'
                                    : 'border-white/10 bg-white/[0.03] text-zinc-400 hover:border-white/30 hover:text-white'
                            "
                            @click="setViewMode('gallery')"
                        >
                            Gallery View
                        </button>
                    </div>

                    <div class="grid gap-4 md:grid-cols-[1fr_220px]">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search brand, model, reference, condition, or category..."
                            class="mn-input"
                        />

                        <select v-model="status" class="mn-input">
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="available">Available</option>
                            <option value="reserved">Reserved</option>
                            <option value="sold">Sold</option>
                            <option value="hidden">Hidden</option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- GALLERY VIEW -->
            <section
                v-if="viewMode === 'gallery'"
                class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
            >
                <div
                    v-for="watch in watches.data"
                    :key="watch.id"
                    class="overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] transition hover:border-white/20"
                >
                    <div class="relative aspect-[4/5] bg-[#050505]">
                        <img
                            v-if="watch.primary_image"
                            :src="watch.primary_image.image_url"
                            class="h-full w-full object-cover"
                            alt=""
                        />

                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center text-sm font-semibold tracking-[0.3em] text-zinc-700"
                        >
                            MONTRE NOVA
                        </div>

                        <div class="absolute left-4 top-4 flex flex-wrap gap-2">
                            <span
                                class="rounded-full border px-3 py-1 text-xs font-medium capitalize backdrop-blur"
                                :class="statusClass(watch.status)"
                            >
                                {{ watch.status }}
                            </span>

                            <span
                                v-if="
                                    watch.status === 'available' &&
                                    watch.is_visible &&
                                    watch.primary_image
                                "
                                class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-300 backdrop-blur"
                            >
                                Ready to Post
                            </span>

                            <span
                                v-if="isReservationOverdue(watch)"
                                class="rounded-full border border-red-500/20 bg-red-500/10 px-3 py-1 text-xs font-medium text-red-300 backdrop-blur"
                            >
                                Overdue
                            </span>
                        </div>

                        <div
                            class="absolute bottom-4 right-4 rounded-full bg-black/80 px-3 py-1 text-xs font-medium text-white backdrop-blur"
                        >
                            {{ watch.images_count || 0 }} photos
                        </div>
                    </div>

                    <div class="p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-base font-semibold text-white">
                                    {{ watch.brand }} {{ watch.model_name }}
                                </p>

                                <p class="mt-1 text-xs text-zinc-500">
                                    Ref.
                                    {{
                                        watch.reference_number || "No reference"
                                    }}
                                </p>
                            </div>

                            <span
                                class="rounded-full border px-3 py-1 text-xs font-medium"
                                :class="visibilityClass(watch)"
                            >
                                {{
                                    watch.status === "sold"
                                        ? "Sold"
                                        : watch.is_visible
                                          ? "Visible"
                                          : "Hidden"
                                }}
                            </span>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <span
                                class="rounded-full border border-white/10 bg-white/[0.03] px-2.5 py-1 text-[11px] text-zinc-400"
                            >
                                {{ watch.condition || "No condition" }}
                            </span>

                            <span
                                class="rounded-full border border-white/10 bg-white/[0.03] px-2.5 py-1 text-[11px] text-zinc-400"
                            >
                                {{ watch.category || "No category" }}
                            </span>

                            <span
                                v-if="!watch.primary_image"
                                class="rounded-full border border-red-500/20 bg-red-500/10 px-2.5 py-1 text-[11px] text-red-300"
                            >
                                No Photo
                            </span>
                        </div>

                        <div
                            v-if="watch.status === 'reserved'"
                            class="mt-4 rounded-2xl border border-amber-500/10 bg-amber-500/5 p-4"
                        >
                            <p class="text-xs font-semibold text-amber-300">
                                {{
                                    watch.reserved_customer_name ||
                                    "Reserved Customer"
                                }}
                            </p>

                            <p
                                v-if="watch.reserved_contact_number"
                                class="mt-1 text-xs text-zinc-500"
                            >
                                {{ watch.reserved_contact_number }}
                            </p>

                            <p
                                v-if="watch.reservation_deadline"
                                class="mt-1 text-xs"
                                :class="
                                    isReservationOverdue(watch)
                                        ? 'text-red-300'
                                        : 'text-zinc-500'
                                "
                            >
                                Until
                                {{ formatDate(watch.reservation_deadline) }}
                            </p>
                        </div>

                        <div class="mt-5 grid grid-cols-2 gap-3">
                            <div
                                class="rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                            >
                                <p class="text-xs text-zinc-500">Capital</p>
                                <p
                                    class="mt-1 text-sm font-semibold text-white"
                                >
                                    {{ peso(watch.capital_price) }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                            >
                                <p class="text-xs text-zinc-500">Selling</p>
                                <p
                                    class="mt-1 text-sm font-semibold text-white"
                                >
                                    {{ peso(listedPrice(watch)) }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                            >
                                <p class="text-xs text-zinc-500">Profit</p>
                                <p
                                    class="mt-1 text-sm font-semibold"
                                    :class="
                                        expectedProfit(watch) >= 0
                                            ? 'text-emerald-300'
                                            : 'text-red-300'
                                    "
                                >
                                    {{ peso(expectedProfit(watch)) }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                            >
                                <p class="text-xs text-zinc-500">Margin</p>
                                <p
                                    class="mt-1 text-sm font-semibold text-white"
                                >
                                    {{ profitMargin(watch).toFixed(1) }}%
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-2">
                            <button
                                v-if="watch.status !== 'sold'"
                                type="button"
                                class="rounded-xl border border-amber-500/20 px-3 py-2 text-xs font-medium text-amber-300 transition hover:bg-amber-500/10"
                                @click="openReserveModal(watch)"
                            >
                                {{
                                    watch.status === "reserved"
                                        ? "Edit Reserve"
                                        : "Reserve"
                                }}
                            </button>

                            <button
                                v-if="watch.status === 'reserved'"
                                type="button"
                                class="rounded-xl border border-white/10 px-3 py-2 text-xs font-medium text-zinc-300 transition hover:border-white/30 hover:text-white"
                                @click="clearReservation(watch)"
                            >
                                Clear
                            </button>

                            <button
                                v-if="watch.status !== 'sold'"
                                type="button"
                                class="rounded-xl border border-emerald-500/20 px-3 py-2 text-xs font-medium text-emerald-300 transition hover:bg-emerald-500/10"
                                @click="openMarkSoldModal(watch)"
                            >
                                Mark Sold
                            </button>

                            <button
                                type="button"
                                class="rounded-xl border border-white/10 px-3 py-2 text-xs font-medium text-zinc-300 transition hover:border-white/30 hover:text-white"
                                @click="openEditModal(watch)"
                            >
                                Edit
                            </button>

                            <button
                                type="button"
                                class="rounded-xl border border-red-500/20 px-3 py-2 text-xs font-medium text-red-300 transition hover:bg-red-500/10"
                                @click="openDeleteModal(watch)"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    v-if="!watches.data.length"
                    class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-10 text-center sm:col-span-2 xl:col-span-3 2xl:col-span-4"
                >
                    <p class="text-sm font-medium text-white">
                        No watch stocks found.
                    </p>

                    <p class="mt-2 text-sm text-zinc-500">
                        Start by adding your first Montre Nova watch.
                    </p>

                    <button
                        type="button"
                        class="mt-5 rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200"
                        @click="openCreateModal"
                    >
                        Add First Watch
                    </button>
                </div>
            </section>

            <!-- WATCH TABLE -->
            <section
                v-if="viewMode === 'table'"
                class="overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D]"
            >
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10">
                        <thead>
                            <tr class="bg-white/[0.02]">
                                <th
                                    class="px-6 py-4 text-left text-xs uppercase tracking-[0.22em] text-zinc-600"
                                >
                                    Watch
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs uppercase tracking-[0.22em] text-zinc-600"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs uppercase tracking-[0.22em] text-zinc-600"
                                >
                                    Capital
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs uppercase tracking-[0.22em] text-zinc-600"
                                >
                                    Selling
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs uppercase tracking-[0.22em] text-zinc-600"
                                >
                                    Profit
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs uppercase tracking-[0.22em] text-zinc-600"
                                >
                                    Health
                                </th>
                                <th
                                    class="px-6 py-4 text-right text-xs uppercase tracking-[0.22em] text-zinc-600"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-white/10">
                            <tr
                                v-for="watch in watches.data"
                                :key="watch.id"
                                class="transition hover:bg-white/[0.02]"
                            >
                                <td class="px-6 py-5">
                                    <div
                                        class="flex min-w-[280px] items-center gap-4"
                                    >
                                        <div
                                            class="relative h-20 w-20 shrink-0 overflow-hidden rounded-2xl border border-white/10 bg-[#050505]"
                                        >
                                            <img
                                                v-if="watch.primary_image"
                                                :src="
                                                    watch.primary_image
                                                        .image_url
                                                "
                                                class="h-full w-full object-cover"
                                                alt=""
                                            />

                                            <div
                                                v-else
                                                class="flex h-full w-full items-center justify-center text-xs font-semibold text-zinc-600"
                                            >
                                                MN
                                            </div>

                                            <div
                                                class="absolute bottom-1 right-1 rounded-full bg-black/80 px-2 py-0.5 text-[10px] font-medium text-white backdrop-blur"
                                            >
                                                {{ watch.images_count || 0 }}
                                            </div>
                                        </div>

                                        <div>
                                            <p
                                                class="text-sm font-semibold text-white"
                                            >
                                                {{ watch.brand }}
                                                {{ watch.model_name }}
                                            </p>

                                            <p
                                                class="mt-1 text-xs text-zinc-500"
                                            >
                                                Ref.
                                                {{
                                                    watch.reference_number ||
                                                    "No reference"
                                                }}
                                            </p>

                                            <div
                                                class="mt-3 flex flex-wrap gap-2"
                                            >
                                                <span
                                                    class="rounded-full border border-white/10 bg-white/[0.03] px-2.5 py-1 text-[11px] text-zinc-400"
                                                >
                                                    {{
                                                        watch.condition ||
                                                        "No condition"
                                                    }}
                                                </span>

                                                <span
                                                    class="rounded-full border border-white/10 bg-white/[0.03] px-2.5 py-1 text-[11px] text-zinc-400"
                                                >
                                                    {{
                                                        watch.category ||
                                                        "No category"
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-5">
                                    <div class="space-y-2">
                                        <span
                                            class="inline-flex rounded-full border px-3 py-1 text-xs font-medium capitalize"
                                            :class="statusClass(watch.status)"
                                        >
                                            {{ watch.status }}
                                        </span>

                                        <span
                                            class="block w-fit rounded-full border px-3 py-1 text-xs font-medium"
                                            :class="visibilityClass(watch)"
                                        >
                                            {{
                                                watch.status === "sold"
                                                    ? "Sold / Hidden"
                                                    : watch.is_visible
                                                      ? "Visible"
                                                      : "Hidden"
                                            }}
                                        </span>

                                        <div
                                            v-if="watch.status === 'reserved'"
                                            class="mt-3 max-w-[210px] rounded-2xl border border-amber-500/10 bg-amber-500/5 p-3"
                                        >
                                            <p
                                                class="text-xs font-semibold text-amber-300"
                                            >
                                                {{
                                                    watch.reserved_customer_name ||
                                                    "Reserved Customer"
                                                }}
                                            </p>

                                            <p
                                                v-if="
                                                    watch.reserved_contact_number
                                                "
                                                class="mt-1 text-[11px] text-zinc-500"
                                            >
                                                {{
                                                    watch.reserved_contact_number
                                                }}
                                            </p>

                                            <p
                                                v-if="
                                                    watch.reservation_deadline
                                                "
                                                class="mt-1 text-[11px]"
                                                :class="
                                                    isReservationOverdue(watch)
                                                        ? 'text-red-300'
                                                        : 'text-zinc-500'
                                                "
                                            >
                                                Until
                                                {{
                                                    formatDate(
                                                        watch.reservation_deadline,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td
                                    class="px-6 py-5 text-sm font-semibold text-white"
                                >
                                    {{ peso(watch.capital_price) }}
                                </td>

                                <td class="px-6 py-5">
                                    <p class="text-sm font-semibold text-white">
                                        {{ peso(listedPrice(watch)) }}
                                    </p>

                                    <p
                                        v-if="
                                            Number(
                                                watch.discounted_price || 0,
                                            ) > 0
                                        "
                                        class="mt-1 text-xs text-zinc-500 line-through"
                                    >
                                        {{ peso(watch.selling_price) }}
                                    </p>
                                </td>

                                <td class="px-6 py-5">
                                    <p
                                        class="text-sm font-semibold"
                                        :class="
                                            expectedProfit(watch) >= 0
                                                ? 'text-emerald-300'
                                                : 'text-red-300'
                                        "
                                    >
                                        {{ peso(expectedProfit(watch)) }}
                                    </p>

                                    <p class="mt-1 text-xs text-zinc-500">
                                        {{ profitMargin(watch).toFixed(1) }}%
                                        margin
                                    </p>
                                </td>

                                <td class="px-6 py-5">
                                    <div class="flex flex-col gap-2">
                                        <span
                                            class="w-fit rounded-full border px-3 py-1 text-xs font-medium"
                                            :class="profitBadgeClass(watch)"
                                        >
                                            {{
                                                expectedProfit(watch) <= 0
                                                    ? "No Profit"
                                                    : profitMargin(watch) >= 20
                                                      ? "High Profit"
                                                      : profitMargin(watch) >=
                                                          10
                                                        ? "Healthy"
                                                        : "Low Margin"
                                            }}
                                        </span>

                                        <span
                                            v-if="!watch.primary_image"
                                            class="w-fit rounded-full border border-red-500/20 bg-red-500/10 px-3 py-1 text-xs font-medium text-red-300"
                                        >
                                            No Photo
                                        </span>

                                        <span
                                            v-if="
                                                watch.status === 'available' &&
                                                watch.is_visible &&
                                                watch.primary_image
                                            "
                                            class="w-fit rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-300"
                                        >
                                            Ready to Post
                                        </span>

                                        <span
                                            v-if="isReservationOverdue(watch)"
                                            class="w-fit rounded-full border border-red-500/20 bg-red-500/10 px-3 py-1 text-xs font-medium text-red-300"
                                        >
                                            Reservation Overdue
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-5">
                                    <div
                                        class="flex flex-wrap justify-end gap-2"
                                    >
                                        <button
                                            v-if="watch.status !== 'sold'"
                                            type="button"
                                            class="rounded-xl border border-amber-500/20 px-3 py-2 text-xs font-medium text-amber-300 transition hover:bg-amber-500/10"
                                            @click="openReserveModal(watch)"
                                        >
                                            {{
                                                watch.status === "reserved"
                                                    ? "Edit Reserve"
                                                    : "Reserve"
                                            }}
                                        </button>

                                        <button
                                            v-if="watch.status === 'reserved'"
                                            type="button"
                                            class="rounded-xl border border-white/10 px-3 py-2 text-xs font-medium text-zinc-300 transition hover:border-white/30 hover:text-white"
                                            @click="clearReservation(watch)"
                                        >
                                            Clear
                                        </button>

                                        <button
                                            v-if="watch.status !== 'sold'"
                                            type="button"
                                            class="rounded-xl border border-emerald-500/20 px-3 py-2 text-xs font-medium text-emerald-300 transition hover:bg-emerald-500/10"
                                            @click="openMarkSoldModal(watch)"
                                        >
                                            Mark Sold
                                        </button>

                                        <button
                                            type="button"
                                            class="rounded-xl border border-white/10 px-3 py-2 text-xs font-medium text-zinc-300 transition hover:border-white/30 hover:text-white"
                                            @click="openEditModal(watch)"
                                        >
                                            Edit
                                        </button>

                                        <button
                                            type="button"
                                            class="rounded-xl border border-red-500/20 px-3 py-2 text-xs font-medium text-red-300 transition hover:bg-red-500/10"
                                            @click="openDeleteModal(watch)"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!watches.data.length">
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <p class="text-sm font-medium text-white">
                                        No watch stocks found.
                                    </p>

                                    <p class="mt-2 text-sm text-zinc-500">
                                        Start by adding your first Montre Nova
                                        watch.
                                    </p>

                                    <button
                                        type="button"
                                        class="mt-5 rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200"
                                        @click="openCreateModal"
                                    >
                                        Add First Watch
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- PAGINATION -->
            <section
                v-if="watches.links?.length > 3"
                class="flex flex-wrap gap-2 rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5"
            >
                <Link
                    v-for="link in watches.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    v-html="link.label"
                    class="rounded-xl border px-3 py-2 text-sm"
                    :class="[
                        link.active
                            ? 'border-white bg-white text-black'
                            : 'border-white/10 text-zinc-400 hover:border-white/30 hover:text-white',
                        !link.url ? 'pointer-events-none opacity-40' : '',
                    ]"
                />
            </section>
        </div>

        <CreateWatchModal :show="showCreateModal" @close="closeCreateModal" />

        <EditWatchModal
            :show="showEditModal"
            :watch="selectedWatch"
            @close="closeEditModal"
        />

        <DeleteWatchModal
            :show="showDeleteModal"
            :watch="selectedWatch"
            @close="closeDeleteModal"
        />

        <MarkSoldModal
            :show="showMarkSoldModal"
            :watch="selectedWatch"
            @close="closeMarkSoldModal"
        />

        <ReserveWatchModal
            :show="showReserveModal"
            :watch="selectedWatch"
            @close="closeReserveModal"
        />
    </AuthenticatedLayout>
</template>

<style scoped>
.mn-input {
    width: 100%;
    border-radius: 1rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: #050505;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: white;
    outline: none;
}

.mn-input::placeholder {
    color: rgb(63 63 70);
}

.mn-input:focus {
    border-color: rgb(255 255 255 / 0.4);
    box-shadow: 0 0 0 2px rgb(255 255 255 / 0.1);
}
</style>
