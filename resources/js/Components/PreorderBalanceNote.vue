<script setup>
defineProps({ money: { type: Object, default: () => ({}) } });
const peso = (n) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(Number(n || 0));
</script>

<template>
    <div
        v-if="money.customer_deposits || money.supplier_spend"
        class="mb-5 rounded-2xl border border-amber-300/30 bg-amber-100/10 p-4 text-sm leading-7 text-amber-600"
    >
        Cash includes {{ peso(money.customer_deposits) }} in customer deposits
        and deducts {{ peso(money.supplier_spend) }} in pending supplier
        spending. These are excluded from sales and profit until fulfillment.
        Fund value excludes customer deposits and includes pending supplier
        costs.
    </div>
</template>
