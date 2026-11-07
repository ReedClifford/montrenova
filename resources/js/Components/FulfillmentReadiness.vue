<script setup>
import { computed } from "vue";
const props = defineProps({ order: Object });
const emit = defineEmits(["payment", "cost"]);
const money = (n) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(Number(n || 0));
const checks = computed(() => [
    {
        label: "Watch has arrived",
        done: ["arrived", "fulfilled"].includes(props.order.status),
        note: ["arrived", "fulfilled"].includes(props.order.status)
            ? "Ready for handover"
            : "Mark the order as arrived after receiving the watch.",
    },
    {
        label: "Customer fully paid",
        done: props.order.balance <= 0,
        note:
            props.order.balance > 0
                ? `${money(props.order.balance)} still to collect`
                : "No remaining balance",
        action: "payment",
    },
    {
        label: "Actual costs recorded",
        done: props.order.cost > 0,
        note:
            props.order.cost > 0
                ? `${money(props.order.cost)} recorded`
                : "Record the watch acquisition and direct costs.",
        action: "cost",
    },
]);
const remaining = computed(() => checks.value.filter((c) => !c.done).length);
</script>
<template>
    <div class="fulfillment-readiness" aria-label="Fulfillment requirements">
        <div class="readiness-title">
            <strong>Ready for handover?</strong
            ><span>{{ 3 - remaining }}/3 complete</span>
        </div>
        <ul>
            <li
                v-for="check in checks"
                :key="check.label"
                :class="{ complete: check.done }"
            >
                <span class="check-icon" aria-hidden="true">{{
                    check.done ? "✓" : "○"
                }}</span>
                <div>
                    <strong>{{ check.label }}</strong>
                    <p>{{ check.note }}</p>
                    <button
                        v-if="!check.done && check.action"
                        type="button"
                        @click="emit(check.action)"
                    >
                        {{
                            check.action === "payment"
                                ? "Record payment"
                                : "Record cost"
                        }}
                        →
                    </button>
                </div>
            </li>
        </ul>
        <p v-if="remaining" id="fulfillment-blockers" class="readiness-helper">
            Complete
            {{
                remaining === 1
                    ? "the remaining requirement"
                    : "all remaining requirements"
            }}
            to enable Fulfill Preorder.
        </p>
        <p v-else id="fulfillment-blockers" class="readiness-helper">
            All requirements met. Confirm the serial number and hand over the
            watch.
        </p>
    </div>
</template>
<style scoped>
.fulfillment-readiness {
    border: 1px solid #ffffff15;
    background: #ffffff03;
    border-radius: 12px;
    padding: 16px;
    margin-top: 16px;
}
.readiness-title {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    font-size: 12px;
    color: #e4e4e7;
}
.readiness-title > span {
    font-size: 11px;
    color: #d5bd93;
    white-space: nowrap;
}
.fulfillment-readiness ul {
    margin-top: 16px;
    display: grid;
    gap: 16px;
}
.fulfillment-readiness li {
    display: flex;
    align-items: start;
    gap: 10px;
}
.check-icon {
    height: 20px;
    width: 20px;
    flex-shrink: 0;
    color: #71717a;
    line-height: 20px;
}
.complete .check-icon {
    color: #6ee7b7;
}
.fulfillment-readiness li strong {
    font-size: 12px;
    font-weight: 500;
    color: #d4d4d8;
}
.fulfillment-readiness li p {
    color: #a1a1aa;
    font-size: 11px;
    line-height: 1.7;
    margin-top: 4px;
}
.fulfillment-readiness li button {
    font-size: 11px !important;
    min-height: 36px !important;
    padding: 4px 0 !important;
    border: 0 !important;
    background: none !important;
    color: #e4d1ac !important;
    justify-content: start !important;
}
.readiness-helper {
    font-size: 11px;
    color: #c6bda9;
    border-top: 1px solid #ffffff10;
    margin-top: 16px;
    padding-top: 12px;
    line-height: 1.7;
}
</style>
