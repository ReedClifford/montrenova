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

const localDateString = (date = new Date()) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");

    return `${year}-${month}-${day}`;
};

const today = localDateString();

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

const formatDate = (value) => {
    if (!value) return "Not set";

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

const parseDateOnly = (value) => {
    if (!value) return null;

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return null;

    date.setHours(0, 0, 0, 0);

    return date;
};

const diffDays = (start, end) => {
    if (!start || !end) return null;

    const startDate = parseDateOnly(start);
    const endDate = parseDateOnly(end);

    if (!startDate || !endDate) return null;

    return Math.floor(
        (endDate.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24),
    );
};

const listedPrice = computed(() => {
    if (!props.watch) return 0;

    return Number(props.watch.discounted_price || 0) > 0
        ? Number(props.watch.discounted_price)
        : Number(props.watch.selling_price || 0);
});

const expectedProfit = computed(() => {
    if (!props.watch) return 0;

    return listedPrice.value - Number(props.watch.capital_price || 0);
});

const watchTitle = computed(() => {
    if (!props.watch) return "Selected Watch";

    return `${props.watch.brand || ""} ${props.watch.model_name || ""}`.trim();
});

const watchImage = computed(() => {
    return (
        props.watch?.primary_image?.image_url ||
        props.watch?.primary_image?.hd_url ||
        props.watch?.primary_image?.thumbnail_url ||
        null
    );
});

const reservationDays = computed(() => {
    return diffDays(form.reservation_date, form.reservation_deadline);
});

const reservationLengthLabel = computed(() => {
    if (!form.reservation_deadline) return "No deadline set";

    if (reservationDays.value === null) return "Invalid deadline";
    if (reservationDays.value < 0) return "Deadline is before reservation date";
    if (reservationDays.value === 0) return "Same-day reservation";
    if (reservationDays.value === 1) return "1 day reservation";

    return `${reservationDays.value} days reservation`;
});

const deadlineStatusClass = computed(() => {
    if (!form.reservation_deadline) {
        return "border-white/10 bg-white/[0.03] text-zinc-400";
    }

    if (reservationDays.value === null || reservationDays.value < 0) {
        return "border-red-500/20 bg-red-500/10 text-red-300";
    }

    if (reservationDays.value <= 2) {
        return "border-emerald-500/20 bg-emerald-500/10 text-emerald-300";
    }

    if (reservationDays.value <= 7) {
        return "border-amber-500/20 bg-amber-500/10 text-amber-300";
    }

    return "border-red-500/20 bg-red-500/10 text-red-300";
});

const canSubmit = computed(() => {
    return !form.processing;
});

const setDeadlineAfter = (days) => {
    const base = parseDateOnly(form.reservation_date) || new Date();
    base.setDate(base.getDate() + days);

    form.reservation_deadline = localDateString(base);
};

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
                class="fixed inset-0 z-[1000] flex items-end justify-center bg-black/80 px-2 py-2 backdrop-blur-sm sm:items-center sm:px-4 sm:py-6"
            >
                <div class="absolute inset-0" @click="closeModal"></div>

                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="translate-y-8 opacity-0 sm:translate-y-4 sm:scale-95"
                    enter-to-class="translate-y-0 opacity-100 sm:scale-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="translate-y-0 opacity-100 sm:scale-100"
                    leave-to-class="translate-y-8 opacity-0 sm:translate-y-4 sm:scale-95"
                >
                    <form
                        v-if="show && watch"
                        @submit.prevent="submit"
                        class="relative flex h-[92svh] w-full max-w-3xl flex-col overflow-hidden rounded-t-[2rem] border border-white/10 bg-[#0B0B0D] shadow-2xl shadow-black sm:h-auto sm:max-h-[92vh] sm:rounded-[2rem]"
                    >
                        <!-- MOBILE HANDLE -->
                        <div
                            class="flex justify-center border-b border-white/10 bg-[#0B0B0D] py-2 sm:hidden"
                        >
                            <div
                                class="h-1.5 w-12 rounded-full bg-white/20"
                            ></div>
                        </div>

                        <!-- HEADER -->
                        <div
                            class="border-b border-white/10 px-4 py-4 sm:px-6 sm:py-5"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.28em] text-zinc-600 sm:text-xs"
                                    >
                                        Reservation
                                    </p>

                                    <h2
                                        class="mt-2 truncate text-xl font-semibold tracking-tight text-white sm:text-2xl"
                                    >
                                        Reserve Watch
                                    </h2>

                                    <p
                                        class="mt-2 max-w-2xl text-xs leading-5 text-zinc-500 sm:text-sm sm:leading-6"
                                    >
                                        Save buyer details and temporarily mark
                                        this watch as reserved.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="shrink-0 rounded-2xl border border-white/10 bg-white/[0.03] p-3 text-zinc-400 transition hover:border-white/30 hover:text-white"
                                    @click="closeModal"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.7"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- BODY -->
                        <div
                            class="thin-scrollbar flex-1 overflow-y-auto px-4 py-5 sm:px-6 sm:py-6"
                        >
                            <!-- WATCH PREVIEW -->
                            <section
                                class="overflow-hidden rounded-[1.6rem] border border-white/10 bg-white/[0.03]"
                            >
                                <div class="flex gap-4 p-4 sm:p-5">
                                    <div
                                        class="h-24 w-24 shrink-0 overflow-hidden rounded-2xl border border-white/10 bg-[#050505] sm:h-28 sm:w-28"
                                    >
                                        <img
                                            v-if="watchImage"
                                            :src="watchImage"
                                            class="h-full w-full object-cover"
                                            alt=""
                                        />

                                        <div
                                            v-else
                                            class="flex h-full w-full items-center justify-center text-xs font-bold tracking-[0.2em] text-zinc-700"
                                        >
                                            MN
                                        </div>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
                                        >
                                            <div class="min-w-0">
                                                <p
                                                    class="truncate text-base font-semibold text-white sm:text-lg"
                                                >
                                                    {{ watchTitle }}
                                                </p>

                                                <p
                                                    class="mt-1 truncate text-xs text-zinc-500"
                                                >
                                                    Ref.
                                                    {{
                                                        watch.reference_number ||
                                                        "No reference"
                                                    }}
                                                </p>
                                            </div>

                                            <span
                                                class="w-fit rounded-full border border-amber-500/20 bg-amber-500/10 px-3 py-1 text-xs font-bold text-amber-300"
                                            >
                                                Reserved
                                            </span>
                                        </div>

                                        <div
                                            class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3"
                                        >
                                            <div class="mn-mini-card">
                                                <p class="mn-mini-label">
                                                    Capital
                                                </p>
                                                <p class="mn-mini-value">
                                                    {{
                                                        peso(
                                                            watch.capital_price,
                                                        )
                                                    }}
                                                </p>
                                            </div>

                                            <div class="mn-mini-card">
                                                <p class="mn-mini-label">
                                                    Listed
                                                </p>
                                                <p class="mn-mini-value">
                                                    {{ peso(listedPrice) }}
                                                </p>
                                            </div>

                                            <div
                                                class="mn-mini-card col-span-2 sm:col-span-1"
                                            >
                                                <p class="mn-mini-label">
                                                    Profit
                                                </p>
                                                <p
                                                    class="mn-mini-value"
                                                    :class="
                                                        expectedProfit >= 0
                                                            ? 'text-emerald-300'
                                                            : 'text-red-300'
                                                    "
                                                >
                                                    {{ peso(expectedProfit) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- FORM -->
                            <section
                                class="mt-5 grid gap-5 lg:grid-cols-[1fr_0.75fr]"
                            >
                                <div
                                    class="rounded-[1.6rem] border border-white/10 bg-white/[0.03] p-4 sm:p-5"
                                >
                                    <div>
                                        <p
                                            class="text-xs font-bold uppercase tracking-[0.22em] text-zinc-600"
                                        >
                                            Buyer Details
                                        </p>

                                        <h3
                                            class="mt-2 text-lg font-semibold text-white"
                                        >
                                            Customer information
                                        </h3>
                                    </div>

                                    <div class="mt-5 grid gap-4">
                                        <div>
                                            <label class="mn-label">
                                                Customer Name
                                            </label>

                                            <input
                                                v-model="
                                                    form.reserved_customer_name
                                                "
                                                class="mn-input"
                                                placeholder="Customer name"
                                            />

                                            <p
                                                v-if="
                                                    form.errors
                                                        .reserved_customer_name
                                                "
                                                class="mt-2 text-sm text-red-300"
                                            >
                                                {{
                                                    form.errors
                                                        .reserved_customer_name
                                                }}
                                            </p>
                                        </div>

                                        <div>
                                            <label class="mn-label">
                                                Contact Number / Channel
                                            </label>

                                            <input
                                                v-model="
                                                    form.reserved_contact_number
                                                "
                                                class="mn-input"
                                                placeholder="Messenger / phone / Viber"
                                            />

                                            <p
                                                v-if="
                                                    form.errors
                                                        .reserved_contact_number
                                                "
                                                class="mt-2 text-sm text-red-300"
                                            >
                                                {{
                                                    form.errors
                                                        .reserved_contact_number
                                                }}
                                            </p>
                                        </div>

                                        <div>
                                            <label class="mn-label">
                                                Reservation Notes
                                            </label>

                                            <textarea
                                                v-model="form.reservation_notes"
                                                rows="5"
                                                class="mn-input"
                                                placeholder="Payment status, pickup details, special notes..."
                                            ></textarea>

                                            <p
                                                v-if="
                                                    form.errors
                                                        .reservation_notes
                                                "
                                                class="mt-2 text-sm text-red-300"
                                            >
                                                {{
                                                    form.errors
                                                        .reservation_notes
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="rounded-[1.6rem] border border-white/10 bg-white/[0.03] p-4 sm:p-5"
                                >
                                    <div>
                                        <p
                                            class="text-xs font-bold uppercase tracking-[0.22em] text-zinc-600"
                                        >
                                            Reservation Period
                                        </p>

                                        <h3
                                            class="mt-2 text-lg font-semibold text-white"
                                        >
                                            Date and deadline
                                        </h3>
                                    </div>

                                    <div class="mt-5 grid gap-4">
                                        <div>
                                            <label class="mn-label">
                                                Reservation Date
                                            </label>

                                            <input
                                                v-model="form.reservation_date"
                                                type="date"
                                                class="mn-input"
                                            />

                                            <p
                                                v-if="
                                                    form.errors.reservation_date
                                                "
                                                class="mt-2 text-sm text-red-300"
                                            >
                                                {{
                                                    form.errors.reservation_date
                                                }}
                                            </p>
                                        </div>

                                        <div>
                                            <label class="mn-label">
                                                Reservation Deadline
                                            </label>

                                            <input
                                                v-model="
                                                    form.reservation_deadline
                                                "
                                                type="date"
                                                class="mn-input"
                                            />

                                            <p
                                                v-if="
                                                    form.errors
                                                        .reservation_deadline
                                                "
                                                class="mt-2 text-sm text-red-300"
                                            >
                                                {{
                                                    form.errors
                                                        .reservation_deadline
                                                }}
                                            </p>
                                        </div>

                                        <div>
                                            <p
                                                class="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-zinc-600"
                                            >
                                                Quick Deadline
                                            </p>

                                            <div class="grid grid-cols-2 gap-2">
                                                <button
                                                    type="button"
                                                    class="mn-quick-btn"
                                                    @click="setDeadlineAfter(1)"
                                                >
                                                    +1 Day
                                                </button>

                                                <button
                                                    type="button"
                                                    class="mn-quick-btn"
                                                    @click="setDeadlineAfter(2)"
                                                >
                                                    +2 Days
                                                </button>

                                                <button
                                                    type="button"
                                                    class="mn-quick-btn"
                                                    @click="setDeadlineAfter(3)"
                                                >
                                                    +3 Days
                                                </button>

                                                <button
                                                    type="button"
                                                    class="mn-quick-btn"
                                                    @click="setDeadlineAfter(7)"
                                                >
                                                    +7 Days
                                                </button>
                                            </div>
                                        </div>

                                        <div
                                            class="rounded-2xl border p-4"
                                            :class="deadlineStatusClass"
                                        >
                                            <p
                                                class="text-xs font-bold uppercase tracking-[0.18em]"
                                            >
                                                Deadline Status
                                            </p>

                                            <p
                                                class="mt-2 text-sm font-semibold"
                                            >
                                                {{ reservationLengthLabel }}
                                            </p>

                                            <p class="mt-1 text-xs opacity-75">
                                                Deadline:
                                                {{
                                                    formatDate(
                                                        form.reservation_deadline,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <!-- FOOTER -->
                        <div
                            class="safe-bottom border-t border-white/10 bg-[#0B0B0D] px-4 py-4 sm:px-6 sm:py-5"
                        >
                            <div
                                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="text-xs leading-5 text-zinc-500">
                                    <span class="font-semibold text-amber-300">
                                        Reserving:
                                    </span>
                                    {{ watchTitle }}
                                </div>

                                <div
                                    class="grid grid-cols-2 gap-3 sm:flex sm:justify-end"
                                >
                                    <button
                                        type="button"
                                        class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30"
                                        @click="closeModal"
                                    >
                                        Cancel
                                    </button>

                                    <button
                                        type="submit"
                                        :disabled="!canSubmit"
                                        class="rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200 disabled:cursor-not-allowed disabled:bg-zinc-700 disabled:text-zinc-400"
                                    >
                                        {{
                                            form.processing
                                                ? "Saving..."
                                                : "Save Reservation"
                                        }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.safe-bottom {
    padding-bottom: max(1rem, env(safe-area-inset-bottom));
}

.mn-label {
    margin-bottom: 0.5rem;
    display: block;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.16em;
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
    transition:
        border-color 150ms ease,
        box-shadow 150ms ease,
        background-color 150ms ease;
}

.mn-input::placeholder {
    color: rgb(63 63 70);
}

.mn-input:focus {
    border-color: rgb(255 255 255 / 0.4);
    background: #070707;
    box-shadow: 0 0 0 2px rgb(255 255 255 / 0.08);
}

.mn-mini-card {
    border-radius: 0.9rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: rgb(0 0 0 / 0.22);
    padding: 0.85rem;
}

.mn-mini-label {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    color: rgb(113 113 122);
}

.mn-mini-value {
    margin-top: 0.4rem;
    font-size: 0.8rem;
    font-weight: 700;
    color: white;
}

.mn-quick-btn {
    border-radius: 0.85rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: rgb(255 255 255 / 0.03);
    padding: 0.75rem 0.9rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: rgb(212 212 216);
    transition:
        border-color 150ms ease,
        background-color 150ms ease,
        color 150ms ease;
}

.mn-quick-btn:hover {
    border-color: rgb(255 255 255 / 0.3);
    color: white;
}

.thin-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: rgb(255 255 255 / 0.2) transparent;
}

.thin-scrollbar::-webkit-scrollbar {
    height: 5px;
    width: 5px;
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
