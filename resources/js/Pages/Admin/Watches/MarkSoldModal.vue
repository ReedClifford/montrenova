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
    buyer_name: "",
    serial_number: "",
    sold_price: "",
    date_sold: today,
});

const peso = (value) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
};

const expectedPrice = computed(() => {
    if (!props.watch) return 0;

    return Number(props.watch.discounted_price || 0) > 0
        ? Number(props.watch.discounted_price)
        : Number(props.watch.selling_price || 0);
});

const capitalPrice = computed(() => {
    return Number(props.watch?.capital_price || 0);
});

const soldPriceValue = computed(() => {
    return Number(form.sold_price || 0);
});

const estimatedProfit = computed(() => {
    return soldPriceValue.value - capitalPrice.value;
});

const profitIsPositive = computed(() => estimatedProfit.value >= 0);

watch(
    () => props.show,
    (value) => {
        if (value && props.watch) {
            form.buyer_name = "";
            form.serial_number = props.watch.serial_number || "";
            form.sold_price = expectedPrice.value || "";
            form.date_sold = today;
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

    form.patch(route("admin.watches.mark-sold", props.watch.id), {
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
                class="fixed inset-0 z-[1000] flex items-end justify-center bg-black/80 px-0 py-0 backdrop-blur-sm sm:items-center sm:px-4 sm:py-6"
            >
                <div class="absolute inset-0" @click="closeModal"></div>

                <form
                    @submit.prevent="submit"
                    class="relative flex max-h-[94dvh] w-full flex-col overflow-hidden rounded-t-[2rem] border border-white/10 bg-[#0B0B0D] shadow-2xl shadow-black sm:max-h-[92vh] sm:max-w-xl sm:rounded-[2rem]"
                >
                    <!-- MOBILE DRAG INDICATOR -->
                    <div class="flex justify-center pt-3 sm:hidden">
                        <div class="h-1.5 w-12 rounded-full bg-white/15"></div>
                    </div>

                    <!-- STICKY HEADER -->
                    <div
                        class="sticky top-0 z-20 border-b border-white/10 bg-[#0B0B0D]/95 px-4 pb-4 pt-3 backdrop-blur sm:px-6 sm:py-6"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.28em] text-zinc-600 sm:text-xs"
                                >
                                    Complete Sale
                                </p>

                                <h2
                                    class="mt-2 text-xl font-black tracking-tight text-white sm:mt-3 sm:text-2xl"
                                >
                                    Mark as Sold
                                </h2>

                                <p
                                    class="mt-2 max-w-sm text-xs leading-5 text-zinc-500 sm:text-sm sm:leading-6"
                                >
                                    Save buyer details, final sold price, and
                                    warranty reference.
                                </p>
                            </div>

                            <button
                                type="button"
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/[0.03] text-lg text-zinc-400 transition hover:border-white/30 hover:text-white"
                                @click="closeModal"
                                aria-label="Close modal"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- SCROLLABLE CONTENT -->
                    <div
                        class="flex-1 overflow-y-auto overscroll-contain px-4 py-5 sm:px-6 sm:py-6"
                    >
                        <!-- WATCH SUMMARY -->
                        <div
                            class="rounded-[1.5rem] border border-white/10 bg-white/[0.03] p-4 sm:p-5"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-base font-bold text-white sm:text-lg"
                                    >
                                        {{ watch.brand }} {{ watch.model_name }}
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
                                    class="shrink-0 rounded-full border border-red-400/20 bg-red-400/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.12em] text-red-300"
                                >
                                    Sold
                                </span>
                            </div>

                            <div class="mt-5 grid grid-cols-2 gap-3">
                                <div
                                    class="rounded-2xl border border-white/10 bg-black/25 p-4"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.16em] text-zinc-600"
                                    >
                                        Capital
                                    </p>

                                    <p
                                        class="mt-2 text-sm font-bold text-white"
                                    >
                                        {{ peso(watch.capital_price) }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-white/10 bg-black/25 p-4"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.16em] text-zinc-600"
                                    >
                                        Listed
                                    </p>

                                    <p
                                        class="mt-2 text-sm font-bold text-white"
                                    >
                                        {{ peso(expectedPrice) }}
                                    </p>
                                </div>

                                <div
                                    class="col-span-2 rounded-2xl border p-4"
                                    :class="
                                        profitIsPositive
                                            ? 'border-emerald-400/20 bg-emerald-400/10'
                                            : 'border-red-400/20 bg-red-400/10'
                                    "
                                >
                                    <div
                                        class="flex items-center justify-between gap-3"
                                    >
                                        <div>
                                            <p
                                                class="text-[10px] font-bold uppercase tracking-[0.16em]"
                                                :class="
                                                    profitIsPositive
                                                        ? 'text-emerald-300/80'
                                                        : 'text-red-300/80'
                                                "
                                            >
                                                Estimated Profit
                                            </p>

                                            <p
                                                class="mt-2 text-xl font-black"
                                                :class="
                                                    profitIsPositive
                                                        ? 'text-emerald-300'
                                                        : 'text-red-300'
                                                "
                                            >
                                                {{ peso(estimatedProfit) }}
                                            </p>
                                        </div>

                                        <div
                                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border text-lg font-black"
                                            :class="
                                                profitIsPositive
                                                    ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-300'
                                                    : 'border-red-400/20 bg-red-400/10 text-red-300'
                                            "
                                        >
                                            ₱
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- FORM FIELDS -->
                        <div class="mt-6 grid gap-5">
                            <div>
                                <label class="mn-label">
                                    Buyer Name
                                    <span class="text-red-300">*</span>
                                </label>

                                <input
                                    v-model="form.buyer_name"
                                    type="text"
                                    class="mn-input"
                                    placeholder="Enter buyer name"
                                    autocomplete="off"
                                />

                                <p
                                    v-if="form.errors.buyer_name"
                                    class="mt-2 text-sm text-red-300"
                                >
                                    {{ form.errors.buyer_name }}
                                </p>
                            </div>

                            <div>
                                <label class="mn-label">
                                    Watch Serial Number
                                    <span
                                        class="normal-case tracking-normal text-zinc-500"
                                    >
                                        Optional
                                    </span>
                                </label>

                                <input
                                    v-model="form.serial_number"
                                    type="text"
                                    class="mn-input"
                                    placeholder="Enter serial number if available"
                                    autocomplete="off"
                                />

                                <p class="mt-2 text-xs leading-5 text-zinc-600">
                                    Customers can still check warranty using
                                    buyer name only.
                                </p>

                                <p
                                    v-if="form.errors.serial_number"
                                    class="mt-2 text-sm text-red-300"
                                >
                                    {{ form.errors.serial_number }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="mn-label">
                                    Final Sold Price
                                    <span class="text-red-300">*</span>
                                </label>

                                <input
                                    v-model="form.sold_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    inputmode="decimal"
                                    class="mn-input"
                                    placeholder="0.00"
                                />

                                <p
                                    v-if="form.errors.sold_price"
                                    class="mt-2 text-sm text-red-300"
                                >
                                    {{ form.errors.sold_price }}
                                </p>
                            </div>

                            <div>
                                <label class="mn-label">
                                    Date Sold
                                    <span class="text-red-300">*</span>
                                </label>

                                <input
                                    v-model="form.date_sold"
                                    type="date"
                                    class="mn-input"
                                />

                                <p
                                    v-if="form.errors.date_sold"
                                    class="mt-2 text-sm text-red-300"
                                >
                                    {{ form.errors.date_sold }}
                                </p>
                            </div>
                        </div>

                        <!-- SALE SUMMARY -->
                        <div
                            class="mt-6 rounded-2xl border border-white/10 bg-black/25 p-4"
                        >
                            <div
                                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <p
                                        class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600"
                                    >
                                        Sale Summary
                                    </p>

                                    <p
                                        class="mt-2 text-sm leading-6 text-zinc-500"
                                    >
                                        Review the buyer name and final price
                                        before confirming.
                                    </p>
                                </div>

                                <div class="shrink-0">
                                    <p class="text-xs text-zinc-600">Profit</p>

                                    <p
                                        class="mt-1 text-lg font-black"
                                        :class="
                                            profitIsPositive
                                                ? 'text-emerald-300'
                                                : 'text-red-300'
                                        "
                                    >
                                        {{ peso(estimatedProfit) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STICKY FOOTER ACTIONS -->
                    <div
                        class="sticky bottom-0 z-20 border-t border-white/10 bg-[#0B0B0D]/95 px-4 py-4 backdrop-blur sm:px-6"
                    >
                        <div class="grid grid-cols-2 gap-3">
                            <button
                                type="button"
                                class="inline-flex min-h-[48px] items-center justify-center rounded-2xl border border-white/10 bg-white/[0.03] px-5 py-3 text-sm font-bold text-white transition hover:border-white/30 hover:bg-white/[0.06]"
                                @click="closeModal"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex min-h-[48px] items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-black text-black transition hover:bg-zinc-200 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {{
                                    form.processing
                                        ? "Saving..."
                                        : "Confirm Sale"
                                }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.mn-label {
    margin-bottom: 0.55rem;
    display: block;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    color: rgb(113 113 122);
}

.mn-input {
    min-height: 48px;
    width: 100%;
    border-radius: 1rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: #050505;
    padding: 0.9rem 1rem;
    font-size: 16px;
    color: white;
    outline: none;
    transition:
        border-color 150ms ease,
        background-color 150ms ease,
        box-shadow 150ms ease;
}

.mn-input::placeholder {
    color: rgb(63 63 70);
}

.mn-input:focus {
    border-color: rgb(255 255 255 / 0.4);
    background: rgb(255 255 255 / 0.04);
    box-shadow: 0 0 0 2px rgb(255 255 255 / 0.1);
}

@supports (padding-bottom: env(safe-area-inset-bottom)) {
    form > div:last-child {
        padding-bottom: calc(1rem + env(safe-area-inset-bottom));
    }
}
</style>
