<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import PreorderPhotoGallery from "@/Components/PreorderPhotoGallery.vue";
import FormFeedback from "@/Components/FormFeedback.vue";
import { useUnsavedChanges } from "@/Composables/useUnsavedChanges";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";
import "./preorders.css";
const props = defineProps({
    listings: Array,
    orders: Object,
    soldWatches: Array,
    summary: Object,
    filters: Object,
    sourceWatch: Object,
    followUps: { type: Array, default: () => [] },
});
const sources = computed(() =>
    props.sourceWatch &&
    !props.soldWatches.some((w) => w.id === props.sourceWatch.id)
        ? [props.sourceWatch, ...props.soldWatches]
        : props.soldWatches,
);
const page = usePage();
const tab = ref("orders");
const dialog = ref(null);
const search = ref(props.filters.search);
const sourceSearch = ref(props.filters.source_search);
const money = (n) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(Number(n || 0));
const name = (w) =>
    [w?.brand, w?.model_name, w?.reference_number].filter(Boolean).join(" ");
const listing = useForm({
    source_watch_id: "",
    price: "",
    estimated_arrival: "",
    is_visible: true,
});
const editing = ref(null);
const order = useForm({
    preorder_listing_id: "",
    customer_name: "",
    contact: "",
    total_price: "",
    deposit_percentage: 20,
    estimated_arrival: "",
    notes: "",
});
const deposit = computed(
    () =>
        (Number(order.total_price || 0) *
            Number(order.deposit_percentage || 0)) /
        100,
);
const cleanDialog = ref("");
const dialogData = () =>
    JSON.stringify(dialog.value === "listing" ? listing.data() : order.data());
