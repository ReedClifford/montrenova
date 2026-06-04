<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
    money: {
        type: Object,
        default: () => ({
            starting_cash: 0,
            current_money: 0,
            total_capital_spent: 0,
            inventory_value: 0,
            total_sales: 0,
            sold_capital_cost: 0,
            gross_profit: 0,
            total_expenses: 0,
            net_profit: 0,
        }),
    },
    selectedMonth: {
        type: String,
        default: "",
    },
    selectedMonthSummary: {
        type: Object,
        default: () => ({
            label: "",
            sold_count: 0,
            total_sales: 0,
            total_capital: 0,
            gross_profit: 0,
            total_expenses: 0,
            net_profit: 0,
        }),
    },
    counts: {
        type: Object,
        default: () => ({
            total_watches: 0,
            available_watches: 0,
            reserved_watches: 0,
            sold_watches: 0,
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
    recentExpenses: {
        type: Array,
        default: () => [],
    },
});

const showCashModal = ref(false);
const showExpenseModal = ref(false);

const selectedMonthFilter = ref(
    props.selectedMonth || new Date().toISOString().slice(0, 7),
);

const cashForm = useForm({
    starting_cash: props.money.starting_cash || 0,
});

const expenseForm = useForm({
    title: "",
    category: "",
    amount: "",
    spent_at: "",
    notes: "",
});

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
        notation: amount >= 100000 ? "compact" : "standard",
        maximumFractionDigits: amount >= 100000 ? 1 : 0,
    }).format(amount);
};

const netProfitClass = computed(() => {
    return Number(props.money.net_profit || 0) >= 0
        ? "text-emerald-300"
        : "text-red-300";
});

const currentMoneyClass = computed(() => {
    return Number(props.money.current_money || 0) >= 0
        ? "text-white"
        : "text-red-300";
});

const selectedMonthNetClass = computed(() => {
    return Number(props.selectedMonthSummary.net_profit || 0) >= 0
        ? "text-emerald-300"
        : "text-red-300";
});

const expenseRatio = computed(() => {
    const sales = Number(props.money.total_sales || 0);
    const expenses = Number(props.money.total_expenses || 0);

    if (sales <= 0) return 0;

    return (expenses / sales) * 100;
});

const inventoryRatio = computed(() => {
    const startingCash = Number(props.money.starting_cash || 0);
    const inventoryValue = Number(props.money.inventory_value || 0);

    if (startingCash <= 0) return 0;

    return (inventoryValue / startingCash) * 100;
});

const performanceCards = computed(() => [
    {
        label: "This Week",
        period: props.salesPerformance.weekly?.label || "",
        sold: props.salesPerformance.weekly?.sold_count || 0,
        sales: peso(props.salesPerformance.weekly?.total_sales || 0),
        grossProfit: peso(props.salesPerformance.weekly?.gross_profit || 0),
        expenses: peso(props.salesPerformance.weekly?.total_expenses || 0),
        netProfit: peso(props.salesPerformance.weekly?.net_profit || 0),
        netProfitValue: Number(props.salesPerformance.weekly?.net_profit || 0),
    },
    {
        label: "This Month",
        period: props.salesPerformance.monthly?.label || "",
        sold: props.salesPerformance.monthly?.sold_count || 0,
        sales: peso(props.salesPerformance.monthly?.total_sales || 0),
        grossProfit: peso(props.salesPerformance.monthly?.gross_profit || 0),
        expenses: peso(props.salesPerformance.monthly?.total_expenses || 0),
        netProfit: peso(props.salesPerformance.monthly?.net_profit || 0),
        netProfitValue: Number(props.salesPerformance.monthly?.net_profit || 0),
    },
    {
        label: "This Year",
        period: props.salesPerformance.yearly?.label || "",
        sold: props.salesPerformance.yearly?.sold_count || 0,
        sales: peso(props.salesPerformance.yearly?.total_sales || 0),
        grossProfit: peso(props.salesPerformance.yearly?.gross_profit || 0),
        expenses: peso(props.salesPerformance.yearly?.total_expenses || 0),
        netProfit: peso(props.salesPerformance.yearly?.net_profit || 0),
        netProfitValue: Number(props.salesPerformance.yearly?.net_profit || 0),
    },
]);

