<script setup>
import UiIcon from "@/Components/UiIcon.vue";
import { Head } from "@inertiajs/vue3";
import PublicHeader from "@/Components/PublicHeader.vue";
import { computed } from "vue";
const props = defineProps({ order: Object });
const money = (n) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(Number(n || 0));
const date = (value) =>
    value
        ? new Intl.DateTimeFormat("en-PH", {
              dateStyle: "medium",
              timeZone: "Asia/Manila",
          }).format(new Date(value))
        : "To be confirmed";
const steps = [
    ["awaiting_deposit", "Awaiting deposit"],
    ["confirmed", "Confirmed"],
    ["ordered", "Sourcing your watch"],
    ["arrived", "Watch arrived"],
    ["fulfilled", "Completed"],
];
const current = computed(() =>
    steps.findIndex(([status]) => status === props.order.status),
);
const message = computed(
    () =>
        ({
            awaiting_deposit: `A payment of ${money(Math.max(0, props.order.required_deposit - props.order.paid))} more is needed to confirm your preorder.`,
            confirmed:
                "Your deposit is confirmed. Our team will arrange your supplier order.",
            ordered:
                "Your watch has been ordered. We will contact you when it arrives.",
            arrived:
                props.order.balance > 0
                    ? "Your watch has arrived. Please coordinate your remaining payment and handover with us."
                    : "Your watch has arrived and is fully paid. Please contact us to arrange handover.",
            fulfilled:
                "Your preorder is complete. Thank you for choosing Montre Nova.",
            cancelled:
                props.order.refundable > 0
                    ? "Your preorder has been cancelled. Contact us to coordinate your pending refund."
                    : "Your preorder has been cancelled. No refund remains pending.",
        })[props.order.status],
);
const messenger = computed(
    () =>
        "https://m.me/montrenova?text=" +
        encodeURIComponent(
            `Hi Montre Nova! I have a question about preorder ${props.order.reference}.`,
        ),
);
</script>
<template>
    <Head title="Your preorder | Montre Nova"
        ><meta name="robots" content="noindex,nofollow,noarchive" /><meta
            name="referrer"
            content="no-referrer"
    /></Head>
    <div class="tracking-page">
        <PublicHeader position="sticky" />
        <main class="tracking-main">
            <p class="eyebrow">
                Private customer tracking · {{ order.reference }}
            </p>
            <h1>Your preorder</h1>
            <p class="break-words">
                Preorder for <strong>{{ order.customer_name }}</strong>
            </p>
            <p class="muted">
                Last updated {{ date(order.updated_at) }}. Keep this link
                private.
            </p>
            <section class="tracking-panel watch-summary">
                <img
                    v-if="order.image"
                    :src="order.image"
                    :alt="order.watch_name"
                    referrerpolicy="no-referrer"
                />
                <div>
                    <h2>{{ order.watch_name }}</h2>
                    <p class="muted">{{ order.watch_reference }}</p>
                    <p>
                        Estimated arrival: {{ date(order.estimated_arrival) }}
                    </p>
                    <small class="muted"
                        >Arrival dates are estimates. Our team will confirm
                        handover details.</small
                    >
                </div>
            </section>
            <section class="tracking-panel" aria-label="Order progress">
                <h2>
                    {{
                        order.status === "cancelled"
                            ? "Order cancelled"
                            : steps[current]?.[1]
                    }}
                </h2>
                <p class="status-message" role="status">{{ message }}</p>
                <ol v-if="order.status !== 'cancelled'" class="tracking-steps">
                    <li
                        v-for="([status, title], index) in steps"
                        :key="status"
                        :class="{ done: index <= current }"
                        :aria-current="index === current ? 'step' : undefined"
                    >
                        <span>{{ index < current ? "✓" : index + 1 }}</span
                        >{{ title }}
                    </li>
                </ol>
            </section>
            <div class="tracking-columns">
                <section class="tracking-panel">
                    <h2>Payment summary</h2>
                    <dl>
                        <div>
                            <dt>Agreed price</dt>
                            <dd>{{ money(order.total_price) }}</dd>
                        </div>
                        <div>
                            <dt>Required deposit</dt>
                            <dd>{{ money(order.required_deposit) }}</dd>
                        </div>
                        <div>
                            <dt>Payments less refunds</dt>
                            <dd>{{ money(order.paid) }}</dd>
                        </div>
                        <div class="balance">
                            <dt>
                                {{
                                    order.status === "cancelled"
                                        ? "Refund pending"
                                        : "Remaining balance"
                                }}
                            </dt>
                            <dd>
                                {{
                                    money(
                                        order.status === "cancelled"
                                            ? order.refundable
                                            : order.balance,
                                    )
                                }}
                            </dd>
                        </div>
                    </dl>
                    <p class="muted">
                        Payments appear after our team records them. Contact us
                        if a recent payment is missing.
                    </p>
                </section>
                <section class="tracking-panel">
                    <h2>Payment history</h2>
                    <p v-if="!order.transactions.length" class="muted">
                        No payments recorded yet.
                    </p>
                    <ul class="transactions">
                        <li v-for="entry in order.transactions" :key="entry.id">
                            <div>
                                <strong>{{
                                    entry.type === "refund"
                                        ? "Refund"
                                        : "Payment"
                                }}</strong>
                                <p class="muted">
                                    {{ date(entry.paid_at) }} ·
                                    {{ entry.method }}
                                </p>
                            </div>
                            <strong>{{ money(entry.amount) }}</strong>
                        </li>
                    </ul>
                </section>
            </div>
            <a
                :href="messenger"
                target="_blank"
                rel="noreferrer"
                class="tracking-contact"
                >Ask about this preorder on Messenger <UiIcon name="chat"
            /></a>
        </main>
    </div>
