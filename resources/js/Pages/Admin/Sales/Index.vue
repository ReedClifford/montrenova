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

const performanceCards = computed(() => [
    {
        label: "This Week",
        period: props.salesPerformance.weekly?.date_range || "",
        sold: props.salesPerformance.weekly?.sold_count || 0,
        sales: peso(props.salesPerformance.weekly?.total_sales || 0),
        capital: peso(props.salesPerformance.weekly?.total_capital || 0),
        grossProfit: peso(props.salesPerformance.weekly?.gross_profit || 0),
        expenses: peso(props.salesPerformance.weekly?.total_expenses || 0),
        netProfit: peso(props.salesPerformance.weekly?.net_profit || 0),
        netProfitValue: Number(props.salesPerformance.weekly?.net_profit || 0),
    },
    {
        label: "This Month",
        period: props.salesPerformance.monthly?.date_range || "",
        sold: props.salesPerformance.monthly?.sold_count || 0,
        sales: peso(props.salesPerformance.monthly?.total_sales || 0),
        capital: peso(props.salesPerformance.monthly?.total_capital || 0),
        grossProfit: peso(props.salesPerformance.monthly?.gross_profit || 0),
        expenses: peso(props.salesPerformance.monthly?.total_expenses || 0),
        netProfit: peso(props.salesPerformance.monthly?.net_profit || 0),
        netProfitValue: Number(props.salesPerformance.monthly?.net_profit || 0),
    },
    {
        label: "This Year",
        period: props.salesPerformance.yearly?.date_range || "",
        sold: props.salesPerformance.yearly?.sold_count || 0,
        sales: peso(props.salesPerformance.yearly?.total_sales || 0),
        capital: peso(props.salesPerformance.yearly?.total_capital || 0),
        grossProfit: peso(props.salesPerformance.yearly?.gross_profit || 0),
        expenses: peso(props.salesPerformance.yearly?.total_expenses || 0),
        netProfit: peso(props.salesPerformance.yearly?.net_profit || 0),
        netProfitValue: Number(props.salesPerformance.yearly?.net_profit || 0),
    },
]);

const selectedMonthCards = computed(() => [
    {
        label: "Sold Count",
        value: props.selectedMonthSummary.sold_count || 0,
        valueClass: "text-white",
    },
    {
        label: "Total Sales",
        value: peso(props.selectedMonthSummary.total_sales),
        valueClass: "text-white",
    },
    {
        label: "Capital Cost",
        value: peso(props.selectedMonthSummary.total_capital),
        valueClass: "text-white",
    },
    {
        label: "Gross Profit",
        value: peso(props.selectedMonthSummary.gross_profit),
        valueClass: "text-emerald-300",
    },
    {
        label: "Expenses",
        value: peso(props.selectedMonthSummary.total_expenses),
        valueClass: "text-red-300",
    },
    {
        label: "Net Profit",
        value: peso(props.selectedMonthSummary.net_profit),
        valueClass:
            Number(props.selectedMonthSummary.net_profit || 0) >= 0
                ? "text-emerald-300"
                : "text-red-300",
    },
]);
</script>