const heroStats = computed(() => [
    {
        label: "Net Profit",
        value: peso(props.money.net_profit),
        valueClass: netProfitClass.value,
    },
    {
        label: "Inventory Value",
        value: peso(props.money.inventory_value),
        valueClass: "text-white",
    },
    {
        label: "Total Sales",
        value: peso(props.money.total_sales),
        valueClass: "text-white",
    },
]);

const moneyCards = computed(() => [
    {
        label: "Current Money",
        value: peso(props.money.current_money),
        compactValue: compactPeso(props.money.current_money),
        helper: "Estimated cash after sales, capital, and expenses",
        valueClass: currentMoneyClass.value,
    },
    {
        label: "Net Profit",
        value: peso(props.money.net_profit),
        compactValue: compactPeso(props.money.net_profit),
        helper: "Sales minus sold capital cost and expenses",
        valueClass: netProfitClass.value,
    },
    {
        label: "Total Sales",
        value: peso(props.money.total_sales),
        compactValue: compactPeso(props.money.total_sales),
        helper: "Total value from watches marked as sold",
        valueClass: "text-white",
    },
    {
        label: "Total Expenses",
        value: peso(props.money.total_expenses),
        compactValue: compactPeso(props.money.total_expenses),
        helper: "Ads, transpo, packaging, repairs, and others",
        valueClass: "text-red-300",
    },
]);

const inventoryCards = computed(() => [
    {
        label: "Capital Spent",
        value: peso(props.money.total_capital_spent),
        compactValue: compactPeso(props.money.total_capital_spent),
        helper: "Total capital encoded across all watches",
    },
    {
        label: "Inventory Value",
        value: peso(props.money.inventory_value),
        compactValue: compactPeso(props.money.inventory_value),
        helper: "Capital value of unsold watches",
    },
    {
        label: "Gross Profit",
        value: peso(props.money.gross_profit),
        compactValue: compactPeso(props.money.gross_profit),
        helper: "Sales minus capital cost of sold watches",
    },
    {
        label: "Starting Cash",
        value: peso(props.money.starting_cash),
        compactValue: compactPeso(props.money.starting_cash),
        helper: "Your initial business cash setting",
    },
]);

const watchCards = computed(() => [
    {
        label: "Total Watches",
        value: props.counts.total_watches,
        helper: "All encoded watch stocks",
        className: "text-white",
    },
    {
        label: "Available",
        value: props.counts.available_watches,
        helper: "Ready for buyers",
        className: "text-emerald-300",
    },
    {
        label: "Reserved",
        value: props.counts.reserved_watches,
        helper: "Pending payment",
        className: "text-amber-300",
    },
    {
        label: "Sold",
        value: props.counts.sold_watches,
        helper: "Completed sales",
        className: "text-zinc-300",
    },
]);

const insightCards = computed(() => [
    {
        label: "Cash Health",
        value:
            Number(props.money.current_money || 0) >= 0
                ? "Healthy"
                : "Needs Attention",
        helper:
            Number(props.money.current_money || 0) >= 0
                ? "Your estimated cash is positive."
                : "Your estimated cash is below zero.",
        className:
            Number(props.money.current_money || 0) >= 0
                ? "border-emerald-500/20 bg-emerald-500/10 text-emerald-300"
                : "border-red-500/20 bg-red-500/10 text-red-300",
    },
    {
        label: "Expense Ratio",
        value: `${expenseRatio.value.toFixed(1)}%`,
        helper: "Total expenses compared to total sales.",
        className:
            expenseRatio.value <= 20
                ? "border-emerald-500/20 bg-emerald-500/10 text-emerald-300"
                : "border-amber-500/20 bg-amber-500/10 text-amber-300",
    },
    {
        label: "Inventory Load",
        value: `${inventoryRatio.value.toFixed(1)}%`,
        helper: "Unsold inventory compared to starting cash.",
        className:
            inventoryRatio.value <= 60
                ? "border-emerald-500/20 bg-emerald-500/10 text-emerald-300"
                : "border-amber-500/20 bg-amber-500/10 text-amber-300",
    },
]);

