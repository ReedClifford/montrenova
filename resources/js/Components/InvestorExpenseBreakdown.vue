<script setup>
import { computed, ref } from "vue";

const props = defineProps({
    records: { type: Array, default: () => [] },
    startDate: String,
});
const search = ref("");
const category = ref("");
const treatment = ref("");
const from = ref("");
const to = ref("");
const money = (n) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(Number(n || 0));
const date = (value) =>
    new Intl.DateTimeFormat("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
    }).format(new Date(value + "T00:00:00"));
const cents = (row) => Math.round(Number(row.amount) * 100);
const total = (rows) => rows.reduce((sum, row) => sum + cents(row), 0) / 100;
const labels = {
    operating: "Operating expense",
    pending_capital: "Pending preorder capital",
    fulfilled_capital: "Fulfilled watch capital",
};
const categories = computed(() =>
    [...new Set(props.records.map((r) => r.category))].sort(),
);
const operatingTotal = computed(() =>
    total(props.records.filter((r) => r.treatment === "operating")),
);
const capitalTotal = computed(() =>
    total(props.records.filter((r) => r.treatment !== "operating")),
);
const filtered = computed(() =>
    props.records.filter((r) => {
        const matches = [r.title, r.category, r.notes, r.method, r.order_id]
            .join(" ")
            .toLowerCase()
            .includes(search.value.trim().toLowerCase());
        return (
            matches &&
            (!category.value || r.category === category.value) &&
            (!treatment.value || r.treatment === treatment.value) &&
            (!from.value || r.date >= from.value) &&
            (!to.value || r.date <= to.value)
        );
    }),
);
const categoryTotals = computed(() => {
    const groups = new Map();
    for (const row of filtered.value)
        groups.set(row.category, (groups.get(row.category) || 0) + cents(row));
    return [...groups]
        .map(([name, amount]) => ({ name, amount: amount / 100 }))
        .sort((a, b) => b.amount - a.amount);
});
function reset() {
    search.value = "";
    category.value = "";
    treatment.value = "";
    from.value = "";
    to.value = "";
}
</script>