<template>
    <Head title="Sales | Montre Nova" />

    <AuthenticatedLayout title="Sales">
        <div class="space-y-8">
            <!-- HERO -->
            <section
                class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-[#0B0B0D] p-6 shadow-2xl shadow-black/30 sm:p-8"
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
                    class="relative flex flex-col justify-between gap-8 lg:flex-row lg:items-end"
                >
                    <div>
                        <p
                            class="text-xs font-medium uppercase tracking-[0.32em] text-zinc-500"
                        >
                            Montre Nova Sales Analytics
                        </p>

                        <h2
                            class="mt-4 max-w-3xl text-3xl font-semibold tracking-tight text-white sm:text-5xl"
                        >
                            Track profit by week, month, year, and specific
                            sales period.
                        </h2>

                        <p
                            class="mt-5 max-w-2xl text-sm leading-7 text-zinc-400"
                        >
                            Monitor sold watches, gross profit, expenses, net
                            profit, and your top-performing units.
                        </p>
                    </div>

                    <Link
                        :href="route('admin.watches.index')"
                        class="inline-flex items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200"
                    >
                        Manage Watch Stocks
                    </Link>
                </div>
            </section>

            <!-- SPECIFIC MONTH CHECKER -->
            <section>
                <div
                    class="mb-5 flex flex-col justify-between gap-4 md:flex-row md:items-end"
                >
                    <div>
                        <p
                            class="text-xs font-medium uppercase tracking-[0.32em] text-zinc-600"
                        >
                            Specific Month Profit
                        </p>

                        <h3
                            class="mt-2 text-2xl font-semibold tracking-tight text-white"
                        >
                            Check profit by month
                        </h3>

                        <p class="mt-2 text-sm text-zinc-500">
                            Select a month to review sold units, sales,
                            expenses, and net profit.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <input
                            v-model="selectedMonthFilter"
                            type="month"
                            class="mn-input min-w-[220px]"
                        />

                        <button
                            type="button"
                            class="rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200"
                            @click="applyMonthFilter"
                        >
                            Check Month
                        </button>
                    </div>
                </div>

                <div
                    class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-6"
                >
                    <div
                        class="flex flex-col justify-between gap-5 border-b border-white/10 pb-6 md:flex-row md:items-center"
                    >
                        <div>
                            <p
                                class="text-xs uppercase tracking-[0.28em] text-zinc-600"
                            >
                                Selected Period
                            </p>

                            <h4 class="mt-2 text-2xl font-semibold text-white">
                                {{
                                    selectedMonthSummary.label ||
                                    "No month selected"
                                }}
                            </h4>

                            <p class="mt-2 text-sm text-zinc-500">
                                {{
                                    selectedMonthSummary.date_range ||
                                    "Select a month to view performance."
                                }}
                            </p>
                        </div>

                        <div
                            class="rounded-full border border-white/10 bg-white/[0.03] px-4 py-2 text-sm font-medium text-zinc-300"
                        >
                            {{ selectedMonthSummary.sold_count || 0 }} sold
                        </div>
                    </div>

                    <div class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-6">
                        <div
                            v-for="card in selectedMonthCards"
                            :key="card.label"
                            class="rounded-2xl border border-white/10 bg-white/[0.03] p-5"
                        >
                            <p
                                class="text-xs uppercase tracking-[0.22em] text-zinc-600"
                            >
                                {{ card.label }}
                            </p>

                            <p
                                class="mt-3 text-2xl font-semibold"
                                :class="card.valueClass"
                            >
                                {{ card.value }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- WEEKLY MONTHLY YEARLY -->
            <section>
                <div class="mb-5">
                    <p
                        class="text-xs font-medium uppercase tracking-[0.32em] text-zinc-600"
                    >
                        Sales Performance
                    </p>

                    <h3
                        class="mt-2 text-2xl font-semibold tracking-tight text-white"
                    >
                        Weekly, monthly, and yearly profit
                    </h3>

                    <p class="mt-2 text-sm text-zinc-500">
                        Net profit is sales minus capital cost and expenses for
                        the selected period.
                    </p>
                </div>

                <div class="grid gap-5 xl:grid-cols-3">
                    <div
                        v-for="card in performanceCards"
                        :key="card.label"
                        class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-6 transition hover:border-white/20"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p
                                    class="text-xs font-medium uppercase tracking-[0.26em] text-zinc-600"
                                >
                                    {{ card.label }}
                                </p>

                                <p class="mt-2 text-xs text-zinc-500">
                                    {{ card.period }}
                                </p>
                            </div>

                            <div
                                class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-xs font-medium text-zinc-400"
                            >
                                {{ card.sold }} sold
                            </div>
                        </div>

                        <div class="mt-6">
                            <p class="text-sm text-zinc-500">Net Profit</p>

                            <p
                                class="mt-2 text-3xl font-semibold tracking-tight"
                                :class="
                                    card.netProfitValue >= 0
                                        ? 'text-emerald-300'
                                        : 'text-red-300'
                                "
                            >
                                {{ card.netProfit }}
                            </p>
                        </div>

                        <div
                            class="mt-6 space-y-3 border-t border-white/10 pt-5"
                        >
                            <div
                                class="flex items-center justify-between text-sm"
                            >
                                <span class="text-zinc-500">Total Sales</span>
                                <span class="font-semibold text-white">
                                    {{ card.sales }}
                                </span>
                            </div>

                            <div
                                class="flex items-center justify-between text-sm"
                            >
                                <span class="text-zinc-500">Capital Cost</span>
                                <span class="font-semibold text-white">
                                    {{ card.capital }}
                                </span>
                            </div>

                            <div
                                class="flex items-center justify-between text-sm"
                            >
                                <span class="text-zinc-500">Gross Profit</span>
                                <span class="font-semibold text-emerald-300">
                                    {{ card.grossProfit }}
                                </span>
                            </div>

                            <div
                                class="flex items-center justify-between text-sm"
                            >
                                <span class="text-zinc-500">Expenses</span>
                                <span class="font-semibold text-red-300">
                                    {{ card.expenses }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- TOP SOLD + RECENT SALES -->
            <section class="grid gap-5 xl:grid-cols-[0.85fr_1.15fr]">
                <!-- TOP SOLD -->
                <div
                    class="overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D]"
                >
                    <div class="border-b border-white/10 p-6">
                        <p
                            class="text-xs font-medium uppercase tracking-[0.32em] text-zinc-600"
                        >
                            Ranking
                        </p>

                        <h3 class="mt-2 text-xl font-semibold text-white">
                            Top 5 Most Sold Units
                        </h3>
                    </div>

                    <div class="divide-y divide-white/10">
                        <div
                            v-for="unit in topSoldUnits"
                            :key="`${unit.brand}-${unit.model_name}-${unit.reference_number}`"
                            class="p-5 transition hover:bg-white/[0.02]"
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
                                    class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-xs font-medium text-zinc-300"
                                >
                                    {{ unit.sold_count }} sold
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <div
                                    class="rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                                >
                                    <p class="text-xs text-zinc-500">Sales</p>
                                    <p
                                        class="mt-1 text-sm font-semibold text-white"
                                    >
                                        {{ peso(unit.sales_total) }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                                >
                                    <p class="text-xs text-zinc-500">Profit</p>
                                    <p
                                        class="mt-1 text-sm font-semibold text-emerald-300"
                                    >
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
                    <div class="border-b border-white/10 p-6">
                        <p
                            class="text-xs font-medium uppercase tracking-[0.32em] text-zinc-600"
                        >
                            Recent Activity
                        </p>

                        <h3 class="mt-2 text-xl font-semibold text-white">
                            Recent Sold Watches
                        </h3>
                    </div>

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
                                        Date Sold
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs uppercase tracking-[0.22em] text-zinc-600"
                                    >
                                        Sold Price
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs uppercase tracking-[0.22em] text-zinc-600"
                                    >
                                        Profit
                                    </th>
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
                                                class="h-14 w-14 overflow-hidden rounded-2xl border border-white/10 bg-[#050505]"
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
                                        {{ sale.date_sold || "No date" }}
                                    </td>

                                    <td
                                        class="px-6 py-5 text-sm font-semibold text-white"
                                    >
                                        {{ peso(sale.sold_price) }}
                                    </td>

                                    <td
                                        class="px-6 py-5 text-sm font-semibold"
                                        :class="
                                            Number(sale.profit || 0) >= 0
                                                ? 'text-emerald-300'
                                                : 'text-red-300'
                                        "
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
