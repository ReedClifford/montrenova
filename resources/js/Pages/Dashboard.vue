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
            current_onhand_money: 0,
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
    salesProfitTrend: {
        type: Object,
        default: () => ({
            labels: [],
            sales: [],
            profit: [],
            sold_counts: [],
            rows: [],
            max_value: 0,
            totals: {
                months: 12,
                sold_count: 0,
                total_sales: 0,
                total_capital: 0,
                gross_profit: 0,
                best_month: null,
            },
        }),
    },
    topSoldUnits: {
        type: Array,
        default: () => [],
    },
    topProfitingWatches: {
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

const currentOnhandMoney = computed(() => {
    return Number(
        props.money.current_onhand_money ?? props.money.current_money ?? 0,
    );
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
    const shouldCompact = Math.abs(amount) >= 100000;

    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        notation: shouldCompact ? "compact" : "standard",
        maximumFractionDigits: shouldCompact ? 1 : 0,
    }).format(amount);
};

const netProfitClass = computed(() => {
    return Number(props.money.net_profit || 0) >= 0
        ? "text-emerald-300"
        : "text-red-300";
});

const currentOnhandMoneyClass = computed(() => {
    return currentOnhandMoney.value >= 0 ? "text-white" : "text-red-300";
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

const trendChartWidth = 900;
const trendChartHeight = 320;
const trendPadding = {
    top: 28,
    right: 28,
    bottom: 50,
    left: 78,
};

const trendPlotWidth = trendChartWidth - trendPadding.left - trendPadding.right;
const trendPlotHeight =
    trendChartHeight - trendPadding.top - trendPadding.bottom;

const salesProfitTrendRows = computed(() => {
    return Array.isArray(props.salesProfitTrend?.rows)
        ? props.salesProfitTrend.rows
        : [];
});

const trendMonthsLabel = computed(() => {
    return `${props.salesProfitTrend?.totals?.months || salesProfitTrendRows.value.length || 12}M`;
});

const trendTotalSales = computed(() => {
    return salesProfitTrendRows.value.reduce((total, row) => {
        return total + Number(row.total_sales || 0);
    }, 0);
});

const trendTotalProfit = computed(() => {
    return salesProfitTrendRows.value.reduce((total, row) => {
        return total + Number(row.gross_profit || 0);
    }, 0);
});

const trendTotalCapital = computed(() => {
    return salesProfitTrendRows.value.reduce((total, row) => {
        return total + Number(row.total_capital || 0);
    }, 0);
});

const trendTotalSold = computed(() => {
    return salesProfitTrendRows.value.reduce((total, row) => {
        return total + Number(row.sold_count || 0);
    }, 0);
});

const trendAverageProfitPerSale = computed(() => {
    if (trendTotalSold.value <= 0) return 0;

    return trendTotalProfit.value / trendTotalSold.value;
});

const trendHasSales = computed(() => {
    return salesProfitTrendRows.value.some((row) => {
        return (
            Number(row.sold_count || 0) > 0 ||
            Number(row.total_sales || 0) > 0 ||
            Number(row.gross_profit || 0) !== 0
        );
    });
});

const trendHighestProfitMonth = computed(() => {
    if (!salesProfitTrendRows.value.length) return null;

    return salesProfitTrendRows.value.reduce((best, row) => {
        if (!best) return row;

        return Number(row.gross_profit || 0) > Number(best.gross_profit || 0)
            ? row
            : best;
    }, null);
});

const trendMaxValue = computed(() => {
    const values = salesProfitTrendRows.value.flatMap((row) => [
        Number(row.total_sales || 0),
        Number(row.gross_profit || 0),
    ]);

    const max = Math.max(...values, 0);

    if (max <= 0) return 1;

    return max * 1.12;
});

const trendMinValue = computed(() => {
    const values = salesProfitTrendRows.value.flatMap((row) => [
        Number(row.total_sales || 0),
        Number(row.gross_profit || 0),
    ]);

    const min = Math.min(...values, 0);

    return min < 0 ? min * 1.12 : 0;
});

const trendValueRange = computed(() => {
    return trendMaxValue.value - trendMinValue.value || 1;
});

const trendValueToY = (value) => {
    const numericValue = Number(value || 0);

    return (
        trendPadding.top +
        ((trendMaxValue.value - numericValue) / trendValueRange.value) *
            trendPlotHeight
    );
};

const trendZeroY = computed(() => trendValueToY(0));

const trendGridLines = computed(() => {
    const steps = 4;

    return Array.from({ length: steps + 1 }, (_, index) => {
        const ratio = index / steps;
        const value = trendMaxValue.value - trendValueRange.value * ratio;

        return {
            key: index,
            value,
            y: trendValueToY(value),
        };
    });
});

const buildTrendPoints = (key) => {
    const rows = salesProfitTrendRows.value;
    const count = rows.length;

    if (!count) return [];

    return rows.map((row, index) => {
        const x =
            trendPadding.left +
            (index / Math.max(count - 1, 1)) * trendPlotWidth;
        const y = trendValueToY(row[key]);

        return {
            ...row,
            x,
            y,
            value: Number(row[key] || 0),
            valueLabel: peso(row[key] || 0),
            salesLabel: peso(row.total_sales || 0),
            profitLabel: peso(row.gross_profit || 0),
            capitalLabel: peso(row.total_capital || 0),
        };
    });
};

const salesTrendPoints = computed(() => buildTrendPoints("total_sales"));
const profitTrendPoints = computed(() => buildTrendPoints("gross_profit"));

const trendPointsToString = (points) => {
    return points.map((point) => `${point.x},${point.y}`).join(" ");
};

const trendSummaryCards = computed(() => [
    {
        label: `${trendMonthsLabel.value} Sales`,
        value: compactPeso(trendTotalSales.value),
        fullValue: peso(trendTotalSales.value),
        helper: `${trendTotalSold.value} sold watches`,
        valueClass: "text-white",
    },
    {
        label: `${trendMonthsLabel.value} Gross Profit`,
        value: compactPeso(trendTotalProfit.value),
        fullValue: peso(trendTotalProfit.value),
        helper: `Before business expenses`,
        valueClass:
            trendTotalProfit.value >= 0 ? "text-emerald-300" : "text-red-300",
    },
    {
        label: "Capital Recovered",
        value: compactPeso(trendTotalCapital.value),
        fullValue: peso(trendTotalCapital.value),
        helper: "Capital cost of sold units",
        valueClass: "text-zinc-200",
    },
    {
        label: "Avg Profit / Sale",
        value: compactPeso(trendAverageProfitPerSale.value),
        fullValue: peso(trendAverageProfitPerSale.value),
        helper: trendHighestProfitMonth.value?.label
            ? `Best month: ${trendHighestProfitMonth.value.label}`
            : "No sales yet",
        valueClass:
            trendAverageProfitPerSale.value >= 0
                ? "text-emerald-300"
                : "text-red-300",
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
        label: "Current On-hand Money",
        value: peso(currentOnhandMoney.value),
        compactValue: compactPeso(currentOnhandMoney.value),
        helper: "Cash available after sales, inventory capital, and expenses",
        valueClass: currentOnhandMoneyClass.value,
    },
    {
        label: "Net Profit",
        value: peso(props.money.net_profit),
        compactValue: compactPeso(props.money.net_profit),
        helper: "Total sales minus sold capital cost and expenses",
        valueClass: netProfitClass.value,
    },
    {
        label: "Inventory Value",
        value: peso(props.money.inventory_value),
        compactValue: compactPeso(props.money.inventory_value),
        helper: "Capital value of available and reserved watches",
        valueClass: "text-white",
    },
    {
        label: "Total Expenses",
        value: peso(props.money.total_expenses),
        compactValue: compactPeso(props.money.total_expenses),
        helper: "Ads, transpo, packaging, repairs, and other costs",
        valueClass: "text-red-300",
    },
]);

const inventoryCards = computed(() => [
    {
        label: "Total Capital Spent",
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
        label: "Sold Capital Cost",
        value: peso(props.money.sold_capital_cost),
        compactValue: compactPeso(props.money.sold_capital_cost),
        helper: "Capital cost of watches already sold",
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
        value: currentOnhandMoney.value >= 0 ? "Healthy" : "Needs Attention",
        helper:
            currentOnhandMoney.value >= 0
                ? "Your current on-hand money is positive."
                : "Your current on-hand money is below zero.",
        className:
            currentOnhandMoney.value >= 0
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

const unitSalesAmount = (unit) => {
    return Number(
        unit?.sales_total ??
            unit?.sold_price ??
            unit?.sale_price ??
            unit?.final_sold_price ??
            unit?.discounted_price ??
            unit?.selling_price ??
            0,
    );
};

const unitProfitAmount = (unit) => {
    return Number(
        unit?.profit_total ?? unit?.profit ?? unit?.gross_profit ?? 0,
    );
};

const unitCapitalAmount = (unit) => {
    return Number(
        unit?.capital_total ?? unit?.capital_price ?? unit?.capital_cost ?? 0,
    );
};

const profitMargin = (unit) => {
    const sales = unitSalesAmount(unit);

    if (sales <= 0) return "0.0%";

    return `${((unitProfitAmount(unit) / sales) * 100).toFixed(1)}%`;
};

const formatShortDate = (value) => {
    if (!value) return "";

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return "";

    return date.toLocaleDateString("en-PH", {
        month: "short",
        day: "2-digit",
        year: "numeric",
    });
};

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
                            Track on-hand cash, net profit, inventory, and
                            sales.
                        </h2>

                        <p
                            class="mt-4 max-w-2xl text-sm leading-7 text-zinc-400 sm:text-base"
                        >
                            A cleaner overview of your watch business: current
                            on-hand money, expenses, sales performance,
                            inventory value, and top-selling units.
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
                            Current On-hand Money
                        </p>

                        <p
                            class="mt-3 text-4xl font-semibold tracking-tight sm:text-5xl"
                            :class="currentOnhandMoneyClass"
                        >
                            {{ compactPeso(currentOnhandMoney) }}
                        </p>

                        <p class="mt-2 text-sm text-zinc-500">
                            {{ peso(currentOnhandMoney) }}
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
                        Core money overview
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

            <!-- SALES & PROFIT LINE GRAPH -->
            <section
                class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5 shadow-2xl shadow-black/20 sm:p-6"
            >
                <div
                    class="flex flex-col justify-between gap-5 lg:flex-row lg:items-end"
                >
                    <div>
                        <p
                            class="text-xs font-medium uppercase tracking-[0.28em] text-zinc-600"
                        >
                            Sales & Profit Trend
                        </p>

                        <h3 class="mt-2 text-2xl font-semibold text-white">
                            Monthly sales vs gross profit
                        </h3>

                        <p
                            class="mt-2 max-w-2xl text-sm leading-6 text-zinc-500"
                        >
                            Tracks sold watches by date sold. Sales uses your
                            dashboard formula: discounted price if available,
                            otherwise selling price.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span
                            class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.03] px-3 py-1.5 text-xs font-semibold text-zinc-300"
                        >
                            <span class="h-2 w-2 rounded-full bg-white"></span>
                            Sales
                        </span>

                        <span
                            class="inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1.5 text-xs font-semibold text-emerald-300"
                        >
                            <span
                                class="h-2 w-2 rounded-full bg-emerald-300"
                            ></span>
                            Gross Profit
                        </span>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div
                        v-for="card in trendSummaryCards"
                        :key="card.label"
                        class="rounded-[1.25rem] border border-white/10 bg-white/[0.03] p-4"
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

                        <p class="mt-3 text-xs leading-5 text-zinc-500">
                            {{ card.helper }}
                        </p>
                    </div>
                </div>

                <div
                    class="mt-5 overflow-hidden rounded-[1.5rem] border border-white/10 bg-[#050505] p-3 sm:p-5"
                >
                    <div
                        v-if="salesProfitTrendRows.length"
                        class="relative min-h-[22rem]"
                    >
                        <svg
                            class="h-[22rem] w-full"
                            :viewBox="`0 0 ${trendChartWidth} ${trendChartHeight}`"
                            role="img"
                            aria-label="Monthly sales and profit line graph"
                        >
                            <g>
                                <line
                                    v-for="line in trendGridLines"
                                    :key="`grid-${line.key}`"
                                    :x1="trendPadding.left"
                                    :x2="trendChartWidth - trendPadding.right"
                                    :y1="line.y"
                                    :y2="line.y"
                                    class="stroke-white/10"
                                    stroke-width="1"
                                />

                                <text
                                    v-for="line in trendGridLines"
                                    :key="`label-${line.key}`"
                                    :x="trendPadding.left - 10"
                                    :y="line.y + 4"
                                    text-anchor="end"
                                    class="fill-zinc-500 text-[10px]"
                                >
                                    {{ compactPeso(line.value) }}
                                </text>
                            </g>

                            <line
                                :x1="trendPadding.left"
                                :x2="trendChartWidth - trendPadding.right"
                                :y1="trendZeroY"
                                :y2="trendZeroY"
                                class="stroke-white/20"
                                stroke-width="1.5"
                                stroke-dasharray="5 6"
                            />

                            <polyline
                                v-if="salesTrendPoints.length"
                                :points="trendPointsToString(salesTrendPoints)"
                                class="fill-none stroke-white"
                                stroke-width="4"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />

                            <polyline
                                v-if="profitTrendPoints.length"
                                :points="trendPointsToString(profitTrendPoints)"
                                class="fill-none stroke-emerald-300"
                                stroke-width="4"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />

                            <g
                                v-for="point in salesTrendPoints"
                                :key="`sales-point-${point.month}`"
                            >
                                <circle
                                    :cx="point.x"
                                    :cy="point.y"
                                    r="4.5"
                                    class="fill-white"
                                >
                                    <title>
                                        {{ point.label }} Sales:
                                        {{ point.salesLabel }} | Profit:
                                        {{ point.profitLabel }} | Sold:
                                        {{ point.sold_count }}
                                    </title>
                                </circle>
                            </g>

                            <g
                                v-for="point in profitTrendPoints"
                                :key="`profit-point-${point.month}`"
                            >
                                <circle
                                    :cx="point.x"
                                    :cy="point.y"
                                    r="4.5"
                                    class="fill-emerald-300"
                                >
                                    <title>
                                        {{ point.label }} Gross Profit:
                                        {{ point.profitLabel }} | Sales:
                                        {{ point.salesLabel }} | Capital:
                                        {{ point.capitalLabel }}
                                    </title>
                                </circle>
                            </g>

                            <g
                                v-for="(point, index) in salesTrendPoints"
                                :key="`month-label-${point.month}`"
                            >
                                <text
                                    v-if="
                                        index % 2 === 0 ||
                                        index === salesTrendPoints.length - 1
                                    "
                                    :x="point.x"
                                    :y="trendChartHeight - 18"
                                    text-anchor="middle"
                                    class="fill-zinc-500 text-[11px]"
                                >
                                    {{ point.short_label }}
                                </text>
                            </g>
                        </svg>

                        <div
                            v-if="!trendHasSales"
                            class="absolute inset-0 flex items-center justify-center px-6 text-center"
                        >
                            <div
                                class="rounded-[1.5rem] border border-white/10 bg-black/80 p-5 shadow-2xl shadow-black/50 backdrop-blur"
                            >
                                <p class="text-sm font-semibold text-white">
                                    No sold watch data yet.
                                </p>

                                <p
                                    class="mt-2 max-w-sm text-sm leading-6 text-zinc-500"
                                >
                                    Mark watches as sold with a valid sold date
                                    to start generating your sales and profit
                                    graph.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="rounded-2xl border border-dashed border-white/10 p-10 text-center"
                    >
                        <p class="text-sm font-medium text-white">
                            Trend data is not available yet.
                        </p>

                        <p class="mt-2 text-sm text-zinc-500">
                            Refresh after updating the dashboard controller.
                        </p>
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

            <!-- TOP SOLD / TOP PROFIT / EXPENSES -->
            <section class="grid gap-5 2xl:grid-cols-[1fr_1fr_0.85fr]">
                <!-- TOP 5 SOLD UNITS -->
                <div
                    class="overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D]"
                >
                    <div class="border-b border-white/10 p-5 sm:p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p
                                    class="text-xs font-medium uppercase tracking-[0.28em] text-zinc-600"
                                >
                                    Best Sellers
                                </p>

                                <h3
                                    class="mt-2 text-xl font-semibold text-white"
                                >
                                    Top 5 Most Sold Units
                                </h3>
                            </div>

                            <span
                                class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-xs font-semibold text-zinc-400"
                            >
                                By quantity
                            </span>
                        </div>
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

                <!-- TOP 5 HIGHEST PROFITING WATCHES -->
                <div
                    class="overflow-hidden rounded-[1.7rem] border border-emerald-400/10 bg-[#0B0B0D]"
                >
                    <div class="border-b border-white/10 p-5 sm:p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p
                                    class="text-xs font-medium uppercase tracking-[0.28em] text-emerald-400/70"
                                >
                                    Profit Leaders
                                </p>

                                <h3
                                    class="mt-2 text-xl font-semibold text-white"
                                >
                                    Top 5 Highest Profiting Watches
                                </h3>
                            </div>

                            <span
                                class="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-xs font-semibold text-emerald-300"
                            >
                                By profit
                            </span>
                        </div>
                    </div>

                    <!-- MOBILE CARDS -->
                    <div class="divide-y divide-white/10 md:hidden">
                        <div
                            v-for="(unit, index) in topProfitingWatches"
                            :key="`${unit.id || index}-${unit.brand}-${unit.model_name}-${unit.reference_number}`"
                            class="p-5"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-semibold text-white"
                                    >
                                        {{ unit.brand }} {{ unit.model_name }}
                                    </p>

                                    <p
                                        class="mt-1 truncate text-xs text-zinc-500"
                                    >
                                        Ref.
                                        {{
                                            unit.reference_number ||
                                            "No reference"
                                        }}
                                    </p>

                                    <p
                                        v-if="formatShortDate(unit.date_sold)"
                                        class="mt-1 text-xs text-zinc-600"
                                    >
                                        Sold
                                        {{ formatShortDate(unit.date_sold) }}
                                    </p>
                                </div>

                                <div
                                    class="shrink-0 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-xs font-semibold text-emerald-300"
                                >
                                    #{{ index + 1 }}
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <div class="mn-mini-card">
                                    <p class="mn-mini-label">Profit</p>
                                    <p class="mn-mini-value text-emerald-300">
                                        {{
                                            compactPeso(unitProfitAmount(unit))
                                        }}
                                    </p>
                                </div>

                                <div class="mn-mini-card">
                                    <p class="mn-mini-label">Margin</p>
                                    <p class="mn-mini-value">
                                        {{ profitMargin(unit) }}
                                    </p>
                                </div>

                                <div class="mn-mini-card">
                                    <p class="mn-mini-label">Sold Price</p>
                                    <p class="mn-mini-value">
                                        {{ compactPeso(unitSalesAmount(unit)) }}
                                    </p>
                                </div>

                                <div class="mn-mini-card">
                                    <p class="mn-mini-label">Capital</p>
                                    <p class="mn-mini-value text-zinc-300">
                                        {{
                                            compactPeso(unitCapitalAmount(unit))
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="!topProfitingWatches.length"
                            class="p-10 text-center"
                        >
                            <p class="text-sm font-medium text-white">
                                No profit data yet.
                            </p>

                            <p class="mt-2 text-sm text-zinc-500">
                                Add capital price and mark watches as sold to
                                generate your top profit list.
                            </p>
                        </div>
                    </div>

                    <!-- DESKTOP TABLE -->
                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-full divide-y divide-white/10">
                            <thead>
                                <tr class="bg-emerald-400/[0.03]">
                                    <th class="mn-th">Watch</th>
                                    <th class="mn-th">Sold Price</th>
                                    <th class="mn-th">Capital</th>
                                    <th class="mn-th">Profit</th>
                                    <th class="mn-th">Margin</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-white/10">
                                <tr
                                    v-for="(unit, index) in topProfitingWatches"
                                    :key="`${unit.id || index}-${unit.brand}-${unit.model_name}-${unit.reference_number}`"
                                    class="transition hover:bg-emerald-400/[0.025]"
                                >
                                    <td class="px-6 py-5">
                                        <div class="flex items-start gap-3">
                                            <span
                                                class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-emerald-400/20 bg-emerald-400/10 text-xs font-bold text-emerald-300"
                                            >
                                                {{ index + 1 }}
                                            </span>

                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-white"
                                                >
                                                    {{ unit.brand }}
                                                    {{ unit.model_name }}
                                                </p>

                                                <p
                                                    class="mt-1 text-xs text-zinc-500"
                                                >
                                                    Ref.
                                                    {{
                                                        unit.reference_number ||
                                                        "No reference"
                                                    }}
                                                </p>

                                                <p
                                                    v-if="
                                                        formatShortDate(
                                                            unit.date_sold,
                                                        )
                                                    "
                                                    class="mt-1 text-xs text-zinc-600"
                                                >
                                                    Sold
                                                    {{
                                                        formatShortDate(
                                                            unit.date_sold,
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5 text-sm text-zinc-300">
                                        {{ peso(unitSalesAmount(unit)) }}
                                    </td>

                                    <td class="px-6 py-5 text-sm text-zinc-400">
                                        {{ peso(unitCapitalAmount(unit)) }}
                                    </td>

                                    <td
                                        class="px-6 py-5 text-sm font-semibold text-emerald-300"
                                    >
                                        {{ peso(unitProfitAmount(unit)) }}
                                    </td>

                                    <td class="px-6 py-5 text-sm text-white">
                                        {{ profitMargin(unit) }}
                                    </td>
                                </tr>

                                <tr v-if="!topProfitingWatches.length">
                                    <td
                                        colspan="5"
                                        class="px-6 py-14 text-center"
                                    >
                                        <p
                                            class="text-sm font-medium text-white"
                                        >
                                            No profit data yet.
                                        </p>

                                        <p class="mt-2 text-sm text-zinc-500">
                                            Add capital price and mark watches
                                            as sold to generate your highest
                                            profiting watch list.
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
                        This is your base cash. The dashboard uses it to compute
                        your current on-hand money.
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
