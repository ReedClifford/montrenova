<script setup>
import InputError from "@/Components/InputError.vue";
import { useForm } from "@inertiajs/vue3";
import { ref, watch } from "vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close"]);

const activeTab = ref("basic");
const imagePreviews = ref([]);

const form = useForm({
    // stock_code: "",
    brand: "",
    model_name: "",
    reference_number: "",
    condition: "Brand New",
    category: "",
    description: "",

    movement: "",
    case_size: "",
    case_material: "",
    dial_color: "",
    crystal: "",
    bracelet_or_strap: "",
    water_resistance: "",
    box_papers: "",
    warranty_type: "Montre Card 1 Year Service Warranty",

    capital_price: 0,
    selling_price: 0,
    discounted_price: "",

    status: "draft",
    is_featured: false,
    is_visible: true,
    display_price: true,
    allow_inquiry: true,

    date_acquired: "",
    date_sold: "",

    images: [],

    sections: [
        {
            title: "Order & Purchase Process",
            content:
                "Thank you for showing interest in this timepiece. To reserve or purchase this watch, you may contact us through our official channels including Messenger, Viber, Instagram, or your Montre Nova sales representative.",
        },
        {
            title: "Service Warranty",
            content:
                "The Montre Card warranty coverage is valid for one (1) year from the date of purchase. This warranty covers defects related to the watch's movement and internal mechanism, including abnormal timekeeping, significant gain or loss of time, and movement stoppage.",
        },
        {
            title: "Payment Methods",
            content:
                "Accepted payment methods include cash, GCash, bank transfer, QR code payments, and selected trade-ins subject to evaluation. All photos posted are actual photos unless stated otherwise.",
        },
    ],
});

const tabs = [
    { key: "basic", label: "Basic Info" },
    { key: "pricing", label: "Pricing" },
    { key: "specs", label: "Specs" },
    { key: "photos", label: "HD Photos" },
    { key: "terms", label: "Terms" },
];

watch(
    () => props.show,
    (value) => {
        if (value) {
            activeTab.value = "basic";
        }
    },
);

const closeModal = () => {
    if (form.processing) return;

    emit("close");
};

const handleImages = (event) => {
    const files = Array.from(event.target.files || []);

    form.images = files;
    imagePreviews.value = files.map((file) => URL.createObjectURL(file));
};

