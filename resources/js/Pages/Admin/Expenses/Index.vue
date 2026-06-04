<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

const props = defineProps({
    expenses: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
        }),
    },
    summary: {
        type: Object,
        default: () => ({
            selected_month: "",
            month_label: "",
            date_range: "",
            total_expenses: 0,
            expense_count: 0,
            average_expense: 0,
        }),
    },
    categories: {
        type: Array,
        default: () => [],
    },
    categoryBreakdown: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            search: "",
            category: "",
            month: "",
        }),
    },
});

const search = ref(props.filters.search || "");
const category = ref(props.filters.category || "");
const selectedMonth = ref(
    props.filters.month || new Date().toISOString().slice(0, 7),
);

const showExpenseModal = ref(false);
const showDeleteModal = ref(false);
const selectedExpense = ref(null);
const modalMode = ref("create");

let timeout = null;

const form = useForm({
    title: "",
    category: "",
    amount: "",
    spent_at: "",
    notes: "",
});

const peso = (value) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
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

const expenseRows = computed(() => props.expenses?.data || []);

const categoryBreakdownTotal = computed(() => {
    return props.categoryBreakdown.reduce((total, item) => {
        return total + Number(item.total || 0);
    }, 0);
});

const categoryPercentage = (item) => {
    const total = Number(categoryBreakdownTotal.value || 0);

    if (total <= 0) return 0;

    return Math.min((Number(item.total || 0) / total) * 100, 100);
};

const hasActiveFilters = computed(() => {
    return Boolean(search.value || category.value);
});

const highestCategory = computed(() => {
    if (!props.categoryBreakdown.length) {
        return {
            category_name: "None yet",
            total: 0,
        };
    }

    return [...props.categoryBreakdown].sort((a, b) => {
        return Number(b.total || 0) - Number(a.total || 0);
    })[0];
});

const summaryCards = computed(() => [
    {
        label: "Selected Month",
        value: props.summary.month_label || "No month",
        fullValue: props.summary.date_range || "No date range",
        helper: "Current reporting period",
        valueClass: "text-white",
    },
    {
        label: "Total Expenses",
        value: compactPeso(props.summary.total_expenses),
        fullValue: peso(props.summary.total_expenses),
        helper: "Total cost for this filter",
        valueClass: "text-red-300",
    },
    {
        label: "Expense Count",
        value: props.summary.expense_count || 0,
        fullValue: `${props.summary.expense_count || 0} entries`,
        helper: "Number of recorded expenses",
        valueClass: "text-white",
    },
    {
        label: "Average Expense",
        value: compactPeso(props.summary.average_expense),
        fullValue: peso(props.summary.average_expense),
        helper: "Average cost per entry",
        valueClass: "text-white",
    },
]);

const insightCards = computed(() => [
    {
        label: "Top Category",
        value: highestCategory.value.category_name || "General",
        helper: `${peso(highestCategory.value.total)} total spending`,
        className: "border-red-500/20 bg-red-500/10 text-red-300",
    },
    {
        label: "Entries",
        value: props.summary.expense_count || 0,
        helper: "Expenses recorded this period",
        className: "border-white/10 bg-white/[0.03] text-white",
    },
    {
        label: "Average Spend",
        value: compactPeso(props.summary.average_expense),
        helper: "Average amount per expense",
        className: "border-amber-500/20 bg-amber-500/10 text-amber-300",
    },
]);

