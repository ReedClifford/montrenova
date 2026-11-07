<script setup>
import UiIcon from "@/Components/UiIcon.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import PreorderPhotoGallery from "@/Components/PreorderPhotoGallery.vue";
import FulfillmentReadiness from "@/Components/FulfillmentReadiness.vue";
import AttachmentPreview from "@/Components/AttachmentPreview.vue";
import FormFeedback from "@/Components/FormFeedback.vue";
import { paymentMethods } from "@/Utils/paymentMethods";
import { useUnsavedChanges } from "@/Composables/useUnsavedChanges";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import "./preorders.css";
const props = defineProps({ order: Object, trackingUrl: String });
const tracking = useForm({});
const trackingCopied = ref(false);
const trackingCopyError = ref(false);
async function copyTracking() {
    try {
        await navigator.clipboard.writeText(props.trackingUrl);
        trackingCopied.value = true;
        trackingCopyError.value = false;
    } catch {
        trackingCopyError.value = true;
    }
}
function generateTracking() {
    tracking.post(route("admin.preorders.tracking", props.order.id), {
        preserveScroll: true,
        onSuccess: () => {
            dialog.value = null;
            trackingCopied.value = false;
        },
    });
}
const page = usePage();
const dialog = ref(null);
const money = (n) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(Number(n || 0));
const label = (s) => s.replaceAll("_", " ");
const watch = computed(() => props.order.listing.watch_snapshot);
const closed = computed(() =>
    ["fulfilled", "cancelled"].includes(props.order.status),
);
const today = () => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
};
const entry = useForm({
    request_key: "",
    type: "payment",
    amount: "",
    paid_at: today(),
    method: "",
    reference: "",
    notes: "",
    proof: null,
});
const transition = useForm({ status: "", cancellation_reason: "" });
const fulfillment = useForm({ serial_number: "" });
const cleanDialog = ref("");
const activeFormData = () =>
    JSON.stringify(
        dialog.value === "entry"
            ? entry.data()
            : dialog.value === "status"
              ? transition.data()
              : fulfillment.data(),
        (key, value) =>
            value instanceof File
                ? {
                      name: value.name,
                      size: value.size,
                      lastModified: value.lastModified,
                  }
                : value,
    );
const { confirmDiscard } = useUnsavedChanges({
    dirty: () =>
        ["entry", "status", "fulfill"].includes(dialog.value) &&
        Boolean(cleanDialog.value) &&
        activeFormData() !== cleanDialog.value,
    busy: () =>
        entry.processing || transition.processing || fulfillment.processing,
});
async function closeDialog() {
    if (!busy.value && (await confirmDiscard())) dialog.value = null;
}
function openFulfillment() {
    fulfillment.clearErrors();
    dialog.value = "fulfill";
    cleanDialog.value = activeFormData();
}
const entryTitle = computed(
    () =>
        ({
            payment: "Record customer payment",
            cost: "Record supplier / direct cost",
            refund: "Record customer refund",
        })[entry.type],
);
const progress = computed(() =>
    Math.min(
        100,
        Math.max(0, (props.order.paid / Number(props.order.total_price)) * 100),
    ),
);
const steps = [
    "awaiting_deposit",
    "confirmed",
    "ordered",
    "arrived",
    "fulfilled",
];
function openEntry(type) {
    entry.reset();
    entry.clearErrors();
    entry.type = type;
    if (type === "payment")
        entry.amount =
            props.order.status === "awaiting_deposit"
                ? Math.max(
                      0,
                      props.order.required_deposit - props.order.paid,
                  ).toFixed(2)
                : Number(props.order.balance).toFixed(2);
    if (type === "refund")
        entry.amount = Number(props.order.refundable).toFixed(2);
    entry.request_key = crypto.randomUUID();
    entry.paid_at = today();
    dialog.value = "entry";
    cleanDialog.value = activeFormData();
}
function changeStatus(status) {
    transition.clearErrors();
    transition.status = status;
    dialog.value = "status";
    cleanDialog.value = activeFormData();
}
function submitEntry() {
    entry.post(route("admin.preorders.entries.store", props.order.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            dialog.value = null;
            entry.reset();
        },
    });
}
function submitStatus() {
    transition.patch(route("admin.preorders.update", props.order.id), {
        preserveScroll: true,
        onSuccess: () => (dialog.value = null),
    });
}
function fulfill() {
    fulfillment.post(route("admin.preorders.fulfill", props.order.id), {
        preserveScroll: true,
        onSuccess: () => (dialog.value = null),
    });
}
const busy = computed(
    () =>
        entry.processing ||
        transition.processing ||
        fulfillment.processing ||
        tracking.processing,
);
</script>

