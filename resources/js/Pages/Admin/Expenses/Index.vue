<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ref, watch } from "vue";

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

const peso = (value) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
};

const form = useForm({
    title: "",
    category: "",
    amount: "",
    spent_at: "",
    notes: "",
});

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
                    </div>

                    <button
                        type="button"
                        class="rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200"
                        @click="openCreateModal"
                    >
                        Add Expense
                    </button>
                </div>
            </section>

            <!-- SUMMARY -->
            <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-6"
                >
                    <p
                        class="text-xs uppercase tracking-[0.26em] text-zinc-600"
                    >
                        Selected Month
                    </p>
                    <p
                        class="mt-4 text-3xl font-semibold tracking-tight text-white"
                    >
                        {{ summary.month_label }}
                    </p>
                    <p
                        class="mt-4 border-t border-white/10 pt-4 text-sm text-zinc-500"
                    >
                        {{ summary.date_range }}
                    </p>
                </div>

                <div
                    class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-6"
                >
                    <p
                        class="text-xs uppercase tracking-[0.26em] text-zinc-600"
                    >
                        Total Expenses
                    </p>
                    <p
                        class="mt-4 text-3xl font-semibold tracking-tight text-red-300"
                    >
                        {{ peso(summary.total_expenses) }}
                    </p>
                    <p
                        class="mt-4 border-t border-white/10 pt-4 text-sm text-zinc-500"
                    >
                        Total cost for this filter.
                    </p>
                </div>

                <div
                    class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-6"
                >
                    <p
                        class="text-xs uppercase tracking-[0.26em] text-zinc-600"
                    >
                        Expense Count
                    </p>
                    <p
                        class="mt-4 text-4xl font-semibold tracking-tight text-white"
                    >
                        {{ summary.expense_count }}
                    </p>
                    <p
                        class="mt-4 border-t border-white/10 pt-4 text-sm text-zinc-500"
                    >
                        Number of recorded expenses.
                    </p>
                </div>

                <div
                    class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-6"
                >
                    <p
                        class="text-xs uppercase tracking-[0.26em] text-zinc-600"
                    >
                        Average Expense
                    </p>
                    <p
                        class="mt-4 text-3xl font-semibold tracking-tight text-white"
                    >
                        {{ peso(summary.average_expense) }}
                    </p>
                    <p
                        class="mt-4 border-t border-white/10 pt-4 text-sm text-zinc-500"
                    >
                        Average cost per entry.
                    </p>
                </div>
            </section>

            <!-- FILTERS -->
            <section
                class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5"
            >
                <div
                    class="grid gap-4 xl:grid-cols-[1fr_220px_220px_auto_auto]"
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
            </section>

            <!-- CATEGORY BREAKDOWN -->
            <section
                class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-6"
            >
                <div class="mb-5">
                    <p
                        class="text-xs uppercase tracking-[0.32em] text-zinc-600"
                    >
                        Category Breakdown
                    </p>
                    <h3 class="mt-2 text-xl font-semibold text-white">
                        Where your money went
                    </h3>
                </div>

                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <div
                        v-for="item in categoryBreakdown"
                        :key="item.category_name"
                        class="rounded-2xl border border-white/10 bg-white/[0.03] p-5"
                    >
                        <p class="text-sm font-semibold text-white">
                            {{ item.category_name || "General" }}
                        </p>
                        <p class="mt-3 text-2xl font-semibold text-red-300">
                            {{ peso(item.total) }}
                        </p>
                    </div>

                    <div
                        v-if="!categoryBreakdown.length"
                        class="rounded-2xl border border-white/10 bg-white/[0.03] p-5 text-sm text-zinc-500"
                    >
                        No expenses for this period.
                    </div>
                </div>
            </section>

            <!-- TABLE -->
            <section
                class="overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D]"
            >
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10">
                        <thead>
                            <tr class="bg-white/[0.02]">
                                <th
                                    class="px-6 py-4 text-left text-xs uppercase tracking-[0.22em] text-zinc-600"
                                >
                                    Expense
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs uppercase tracking-[0.22em] text-zinc-600"
                                >
                                    Category
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs uppercase tracking-[0.22em] text-zinc-600"
                                >
                                    Date
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs uppercase tracking-[0.22em] text-zinc-600"
                                >
                                    Amount
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
                                v-for="expense in expenses.data"
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

                                <td class="px-6 py-5 text-sm text-zinc-400">
                                    {{ expense.category || "General" }}
                                </td>

                                <td class="px-6 py-5 text-sm text-zinc-400">
                                    {{ expense.spent_at || "—" }}
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
                                            class="rounded-xl border border-white/10 px-3 py-2 text-xs font-medium text-zinc-300 transition hover:border-white/30 hover:text-white"
                                            @click="openEditModal(expense)"
                                        >
                                            Edit
                                        </button>

                                        <button
                                            type="button"
                                            class="rounded-xl border border-red-500/20 px-3 py-2 text-xs font-medium text-red-300 transition hover:bg-red-500/10"
                                            @click="openDeleteModal(expense)"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!expenses.data.length">
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

                <div
                    v-if="expenses.links?.length > 3"
                    class="flex flex-wrap gap-2 border-t border-white/10 p-5"
                >
                    <Link
                        v-for="link in expenses.links"
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
                </div>
            </section>
        </div>

        <!-- ADD / EDIT MODAL -->
        <Teleport to="body">
            <div
                v-if="showExpenseModal"
                class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/80 px-4 py-6 backdrop-blur-sm"
            >
                <div class="absolute inset-0" @click="closeExpenseModal"></div>

                <form
                    @submit.prevent="submitExpense"
                    class="relative w-full max-w-xl rounded-[2rem] border border-white/10 bg-[#0B0B0D] p-6 shadow-2xl shadow-black"
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

                    <div class="mt-6 flex justify-end gap-3">
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
                class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/80 px-4 py-6 backdrop-blur-sm"
            >
                <div class="absolute inset-0" @click="closeDeleteModal"></div>

                <div
                    class="relative w-full max-w-md rounded-[2rem] border border-white/10 bg-[#0B0B0D] p-6 shadow-2xl shadow-black"
                >
                    <p class="text-xs uppercase tracking-[0.3em] text-zinc-600">
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
                            {{ selectedExpense.title }} </span
                        >.
                    </p>

                    <div class="mt-6 flex justify-end gap-3">
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