const { confirmDiscard } = useUnsavedChanges({
    dirty: () =>
        Boolean(
            dialog.value &&
                cleanDialog.value &&
                dialogData() !== cleanDialog.value,
        ),
    busy: () => listing.processing || order.processing,
});
async function closeDialog() {
    if (listing.processing || order.processing || !(await confirmDiscard()))
        return;
    dialog.value = null;
}
function applyQueue(queue = "") {
    tab.value = "orders";
    router.get(
        route("admin.preorders.index"),
        { search: search.value || undefined, queue: queue || undefined },
        { preserveState: true, preserveScroll: true },
    );
}
const queueHelp = {
    deposit: "Record the remaining required deposit to confirm the order.",
    supplier: "Deposit confirmed. Place the supplier order when ready.",
    sourcing:
        "Ordered watches past their arrival estimate, or created at least 14 days ago. Follow up with the supplier.",
    balance:
        "The watch has arrived. Collect the remaining balance before handover.",
    handover:
        "Fully paid and arrived. Check actual costs and serial number before fulfillment.",
};
function openListing(item = null) {
    editing.value = item;
    listing.reset();
    listing.clearErrors();
    if (item) {
        listing.price = item.price;
        listing.estimated_arrival = item.estimated_arrival;
        listing.is_visible = item.is_visible;
    }
    dialog.value = "listing";
    cleanDialog.value = dialogData();
}
function saveListing() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            dialog.value = null;
            tab.value = "listings";
        },
    };
    if (editing.value)
        listing.patch(
            route("admin.preorder-listings.update", editing.value.id),
            options,
        );
    else listing.post(route("admin.preorder-listings.store"), options);
}
function openOrder(item = null) {
    order.reset();
    order.clearErrors();
    if (item) {
        order.preorder_listing_id = item.id;
        order.total_price = item.price;
    }
    dialog.value = "order";
    cleanDialog.value = dialogData();
}
function selectListing() {
    order.total_price =
        props.listings.find((l) => l.id == order.preorder_listing_id)?.price ||
        "";
}
function findSources() {
    router.get(
        route("admin.preorders.index"),
        {
            search: search.value,
            source_search: sourceSearch.value,
            queue: props.filters.queue || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
}
onMounted(() => {
    if (!props.sourceWatch) return;
    const existing = props.listings.find(
        (l) => l.source_watch_id === props.sourceWatch.id,
    );
    openListing(existing || null);
    if (!existing) {
        listing.source_watch_id = props.sourceWatch.id;
        listing.price = props.sourceWatch.selling_price;
        cleanDialog.value = dialogData();
    }
});
</script>

<template>
    <Head title="Preorders" />
    <AuthenticatedLayout title="Preorders">
        <div class="po">
            <div class="row between mobile-stack">
                <div>
                    <p class="eyebrow">Montre Nova · Made to order</p>
                    <h1>Preorders</h1>
                    <p class="muted">
                        From the first deposit to the final handover.
                    </p>
                </div>
                <div class="row">
                    <button @click="openListing()">+ Publish a watch</button
                    ><button
                        class="primary"
                        @click="openOrder()"
                        :disabled="!listings.length"
                    >
                        + Customer order
                    </button>
                </div>
            </div>
            <div v-if="page.props.flash?.success" class="success" role="status">
                {{ page.props.flash.success }}
            </div>
            <div class="stats">
                <div class="stat">
                    <span class="muted">Customer deposits held</span
                    ><strong>{{ money(summary.customer_deposits) }}</strong>
                </div>
                <div class="stat">
                    <span class="muted">Pending supplier spending</span
                    ><strong>{{ money(summary.supplier_spend) }}</strong>
                </div>
                <div class="stat">
                    <span class="muted">Public preorder listings</span
                    ><strong>{{
                        listings.filter((l) => l.is_visible).length
                    }}</strong>
                </div>
            </div>
            <section class="follow-up-queue" aria-labelledby="follow-up-title">
                <div class="row between">
                    <div>
                        <h2 id="follow-up-title">Preorder follow-ups</h2>
                        <p class="muted">
                            Across all orders · choose a queue to see what needs
                            attention.
                        </p>
                    </div>
                    <button v-if="filters.queue" @click="applyQueue()">
                        Show all orders
                    </button>
                </div>
                <div class="queue-cards">
                    <button
                        v-for="queue in followUps"
                        :key="queue.key"
                        :class="{ active: filters.queue === queue.key }"
                        :aria-pressed="filters.queue === queue.key"
                        @click="applyQueue(queue.key)"
                    >
                        <strong>{{ queue.count }}</strong
                        ><span>{{ queue.label }}</span>
                    </button>
                </div>
                <p v-if="filters.queue" class="queue-help" role="status">
                    {{ queueHelp[filters.queue] }}
                </p>
            </section>
            <p class="notice">
                Deposits are held for customers until fulfillment. Sales and
                profit are recorded once, when a fully paid order is handed
                over.
            </p>
            <div class="tabs" role="group" aria-label="Preorder views">
                <button
                    :class="{ active: tab === 'orders' }"
                    @click="tab = 'orders'"
                >
                    Customer orders</button
                ><button
                    :class="{ active: tab === 'listings' }"
                    @click="tab = 'listings'"
                >
                    Watch listings
                </button>
            </div>
            <section v-if="tab === 'orders'" class="panel">
                <form
                    class="row"
                    @submit.prevent="
                        router.get(
                            route('admin.preorders.index'),
                            { search, queue: filters.queue || undefined },
                            { preserveState: true, preserveScroll: true },
                        )
                    "
                >
                    <label style="flex: 1"
                        >Search customers<input
                            v-model="search"
                            placeholder="Customer name or contact"
                            maxlength="100" /></label
                    ><button style="align-self: end">Search</button>
                </form>
                <div v-if="!orders.data.length" class="empty">
                    <h2>
                        {{
                            filters.queue || filters.search
                                ? "No matching orders"
                                : "No orders yet"
                        }}
                    </h2>
                    <p class="muted">
                        {{
                            filters.queue || filters.search
                                ? "Try another queue or clear your search to see more orders."
                                : "Publish a sold watch, then create an order after your Messenger conversation."
                        }}
                    </p>
                </div>
                <Link
                    v-for="o in orders.data"
                    :key="o.id"
                    :href="route('admin.preorders.show', o.id)"
                    class="item row between"
                    style="color: inherit; text-decoration: none"
                >
                    <div class="break">
                        <p class="eyebrow">Order #{{ o.id }}</p>
                        <h2>{{ o.customer_name }}</h2>
                        <p class="muted">
                            {{ name(o.listing.watch_snapshot) }}
                        </p>
                        <div class="row" style="margin-top: 8px">
                            <span class="badge">{{
                                o.status.replaceAll("_", " ")
                            }}</span
                            ><span class="muted">{{
                                o.payment_status.replaceAll("_", " ")
                            }}</span>
                        </div>
                    </div>
                    <div class="row order-values">
                        <div>
                            <p class="muted">Paid</p>
                            <strong>{{ money(o.paid) }}</strong>
                        </div>
                        <div>
                            <p class="muted">
                                {{
                                    o.status === "cancelled"
                                        ? "Refund pending"
                                        : "Balance"
                                }}
                            </p>
                            <strong>{{
                                money(
                                    o.status === "cancelled"
                                        ? o.refundable
                                        : o.balance,
                                )
                            }}</strong>
                        </div>
                        <span aria-hidden="true">→</span>
                    </div>
                </Link>
                <nav
                    v-if="orders.last_page > 1"
                    class="row"
                    aria-label="Order pagination"
                    style="margin-top: 20px"
                >
                    <Link
                        v-if="orders.prev_page_url"
                        :href="orders.prev_page_url"
                        class="button"
                        preserve-scroll
                        >Previous</Link
                    ><span class="muted"
                        >{{ orders.current_page }} /
                        {{ orders.last_page }}</span
                    ><Link
                        v-if="orders.next_page_url"
                        :href="orders.next_page_url"
                        class="button"
                        preserve-scroll
                        >Next</Link
                    >
                </nav>
            </section>
            <section v-else class="panel">
                <h2>Preorder watch listings</h2>
                <p class="muted">
                    Photos and specifications are copied from sold watches.
                    Existing sales stay intact.
                </p>
                <div v-if="!listings.length" class="empty">
                    Publish your first watch to make it available for preorder.
                </div>
                <div v-for="l in listings" :key="l.id" class="item row between">
                    <div class="row">
                        <PreorderPhotoGallery
                            :images="l.watch_snapshot.images || []"
                            :primary-image="l.watch_snapshot.primary_image_url"
                            :title="name(l.watch_snapshot)"
                            thumbnail
                        />
                        <div class="break">
                            <h2>{{ name(l.watch_snapshot) }}</h2>
                            <p class="muted">
                                {{ money(l.price) }} ·
                                {{ l.orders_count }} orders
                            </p>
                            <span class="badge">{{
                                l.is_visible ? "Published" : "Hidden"
                            }}</span>
                        </div>
                    </div>
                    <div class="row">
                        <button @click="openListing(l)">Edit</button
                        ><a
                            v-if="l.is_visible"
                            :href="route('public.preorders.show', l.id)"
                            class="button"
                            target="_blank"
                            rel="noopener"
                            >Preview ↗</a
                        ><button @click="openOrder(l)" class="primary">
                            New order
                        </button>
                    </div>
                </div>
            </section>
        </div>
        <Modal
            :show="dialog !== null"
            :closeable="!listing.processing && !order.processing"
            @close="closeDialog"
        >
            <div
                class="po dialog"
                role="region"
                :aria-label="
                    dialog === 'listing'
                        ? 'Preorder listing'
                        : 'New customer order'
                "
            >
                <template v-if="dialog === 'listing'"
                    ><p class="eyebrow">Collection</p>
                    <h2>
                        {{
                            editing
                                ? "Edit preorder listing"
                                : "Publish a sold watch for preorder"
                        }}
                    </h2>
                    <p class="muted">
                        The original sale and customer warranty will stay
                        intact.
                    </p>
                    <form @submit.prevent="saveListing">
                        <p class="form-hint">
                            Fields marked required must be completed. Arrival
                            estimate is optional.
                        </p>
                        <FormFeedback :errors="listing.errors" />
                        <div class="grid">
                            <template v-if="!editing"
                                ><label class="span-2"
                                    >Find a sold model
                                    <div class="row">
                                        <input
                                            v-model="sourceSearch"
                                            placeholder="Model or reference"
                                            style="flex: 1"
                                        /><button
                                            type="button"
                                            @click="findSources"
                                        >
                                            Find
                                        </button>
                                    </div></label
                                ><label class="span-2"
                                    >Sold watch<select
                                        required
                                        v-model="listing.source_watch_id"
                                    >
                                        <option disabled value="">
                                            Select from the latest 50 matches
                                        </option>
                                        <option
                                            v-for="w in sources"
                                            :key="w.id"
                                            :value="w.id"
                                        >
                                            #{{ w.id }} · {{ name(w) }}
                                        </option>
                                    </select></label
                                ></template
                            ><label
                                >Preorder price (₱)<input
                                    v-model="listing.price"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    required /></label
                            ><label
                                >Arrival estimate<input
                                    v-model="listing.estimated_arrival"
                                    placeholder="e.g. 2–3 weeks after confirmation"
                                    maxlength="255" /></label
                            ><label v-if="editing" class="row"
                                ><input
                                    type="checkbox"
                                    v-model="listing.is_visible"
                                />
                                Visible in Collection</label
                            >
                        </div>
                        <div
                            v-for="(error, key) in listing.errors"
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
                                :disabled="listing.processing"
                            >
                                Cancel</button
                            ><button
                                class="primary"
                                :disabled="listing.processing"
                            >
                                {{
                                    listing.processing
                                        ? "Saving…"
                                        : editing
                                          ? "Save changes"
                                          : "Publish preorder"
                                }}
                            </button>
                        </div>
                    </form>
                </template>
                <template v-else
                    ><p class="eyebrow">Customer order</p>
                    <h2>Create a preorder</h2>
                    <form
                        @submit.prevent="
                            order.post(route('admin.preorders.store'))
                        "
                    >
                        <p class="form-hint">
                            Watch, customer, contact, price and deposit are
                            required. Notes stay private to the admin.
                        </p>
                        <FormFeedback :errors="order.errors" />
                        <div class="grid">
                            <label class="span-2"
                                >Watch<select
                                    v-model="order.preorder_listing_id"
                                    @change="selectListing"
                                    required
                                >
                                    <option disabled value="">
                                        Choose a preorder listing
                                    </option>
                                    <option
                                        v-for="l in listings"
                                        :key="l.id"
                                        :value="l.id"
                                    >
                                        {{ name(l.watch_snapshot) }}
                                    </option>
                                </select></label
                            ><label
                                >Customer name<input
                                    v-model="order.customer_name"
                                    required
                                    maxlength="255"
                                    autocomplete="name" /></label
                            ><label
                                >Contact / Messenger<input
                                    v-model="order.contact"
                                    required
                                    maxlength="255" /></label
                            ><label
                                >Agreed total price (₱)<input
                                    v-model="order.total_price"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    required /></label
                            ><label
                                >Required deposit (%)<input
                                    v-model="order.deposit_percentage"
                                    type="number"
                                    step="0.01"
                                    min="1"
                                    max="100"
                                    required /></label
                            ><label
                                >Estimated arrival<input
                                    v-model="order.estimated_arrival"
                                    type="date" /></label
                            ><label
                                >Notes<textarea
                                    v-model="order.notes"
                                    maxlength="5000"
                                    rows="2"
                                />
                            </label>
                        </div>
                        <p class="notice">
                            Required down payment:
                            <strong>{{ money(deposit) }}</strong
                            >. The agreed price is saved for this order.
                        </p>
                        <div
                            v-for="(error, key) in order.errors"
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
                                :disabled="order.processing"
                            >
                                Cancel</button
                            ><button
                                class="primary"
                                :disabled="order.processing"
                            >
                                {{
                                    order.processing
                                        ? "Creating…"
                                        : "Create order"
                                }}
                            </button>
                        </div>
                    </form></template
                >
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
