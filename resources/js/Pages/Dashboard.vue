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

const peso = (value) => {
    const amount = Number(value || 0);

    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 2,
    }).format(amount);
};

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

const moneyCards = computed(() => [
    {
        label: "Current Money",
        value: peso(props.money.current_money),
        helper: "Estimated cash after sales, capital, and expenses",
        valueClass: currentMoneyClass.value,
    },
    {
        label: "Net Profit",
        value: peso(props.money.net_profit),
        helper: "Sales minus sold capital cost and expenses",
        valueClass: netProfitClass.value,
    },
    {
        label: "Total Sales",
        value: peso(props.money.total_sales),
        helper: "Total value from watches marked as sold",
        valueClass: "text-white",
    },
    {
        label: "Total Expenses",
        value: peso(props.money.total_expenses),
        helper: "Ads, transpo, packaging, repairs, and others",
        valueClass: "text-red-300",
    },
]);

const inventoryCards = computed(() => [
    {
        label: "Capital Spent",
        value: peso(props.money.total_capital_spent),
        helper: "Total capital encoded across all watches",
    },
    {
        label: "Inventory Value",
        value: peso(props.money.inventory_value),
        helper: "Capital value of unsold watches",
    },
    {
        label: "Gross Profit",
        value: peso(props.money.gross_profit),
        helper: "Sales minus capital cost of sold watches",
    },
    {
        label: "Starting Cash",
        value: peso(props.money.starting_cash),
        helper: "Your initial business cash setting",
    },
]);

const watchCards = computed(() => [
    {
        label: "Total Watches",
        value: props.counts.total_watches,
        helper: "All encoded watch stocks",
    },
    {
        label: "Available",
        value: props.counts.available_watches,
        helper: "Visible stocks ready for buyers",
    },
    {
        label: "Reserved",
        value: props.counts.reserved_watches,
        helper: "Pending completion or payment",
    },
    {
        label: "Sold",
        value: props.counts.sold_watches,
        helper: "Completed watch sales",
    },
]);
</script>

