<script setup>
import { useForm } from "@inertiajs/vue3";
import { computed, watch } from "vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    watch: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(["close"]);

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    reserved_customer_name: "",
    reserved_contact_number: "",
    reservation_date: today,
    reservation_deadline: "",
    reservation_notes: "",
});

const peso = (value) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
};

const listedPrice = computed(() => {
    if (!props.watch) return 0;

    return Number(props.watch.discounted_price || 0) > 0
        ? Number(props.watch.discounted_price)
        : Number(props.watch.selling_price || 0);
});

watch(
    () => props.show,
    (value) => {
        if (value && props.watch) {
            form.reserved_customer_name =
                props.watch.reserved_customer_name || "";
            form.reserved_contact_number =
                props.watch.reserved_contact_number || "";
            form.reservation_date = props.watch.reservation_date || today;
            form.reservation_deadline = props.watch.reservation_deadline || "";
            form.reservation_notes = props.watch.reservation_notes || "";

            form.clearErrors();
        }
    },
);

const closeModal = () => {
    if (form.processing) return;

    emit("close");
};

const submit = () => {
    if (!props.watch) return;

    form.patch(route("admin.watches.reserve", props.watch.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit("close");
        },
    });
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show && watch"
                class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/80 px-4 py-6 backdrop-blur-sm"
            >
                <div class="absolute inset-0" @click="closeModal"></div>

                <form
                    @submit.prevent="submit"
                    class="relative w-full max-w-2xl rounded-[2rem] border border-white/10 bg-[#0B0B0D] p-6 shadow-2xl shadow-black"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p
                                class="text-xs uppercase tracking-[0.3em] text-zinc-600"
                            >
                                Reservation
                            </p>

                            <h2
                                class="mt-3 text-2xl font-semibold tracking-tight text-white"
                            >
                                Reserve Watch
                            </h2>

                            <p class="mt-2 text-sm leading-6 text-zinc-400">
                                Save buyer reservation details and temporarily
                                remove the watch from public availability.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-full border border-white/10 p-2 text-zinc-400 transition hover:border-white/30 hover:text-white"
                            @click="closeModal"
                        >
                            ✕
                        </button>
                    </div>

                    <div
                        class="mt-6 rounded-[1.5rem] border border-white/10 bg-white/[0.03] p-5"
                    >
                        <p class="text-sm font-semibold text-white">
                            {{ watch.brand }} {{ watch.model_name }}
                        </p>

                        <p class="mt-1 text-xs text-zinc-500">
                            Ref. {{ watch.reference_number || "No reference" }}
                        </p>

                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <div
                                class="rounded-2xl border border-white/10 bg-black/20 p-4"
                            >
                                <p class="text-xs text-zinc-500">Capital</p>
                                <p
                                    class="mt-1 text-sm font-semibold text-white"
                                >
                                    {{ peso(watch.capital_price) }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-white/10 bg-black/20 p-4"
                            >
                                <p class="text-xs text-zinc-500">
                                    Listed Price
                                </p>
                                <p
                                    class="mt-1 text-sm font-semibold text-white"
                                >
                                    {{ peso(listedPrice) }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-white/10 bg-black/20 p-4"
                            >
                                <p class="text-xs text-zinc-500">Status</p>
                                <p
                                    class="mt-1 text-sm font-semibold capitalize text-amber-300"
                                >
                                    Reserved
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mn-label">Customer Name</label>
                            <input
                                v-model="form.reserved_customer_name"
                                class="mn-input"
                                placeholder="Customer name"
                            />
                            <p
                                v-if="form.errors.reserved_customer_name"
                                class="mt-2 text-sm text-red-300"
                            >
                                {{ form.errors.reserved_customer_name }}
                            </p>
                        </div>

                        <div>
                            <label class="mn-label">Contact Number</label>
                            <input
                                v-model="form.reserved_contact_number"
                                class="mn-input"
                                placeholder="Messenger / phone / Viber"
                            />
                            <p
                                v-if="form.errors.reserved_contact_number"
                                class="mt-2 text-sm text-red-300"
                            >
                                {{ form.errors.reserved_contact_number }}
                            </p>
                        </div>

                        <div>
                            <label class="mn-label">Reservation Date</label>
                            <input
                                v-model="form.reservation_date"
                                type="date"
                                class="mn-input"
                            />
                            <p
                                v-if="form.errors.reservation_date"
                                class="mt-2 text-sm text-red-300"
                            >
                                {{ form.errors.reservation_date }}
                            </p>
                        </div>

                        <div>
                            <label class="mn-label">Reservation Deadline</label>
                            <input
                                v-model="form.reservation_deadline"
                                type="date"
                                class="mn-input"
                            />
                            <p
                                v-if="form.errors.reservation_deadline"
                                class="mt-2 text-sm text-red-300"
                            >
                                {{ form.errors.reservation_deadline }}
                            </p>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mn-label">Reservation Notes</label>
                            <textarea
                                v-model="form.reservation_notes"
                                rows="4"
                                class="mn-input"
                                placeholder="Payment status, pickup details, special notes..."
                            ></textarea>
                            <p
                                v-if="form.errors.reservation_notes"
                                class="mt-2 text-sm text-red-300"
                            >
                                {{ form.errors.reservation_notes }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            type="button"
                            class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30"
                            @click="closeModal"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200 disabled:opacity-60"
                        >
                            {{
                                form.processing
                                    ? "Saving..."
                                    : "Save Reservation"
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </Transition>
    </Teleport>
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