const submit = () => {
    form.post(route("admin.watches.store"), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            imagePreviews.value = [];
            activeTab.value = "basic";
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
                v-if="show"
                class="fixed inset-0 z-[999] flex items-center justify-center bg-black/80 px-4 py-6 backdrop-blur-sm"
            >
                <div class="absolute inset-0" @click="closeModal"></div>

                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="translate-y-4 scale-95 opacity-0"
                    enter-to-class="translate-y-0 scale-100 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="translate-y-0 scale-100 opacity-100"
                    leave-to-class="translate-y-4 scale-95 opacity-0"
                >
                    <form
                        v-if="show"
                        @submit.prevent="submit"
                        class="relative flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-[2rem] border border-white/10 bg-[#080808] shadow-2xl shadow-black"
                    >
                        <!-- HEADER -->
                        <div
                            class="border-b border-white/10 bg-[#0B0B0D] px-6 py-5"
                        >
                            <div class="flex items-start justify-between gap-5">
                                <div>
                                    <p
                                        class="text-xs uppercase tracking-[0.3em] text-zinc-600"
                                    >
                                        Montre Nova Inventory
                                    </p>

                                    <h2
                                        class="mt-2 text-2xl font-semibold tracking-tight text-white"
                                    >
                                        Add New Watch
                                    </h2>

                                    <p class="mt-2 text-sm text-zinc-400">
                                        Encode stock details, pricing,
                                        specifications, terms, and HD photos.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="rounded-2xl border border-white/10 p-3 text-zinc-400 transition hover:border-white/30 hover:text-white"
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

                            <!-- TABS -->
                            <div class="mt-6 flex gap-2 overflow-x-auto">
                                <button
                                    v-for="tab in tabs"
                                    :key="tab.key"
                                    type="button"
                                    class="whitespace-nowrap rounded-2xl px-4 py-2 text-sm font-medium transition"
                                    :class="
                                        activeTab === tab.key
                                            ? 'bg-white text-black'
                                            : 'border border-white/10 bg-white/[0.03] text-zinc-400 hover:border-white/30 hover:text-white'
                                    "
                                    @click="activeTab = tab.key"
                                >
                                    {{ tab.label }}
                                </button>
                            </div>
                        </div>

                        <!-- BODY -->
                        <div class="flex-1 overflow-y-auto px-6 py-6">
                            <!-- BASIC -->
                            <div
                                v-if="activeTab === 'basic'"
                                class="grid gap-5 md:grid-cols-2"
                            >
                                <div>
                                    <label class="mn-label">Brand</label>
                                    <input
                                        v-model="form.brand"
                                        class="mn-input"
                                        placeholder="Seiko"
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.brand"
                                    />
                                </div>

                                <div>
                                    <label class="mn-label">Model Name</label>
                                    <input
                                        v-model="form.model_name"
                                        class="mn-input"
                                        placeholder="Prospex Speedtimer"
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.model_name"
                                    />
                                </div>

                                <div>
                                    <label class="mn-label"
                                        >Reference Number</label
                                    >
                                    <input
                                        v-model="form.reference_number"
                                        class="mn-input"
                                        placeholder="SSC813"
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.reference_number"
                                    />
                                </div>

                                <div>
                                    <label class="mn-label">Condition</label>
                                    <select
                                        v-model="form.condition"
                                        class="mn-input"
                                    >
                                        <option>Brand New</option>
                                        <option>Pre-owned</option>
                                        <option>Like New</option>
                                        <option>Used</option>
                                    </select>
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.condition"
                                    />
                                </div>

                                <div>
                                    <label class="mn-label">Category</label>
                                    <input
                                        v-model="form.category"
                                        class="mn-input"
                                        placeholder="Diver, GMT, Dress, Chronograph"
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.category"
                                    />
                                </div>

                                <div class="md:col-span-2">
                                    <label class="mn-label">Description</label>
                                    <textarea
                                        v-model="form.description"
                                        rows="6"
                                        class="mn-input"
                                        placeholder="Short product description..."
                                    ></textarea>
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.description"
                                    />
                                </div>
                            </div>

                            <!-- PRICING -->
                            <div
                                v-if="activeTab === 'pricing'"
                                class="grid gap-6 lg:grid-cols-[1fr_0.8fr]"
                            >
                                <div
                                    class="rounded-[1.5rem] border border-white/10 bg-white/[0.03] p-5"
                                >
                                    <h3
                                        class="text-lg font-semibold text-white"
                                    >
                                        Pricing
                                    </h3>

                                    <div class="mt-5 grid gap-5 md:grid-cols-2">
                                        <div>
                                            <label class="mn-label"
                                                >Capital Price</label
                                            >
                                            <input
                                                v-model="form.capital_price"
                                                type="number"
                                                step="0.01"
                                                class="mn-input"
                                                placeholder="0.00"
                                            />
                                            <InputError
                                                class="mt-2"
                                                :message="
                                                    form.errors.capital_price
                                                "
                                            />
                                        </div>

                                        <div>
                                            <label class="mn-label"
                                                >Selling Price</label
                                            >
                                            <input
                                                v-model="form.selling_price"
                                                type="number"
                                                step="0.01"
                                                class="mn-input"
                                                placeholder="0.00"
                                            />
                                            <InputError
                                                class="mt-2"
                                                :message="
                                                    form.errors.selling_price
                                                "
                                            />
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="mn-label"
                                                >Discounted Price</label
                                            >
                                            <input
                                                v-model="form.discounted_price"
                                                type="number"
                                                step="0.01"
                                                class="mn-input"
                                                placeholder="Optional"
                                            />
                                            <InputError
                                                class="mt-2"
                                                :message="
                                                    form.errors.discounted_price
                                                "
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="rounded-[1.5rem] border border-white/10 bg-white/[0.03] p-5"
                                >
                                    <h3
                                        class="text-lg font-semibold text-white"
                                    >
                                        Status & Display
                                    </h3>

                                    <div class="mt-5 space-y-5">
                                        <div>
                                            <label class="mn-label"
                                                >Status</label
                                            >
                                            <select
                                                v-model="form.status"
                                                class="mn-input"
                                            >
                                                <option value="draft">
                                                    Draft
                                                </option>
                                                <option value="available">
                                                    Available
                                                </option>
                                                <option value="reserved">
                                                    Reserved
                                                </option>
                                                <option value="sold">
                                                    Sold
                                                </option>
                                                <option value="hidden">
                                                    Hidden
                                                </option>
                                            </select>
                                            <InputError
                                                class="mt-2"
                                                :message="form.errors.status"
                                            />
                                        </div>

                                        <div
                                            class="space-y-3 border-t border-white/10 pt-5"
                                        >
                                            <label class="mn-toggle">
                                                <span>Visible on website</span>
                                                <input
                                                    v-model="form.is_visible"
                                                    type="checkbox"
                                                    class="mn-checkbox"
                                                />
                                            </label>

                                            <label class="mn-toggle">
                                                <span>Featured watch</span>
                                                <input
                                                    v-model="form.is_featured"
                                                    type="checkbox"
                                                    class="mn-checkbox"
                                                />
                                            </label>

                                            <label class="mn-toggle">
                                                <span>Display price</span>
                                                <input
                                                    v-model="form.display_price"
                                                    type="checkbox"
                                                    class="mn-checkbox"
                                                />
                                            </label>

                                            <label class="mn-toggle">
                                                <span>Allow inquiry</span>
                                                <input
                                                    v-model="form.allow_inquiry"
                                                    type="checkbox"
                                                    class="mn-checkbox"
                                                />
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SPECS -->
                            <div
                                v-if="activeTab === 'specs'"
                                class="grid gap-5 md:grid-cols-2"
                            >
                                <div>
                                    <label class="mn-label">Movement</label>
                                    <input
                                        v-model="form.movement"
                                        class="mn-input"
                                        placeholder="Automatic, Quartz, Solar..."
                                    />
                                </div>

                                <div>
                                    <label class="mn-label">Case Size</label>
                                    <input
                                        v-model="form.case_size"
                                        class="mn-input"
                                        placeholder="40mm"
                                    />
                                </div>

                                <div>
                                    <label class="mn-label"
                                        >Case Material</label
                                    >
                                    <input
                                        v-model="form.case_material"
                                        class="mn-input"
                                        placeholder="Stainless Steel"
                                    />
                                </div>

                                <div>
                                    <label class="mn-label">Dial Color</label>
                                    <input
                                        v-model="form.dial_color"
                                        class="mn-input"
                                        placeholder="Black, Blue, White..."
                                    />
                                </div>

                                <div>
                                    <label class="mn-label">Crystal</label>
                                    <input
                                        v-model="form.crystal"
                                        class="mn-input"
                                        placeholder="Sapphire, Hardlex..."
                                    />
                                </div>

                                <div>
                                    <label class="mn-label"
                                        >Bracelet / Strap</label
                                    >
                                    <input
                                        v-model="form.bracelet_or_strap"
                                        class="mn-input"
                                        placeholder="Steel bracelet, leather strap..."
                                    />
                                </div>

                                <div>
                                    <label class="mn-label"
                                        >Water Resistance</label
                                    >
                                    <input
                                        v-model="form.water_resistance"
                                        class="mn-input"
                                        placeholder="100m, 200m..."
                                    />
                                </div>

                                <div>
                                    <label class="mn-label"
                                        >Box and Papers</label
                                    >
                                    <input
                                        v-model="form.box_papers"
                                        class="mn-input"
                                        placeholder="Complete Set, Watch Only..."
                                    />
                                </div>

                                <div class="md:col-span-2">
                                    <label class="mn-label"
                                        >Warranty Type</label
                                    >
                                    <input
                                        v-model="form.warranty_type"
                                        class="mn-input"
                                        placeholder="Montre Card 1 Year Service Warranty"
                                    />
                                </div>
                            </div>

                            <!-- PHOTOS -->
                            <div
                                v-if="activeTab === 'photos'"
                                class="grid gap-6 lg:grid-cols-[0.8fr_1fr]"
                            >
                                <div>
                                    <label
                                        class="flex min-h-[320px] cursor-pointer flex-col items-center justify-center rounded-[1.7rem] border border-dashed border-white/20 bg-white/[0.03] px-6 py-10 text-center transition hover:border-white/40 hover:bg-white/[0.05]"
                                    >
                                        <div
                                            class="flex h-16 w-16 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.04]"
                                        >
                                            <svg
                                                class="h-7 w-7 text-zinc-400"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="1.7"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M12 16.5V9.75m0 0l-3 3m3-3l3 3M3.75 18.75h16.5A2.25 2.25 0 0022.5 16.5v-9A2.25 2.25 0 0020.25 5.25H3.75A2.25 2.25 0 001.5 7.5v9a2.25 2.25 0 002.25 2.25z"
                                                />
                                            </svg>
                                        </div>

                                        <span
                                            class="mt-5 text-sm font-semibold text-white"
                                        >
                                            Upload HD Watch Photos
                                        </span>

                                        <span
                                            class="mt-2 max-w-sm text-xs leading-6 text-zinc-500"
                                        >
                                            Upload front shot, wrist shot,
                                            caseback, side profile, box/papers,
                                            and condition detail photos.
                                        </span>

                                        <span
                                            class="mt-4 rounded-full border border-white/10 px-4 py-2 text-xs font-medium text-zinc-400"
                                        >
                                            JPG, PNG, WEBP up to 10MB each
                                        </span>

                                        <input
                                            type="file"
                                            multiple
                                            accept="image/*"
                                            class="hidden"
                                            @change="handleImages"
                                        />
                                    </label>

                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.images"
                                    />
                                </div>

                                <div>
                                    <div
                                        v-if="imagePreviews.length"
                                        class="grid grid-cols-2 gap-3 md:grid-cols-3"
                                    >
                                        <div
                                            v-for="(
                                                preview, index
                                            ) in imagePreviews"
                                            :key="preview"
                                            class="group relative overflow-hidden rounded-2xl border border-white/10 bg-[#050505]"
                                        >
                                            <img
                                                :src="preview"
                                                class="aspect-square w-full object-cover"
                                            />

                                            <div
                                                class="absolute left-3 top-3 rounded-full bg-black/70 px-3 py-1 text-xs font-medium text-white backdrop-blur"
                                            >
                                                {{
                                                    index === 0
                                                        ? "Primary"
                                                        : `Photo ${index + 1}`
                                                }}
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        v-else
                                        class="flex min-h-[320px] items-center justify-center rounded-[1.7rem] border border-white/10 bg-[#050505] text-center"
                                    >
                                        <div>
                                            <p
                                                class="text-sm font-medium text-white"
                                            >
                                                No photos selected yet.
                                            </p>
                                            <p
                                                class="mt-2 text-sm text-zinc-500"
                                            >
                                                Your previews will appear here.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TERMS -->
                            <div v-if="activeTab === 'terms'" class="space-y-5">
                                <div
                                    v-for="(section, index) in form.sections"
                                    :key="index"
                                    class="rounded-[1.5rem] border border-white/10 bg-white/[0.03] p-5"
                                >
                                    <label class="mn-label"
                                        >Section Title</label
                                    >
                                    <input
                                        v-model="section.title"
                                        class="mn-input"
                                        placeholder="Section Title"
                                    />

                                    <label class="mn-label mt-4">Content</label>
                                    <textarea
                                        v-model="section.content"
                                        rows="6"
                                        class="mn-input"
                                        placeholder="Section content..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div
                            class="border-t border-white/10 bg-[#0B0B0D] px-6 py-5"
                        >
                            <div
                                class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between"
                            >
                                <button
                                    type="button"
                                    class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30"
                                    @click="closeModal"
                                >
                                    Cancel
                                </button>

                                <div class="flex flex-col gap-3 sm:flex-row">
                                    <button
                                        v-if="activeTab !== 'basic'"
                                        type="button"
                                        class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-semibold text-zinc-300 transition hover:border-white/30 hover:text-white"
                                        @click="
                                            activeTab =
                                                tabs[
                                                    Math.max(
                                                        0,
                                                        tabs.findIndex(
                                                            (tab) =>
                                                                tab.key ===
                                                                activeTab,
                                                        ) - 1,
                                                    )
                                                ].key
                                        "
                                    >
                                        Previous
                                    </button>

                                    <button
                                        v-if="activeTab !== 'terms'"
                                        type="button"
                                        class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-semibold text-zinc-300 transition hover:border-white/30 hover:text-white"
                                        @click="
                                            activeTab =
                                                tabs[
                                                    Math.min(
                                                        tabs.length - 1,
                                                        tabs.findIndex(
                                                            (tab) =>
                                                                tab.key ===
                                                                activeTab,
                                                        ) + 1,
                                                    )
                                                ].key
                                        "
                                    >
                                        Next
                                    </button>

                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="rounded-2xl bg-white px-6 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        {{
                                            form.processing
                                                ? "Saving Watch..."
                                                : "Save Watch"
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

.mn-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    border-radius: 1rem;
    border: 1px solid rgb(255 255 255 / 0.08);
    background: rgb(255 255 255 / 0.03);
    padding: 0.9rem 1rem;
    font-size: 0.875rem;
    color: rgb(161 161 170);
}

.mn-checkbox {
    border-radius: 0.375rem;
    border-color: rgb(255 255 255 / 0.2);
    background: black;
    color: white;
}
</style>