<template>
    <Head title="Dashboard | Montre Nova" />

    <AuthenticatedLayout title="Dashboard">
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
                        class="absolute bottom-[-12rem] left-[20%] h-[28rem] w-[28rem] rounded-full bg-zinc-700/10 blur-3xl"
                    ></div>
                </div>

                <div
                    class="relative grid gap-8 lg:grid-cols-[1fr_0.45fr] lg:items-center"
                >
                    <div>
                        <p
                            class="text-xs font-medium uppercase tracking-[0.32em] text-zinc-500"
                        >
                            Montre Nova Finance Overview
                        </p>

                        <h2
                            class="mt-4 max-w-3xl text-3xl font-semibold tracking-tight text-white sm:text-5xl"
                        >
                            Track your cash, profit, inventory, and best-selling
                            watches.
                        </h2>

                        <p
                            class="mt-5 max-w-2xl text-sm leading-7 text-zinc-400 sm:text-base"
                        >
                            This dashboard helps you monitor your current money,
                            expenses, sales performance, inventory value, and
                            top sold units.
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
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
                        class="rounded-[1.7rem] border border-white/10 bg-white/[0.03] p-6"
                    >
                        <p
                            class="text-xs uppercase tracking-[0.26em] text-zinc-600"
                        >
                            Current Money
                        </p>

                        <p
                            class="mt-4 text-4xl font-semibold tracking-tight"
                            :class="currentMoneyClass"
                        >
                            {{ peso(money.current_money) }}
                        </p>

                        <div class="mt-6 border-t border-white/10 pt-5">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-zinc-500">
                                    Net Profit
                                </span>
                                <span
                                    class="text-sm font-semibold"
                                    :class="netProfitClass"
                                >
                                    {{ peso(money.net_profit) }}
                                </span>
                            </div>

                            <div class="mt-3 flex items-center justify-between">
                                <span class="text-sm text-zinc-500">
                                    Inventory Value
                                </span>
                                <span class="text-sm font-semibold text-white">
                                    {{ peso(money.inventory_value) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- MAIN MONEY CARDS -->
            <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="card in moneyCards"
                    :key="card.label"
                    class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-6 transition hover:border-white/20"
                >
                    <p
                        class="text-xs font-medium uppercase tracking-[0.26em] text-zinc-600"
                    >
                        {{ card.label }}
                    </p>

                    <p
                        class="mt-4 text-3xl font-semibold tracking-tight"
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

            <!-- INVENTORY MONEY CARDS -->
            <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="card in inventoryCards"
                    :key="card.label"
                    class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-6 transition hover:border-white/20"
                >
                    <p
                        class="text-xs font-medium uppercase tracking-[0.26em] text-zinc-600"
                    >
                        {{ card.label }}
                    </p>

                    <p
                        class="mt-4 text-2xl font-semibold tracking-tight text-white"
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

            <!-- WATCH COUNTS -->
            <section>
                <div class="mb-5">
                    <p
                        class="text-xs font-medium uppercase tracking-[0.32em] text-zinc-600"
                    >
                        Watch Inventory
                    </p>

                    <h3
                        class="mt-2 text-2xl font-semibold tracking-tight text-white"
                    >
                        Stock status summary
                    </h3>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    <div
                        v-for="card in watchCards"
                        :key="card.label"
                        class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-6 transition hover:border-white/20"
                    >
                        <p
                            class="text-xs font-medium uppercase tracking-[0.26em] text-zinc-600"
                        >
                            {{ card.label }}
                        </p>

                        <p
                            class="mt-4 text-4xl font-semibold tracking-tight text-white"
                        >
                            {{ card.value }}
                        </p>

                        <p
                            class="mt-4 border-t border-white/10 pt-4 text-sm text-zinc-500"
                        >
                            {{ card.helper }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- TOP SOLD + EXPENSES -->
            <section class="grid gap-5 xl:grid-cols-[1fr_0.75fr]">
                <!-- TOP 5 SOLD UNITS -->
                <div
                    class="overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D]"
                >
                    <div class="border-b border-white/10 p-6">
                        <p
                            class="text-xs font-medium uppercase tracking-[0.32em] text-zinc-600"
                        >
                            Sales Performance
                        </p>

                        <h3 class="mt-2 text-xl font-semibold text-white">
                            Top 5 Most Sold Units
                        </h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-white/10">
                            <thead>
                                <tr class="bg-white/[0.02]">
                                    <th
                                        class="px-6 py-4 text-left text-xs uppercase tracking-[0.22em] text-zinc-600"
                                    >
                                        Unit
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs uppercase tracking-[0.22em] text-zinc-600"
                                    >
                                        Sold
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs uppercase tracking-[0.22em] text-zinc-600"
                                    >
                                        Sales
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
                        class="flex items-center justify-between gap-4 border-b border-white/10 p-6"
                    >
                        <div>
                            <p
                                class="text-xs font-medium uppercase tracking-[0.32em] text-zinc-600"
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
                            <div>
                                <p class="text-sm font-semibold text-white">
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

                            <div class="text-right">
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
                class="fixed inset-0 z-[999] flex items-center justify-center bg-black/80 px-4 py-6 backdrop-blur-sm"
            >
                <div
                    class="absolute inset-0"
                    @click="showCashModal = false"
                ></div>

                <form
                    @submit.prevent="updateStartingCash"
                    class="relative w-full max-w-md rounded-[2rem] border border-white/10 bg-[#0B0B0D] p-6 shadow-2xl shadow-black"
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

                    <div class="mt-6 flex justify-end gap-3">
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
                class="fixed inset-0 z-[999] flex items-center justify-center bg-black/80 px-4 py-6 backdrop-blur-sm"
            >
                <div
                    class="absolute inset-0"
                    @click="showExpenseModal = false"
                ></div>

                <form
                    @submit.prevent="addExpense"
                    class="relative w-full max-w-xl rounded-[2rem] border border-white/10 bg-[#0B0B0D] p-6 shadow-2xl shadow-black"
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

                    <div class="mt-6 flex justify-end gap-3">
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