const applyMonthFilter = () => {
    router.get(
        route("dashboard"),
        {
            month: selectedMonthFilter.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const updateStartingCash = () => {
    cashForm.patch(route("admin.dashboard.starting-cash.update"), {
        preserveScroll: true,
        onSuccess: () => {
            showCashModal.value = false;
        },
    });
};

const addExpense = () => {
    expenseForm.post(route("admin.dashboard.expenses.store"), {
        preserveScroll: true,
        onSuccess: () => {
            expenseForm.reset();
            showExpenseModal.value = false;
        },
    });
};

const deleteExpense = (expense) => {
    if (!confirm(`Delete expense "${expense.title}"?`)) return;

    router.delete(route("admin.dashboard.expenses.destroy", expense.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Dashboard | Montre Nova" />

    <AuthenticatedLayout title="Dashboard">
        <div class="space-y-6 sm:space-y-8">
            <!-- MOBILE QUICK ACTIONS -->
            <section class="grid grid-cols-2 gap-3 sm:hidden">
                <button
                    type="button"
                    class="rounded-2xl bg-white px-4 py-3 text-sm font-bold text-black"
                    @click="showExpenseModal = true"
                >
                    Add Expense
                </button>

                <button
                    type="button"
                    class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm font-bold text-white"
                    @click="showCashModal = true"
                >
                    Cash Setup
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
                        class="absolute bottom-[-12rem] left-[20%] h-[28rem] w-[28rem] rounded-full bg-zinc-700/10 blur-3xl"
                    ></div>
                </div>

                <div
                    class="relative grid gap-5 lg:grid-cols-[1fr_0.45fr] lg:items-center"
                >
                    <div>
                        <p
                            class="text-xs font-medium uppercase tracking-[0.28em] text-zinc-500"
                        >
                            Montre Nova Finance Overview
                        </p>

                        <h2
                            class="mt-4 max-w-3xl text-3xl font-semibold tracking-tight text-white sm:text-5xl"
                        >
                            Track cash, profit, inventory, and sales.
                        </h2>

                        <p
                            class="mt-4 max-w-2xl text-sm leading-7 text-zinc-400 sm:text-base"
                        >
                            A cleaner overview of your watch business: money,
                            expenses, sales performance, inventory value, and
                            top-selling units.
                        </p>

                        <div
                            class="mt-6 hidden flex-col gap-3 sm:flex sm:flex-row"
                        >
                            <button
                                type="button"
                                class="rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200"
                                @click="showCashModal = true"
                            >
                                Update Starting Cash
                            </button>

                            <button
                                type="button"
                                class="rounded-2xl border border-white/10 bg-white/[0.03] px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/[0.06]"
                                @click="showExpenseModal = true"
                            >
                                Add Expense
                            </button>
                        </div>
                    </div>

                    <div
                        class="rounded-[1.5rem] border border-white/10 bg-white/[0.03] p-5 sm:p-6"
                    >
                        <p
                            class="text-xs uppercase tracking-[0.24em] text-zinc-600"
                        >
                            Current Money
                        </p>

                        <p
                            class="mt-3 text-4xl font-semibold tracking-tight sm:text-5xl"
                            :class="currentMoneyClass"
                        >
                            {{ compactPeso(money.current_money) }}
                        </p>

                        <p class="mt-2 text-sm text-zinc-500">
                            {{ peso(money.current_money) }}
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

            <!-- MONTH FILTER -->
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
                            Monthly Snapshot
                        </p>

                        <h3 class="mt-2 text-2xl font-semibold text-white">
                            {{ selectedMonthSummary.label || "Selected Month" }}
                        </h3>

                        <p class="mt-2 text-sm text-zinc-500">
                            Review monthly sales, expenses, and net profit.
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
                            Apply
                        </button>
                    </form>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    <div class="mn-mini-card">
                        <p class="mn-mini-label">Sold</p>
                        <p class="mn-mini-value">
                            {{ selectedMonthSummary.sold_count || 0 }}
                        </p>
                    </div>

                    <div class="mn-mini-card">
                        <p class="mn-mini-label">Sales</p>
                        <p class="mn-mini-value">
                            {{ compactPeso(selectedMonthSummary.total_sales) }}
                        </p>
                    </div>

                    <div class="mn-mini-card">
                        <p class="mn-mini-label">Gross Profit</p>
                        <p class="mn-mini-value text-emerald-300">
                            {{ compactPeso(selectedMonthSummary.gross_profit) }}
                        </p>
                    </div>

                    <div class="mn-mini-card">
                        <p class="mn-mini-label">Expenses</p>
                        <p class="mn-mini-value text-red-300">
                            {{
                                compactPeso(selectedMonthSummary.total_expenses)
                            }}
                        </p>
                    </div>

                    <div class="mn-mini-card sm:col-span-2 xl:col-span-1">
                        <p class="mn-mini-label">Net Profit</p>
                        <p class="mn-mini-value" :class="selectedMonthNetClass">
                            {{ compactPeso(selectedMonthSummary.net_profit) }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- MAIN MONEY CARDS -->
            <section>
                <div class="mb-4">
                    <p
                        class="text-xs font-medium uppercase tracking-[0.28em] text-zinc-600"
                    >
                        Financial Summary
                    </p>

                    <h3 class="mt-2 text-2xl font-semibold text-white">
                        Money overview
                    </h3>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div
                        v-for="card in moneyCards"
                        :key="card.label"
                        class="rounded-[1.5rem] border border-white/10 bg-[#0B0B0D] p-5 transition hover:border-white/20 sm:p-6"
                    >
                        <p
                            class="text-xs font-medium uppercase tracking-[0.22em] text-zinc-600"
                        >
                            {{ card.label }}
                        </p>

                        <p
                            class="mt-4 text-3xl font-semibold tracking-tight"
                            :class="card.valueClass"
                        >
                            {{ card.compactValue }}
                        </p>

                        <p class="mt-1 text-xs text-zinc-600">
                            {{ card.value }}
                        </p>

                        <p
                            class="mt-4 border-t border-white/10 pt-4 text-sm leading-6 text-zinc-500"
                        >
                            {{ card.helper }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- SALES PERFORMANCE -->
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
                </div>

                <div class="grid gap-3 lg:grid-cols-3">
                    <div
                        v-for="card in performanceCards"
                        :key="card.label"
                        class="rounded-[1.5rem] border border-white/10 bg-[#0B0B0D] p-5 sm:p-6"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p
                                    class="text-xs font-bold uppercase tracking-[0.22em] text-zinc-600"
                                >
                                    {{ card.label }}
                                </p>

                                <p class="mt-2 text-sm text-zinc-500">
                                    {{ card.period }}
                                </p>
                            </div>

                            <div
                                class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-xs font-semibold text-zinc-400"
                            >
                                {{ card.sold }} sold
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-2 gap-3">
                            <div class="mn-mini-card">
                                <p class="mn-mini-label">Sales</p>
                                <p class="mn-mini-value">
                                    {{ card.sales }}
                                </p>
                            </div>

                            <div class="mn-mini-card">
                                <p class="mn-mini-label">Gross</p>
                                <p class="mn-mini-value text-emerald-300">
                                    {{ card.grossProfit }}
                                </p>
                            </div>

                            <div class="mn-mini-card">
                                <p class="mn-mini-label">Expenses</p>
                                <p class="mn-mini-value text-red-300">
                                    {{ card.expenses }}
                                </p>
                            </div>

                            <div class="mn-mini-card">
                                <p class="mn-mini-label">Net</p>
                                <p
                                    class="mn-mini-value"
                                    :class="
                                        card.netProfitValue >= 0
                                            ? 'text-emerald-300'
                                            : 'text-red-300'
                                    "
                                >
                                    {{ card.netProfit }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- INVENTORY + WATCH COUNTS -->
            <section class="grid gap-5 xl:grid-cols-[1fr_0.8fr]">
                <div
                    class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5 sm:p-6"
                >
                    <div class="mb-5">
                        <p
                            class="text-xs font-medium uppercase tracking-[0.28em] text-zinc-600"
                        >
                            Inventory Money
                        </p>

                        <h3 class="mt-2 text-xl font-semibold text-white">
                            Capital and stock value
                        </h3>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div
                            v-for="card in inventoryCards"
                            :key="card.label"
                            class="rounded-[1.4rem] border border-white/10 bg-white/[0.03] p-4"
                        >
                            <p class="mn-mini-label">
                                {{ card.label }}
                            </p>

                            <p class="mt-2 text-2xl font-semibold text-white">
                                {{ card.compactValue }}
                            </p>

                            <p class="mt-1 text-xs text-zinc-600">
                                {{ card.value }}
                            </p>

                            <p class="mt-3 text-xs leading-5 text-zinc-500">
                                {{ card.helper }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5 sm:p-6"
                >
                    <div class="mb-5">
                        <p
                            class="text-xs font-medium uppercase tracking-[0.28em] text-zinc-600"
                        >
                            Watch Inventory
                        </p>

                        <h3 class="mt-2 text-xl font-semibold text-white">
                            Stock status
                        </h3>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div
                            v-for="card in watchCards"
                            :key="card.label"
                            class="rounded-[1.4rem] border border-white/10 bg-white/[0.03] p-4"
                        >
                            <p class="mn-mini-label">
                                {{ card.label }}
                            </p>

                            <p
                                class="mt-2 text-4xl font-semibold tracking-tight"
                                :class="card.className"
                            >
                                {{ card.value }}
                            </p>

                            <p class="mt-2 text-xs leading-5 text-zinc-500">
                                {{ card.helper }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- TOP SOLD + EXPENSES -->
            <section class="grid gap-5 xl:grid-cols-[1fr_0.75fr]">
                <!-- TOP 5 SOLD UNITS -->
                <div
                    class="overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D]"
                >
                    <div class="border-b border-white/10 p-5 sm:p-6">
                        <p
                            class="text-xs font-medium uppercase tracking-[0.28em] text-zinc-600"
                        >
                            Best Sellers
                        </p>

                        <h3 class="mt-2 text-xl font-semibold text-white">
                            Top 5 Most Sold Units
                        </h3>
                    </div>

                    <!-- MOBILE CARDS -->
                    <div class="divide-y divide-white/10 md:hidden">
                        <div
                            v-for="unit in topSoldUnits"
                            :key="`${unit.brand}-${unit.model_name}-${unit.reference_number}`"
                            class="p-5"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold text-white">
                                        {{ unit.brand }} {{ unit.model_name }}
                                    </p>

                                    <p class="mt-1 text-xs text-zinc-500">
                                        Ref.
                                        {{
                                            unit.reference_number ||
                                            "No reference"
                                        }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-xs font-semibold text-zinc-300"
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
                                </div>

                                <div class="mn-mini-card">
                                    <p class="mn-mini-label">Profit</p>
                                    <p class="mn-mini-value text-emerald-300">
                                        {{ compactPeso(unit.profit_total) }}
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

                    <!-- DESKTOP TABLE -->
                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-full divide-y divide-white/10">
                            <thead>
                                <tr class="bg-white/[0.02]">
                                    <th class="mn-th">Unit</th>
                                    <th class="mn-th">Sold</th>
                                    <th class="mn-th">Sales</th>
                                    <th class="mn-th">Profit</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-white/10">
                                <tr
                                    v-for="unit in topSoldUnits"
                                    :key="`${unit.brand}-${unit.model_name}-${unit.reference_number}`"
                                    class="transition hover:bg-white/[0.02]"
                                >
                                    <td class="px-6 py-5">
                                        <p
                                            class="text-sm font-semibold text-white"
                                        >
                                            {{ unit.brand }}
                                            {{ unit.model_name }}
                                        </p>

                                        <p class="mt-1 text-xs text-zinc-500">
                                            Ref.
                                            {{
                                                unit.reference_number ||
                                                "No reference"
                                            }}
                                        </p>
                                    </td>

                                    <td
                                        class="px-6 py-5 text-sm font-semibold text-white"
                                    >
                                        {{ unit.sold_count }}
                                    </td>

                                    <td class="px-6 py-5 text-sm text-zinc-300">
                                        {{ peso(unit.sales_total) }}
                                    </td>

                                    <td
                                        class="px-6 py-5 text-sm text-emerald-300"
                                    >
                                        {{ peso(unit.profit_total) }}
                                    </td>
                                </tr>

                                <tr v-if="!topSoldUnits.length">
                                    <td
                                        colspan="4"
                                        class="px-6 py-14 text-center"
                                    >
                                        <p
                                            class="text-sm font-medium text-white"
                                        >
                                            No sold units yet.
                                        </p>

                                        <p class="mt-2 text-sm text-zinc-500">
                                            Mark watches as sold to generate
                                            your top selling list.
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- RECENT EXPENSES -->
                <div
                    class="overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D]"
                >
                    <div
                        class="flex items-center justify-between gap-4 border-b border-white/10 p-5 sm:p-6"
                    >
                        <div>
                            <p
                                class="text-xs font-medium uppercase tracking-[0.28em] text-zinc-600"
                            >
                                Expenses
                            </p>

                            <h3 class="mt-2 text-xl font-semibold text-white">
                                Recent Expenses
                            </h3>
                        </div>

                        <button
                            type="button"
                            class="rounded-2xl bg-white px-4 py-2 text-sm font-semibold text-black transition hover:bg-zinc-200"
                            @click="showExpenseModal = true"
                        >
                            Add
                        </button>
                    </div>

                    <div class="divide-y divide-white/10">
                        <div
                            v-for="expense in recentExpenses"
                            :key="expense.id"
                            class="flex items-start justify-between gap-4 p-5 transition hover:bg-white/[0.02]"
                        >
                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-semibold text-white"
                                >
                                    {{ expense.title }}
                                </p>

                                <p class="mt-1 text-xs text-zinc-500">
                                    {{ expense.category || "General" }}
                                    <span v-if="expense.spent_at">
                                        • {{ expense.spent_at }}
                                    </span>
                                </p>

                                <p
                                    v-if="expense.notes"
                                    class="mt-2 line-clamp-2 text-xs leading-5 text-zinc-600"
                                >
                                    {{ expense.notes }}
                                </p>
                            </div>

                            <div class="shrink-0 text-right">
                                <p class="text-sm font-semibold text-red-300">
                                    {{ peso(expense.amount) }}
                                </p>

                                <button
                                    type="button"
                                    class="mt-2 text-xs font-medium text-zinc-500 transition hover:text-red-300"
                                    @click="deleteExpense(expense)"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="!recentExpenses.length"
                            class="p-10 text-center"
                        >
                            <p class="text-sm font-medium text-white">
                                No expenses yet.
                            </p>

                            <p class="mt-2 text-sm text-zinc-500">
                                Add ads, transpo, packaging, and other business
                                costs.
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- STARTING CASH MODAL -->
        <Teleport to="body">
            <div
                v-if="showCashModal"
                class="fixed inset-0 z-[999] flex items-end justify-center bg-black/80 px-3 py-3 backdrop-blur-sm sm:items-center sm:px-4 sm:py-6"
            >
                <div
                    class="absolute inset-0"
                    @click="showCashModal = false"
                ></div>

                <form
                    @submit.prevent="updateStartingCash"
                    class="relative w-full max-w-md rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5 shadow-2xl shadow-black sm:rounded-[2rem] sm:p-6"
                >
                    <p class="text-xs uppercase tracking-[0.3em] text-zinc-600">
                        Business Cash
                    </p>

                    <h2
                        class="mt-3 text-2xl font-semibold tracking-tight text-white"
                    >
                        Update Starting Cash
                    </h2>

                    <p class="mt-3 text-sm leading-6 text-zinc-400">
                        This is your base cash. The dashboard uses it to
                        estimate current money.
                    </p>

                    <div class="mt-6">
                        <label class="mn-label">Starting Cash</label>

                        <input
                            v-model="cashForm.starting_cash"
                            type="number"
                            step="0.01"
                            class="mn-input"
                            placeholder="0.00"
                        />

                        <p
                            v-if="cashForm.errors.starting_cash"
                            class="mt-2 text-sm text-red-300"
                        >
                            {{ cashForm.errors.starting_cash }}
                        </p>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30"
                            @click="showCashModal = false"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            :disabled="cashForm.processing"
                            class="rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200 disabled:opacity-60"
                        >
                            {{ cashForm.processing ? "Saving..." : "Save" }}
                        </button>
                    </div>
                </form>
            </div>
        </Teleport>

        <!-- EXPENSE MODAL -->
        <Teleport to="body">
            <div
                v-if="showExpenseModal"
                class="fixed inset-0 z-[999] flex items-end justify-center bg-black/80 px-3 py-3 backdrop-blur-sm sm:items-center sm:px-4 sm:py-6"
            >
                <div
                    class="absolute inset-0"
                    @click="showExpenseModal = false"
                ></div>

                <form
                    @submit.prevent="addExpense"
                    class="relative max-h-[92vh] w-full max-w-xl overflow-y-auto rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5 shadow-2xl shadow-black sm:rounded-[2rem] sm:p-6"
                >
                    <p class="text-xs uppercase tracking-[0.3em] text-zinc-600">
                        Business Expense
                    </p>

                    <h2
                        class="mt-3 text-2xl font-semibold tracking-tight text-white"
                    >
                        Add Expense
                    </h2>

                    <p class="mt-3 text-sm leading-6 text-zinc-400">
                        Record ads, transportation, packaging, paperbags,
                        repairs, cleaning, or other costs.
                    </p>

                    <div class="mt-6 grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="mn-label">Expense Title</label>

                            <input
                                v-model="expenseForm.title"
                                class="mn-input"
                                placeholder="Facebook Ads"
                            />

                            <p
                                v-if="expenseForm.errors.title"
                                class="mt-2 text-sm text-red-300"
                            >
                                {{ expenseForm.errors.title }}
                            </p>
                        </div>

                        <div>
                            <label class="mn-label">Category</label>

                            <input
                                v-model="expenseForm.category"
                                class="mn-input"
                                placeholder="Ads, Transpo, Packaging"
                            />
                        </div>

                        <div>
                            <label class="mn-label">Amount</label>

                            <input
                                v-model="expenseForm.amount"
                                type="number"
                                step="0.01"
                                class="mn-input"
                                placeholder="0.00"
                            />

                            <p
                                v-if="expenseForm.errors.amount"
                                class="mt-2 text-sm text-red-300"
                            >
                                {{ expenseForm.errors.amount }}
                            </p>
                        </div>

                        <div>
                            <label class="mn-label">Date Spent</label>

                            <input
                                v-model="expenseForm.spent_at"
                                type="date"
                                class="mn-input"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <label class="mn-label">Notes</label>

                            <textarea
                                v-model="expenseForm.notes"
                                rows="4"
                                class="mn-input"
                                placeholder="Optional notes..."
                            ></textarea>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30"
                            @click="showExpenseModal = false"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            :disabled="expenseForm.processing"
                            class="rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200 disabled:opacity-60"
                        >
                            {{
                                expenseForm.processing
                                    ? "Saving..."
                                    : "Save Expense"
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.mn-label {
    margin-bottom: 0.5rem;
    display: block;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    color: rgb(113 113 122);
}

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