<template>
    <section
        class="investor-costs"
        aria-label="Expense and supplier cost breakdown"
    >
        <header>
            <div>
                <p class="eyebrow">Expense transparency</p>
                <h2>Expenses & supplier costs</h2>
                <p>
                    All recorded costs within this dashboard’s scope, from
                    {{ date(startDate) }}. No month or year filter is applied by
                    default.
                </p>
            </div>
            <span class="count">{{ records.length }} records</span>
        </header>
        <div class="totals">
            <div>
                <span>Operating expenses</span
                ><strong>{{ money(operatingTotal) }}</strong
                ><small>Deducted from gross profit</small>
            </div>
            <div>
                <span>Preorder supplier / direct costs</span
                ><strong>{{ money(capitalTotal) }}</strong
                ><small>Pending acquisition or fulfilled watch capital</small>
            </div>
            <div>
                <span>Total recorded costs</span
                ><strong>{{ money(operatingTotal + capitalTotal) }}</strong
                ><small>Cancellation costs included once</small>
            </div>
        </div>
        <p class="explanation">
            Supplier costs reduce cash when paid. Pending preorder costs become
            watch capital at fulfillment and are not deducted again as operating
            expenses. Cancelled supplier costs appear under their cancellation
            expense, with the original transactions below it.
        </p>
        <div class="filters">
            <label class="search"
                >Search expenses<input
                    v-model="search"
                    type="search"
                    placeholder="Title, watch, category or notes" /></label
            ><label
                >Category<select v-model="category">
                    <option value="">All categories</option>
                    <option v-for="c in categories" :key="c">{{ c }}</option>
                </select></label
            ><label
                >Cost type<select v-model="treatment">
                    <option value="">All cost types</option>
                    <option
                        v-for="(label, key) in labels"
                        :key="key"
                        :value="key"
                    >
                        {{ label }}
                    </option>
                </select></label
            ><label
                >From<input
                    v-model="from"
                    type="date"
                    :max="to || undefined" /></label
            ><label
                >To<input
                    v-model="to"
                    type="date"
                    :min="from || undefined" /></label
            ><button type="button" @click="reset">Clear filters</button>
        </div>
        <div class="results" role="status">
            <span>{{ filtered.length }} of {{ records.length }} records</span
            ><strong>Shown total: {{ money(total(filtered)) }}</strong>
        </div>
        <details v-if="categoryTotals.length" class="category-summary" open>
            <summary>Breakdown by category</summary>
            <div v-for="group in categoryTotals" :key="group.name">
                <span>{{ group.name }}</span
                ><strong>{{ money(group.amount) }}</strong>
            </div>
        </details>
        <div v-if="!filtered.length" class="empty">
            <h3>
                {{
                    records.length
                        ? "No matching expenses"
                        : "No recorded costs yet"
                }}
            </h3>
            <p>
                {{
                    records.length
                        ? "Clear or adjust the filters to see all records."
                        : "Operating expenses and paid preorder costs will appear here."
                }}
            </p>
        </div>
        <div v-else>
            <div class="headings">
                <span>Expense / details</span><span>Category & treatment</span
                ><span>Date</span><span>Amount</span>
            </div>
            <article
                v-for="record in filtered"
                :key="record.id"
                class="cost-row"
            >
                <div class="description">
                    <h3>{{ record.title }}</h3>
                    <p v-if="record.notes" class="notes">{{ record.notes }}</p>
                    <p v-if="record.method" class="method">
                        Paid via {{ record.method }}
                    </p>
                    <details v-if="record.details.length" class="cost-details">
                        <summary>
                            Original supplier transactions (included in this
                            expense)
                        </summary>
                        <div v-for="detail in record.details" :key="detail.id">
                            <div>
                                <strong>{{ money(detail.amount) }}</strong> ·
                                {{ date(detail.date) }} · {{ detail.method }}
                                <p v-if="detail.notes" class="notes">
                                    {{ detail.notes }}
                                </p>
                            </div>
                        </div>
                    </details>
                </div>
                <div>
                    <span class="category">{{ record.category }}</span
                    ><span class="treatment" :class="record.treatment">{{
                        labels[record.treatment]
                    }}</span>
                </div>
                <time :datetime="record.date">{{ date(record.date) }}</time
                ><strong class="amount">{{ money(record.amount) }}</strong>
            </article>
        </div>
    </section>
</template>

