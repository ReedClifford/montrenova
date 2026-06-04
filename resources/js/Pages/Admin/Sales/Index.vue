<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
    selectedMonth: {
        type: String,
        default: "",
    },
    selectedMonthSummary: {
        type: Object,
        default: () => ({
            label: "",
            date_range: "",
            sold_count: 0,
            total_sales: 0,
            total_capital: 0,
            gross_profit: 0,
            total_expenses: 0,
            net_profit: 0,
        }),
    },
    salesPerformance: {
        type: Object,
        default: () => ({
            weekly: {},
            monthly: {},
            yearly: {},
        }),
    },
    topSoldUnits: {
        type: Array,
        default: () => [],
    },
    recentSales: {
        type: Array,
        default: () => [],
    },
});

const selectedMonthFilter = ref(
    props.selectedMonth || new Date().toISOString().slice(0, 7),
);

const peso = (value) => {
    const amount = Number(value || 0);

    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 2,
    }).format(amount);
};

const compactPeso = (value) => {
    const amount = Number(value || 0);

    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        notation: Math.abs(amount) >= 100000 ? "compact" : "standard",
        maximumFractionDigits: Math.abs(amount) >= 100000 ? 1 : 0,
    }).format(amount);
};

const formatDate = (value) => {
    if (!value) return "No date";

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

const netProfitClass = (value) => {
    return Number(value || 0) >= 0 ? "text-emerald-300" : "text-red-300";
};

const profitBadgeClass = (value) => {
    return Number(value || 0) >= 0
        ? "border-emerald-500/20 bg-emerald-500/10 text-emerald-300"
        : "border-red-500/20 bg-red-500/10 text-red-300";
};

const applyMonthFilter = () => {
    router.get(
        route("admin.sales.index"),
        {
            month: selectedMonthFilter.value,
        },
        {
            preserveState: false,
            preserveScroll: true,
            replace: true,
        },
    );
};

const selectedMonthNetProfitClass = computed(() => {
    return netProfitClass(props.selectedMonthSummary.net_profit);
});

const selectedMonthMargin = computed(() => {
    const totalSales = Number(props.selectedMonthSummary.total_sales || 0);
    const grossProfit = Number(props.selectedMonthSummary.gross_profit || 0);

    if (totalSales <= 0) return 0;

    return (grossProfit / totalSales) * 100;
});

const selectedMonthCards = computed(() => [
    {
        label: "Sold",
        value: props.selectedMonthSummary.sold_count || 0,
        fullValue: `${props.selectedMonthSummary.sold_count || 0} watches`,
        valueClass: "text-white",
    },
    {
        label: "Sales",
        value: compactPeso(props.selectedMonthSummary.total_sales),
        fullValue: peso(props.selectedMonthSummary.total_sales),
        valueClass: "text-white",
    },
    {
        label: "Capital",
        value: compactPeso(props.selectedMonthSummary.total_capital),
        fullValue: peso(props.selectedMonthSummary.total_capital),
        valueClass: "text-zinc-300",
    },
    {
        label: "Gross",
        value: compactPeso(props.selectedMonthSummary.gross_profit),
        fullValue: peso(props.selectedMonthSummary.gross_profit),
        valueClass: "text-emerald-300",
    },
    {
        label: "Expenses",
        value: compactPeso(props.selectedMonthSummary.total_expenses),
        fullValue: peso(props.selectedMonthSummary.total_expenses),
        valueClass: "text-red-300",
    },
    {
        label: "Net",
        value: compactPeso(props.selectedMonthSummary.net_profit),
        fullValue: peso(props.selectedMonthSummary.net_profit),
        valueClass: selectedMonthNetProfitClass.value,
    },
]);

const performanceCards = computed(() => [
    {
        label: "This Week",
        period: props.salesPerformance.weekly?.date_range || "",
        sold: props.salesPerformance.weekly?.sold_count || 0,
        sales: peso(props.salesPerformance.weekly?.total_sales || 0),
        compactSales: compactPeso(
            props.salesPerformance.weekly?.total_sales || 0,
        ),
        capital: peso(props.salesPerformance.weekly?.total_capital || 0),
        grossProfit: peso(props.salesPerformance.weekly?.gross_profit || 0),
        compactGrossProfit: compactPeso(
            props.salesPerformance.weekly?.gross_profit || 0,
        ),
        expenses: peso(props.salesPerformance.weekly?.total_expenses || 0),
        netProfit: peso(props.salesPerformance.weekly?.net_profit || 0),
        compactNetProfit: compactPeso(
            props.salesPerformance.weekly?.net_profit || 0,
        ),
        netProfitValue: Number(props.salesPerformance.weekly?.net_profit || 0),
    },
    {
        label: "This Month",
        period: props.salesPerformance.monthly?.date_range || "",
        sold: props.salesPerformance.monthly?.sold_count || 0,
        sales: peso(props.salesPerformance.monthly?.total_sales || 0),
        compactSales: compactPeso(
            props.salesPerformance.monthly?.total_sales || 0,
        ),
        capital: peso(props.salesPerformance.monthly?.total_capital || 0),
        grossProfit: peso(props.salesPerformance.monthly?.gross_profit || 0),
        compactGrossProfit: compactPeso(
            props.salesPerformance.monthly?.gross_profit || 0,
        ),
        expenses: peso(props.salesPerformance.monthly?.total_expenses || 0),
        netProfit: peso(props.salesPerformance.monthly?.net_profit || 0),
        compactNetProfit: compactPeso(
            props.salesPerformance.monthly?.net_profit || 0,
        ),
        netProfitValue: Number(props.salesPerformance.monthly?.net_profit || 0),
    },
    {
        label: "This Year",
        period: props.salesPerformance.yearly?.date_range || "",
        sold: props.salesPerformance.yearly?.sold_count || 0,
        sales: peso(props.salesPerformance.yearly?.total_sales || 0),
        compactSales: compactPeso(
            props.salesPerformance.yearly?.total_sales || 0,
        ),
        capital: peso(props.salesPerformance.yearly?.total_capital || 0),
        grossProfit: peso(props.salesPerformance.yearly?.gross_profit || 0),
        compactGrossProfit: compactPeso(
            props.salesPerformance.yearly?.gross_profit || 0,
        ),
        expenses: peso(props.salesPerformance.yearly?.total_expenses || 0),
        netProfit: peso(props.salesPerformance.yearly?.net_profit || 0),
        compactNetProfit: compactPeso(
            props.salesPerformance.yearly?.net_profit || 0,
        ),
        netProfitValue: Number(props.salesPerformance.yearly?.net_profit || 0),
    },
]);

const heroStats = computed(() => [
    {
        label: "Selected Month Sales",
        value: compactPeso(props.selectedMonthSummary.total_sales),
        fullValue: peso(props.selectedMonthSummary.total_sales),
        valueClass: "text-white",
    },
    {
        label: "Selected Month Net",
        value: compactPeso(props.selectedMonthSummary.net_profit),
        fullValue: peso(props.selectedMonthSummary.net_profit),
        valueClass: selectedMonthNetProfitClass.value,
    },
    {
        label: "Units Sold",
        value: props.selectedMonthSummary.sold_count || 0,
        fullValue: `${props.selectedMonthSummary.sold_count || 0} sold watches`,
        valueClass: "text-white",
    },
]);

const insightCards = computed(() => [
    {
        label: "Month Health",
        value:
            Number(props.selectedMonthSummary.net_profit || 0) >= 0
                ? "Profitable"
                : "Needs Review",
        helper:
            Number(props.selectedMonthSummary.net_profit || 0) >= 0
                ? "Selected month is showing positive net profit."
                : "Selected month has negative net profit.",
        className:
            Number(props.selectedMonthSummary.net_profit || 0) >= 0
                ? "border-emerald-500/20 bg-emerald-500/10 text-emerald-300"
                : "border-red-500/20 bg-red-500/10 text-red-300",
    },
    {
        label: "Gross Margin",
        value: `${selectedMonthMargin.value.toFixed(1)}%`,
        helper: "Gross profit compared to total sales.",
        className:
            selectedMonthMargin.value >= 20
                ? "border-emerald-500/20 bg-emerald-500/10 text-emerald-300"
                : "border-amber-500/20 bg-amber-500/10 text-amber-300",
    },
    {
        label: "Expenses",
        value: compactPeso(props.selectedMonthSummary.total_expenses),
        helper: "Expenses recorded for the selected month.",
        className:
            Number(props.selectedMonthSummary.total_expenses || 0) <=
            Number(props.selectedMonthSummary.gross_profit || 0) * 0.3
                ? "border-emerald-500/20 bg-emerald-500/10 text-emerald-300"
                : "border-amber-500/20 bg-amber-500/10 text-amber-300",
    },
]);
</script>

<template>
    <Head title="Sales | Montre Nova" />

    <AuthenticatedLayout title="Sales">
        <div class="space-y-6 sm:space-y-8">
            <!-- MOBILE QUICK ACTIONS -->
            <section class="grid grid-cols-2 gap-3 sm:hidden">
                <Link
                    :href="route('admin.watches.index')"
                    class="rounded-2xl bg-white px-4 py-3 text-center text-sm font-bold text-black"
                >
                    Manage Stocks
                </Link>

                <button
                    type="button"
                    class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm font-bold text-white"
                    @click="applyMonthFilter"
                >
                    Refresh Month
                </button>
            </section>

            <!-- HERO -->
            <section
                class="relative overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5 shadow-2xl shadow-black/30 sm:rounded-[2rem] sm:p-8"
            >
                <div class="pointer-events-none absolute inset-0">
                    <div
                        class="absolute right-[-10rem] top-[-12rem] h-[30rem] w-[30rem] rounded-full bg-white/[0.04] blur-3xl"
                    ></div>

                    <div
                        class="absolute bottom-[-12rem] left-[10%] h-[28rem] w-[28rem] rounded-full bg-zinc-700/10 blur-3xl"
                    ></div>
                </div>

                <div
                    class="relative grid gap-6 lg:grid-cols-[1fr_0.48fr] lg:items-center"
                >
                    <div>
                        <p
                            class="text-xs font-medium uppercase tracking-[0.28em] text-zinc-500"
                        >
                            Montre Nova Sales Analytics
                        </p>

                        <h2
                            class="mt-4 max-w-3xl text-3xl font-semibold tracking-tight text-white sm:text-5xl"
                        >
                            Sales, profit, and sold watch performance.
                        </h2>

                        <p
                            class="mt-4 max-w-2xl text-sm leading-7 text-zinc-400 sm:text-base"
                        >
                            Track sold watches, capital cost, gross profit,
                            expenses, net profit, and top-performing units.
                        </p>

                        <div
                            class="mt-6 hidden flex-col gap-3 sm:flex sm:flex-row"
                        >
                            <Link
                                :href="route('admin.watches.index')"
                                class="inline-flex items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200"
                            >
                                Manage Watch Stocks
                            </Link>

                            <a
                                href="#recent-sales"
                                class="inline-flex items-center justify-center rounded-2xl border border-white/10 bg-white/[0.03] px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/[0.06]"
                            >
                                View Recent Sales
                            </a>
                        </div>
                    </div>

                    <div
                        class="rounded-[1.5rem] border border-white/10 bg-white/[0.03] p-5 sm:p-6"
                    >
                        <p
                            class="text-xs uppercase tracking-[0.24em] text-zinc-600"
                        >
                            Selected Month Net Profit
                        </p>

                        <p
                            class="mt-3 text-4xl font-semibold tracking-tight sm:text-5xl"
                            :class="selectedMonthNetProfitClass"
                        >
                            {{ compactPeso(selectedMonthSummary.net_profit) }}
                        </p>

                        <p class="mt-2 text-sm text-zinc-500">
                            {{ peso(selectedMonthSummary.net_profit) }}
                        </p>

                        <div
                            class="mt-5 space-y-3 border-t border-white/10 pt-5"
                        >
                            <div
                                v-for="stat in heroStats"
                                :key="stat.label"
                                class="flex items-center justify-between gap-4"
                            >
                                <span class="text-sm text-zinc-500">
                                    {{ stat.label }}
                                </span>

                                <span
                                    class="text-right text-sm font-semibold"
                                    :class="stat.valueClass"
                                >
                                    {{ stat.value }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- INSIGHTS -->
            <section class="grid gap-3 sm:grid-cols-3">
                <div
                    v-for="card in insightCards"
                    :key="card.label"
                    class="rounded-[1.4rem] border p-4 sm:p-5"
                    :class="card.className"
                >
                    <p
                        class="text-xs font-bold uppercase tracking-[0.18em] opacity-80"
                    >
                        {{ card.label }}
                    </p>

                    <p class="mt-3 text-2xl font-semibold tracking-tight">
                        {{ card.value }}
                    </p>

                    <p class="mt-2 text-xs leading-5 opacity-75">
                        {{ card.helper }}
                    </p>
                </div>
            </section>

            <!-- MONTH CHECKER -->
            <section
                class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5 sm:p-6"
            >
                <div
                    class="flex flex-col justify-between gap-5 lg:flex-row lg:items-end"
                >
                    <div>
                        <p
                            class="text-xs font-medium uppercase tracking-[0.28em] text-zinc-600"
                        >
                            Specific Month Profit
                        </p>

                        <h3 class="mt-2 text-2xl font-semibold text-white">
                            {{ selectedMonthSummary.label || "Selected Month" }}
                        </h3>

                        <p class="mt-2 text-sm text-zinc-500">
                            {{
                                selectedMonthSummary.date_range ||
                                "Select a month to view performance."
                            }}
                        </p>
                    </div>

                    <form
                        class="grid gap-3 sm:grid-cols-[1fr_auto]"
                        @submit.prevent="applyMonthFilter"
                    >
                        <input
                            v-model="selectedMonthFilter"
                            type="month"
                            class="mn-input"
                        />

                        <button
                            type="submit"
                            class="rounded-2xl bg-white px-5 py-3 text-sm font-bold text-black transition hover:bg-zinc-200"
                        >
                            Check Month
                        </button>
                    </form>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                    <div
                        v-for="card in selectedMonthCards"
                        :key="card.label"
                        class="mn-mini-card"
                    >
                        <p class="mn-mini-label">
                            {{ card.label }}
                        </p>

                        <p
                            class="mt-2 text-2xl font-semibold tracking-tight"
                            :class="card.valueClass"
                        >
                            {{ card.value }}
                        </p>

                        <p class="mt-1 text-xs text-zinc-600">
                            {{ card.fullValue }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- PERFORMANCE -->
            <section>
                <div class="mb-4">
                    <p
                        class="text-xs font-medium uppercase tracking-[0.28em] text-zinc-600"
                    >
                        Sales Performance
                    </p>

                    <h3 class="mt-2 text-2xl font-semibold text-white">
                        Week, month, and year
                    </h3>

                    <p class="mt-2 text-sm text-zinc-500">
                        Net profit is sales minus capital cost and expenses.
                    </p>
                </div>

                <div class="grid gap-4 xl:grid-cols-3">
                    <div
                        v-for="card in performanceCards"
                        :key="card.label"
                        class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5 transition hover:border-white/20 sm:p-6"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p
                                    class="text-xs font-bold uppercase tracking-[0.22em] text-zinc-600"
                                >
                                    {{ card.label }}
                                </p>

                                <p class="mt-2 text-xs leading-5 text-zinc-500">
                                    {{ card.period }}
                                </p>
                            </div>

                            <div
                                class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-xs font-semibold text-zinc-400"
                            >
                                {{ card.sold }} sold
                            </div>
                        </div>

                        <div class="mt-5">
                            <p class="text-sm text-zinc-500">Net Profit</p>

                            <p
                                class="mt-2 text-3xl font-semibold tracking-tight"
                                :class="netProfitClass(card.netProfitValue)"
                            >
                                {{ card.compactNetProfit }}
                            </p>

                            <p class="mt-1 text-xs text-zinc-600">
                                {{ card.netProfit }}
                            </p>
                        </div>

                        <div class="mt-5 grid grid-cols-2 gap-3">
                            <div class="mn-mini-card">
                                <p class="mn-mini-label">Sales</p>
                                <p class="mn-mini-value">
                                    {{ card.compactSales }}
                                </p>
                            </div>

                            <div class="mn-mini-card">
                                <p class="mn-mini-label">Gross</p>
                                <p class="mn-mini-value text-emerald-300">
                                    {{ card.compactGrossProfit }}
                                </p>
                            </div>

                            <div class="mn-mini-card">
                                <p class="mn-mini-label">Capital</p>
                                <p class="mn-mini-value">
                                    {{ card.capital }}
                                </p>
                            </div>

                            <div class="mn-mini-card">
                                <p class="mn-mini-label">Expenses</p>
                                <p class="mn-mini-value text-red-300">
                                    {{ card.expenses }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- TOP SOLD + RECENT SALES -->
            <section
                id="recent-sales"
                class="grid gap-5 xl:grid-cols-[0.85fr_1.15fr]"
            >
                <!-- TOP SOLD -->
                <div
                    class="overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D]"
                >
                    <div class="border-b border-white/10 p-5 sm:p-6">
                        <p
                            class="text-xs font-medium uppercase tracking-[0.28em] text-zinc-600"
                        >
                            Ranking
                        </p>

                        <h3 class="mt-2 text-xl font-semibold text-white">
                            Top 5 Most Sold Units
                        </h3>
                    </div>

                    <div class="divide-y divide-white/10">
                        <div
                            v-for="(unit, index) in topSoldUnits"
                            :key="`${unit.brand}-${unit.model_name}-${unit.reference_number}`"
                            class="p-5 transition hover:bg-white/[0.02]"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white text-xs font-black text-black"
                                        >
                                            {{ index + 1 }}
                                        </span>

                                        <p
                                            class="truncate text-sm font-semibold text-white"
                                        >
                                            {{ unit.brand }}
                                            {{ unit.model_name }}
                                        </p>
                                    </div>

                                    <p
                                        class="mt-2 truncate text-xs text-zinc-500"
                                    >
                                        Ref.
                                        {{
                                            unit.reference_number ||
                                            "No reference"
                                        }}
                                    </p>
                                </div>

                                <div
                                    class="shrink-0 rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-xs font-medium text-zinc-300"
                                >
                                    {{ unit.sold_count }} sold
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <div class="mn-mini-card">
                                    <p class="mn-mini-label">Sales</p>
                                    <p class="mn-mini-value">
                                        {{ compactPeso(unit.sales_total) }}
                                    </p>
                                    <p class="mt-1 text-xs text-zinc-600">
                                        {{ peso(unit.sales_total) }}
                                    </p>
                                </div>

                                <div class="mn-mini-card">
                                    <p class="mn-mini-label">Profit</p>
                                    <p class="mn-mini-value text-emerald-300">
                                        {{ compactPeso(unit.profit_total) }}
                                    </p>
                                    <p class="mt-1 text-xs text-zinc-600">
                                        {{ peso(unit.profit_total) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="!topSoldUnits.length"
                            class="p-10 text-center"
                        >
                            <p class="text-sm font-medium text-white">
                                No sold units yet.
                            </p>

                            <p class="mt-2 text-sm text-zinc-500">
                                Mark watches as sold to generate your top
                                selling list.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- RECENT SALES -->
                <div
                    class="overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D]"
                >
                    <div class="border-b border-white/10 p-5 sm:p-6">
                        <p
                            class="text-xs font-medium uppercase tracking-[0.28em] text-zinc-600"
                        >
                            Recent Activity
                        </p>

                        <h3 class="mt-2 text-xl font-semibold text-white">
                            Recent Sold Watches
                        </h3>
                    </div>

                    <!-- MOBILE CARDS -->
                    <div class="divide-y divide-white/10 md:hidden">
                        <div
                            v-for="sale in recentSales"
                            :key="sale.id"
                            class="p-5"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="h-16 w-16 shrink-0 overflow-hidden rounded-2xl border border-white/10 bg-[#050505]"
                                >
                                    <img
                                        v-if="sale.primary_image"
                                        :src="sale.primary_image.image_url"
                                        class="h-full w-full object-cover"
                                        alt=""
                                    />

                                    <div
                                        v-else
                                        class="flex h-full w-full items-center justify-center text-xs font-semibold text-zinc-600"
                                    >
                                        MN
                                    </div>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p
                                        class="truncate text-sm font-semibold text-white"
                                    >
                                        {{ sale.brand }} {{ sale.model_name }}
                                    </p>

                                    <p
                                        class="mt-1 truncate text-xs text-zinc-500"
                                    >
                                        Ref.
                                        {{
                                            sale.reference_number ||
                                            "No reference"
                                        }}
                                    </p>

                                    <p class="mt-1 text-xs text-zinc-600">
                                        {{ formatDate(sale.date_sold) }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <div class="mn-mini-card">
                                    <p class="mn-mini-label">Sold Price</p>
                                    <p class="mn-mini-value">
                                        {{ compactPeso(sale.sold_price) }}
                                    </p>
                                </div>

                                <div class="mn-mini-card">
                                    <p class="mn-mini-label">Profit</p>
                                    <p
                                        class="mn-mini-value"
                                        :class="netProfitClass(sale.profit)"
                                    >
                                        {{ compactPeso(sale.profit) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="!recentSales.length"
                            class="p-10 text-center"
                        >
                            <p class="text-sm font-medium text-white">
                                No sold watches yet.
                            </p>

                            <p class="mt-2 text-sm text-zinc-500">
                                Change a watch status to sold to show it here.
                            </p>
                        </div>
                    </div>

                    <!-- DESKTOP TABLE -->
                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-full divide-y divide-white/10">
                            <thead>
                                <tr class="bg-white/[0.02]">
                                    <th class="mn-th">Watch</th>
                                    <th class="mn-th">Date Sold</th>
                                    <th class="mn-th">Sold Price</th>
                                    <th class="mn-th">Profit</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-white/10">
                                <tr
                                    v-for="sale in recentSales"
                                    :key="sale.id"
                                    class="transition hover:bg-white/[0.02]"
                                >
                                    <td class="px-6 py-5">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="h-14 w-14 shrink-0 overflow-hidden rounded-2xl border border-white/10 bg-[#050505]"
                                            >
                                                <img
                                                    v-if="sale.primary_image"
                                                    :src="
                                                        sale.primary_image
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
                                            </div>

                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-white"
                                                >
                                                    {{ sale.brand }}
                                                    {{ sale.model_name }}
                                                </p>

                                                <p
                                                    class="mt-1 text-xs text-zinc-500"
                                                >
                                                    Ref.
                                                    {{
                                                        sale.reference_number ||
                                                        "No reference"
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5 text-sm text-zinc-400">
                                        {{ formatDate(sale.date_sold) }}
                                    </td>

                                    <td
                                        class="px-6 py-5 text-sm font-semibold text-white"
                                    >
                                        {{ peso(sale.sold_price) }}
                                    </td>

                                    <td
                                        class="px-6 py-5 text-sm font-semibold"
                                        :class="netProfitClass(sale.profit)"
                                    >
                                        {{ peso(sale.profit) }}
                                    </td>
                                </tr>

                                <tr v-if="!recentSales.length">
                                    <td
                                        colspan="4"
                                        class="px-6 py-16 text-center"
                                    >
                                        <p
                                            class="text-sm font-medium text-white"
                                        >
                                            No sold watches yet.
                                        </p>

                                        <p class="mt-2 text-sm text-zinc-500">
                                            Change a watch status to sold to
                                            show it here.
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.mn-input {
    width: 100%;
    border-radius: 1rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: #050505;
    padding: 0.85rem 1rem;
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

.mn-mini-card {
    border-radius: 1rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: rgb(255 255 255 / 0.03);
    padding: 1rem;
}

.mn-mini-label {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.16em;
    color: rgb(113 113 122);
}

.mn-mini-value {
    margin-top: 0.45rem;
    font-size: 0.95rem;
    font-weight: 700;
    color: white;
}

.mn-th {
    padding: 1rem 1.5rem;
    text-align: left;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.22em;
    color: rgb(82 82 91);
}
</style>