const applyFilters = () => {
    router.get(
        route("admin.expenses.index"),
        {
            search: search.value,
            category: category.value,
            month: selectedMonth.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

watch([search, category], () => {
    clearTimeout(timeout);

    timeout = setTimeout(() => {
        applyFilters();
    }, 350);
});

const checkMonth = () => {
    applyFilters();
};

const setCategoryFilter = (value) => {
    category.value = value;
};

const clearFilters = () => {
    search.value = "";
    category.value = "";
    selectedMonth.value = new Date().toISOString().slice(0, 7);

    router.get(
        route("admin.expenses.index"),
        {
            month: selectedMonth.value,
        },
        {
            preserveState: false,
            preserveScroll: true,
            replace: true,
        },
    );
};

const openCreateModal = () => {
    modalMode.value = "create";
    selectedExpense.value = null;
    form.reset();
    form.clearErrors();

    if (!form.spent_at) {
        form.spent_at = new Date().toISOString().slice(0, 10);
    }

    showExpenseModal.value = true;
};

const openEditModal = (expense) => {
    modalMode.value = "edit";
    selectedExpense.value = expense;

    form.title = expense.title || "";
    form.category = expense.category || "";
    form.amount = expense.amount || "";
    form.spent_at = expense.spent_at || "";
    form.notes = expense.notes || "";

    form.clearErrors();
    showExpenseModal.value = true;
};

const closeExpenseModal = () => {
    showExpenseModal.value = false;
    selectedExpense.value = null;
    form.reset();
    form.clearErrors();
};

const submitExpense = () => {
    if (modalMode.value === "create") {
        form.post(route("admin.expenses.store"), {
            preserveScroll: true,
            onSuccess: () => closeExpenseModal(),
        });

        return;
    }

    form.patch(route("admin.expenses.update", selectedExpense.value.id), {
        preserveScroll: true,
        onSuccess: () => closeExpenseModal(),
    });
};

const openDeleteModal = (expense) => {
    selectedExpense.value = expense;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    selectedExpense.value = null;
    showDeleteModal.value = false;
};

const deleteExpense = () => {
    if (!selectedExpense.value) return;

    router.delete(route("admin.expenses.destroy", selectedExpense.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
};
</script>

<template>
    <Head title="Expenses | Montre Nova" />

    <AuthenticatedLayout title="Expenses">
        <div class="space-y-6 sm:space-y-8">
            <!-- MOBILE QUICK ACTIONS -->
            <section class="grid grid-cols-2 gap-3 sm:hidden">
                <button
                    type="button"
                    class="rounded-2xl bg-white px-4 py-3 text-sm font-bold text-black"
                    @click="openCreateModal"
                >
                    Add Expense
                </button>

                <button
                    type="button"
                    class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm font-bold text-white"
                    @click="checkMonth"
                >
                    Refresh Month
                </button>
            </section>

            <!-- HEADER -->
            <section
                class="relative overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5 shadow-2xl shadow-black/30 sm:rounded-[2rem] sm:p-8"
            >
                <div class="pointer-events-none absolute inset-0">
                    <div
                        class="absolute right-[-12rem] top-[-12rem] h-[30rem] w-[30rem] rounded-full bg-white/[0.04] blur-3xl"
                    ></div>
                </div>

                <div
                    class="relative grid gap-6 lg:grid-cols-[1fr_0.45fr] lg:items-center"
                >
                    <div>
                        <p
                            class="text-xs uppercase tracking-[0.28em] text-zinc-600"
                        >
                            Montre Nova Expense Tracker
                        </p>

                        <h2
                            class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-5xl"
                        >
                            Expenses
                        </h2>

                        <p
                            class="mt-4 max-w-2xl text-sm leading-7 text-zinc-400"
                        >
                            Track ads, transportation, packaging, repairs, fees,
                            and other costs that affect your net profit.
                        </p>

                        <div class="mt-6 hidden sm:flex">
                            <button
                                type="button"
                                class="rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200"
                                @click="openCreateModal"
                            >
                                Add Expense
                            </button>
                        </div>
                    </div>

                    <div
                        class="rounded-[1.5rem] border border-red-500/20 bg-red-500/10 p-5 sm:p-6"
                    >
                        <p
                            class="text-xs uppercase tracking-[0.24em] text-red-300/80"
                        >
                            Total Expenses
                        </p>

                        <p
                            class="mt-3 text-4xl font-semibold tracking-tight text-red-300 sm:text-5xl"
                        >
                            {{ compactPeso(summary.total_expenses) }}
                        </p>

                        <p class="mt-2 text-sm text-red-200/70">
                            {{ peso(summary.total_expenses) }}
                        </p>

                        <div class="mt-5 border-t border-red-400/20 pt-5">
                            <p class="text-sm text-red-200/70">
                                {{ summary.month_label || "Selected Month" }}
                            </p>

                            <p class="mt-1 text-xs text-red-200/50">
                                {{ summary.date_range }}
                            </p>
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

                    <p
                        class="mt-3 truncate text-2xl font-semibold tracking-tight"
                    >
                        {{ card.value }}
                    </p>

                    <p class="mt-2 text-xs leading-5 opacity-75">
                        {{ card.helper }}
                    </p>
                </div>
            </section>

            <!-- SUMMARY -->
            <section>
                <div class="mb-4">
                    <p
                        class="text-xs uppercase tracking-[0.28em] text-zinc-600"
                    >
                        Expense Summary
                    </p>

                    <h3 class="mt-2 text-2xl font-semibold text-white">
                        Monthly spending overview
                    </h3>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div
                        v-for="card in summaryCards"
                        :key="card.label"
                        class="rounded-[1.5rem] border border-white/10 bg-[#0B0B0D] p-5 transition hover:border-white/20 sm:p-6"
                    >
                        <p
                            class="text-xs uppercase tracking-[0.22em] text-zinc-600"
                        >
                            {{ card.label }}
                        </p>

                        <p
                            class="mt-4 text-2xl font-semibold tracking-tight"
                            :class="card.valueClass"
                        >
                            {{ card.value }}
                        </p>

                        <p class="mt-1 text-xs text-zinc-600">
                            {{ card.fullValue }}
                        </p>

                        <p
                            class="mt-4 border-t border-white/10 pt-4 text-sm leading-6 text-zinc-500"
                        >
                            {{ card.helper }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- FILTERS -->
            <section
                class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5 sm:p-6"
            >
                <div class="grid gap-5">
                    <div
                        class="grid gap-3 xl:grid-cols-[1fr_220px_220px_auto_auto]"
                    >
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search title, category, or notes..."
                            class="mn-input"
                        />

                        <select v-model="category" class="mn-input">
                            <option value="">All Categories</option>
                            <option
                                v-for="item in categories"
                                :key="item"
                                :value="item"
                            >
                                {{ item }}
                            </option>
                        </select>

                        <input
                            v-model="selectedMonth"
                            type="month"
                            class="mn-input"
                        />

                        <button
                            type="button"
                            class="rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200"
                            @click="checkMonth"
                        >
                            Check
                        </button>

                        <button
                            type="button"
                            class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30"
                            @click="clearFilters"
                        >
                            Clear
                        </button>
                    </div>

                    <div
                        v-if="categories.length"
                        class="thin-scrollbar flex gap-2 overflow-x-auto pb-1"
                    >
                        <button
                            type="button"
                            class="shrink-0 rounded-2xl border px-4 py-2 text-sm font-medium transition"
                            :class="
                                category === ''
                                    ? 'border-white bg-white text-black'
                                    : 'border-white/10 bg-white/[0.03] text-zinc-400 hover:border-white/30 hover:text-white'
                            "
                            @click="setCategoryFilter('')"
                        >
                            All
                        </button>

                        <button
                            v-for="item in categories"
                            :key="item"
                            type="button"
                            class="shrink-0 rounded-2xl border px-4 py-2 text-sm font-medium transition"
                            :class="
                                category === item
                                    ? 'border-white bg-white text-black'
                                    : 'border-white/10 bg-white/[0.03] text-zinc-400 hover:border-white/30 hover:text-white'
                            "
                            @click="setCategoryFilter(item)"
                        >
                            {{ item }}
                        </button>
                    </div>

                    <div
                        class="flex flex-col gap-2 rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-zinc-400 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <span>
                            Showing
                            <strong class="text-white">
                                {{ expenseRows.length }}
                            </strong>
                            expense entries
                        </span>

                        <button
                            v-if="hasActiveFilters"
                            type="button"
                            class="text-xs font-semibold text-white underline underline-offset-4"
                            @click="clearFilters"
                        >
                            Clear filters
                        </button>
                    </div>
                </div>
            </section>

            <!-- CATEGORY BREAKDOWN -->
            <section
                class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5 sm:p-6"
            >
                <div class="mb-5">
                    <p
                        class="text-xs uppercase tracking-[0.28em] text-zinc-600"
                    >
                        Category Breakdown
                    </p>

                    <h3 class="mt-2 text-xl font-semibold text-white">
                        Where your money went
                    </h3>
                </div>

                <div v-if="categoryBreakdown.length" class="space-y-3">
                    <div
                        v-for="item in categoryBreakdown"
                        :key="item.category_name"
                        class="rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-white">
                                    {{ item.category_name || "General" }}
                                </p>

                                <p class="mt-1 text-xs text-zinc-500">
                                    {{ categoryPercentage(item).toFixed(1) }}%
                                    of total spending
                                </p>
                            </div>

                            <p
                                class="shrink-0 text-sm font-semibold text-red-300"
                            >
                                {{ peso(item.total) }}
                            </p>
                        </div>

                        <div
                            class="mt-4 h-2 overflow-hidden rounded-full bg-zinc-900"
                        >
                            <div
                                class="h-full rounded-full bg-red-300"
                                :style="{
                                    width: `${categoryPercentage(item)}%`,
                                }"
                            ></div>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="rounded-2xl border border-white/10 bg-white/[0.03] p-8 text-center"
                >
                    <p class="text-sm font-medium text-white">
                        No category spending yet.
                    </p>

                    <p class="mt-2 text-sm text-zinc-500">
                        Add expenses to see category breakdown.
                    </p>
                </div>
            </section>

            <!-- MOBILE CARDS -->
            <section class="space-y-4 md:hidden">
                <div
                    v-for="expense in expenseRows"
                    :key="expense.id"
                    class="rounded-[1.5rem] border border-white/10 bg-[#0B0B0D] p-5"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-semibold text-white"
                            >
                                {{ expense.title }}
                            </p>

                            <p class="mt-1 text-xs text-zinc-500">
                                {{ expense.category || "General" }}
                            </p>
                        </div>

                        <p class="shrink-0 text-sm font-semibold text-red-300">
                            {{ compactPeso(expense.amount) }}
                        </p>
                    </div>

                    <p class="mt-3 text-xs text-zinc-600">
                        {{ formatDate(expense.spent_at) }}
                    </p>

                    <p
                        v-if="expense.notes"
                        class="mt-3 line-clamp-2 text-xs leading-5 text-zinc-500"
                    >
                        {{ expense.notes }}
                    </p>

                    <div class="mt-5 grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            class="mn-action-btn border-white/10 text-zinc-300"
                            @click="openEditModal(expense)"
                        >
                            Edit
                        </button>

                        <button
                            type="button"
                            class="mn-action-btn border-red-500/20 text-red-300"
                            @click="openDeleteModal(expense)"
                        >
                            Delete
                        </button>
                    </div>
                </div>

                <div
                    v-if="!expenseRows.length"
                    class="rounded-[1.5rem] border border-white/10 bg-[#0B0B0D] p-10 text-center"
                >
                    <p class="text-sm font-medium text-white">
                        No expenses found.
                    </p>

                    <p class="mt-2 text-sm text-zinc-500">
                        Add expenses or adjust your filters.
                    </p>

                    <button
                        type="button"
                        class="mt-5 rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black"
                        @click="openCreateModal"
                    >
                        Add Expense
                    </button>
                </div>
            </section>

            <!-- DESKTOP TABLE -->
            <section
                class="hidden overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] md:block"
            >
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10">
                        <thead>
                            <tr class="bg-white/[0.02]">
                                <th class="mn-th">Expense</th>
                                <th class="mn-th">Category</th>
                                <th class="mn-th">Date</th>
                                <th class="mn-th">Amount</th>
                                <th class="mn-th text-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-white/10">
                            <tr
                                v-for="expense in expenseRows"
                                :key="expense.id"
                                class="transition hover:bg-white/[0.02]"
                            >
                                <td class="px-6 py-5">
                                    <p class="text-sm font-semibold text-white">
                                        {{ expense.title }}
                                    </p>

                                    <p
                                        v-if="expense.notes"
                                        class="mt-1 max-w-md truncate text-xs text-zinc-500"
                                    >
                                        {{ expense.notes }}
                                    </p>
                                </td>

                                <td class="px-6 py-5">
                                    <span
                                        class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-xs font-semibold text-zinc-300"
                                    >
                                        {{ expense.category || "General" }}
                                    </span>
                                </td>

                                <td class="px-6 py-5 text-sm text-zinc-400">
                                    {{ formatDate(expense.spent_at) }}
                                </td>

                                <td
                                    class="px-6 py-5 text-sm font-semibold text-red-300"
                                >
                                    {{ peso(expense.amount) }}
                                </td>

                                <td class="px-6 py-5">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            type="button"
                                            class="mn-action-btn border-white/10 text-zinc-300"
                                            @click="openEditModal(expense)"
                                        >
                                            Edit
                                        </button>

                                        <button
                                            type="button"
                                            class="mn-action-btn border-red-500/20 text-red-300"
                                            @click="openDeleteModal(expense)"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!expenseRows.length">
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <p class="text-sm font-medium text-white">
                                        No expenses found.
                                    </p>

                                    <p class="mt-2 text-sm text-zinc-500">
                                        Add expenses or adjust your filters.
                                    </p>

                                    <button
                                        type="button"
                                        class="mt-5 rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200"
                                        @click="openCreateModal"
                                    >
                                        Add Expense
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- PAGINATION -->
            <section
                v-if="expenses.links?.length > 3"
                class="thin-scrollbar flex gap-2 overflow-x-auto rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-4 sm:flex-wrap sm:p-5"
            >
                <Link
                    v-for="link in expenses.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    v-html="link.label"
                    class="shrink-0 rounded-xl border px-3 py-2 text-sm"
                    :class="[
                        link.active
                            ? 'border-white bg-white text-black'
                            : 'border-white/10 text-zinc-400 hover:border-white/30 hover:text-white',
                        !link.url ? 'pointer-events-none opacity-40' : '',
                    ]"
                />
            </section>
        </div>

        <!-- ADD / EDIT MODAL -->
        <Teleport to="body">
            <div
                v-if="showExpenseModal"
                class="fixed inset-0 z-[1000] flex items-end justify-center bg-black/80 px-3 py-3 backdrop-blur-sm sm:items-center sm:px-4 sm:py-6"
            >
                <div class="absolute inset-0" @click="closeExpenseModal"></div>

                <form
                    @submit.prevent="submitExpense"
                    class="relative max-h-[92vh] w-full max-w-xl overflow-y-auto rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5 shadow-2xl shadow-black sm:rounded-[2rem] sm:p-6"
                >
                    <p class="text-xs uppercase tracking-[0.3em] text-zinc-600">
                        Expense
                    </p>

                    <h2
                        class="mt-3 text-2xl font-semibold tracking-tight text-white"
                    >
                        {{
                            modalMode === "create"
                                ? "Add Expense"
                                : "Edit Expense"
                        }}
                    </h2>

                    <p class="mt-3 text-sm leading-6 text-zinc-500">
                        Record business costs like ads, transportation,
                        packaging, repairs, fees, and other expenses.
                    </p>

                    <datalist id="expense-categories">
                        <option
                            v-for="item in categories"
                            :key="item"
                            :value="item"
                        />
                    </datalist>

                    <div class="mt-6 grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="mn-label">Title</label>

                            <input
                                v-model="form.title"
                                class="mn-input"
                                placeholder="Facebook Ads"
                            />

                            <p
                                v-if="form.errors.title"
                                class="mt-2 text-sm text-red-300"
                            >
                                {{ form.errors.title }}
                            </p>
                        </div>

                        <div>
                            <label class="mn-label">Category</label>

                            <input
                                v-model="form.category"
                                list="expense-categories"
                                class="mn-input"
                                placeholder="Ads, Transpo, Packaging"
                            />

                            <p
                                v-if="form.errors.category"
                                class="mt-2 text-sm text-red-300"
                            >
                                {{ form.errors.category }}
                            </p>
                        </div>

                        <div>
                            <label class="mn-label">Amount</label>

                            <input
                                v-model="form.amount"
                                type="number"
                                step="0.01"
                                class="mn-input"
                                placeholder="0.00"
                            />

                            <p
                                v-if="form.errors.amount"
                                class="mt-2 text-sm text-red-300"
                            >
                                {{ form.errors.amount }}
                            </p>
                        </div>

                        <div>
                            <label class="mn-label">Date Spent</label>

                            <input
                                v-model="form.spent_at"
                                type="date"
                                class="mn-input"
                            />

                            <p
                                v-if="form.errors.spent_at"
                                class="mt-2 text-sm text-red-300"
                            >
                                {{ form.errors.spent_at }}
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="mn-label">Notes</label>

                            <textarea
                                v-model="form.notes"
                                rows="4"
                                class="mn-input"
                                placeholder="Optional notes..."
                            ></textarea>

                            <p
                                v-if="form.errors.notes"
                                class="mt-2 text-sm text-red-300"
                            >
                                {{ form.errors.notes }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30"
                            @click="closeExpenseModal"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200 disabled:opacity-60"
                        >
                            {{ form.processing ? "Saving..." : "Save Expense" }}
                        </button>
                    </div>
                </form>
            </div>
        </Teleport>

        <!-- DELETE MODAL -->
        <Teleport to="body">
            <div
                v-if="showDeleteModal && selectedExpense"
                class="fixed inset-0 z-[1000] flex items-end justify-center bg-black/80 px-3 py-3 backdrop-blur-sm sm:items-center sm:px-4 sm:py-6"
            >
                <div class="absolute inset-0" @click="closeDeleteModal"></div>

                <div
                    class="relative w-full max-w-md rounded-[1.7rem] border border-red-500/20 bg-[#0B0B0D] p-5 shadow-2xl shadow-black sm:rounded-[2rem] sm:p-6"
                >
                    <p
                        class="text-xs uppercase tracking-[0.3em] text-red-300/70"
                    >
                        Delete Expense
                    </p>

                    <h2
                        class="mt-3 text-2xl font-semibold tracking-tight text-white"
                    >
                        Are you sure?
                    </h2>

                    <p class="mt-3 text-sm leading-6 text-zinc-400">
                        This will permanently delete
                        <span class="font-semibold text-white">
                            {{ selectedExpense.title }}
                        </span>
                        from your expense records.
                    </p>

                    <div
                        class="mt-5 rounded-2xl border border-red-500/20 bg-red-500/10 p-4"
                    >
                        <p class="text-sm font-semibold text-red-300">
                            {{ peso(selectedExpense.amount) }}
                        </p>

                        <p class="mt-1 text-xs text-red-200/70">
                            {{ selectedExpense.category || "General" }} •
                            {{ formatDate(selectedExpense.spent_at) }}
                        </p>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30"
                            @click="closeDeleteModal"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            class="rounded-2xl bg-red-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-red-400"
                            @click="deleteExpense"
                        >
                            Delete
                        </button>
                    </div>
                </div>
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

.mn-action-btn {
    border-radius: 0.85rem;
    border-width: 1px;
    padding: 0.75rem 0.9rem;
    font-size: 0.75rem;
    font-weight: 700;
    transition:
        border-color 150ms ease,
        background-color 150ms ease,
        color 150ms ease;
}

.mn-th {
    padding: 1rem 1.5rem;
    text-align: left;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.22em;
    color: rgb(82 82 91);
}

.thin-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: rgb(255 255 255 / 0.2) transparent;
}

.thin-scrollbar::-webkit-scrollbar {
    height: 5px;
}

.thin-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.thin-scrollbar::-webkit-scrollbar-thumb {
    background: rgb(255 255 255 / 0.18);
    border-radius: 999px;
}

.thin-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgb(255 255 255 / 0.35);
}
</style>