<style scoped>
.investor-costs {
    background: #ffffff;
    color: #334155;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    overflow: hidden;
}
.investor-costs header {
    padding: 24px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
}
.eyebrow {
    color: #4f46e5;
    font-size: 10px;
    letter-spacing: 0.16em;
    text-transform: uppercase;
}
.investor-costs h2 {
    font-size: 22px;
    font-weight: 650;
    letter-spacing: -0.03em;
    margin: 5px 0 8px;
    color: #0f172a;
}
.investor-costs header p:not(.eyebrow) {
    font-size: 13px;
    line-height: 1.8;
    color: #64748b;
}
.count {
    white-space: nowrap;
    color: #4f46e5;
    background: #4f46e510;
    padding: 7px 11px;
    border-radius: 24px;
    font-size: 12px;
}
.totals {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    padding: 0 24px;
}
.totals > div {
    padding: 18px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #f8fafc;
}
.totals span,
.totals small {
    display: block;
    color: #64748b;
    font-size: 12px;
    line-height: 1.6;
}
.totals strong {
    display: block;
    color: #0f172a;
    font-size: 23px;
    font-weight: 600;
    margin: 9px 0;
    overflow-wrap: anywhere;
}
.explanation {
    font-size: 12px;
    color: #475569;
    background: #4f46e508;
    border: 1px solid #4f46e520;
    border-radius: 10px;
    padding: 14px 16px;
    margin: 20px 24px;
    line-height: 1.9;
}
.filters {
    display: grid;
    grid-template-columns: 2fr 1.4fr 1.5fr 1fr 1fr;
    gap: 12px;
    padding: 0 24px;
}
.filters label {
    color: #64748b;
    font-size: 11px;
    display: block;
    min-width: 0;
}
.filters input,
.filters select {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #334155;
    border-radius: 9px;
    padding: 10px;
    width: 100%;
    margin-top: 6px;
    font-size: 12px;
    min-height: 44px;
    min-width: 0;
    color-scheme: light;
}
.filters button {
    grid-column: 1/-1;
    justify-self: end;
    color: #4f46e5;
    font-size: 12px;
    padding: 7px 10px;
    min-height: 40px;
}
.filters :focus-visible,
summary:focus-visible {
    outline: 2px solid #4f46e5;
    outline-offset: 3px;
}
.results {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    padding: 16px 24px;
    color: #64748b;
    font-size: 12px;
    border-bottom: 1px solid #e2e8f0;
}
.results strong {
    color: #0f172a;
}
.category-summary {
    margin: 16px 24px;
    padding: 12px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 12px;
}
.category-summary summary,
.cost-details summary {
    cursor: pointer;
    color: #4f46e5;
    line-height: 1.8;
}
.category-summary > div {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    margin-top: 12px;
    color: #475569;
}
.headings,
.cost-row {
    display: grid;
    grid-template-columns:
        minmax(0, 2fr) minmax(0, 1.4fr) minmax(0, 0.9fr)
        minmax(0, 1fr);
    gap: 20px;
    padding: 18px 24px;
}
.headings {
    color: #64748b;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    border-bottom: 1px solid #e2e8f0;
}
.headings span:last-child {
    text-align: right;
}
.cost-row {
    border-bottom: 1px solid #e2e8f0;
    align-items: start;
    font-size: 12px;
    overflow-wrap: anywhere;
}
.cost-row:last-child {
    border-bottom: 0;
}
.cost-row h3 {
    color: #0f172a;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.8;
}
.notes {
    white-space: pre-wrap;
    color: #64748b;
    font-size: 12px;
    line-height: 1.8;
    margin-top: 6px;
}
.method {
    color: #64748b;
    font-size: 11px;
    margin-top: 8px;
}
.category {
    display: block;
    color: #64748b;
    font-size: 12px;
    line-height: 1.8;
}
.treatment {
    display: inline-block;
    margin-top: 7px;
    border-radius: 6px;
    background: #f1f5f9;
    padding: 4px 7px;
    font-size: 10px;
    line-height: 1.6;
    color: #475569;
}
.treatment.pending_capital {
    color: #a16207;
    background: #a162070b;
}
.treatment.fulfilled_capital {
    color: #047857;
    background: #0478570b;
}
.cost-row time {
    color: #64748b;
    line-height: 1.8;
}
.amount {
    text-align: right;
    color: #b91c1c;
    font-size: 14px;
    line-height: 1.8;
    white-space: nowrap;
}
.cost-details {
    margin-top: 12px;
    font-size: 11px;
}
.cost-details > div {
    padding: 10px 0;
    border-bottom: 1px solid #e2e8f0;
    color: #64748b;
}
.empty {
    text-align: center;
    padding: 48px 20px;
}
.empty h3 {
    color: #334155;
    font-weight: 600;
}
.empty p {
    color: #64748b;
    font-size: 13px;
    margin-top: 10px;
}
@media (max-width: 1100px) {
    .filters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .filters .search {
        grid-column: 1/-1;
    }
    .totals strong {
        font-size: 19px;
    }
}
@media (max-width: 700px) {
    .investor-costs header {
        padding: 20px;
        flex-direction: column;
        gap: 10px;
    }
    .totals {
        grid-template-columns: 1fr;
        padding: 0 20px;
    }
    .totals > div {
        padding: 14px;
    }
    .totals strong {
        font-size: 23px;
        margin: 6px 0;
    }
    .explanation {
        margin: 16px 20px;
    }
    .filters {
        padding: 0 20px;
    }
    .filters label:nth-child(2),
    .filters label:nth-child(3) {
        grid-column: 1/-1;
    }
    .results {
        padding: 16px 20px;
    }
    .headings {
        display: none;
    }
    .cost-row {
        grid-template-columns: minmax(0, 1fr) auto;
        padding: 20px;
        gap: 12px;
    }
    .cost-row .description {
        grid-column: 1/-1;
    }
    .cost-row > div:nth-child(2) {
        grid-column: 1/-1;
    }
    .cost-row time {
        align-self: center;
    }
    .category-summary {
        margin: 16px 20px;
    }
    .amount {
        font-size: 16px;
    }
}
</style>