<template>
    <Head :title="`Preorder #${order.id}`" />
    <AuthenticatedLayout title="Preorder details"
        ><div class="po">
            <Link :href="route('admin.preorders.index')" class="muted"
                >← All preorders</Link
            >
            <div class="row between" style="margin-top: 20px">
                <div>
                    <p class="eyebrow">Preorder #{{ order.id }}</p>
                    <h1>{{ order.customer_name }}</h1>
                    <p class="muted break">{{ order.contact }}</p>
                </div>
                <span class="badge">{{ label(order.status) }}</span>
            </div>
            <div v-if="page.props.flash?.success" class="success" role="status">
                {{ page.props.flash.success }}
            </div>
            <div
                v-if="order.status !== 'cancelled'"
                class="steps"
                aria-label="Order progress"
            >
                <span
                    v-for="(step, i) in steps"
                    :key="step"
                    class="step"
                    :class="{ active: steps.indexOf(order.status) >= i }"
                    >{{ i + 1 }} · {{ label(step) }}</span
                >
            </div>
            <div class="detail">
                <div>
                    <section class="panel">
                        <h2>Customer tracking</h2>
                        <p class="muted" style="margin: 12px 0">
                            Share this private link directly with the customer.
                            Anyone with the link can view the order status and
                            payment summary.
                        </p>
                        <template v-if="trackingUrl">
                            <input
                                aria-label="Private tracking link"
                                :value="trackingUrl"
                                readonly
                                @focus="$event.target.select()"
                                style="width: 100%; min-width: 0"
                            />
                            <div class="row" style="margin-top: 12px">
                                <button @click="copyTracking">
                                    {{
                                        trackingCopied ? "Copied" : "Copy link"
                                    }}
                                </button>
                                <a
                                    :href="trackingUrl"
                                    target="_blank"
                                    rel="noreferrer"
                                    class="button"
                                    >Preview customer view <UiIcon name="eye" /></a
                                >
                                <button @click="dialog = 'resetTracking'">
                                    Reset link
                                </button>
                            </div>
                            <p
                                v-if="trackingCopyError"
                                role="status"
                                class="muted"
                            >
                                Select and copy the link above.
                            </p>
                        </template>
                        <button
                            v-else
                            class="primary"
                            :disabled="tracking.processing"
                            @click="generateTracking"
                        >
                            Create tracking link
                        </button>
                    </section>
                    <section class="panel">
                        <div class="row">
                            <div>
                                <h2>
                                    {{ watch.brand }} {{ watch.model_name }}
                                </h2>
                                <p class="muted">
                                    {{ watch.reference_number }}
                                </p>
                            </div>
                        </div>
                        <div style="margin-top: 18px">
                            <PreorderPhotoGallery
                                :images="watch.images || []"
                                :primary-image="watch.primary_image_url"
                                :title="
                                    [watch.brand, watch.model_name]
                                        .filter(Boolean)
                                        .join(' ')
                                "
                                compact
                                badge="Reference photos"
                            />
                        </div>
                        <div class="grid" style="margin-top: 20px">
                            <div>
                                <p class="muted">Estimated arrival</p>
                                <p>
                                    {{
                                        order.estimated_arrival ||
                                        "To be confirmed"
                                    }}
                                </p>
                            </div>
                            <div>
                                <p class="muted">
                                    Required deposit ·
                                    {{ order.deposit_percentage }}%
                                </p>
                                <p>{{ money(order.required_deposit) }}</p>
                            </div>
                        </div>
                        <p
                            v-if="order.notes"
                            class="muted break"
                            style="white-space: pre-wrap; margin-top: 16px"
                        >
                            {{ order.notes }}
                        </p>
                    </section>
                    <section class="panel">
                        <div class="row between">
                            <h2>Transactions</h2>
                            <span class="muted"
                                >{{ order.entries.length }} records</span
                            >
                        </div>
                        <p class="muted">
                            Payments, supplier spending and refunds stay in the
                            order history.
                        </p>
                        <div v-if="!order.entries.length" class="empty muted">
                            No transactions recorded yet.
                        </div>
                        <div
                            v-for="e in order.entries"
                            :key="e.id"
                            class="item"
                        >
                            <div class="row between">
                                <div>
                                    <span class="badge">{{
                                        e.type === "cost"
                                            ? "Supplier / direct cost"
                                            : e.type
                                    }}</span>
                                    <p class="muted" style="margin-top: 8px">
                                        {{ e.paid_at }} · {{ e.method }}
                                    </p>
                                </div>
                                <strong>{{ money(e.amount) }}</strong>
                            </div>
                            <p v-if="e.reference" class="muted break">
                                Reference: {{ e.reference }}
                            </p>
                            <p
                                v-if="e.notes"
                                class="muted break"
                                style="white-space: pre-wrap"
                            >
                                {{ e.notes }}
                            </p>
                            <a
                                v-if="e.has_proof"
                                :href="route('admin.preorders.proof', e.id)"
                                class="button"
                                style="margin-top: 10px"
                                >Download proof</a
                            >
                        </div>
                    </section>
                </div>
                <div>
                    <section class="panel">
                        <div class="row between">
                            <h2>Payment summary</h2>
                            <span class="badge">{{
                                label(order.payment_status)
                            }}</span>
                        </div>
                        <div class="money-row">
                            <span class="muted">Agreed price</span
                            ><strong>{{ money(order.total_price) }}</strong>
                        </div>
                        <div class="money-row">
                            <span class="muted">Net customer payments</span
                            ><strong>{{ money(order.paid) }}</strong>
                        </div>
                        <progress
                            :value="progress"
                            max="100"
                            :aria-label="`${Math.round(progress)}% paid`"
                        />
                        <div class="money-row">
                            <span>{{
                                order.status === "cancelled"
                                    ? "Refund pending"
                                    : "Remaining balance"
                            }}</span
                            ><strong style="font-size: 24px; color: #e7d5b4">{{
                                money(
                                    order.status === "cancelled"
                                        ? order.refundable
                                        : order.balance,
                                )
                            }}</strong>
                        </div>
                        <div
                            v-if="!closed"
                            class="row"
                            style="margin-top: 20px"
                        >
                            <button
                                class="primary"
                                @click="openEntry('payment')"
                                :disabled="order.balance <= 0"
                            >
                                Record payment</button
                            ><button @click="openEntry('cost')">
                                Record cost
                            </button>
                        </div>
                        <button
                            v-if="
                                order.status === 'cancelled' &&
                                order.refundable > 0
                            "
                            class="primary"
                            style="margin-top: 20px"
                            @click="openEntry('refund')"
                        >
                            Record refund
                        </button>
                    </section>
                    <section class="panel">
                        <h2>Cost & profit</h2>
                        <div class="money-row">
                            <span class="muted"
                                >Actual paid supplier & direct costs</span
                            ><strong>{{ money(order.cost) }}</strong>
                        </div>
                        <div class="money-row">
                            <span class="muted">{{
                                order.status === "fulfilled"
                                    ? "Recorded gross profit"
                                    : order.status === "cancelled"
                                      ? "Supplier spending written off"
                                      : "Projected gross profit"
                            }}</span
                            ><strong>{{
                                order.cost > 0 || order.status === "cancelled"
                                    ? money(
                                          order.status === "cancelled"
                                              ? order.cost
                                              : Number(order.total_price) -
                                                    order.cost,
                                      )
                                    : "Awaiting costs"
                            }}</strong>
                        </div>
                        <p class="muted" style="margin-top: 14px">
                            {{
                                order.status === "fulfilled"
                                    ? "Included in sales reports. General business expenses are deducted separately."
                                    : order.status === "cancelled"
                                      ? "Cancellation costs are recorded once as a business expense."
                                      : "Record all paid acquisition costs, including shipping. These become the new watch’s capital at fulfillment; do not enter them again in Expenses."
                            }}
                        </p>
                    </section>
                    <section class="panel">
                        <h2>Next step</h2>
                        <FulfillmentReadiness
                            v-if="!closed"
                            :order="order"
                            @payment="openEntry('payment')"
                            @cost="openEntry('cost')"
                        />
                        <p
                            v-if="order.status === 'awaiting_deposit'"
                            class="notice"
                        >
                            Record
                            {{
                                money(
                                    Math.max(
                                        0,
                                        order.required_deposit - order.paid,
                                    ),
                                )
                            }}
                            more in customer payments to confirm this preorder.
                        </p>
                        <template v-if="order.status === 'confirmed'"
                            ><p class="muted">
                                Deposit confirmed. Place the supplier order when
                                ready.
                            </p>
                            <button
                                class="primary"
                                style="margin-top: 14px"
                                @click="changeStatus('ordered')"
                            >
                                Mark ordered from supplier
                            </button></template
                        ><template v-if="order.status === 'ordered'"
                            ><p class="muted">
                                Waiting for the watch to arrive.
                            </p>
                            <button
                                class="primary"
                                style="margin-top: 14px"
                                @click="changeStatus('arrived')"
                            >
                                Mark as arrived
                            </button></template
                        ><template v-if="order.status === 'arrived'"
                            ><p class="muted">
                                Collect the remaining balance and record all
                                actual costs before handover.
                            </p>
                            <button
                                class="primary"
                                style="margin-top: 14px"
                                @click="openFulfillment"
                                :disabled="order.balance > 0 || order.cost <= 0"
                                aria-describedby="fulfillment-blockers"
                                :title="
                                    order.balance > 0
                                        ? 'Collect the remaining balance first'
                                        : order.cost <= 0
                                          ? 'Record actual supplier costs first'
                                          : 'Confirm handover'
                                "
                            >
                                Fulfill preorder
                            </button></template
                        ><template v-if="order.status === 'fulfilled'"
                            ><p class="notice">
                                Fulfilled. Sale #{{ order.fulfilled_watch_id }}
                                is included in sales and profit reports. The
                                customer’s one-year warranty starts on the
                                handover date.
                            </p>
                            <Link
                                :href="route('admin.sales.index')"
                                class="button"
                                >View sales</Link
                            ></template
                        ><template v-if="order.status === 'cancelled'"
                            ><p class="notice">
                                Order cancelled.
                                {{
                                    order.refundable > 0
                                        ? "The remaining deposit must be refunded."
                                        : "No customer refund remains."
                                }}
                            </p>
                            <p class="muted break">
                                {{ order.cancellation_reason }}
                            </p></template
                        ><button
                            v-if="!closed"
                            class="danger"
                            style="margin-top: 20px"
                            @click="changeStatus('cancelled')"
                        >
                            Cancel order
                        </button>
                    </section>
                </div>
            </div>
        </div>
        <Modal :show="dialog !== null" :closeable="!busy" @close="closeDialog"
            ><div class="po dialog">
                <form
                    v-if="dialog === 'resetTracking'"
                    @submit.prevent="generateTracking"
                >
                    <h2>Reset tracking link?</h2>
                    <p class="notice">
                        The previous link will stop working. Share the new link
                        with the customer after resetting.
                    </p>
                    <div class="row">
                        <button
                            type="button"
                            :disabled="tracking.processing"
                            @click="closeDialog"
                        >
                            Keep current link</button
                        ><button
                            class="primary"
                            :disabled="tracking.processing"
                        >
                            Reset link
                        </button>
                    </div>
                </form>
                <form v-if="dialog === 'entry'" @submit.prevent="submitEntry">
                    <FormFeedback :errors="entry.errors" />
                    <p class="eyebrow">Order #{{ order.id }}</p>
                    <h2>{{ entryTitle }}</h2>
                    <p class="notice">
                        {{
                            entry.type === "payment"
                                ? `Remaining balance: ${money(order.balance)}. Record only payments you have received.`
                                : entry.type === "refund"
                                  ? `Refundable deposit: ${money(order.refundable)}. Record only money returned to the customer.`
                                  : "Record actual paid supplier and direct costs. Include a description, such as watch acquisition or shipping."
                        }}
                    </p>
                    <div class="grid">
                        <label
                            >Amount (₱)<input
                                v-model="entry.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                :max="
                                    entry.type === 'payment'
                                        ? order.balance
                                        : entry.type === 'refund'
                                          ? order.refundable
                                          : undefined
                                "
                                required /></label
                        ><label
                            >Payment date<input
                                v-model="entry.paid_at"
                                type="date"
                                :max="today()"
                                :min="order.created_at.slice(0, 10)"
                                required /></label
                        ><label
                            >Method<select v-model="entry.method" required>
                                <option disabled value="">Select method</option>
                                <option
                                    v-for="method in paymentMethods"
                                    :key="method"
                                    :value="method"
                                >
                                    {{ method }}
                                </option>
                            </select></label
                        ><label
                            >Reference number<input
                                v-model="entry.reference"
                                maxlength="255" /></label
                        ><label class="span-2"
                            >Description / notes<textarea
                                v-model="entry.notes"
                                rows="3"
                                maxlength="5000"
                            /></label
                        ><label class="span-2"
                            >Proof (optional · image or PDF, up to 5 MB)<input
                                type="file"
                                accept="image/jpeg,image/png,image/webp,application/pdf"
                                @change="
                                    entry.proof = $event.target.files[0] || null
                                "
                        /></label>
                    </div>
                    <AttachmentPreview :file="entry.proof" />
                    <p v-if="entry.method === 'Others'" class="form-hint">
                        Describe the payment arrangement in the notes above.
                    </p>
                    <div
                        v-for="(error, key) in entry.errors"
                        :key="key"
                        class="error"
                        role="alert"
                    >
                        {{ error }}
                    </div>
                    <p v-if="entry.progress" class="muted">
                        Uploading {{ entry.progress.percentage }}%
                    </p>
                    <div class="actions">
                        <button
                            type="button"
                            @click="closeDialog"
                            :disabled="busy"
                        >
                            Back</button
                        ><button class="primary" :disabled="busy">
                            {{ busy ? "Recording…" : "Record transaction" }}
                        </button>
                    </div>
                </form>
                <form
                    v-else-if="dialog === 'status'"
                    @submit.prevent="submitStatus"
                >
                    <h2>
                        {{
                            transition.status === "cancelled"
                                ? "Cancel this preorder?"
                                : `Mark order as ${transition.status}?`
                        }}
                    </h2>
                    <template v-if="transition.status === 'cancelled'"
                        ><p class="notice">
                            Customer deposit to refund:
                            {{ money(order.refundable) }}. Supplier costs of
                            {{ money(order.cost) }} will be written off as an
                            expense. Cancellation is final; record the customer
                            refund afterward.
                        </p>
                        <label
                            >Cancellation reason<textarea
                                v-model="transition.cancellation_reason"
                                required
                                maxlength="5000"
                                rows="3"
                            /></label
                    ></template>
                    <p v-else class="muted" style="margin-top: 15px">
                        {{
                            transition.status === "ordered"
                                ? "Confirm that you have placed the order with your supplier."
                                : "Confirm that the watch has arrived and is ready for final payment and handover."
                        }}
                    </p>
                    <div
                        v-for="(error, key) in transition.errors"
                        :key="key"
                        class="error"
                        role="alert"
                    >
                        {{ error }}
                    </div>
                    <div class="actions">
                        <button
                            type="button"
                            @click="closeDialog"
                            :disabled="busy"
                        >
                            Go back</button
                        ><button
                            :class="
                                transition.status === 'cancelled'
                                    ? 'danger'
                                    : 'primary'
                            "
                            :disabled="busy"
                        >
                            {{ busy ? "Saving…" : "Confirm" }}
                        </button>
                    </div>
                </form>
                <form
                    v-else-if="dialog === 'fulfill'"
                    @submit.prevent="fulfill"
                >
                    <p class="eyebrow">Final handover</p>
                    <h2>Fulfill preorder #{{ order.id }}</h2>
                    <p class="notice">
                        This records a {{ money(order.total_price) }} sale with
                        {{ money(order.cost) }} capital. Warranty starts today.
                        Check all costs before continuing; financial records
                        will be locked.
                    </p>
                    <label
                        >Actual watch serial number<input
                            v-model="fulfillment.serial_number"
                            required
                            maxlength="255"
                    /></label>
                    <div
                        v-for="(error, key) in fulfillment.errors"
                        :key="key"
                        class="error"
                        role="alert"
                    >
                        {{ error }}
                    </div>
                    <div class="actions">
                        <button
                            type="button"
                            @click="closeDialog"
                            :disabled="busy"
                        >
                            Back</button
                        ><button class="primary" :disabled="busy">
                            {{ busy ? "Fulfilling…" : "Confirm handover" }}
                        </button>
                    </div>
                </form>
            </div></Modal
        ></AuthenticatedLayout
    >
</template>