</template>
<style scoped>
.tracking-page {
    min-height: 100vh;
    background: #0c0c0e;
    color: #f4f4f5;
}
.tracking-main {
    max-width: 1050px;
    margin: auto;
    padding: 40px 20px 70px;
}
.eyebrow {
    font-size: 10px;
    letter-spacing: 0.13em;
    text-transform: uppercase;
    color: #d6bd8f;
}
h1 {
    font-size: clamp(28px, 5vw, 42px);
    margin: 10px 0;
}
h2 {
    font-size: 19px;
    margin-bottom: 12px;
}
.muted {
    color: #a1a1aa;
    font-size: 13px;
    line-height: 1.7;
}
.tracking-panel {
    background: #18181b;
    border: 1px solid #ffffff17;
    border-radius: 12px;
    padding: 24px;
    margin-top: 22px;
    min-width: 0;
}
.watch-summary {
    display: flex;
    align-items: center;
    gap: 24px;
}
.watch-summary img {
    width: 120px;
    height: 120px;
    object-fit: contain;
    border-radius: 8px;
    background: #101012;
}
.watch-summary p {
    margin-bottom: 8px;
}
.tracking-steps {
    display: flex;
    gap: 12px;
    margin: 24px 0 0;
    list-style: none;
    padding: 0;
}
.tracking-steps li {
    flex: 1;
    color: #94949e;
    font-size: 12px;
    line-height: 1.5;
}
.tracking-steps span {
    display: grid;
    place-items: center;
    width: 30px;
    height: 30px;
    border: 1px solid #55555d;
    border-radius: 50%;
    margin-bottom: 10px;
}
.tracking-steps .done {
    color: #e2cba5;
}
.done span {
    background: #d6bd8f;
    color: #18181b;
    border-color: #d6bd8f;
}
.status-message {
    font-size: 14px;
    line-height: 1.8;
    color: #ddd;
}
.tracking-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 22px;
}
dl > div {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    padding: 13px 0;
    border-bottom: 1px solid #ffffff12;
    font-size: 13px;
}
dd {
    text-align: right;
    font-weight: 650;
}
.balance {
    color: #e2cba5;
}
.transactions {
    list-style: none;
    padding: 0;
}
.transactions li {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    padding: 14px 0;
    border-bottom: 1px solid #ffffff12;
    font-size: 13px;
}
.tracking-contact {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 48px;
    border: 1px solid #d6bd8f65;
    color: #e2cba5;
    border-radius: 8px;
    padding: 14px;
    margin-top: 24px;
    text-align: center;
    font-size: 13px;
}
a:focus-visible {
    outline: 2px solid #e2cba5;
    outline-offset: 4px;
}
@media (max-width: 639px) {
    .tracking-main {
        padding: 24px 16px 48px;
    }
    .tracking-panel {
        padding: 18px;
    }
    .watch-summary {
        align-items: flex-start;
        gap: 14px;
    }
    .watch-summary img {
        width: 80px;
        height: 100px;
    }
    .tracking-columns {
        grid-template-columns: 1fr;
        gap: 0;
    }
    .tracking-steps {
        flex-direction: column;
        gap: 16px;
    }
    .tracking-steps li {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .tracking-steps span {
        margin: 0;
        flex: none;
    }
    dl > div {
        flex-wrap: wrap;
    }
    .transactions li {
        flex-wrap: wrap;
    }
}
</style>
