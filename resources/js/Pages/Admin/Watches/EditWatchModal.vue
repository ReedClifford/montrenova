<script setup>
import InputError from "@/Components/InputError.vue";
import { router, useForm } from "@inertiajs/vue3";
import { ref, watch } from "vue";

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

const activeTab = ref("basic");
const imagePreviews = ref([]);

const form = useForm({
    _method: "patch",

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

    images: [],

    sections: [],
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
        if (!value || !props.watch) return;

        activeTab.value = "basic";
        imagePreviews.value = [];
        form.clearErrors();

        form.brand = props.watch.brand || "";
        form.model_name = props.watch.model_name || "";
        form.reference_number = props.watch.reference_number || "";
        form.condition = props.watch.condition || "Brand New";
        form.category = props.watch.category || "";
        form.description = props.watch.description || "";

        form.movement = props.watch.movement || "";
        form.case_size = props.watch.case_size || "";
        form.case_material = props.watch.case_material || "";
        form.dial_color = props.watch.dial_color || "";
        form.crystal = props.watch.crystal || "";
        form.bracelet_or_strap = props.watch.bracelet_or_strap || "";
        form.water_resistance = props.watch.water_resistance || "";
        form.box_papers = props.watch.box_papers || "";
        form.warranty_type =
            props.watch.warranty_type || "Montre Card 1 Year Service Warranty";

        form.capital_price = props.watch.capital_price || 0;
        form.selling_price = props.watch.selling_price || 0;
        form.discounted_price = props.watch.discounted_price || "";

        form.status = props.watch.status || "draft";
        form.is_featured = Boolean(props.watch.is_featured);
        form.is_visible = Boolean(props.watch.is_visible);
        form.display_price = Boolean(props.watch.display_price);
        form.allow_inquiry = Boolean(props.watch.allow_inquiry);

        form.images = [];

        form.sections = props.watch.sections?.length
            ? props.watch.sections.map((section) => ({
                  title: section.title || "",
                  content: section.content || "",
              }))
            : [
                  {
                      title: "Order & Purchase Process",
                      content:
                          "Thank you for showing interest in this timepiece. To reserve or purchase this watch, you may contact us through our official channels.",
                  },
                  {
                      title: "Service Warranty",
                      content:
                          "The Montre Card warranty coverage is valid for one (1) year from the date of purchase.",
                  },
                  {
                      title: "Payment Methods",
                      content:
                          "Accepted payment methods include cash, GCash, bank transfer, QR code payments, and selected trade-ins subject to evaluation.",
                  },
              ];
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
    if (!props.watch) return;

    form.post(route("admin.watches.update", props.watch.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            imagePreviews.value = [];
            emit("close");
            router.reload({ only: ["watches"] });
        },
    });
};

const deleteImage = (image) => {
    if (!confirm("Delete this photo?")) return;

    router.delete(route("admin.watch-images.destroy", image.id), {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({ only: ["watches"] });
        },
    });
};

const setPrimaryImage = (image) => {
    router.patch(
        route("admin.watch-images.primary", image.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ only: ["watches"] });
            },
        },
    );
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
                class="fixed inset-0 z-[999] flex items-center justify-center bg-black/80 px-4 py-6 backdrop-blur-sm"
            >
                <div class="absolute inset-0" @click="closeModal"></div>

                <form
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
                                    Edit Watch
                                </h2>

                                <p class="mt-2 text-sm text-zinc-400">
                                    Update watch details, price, status, photos,
                                    and terms.
                                </p>
                            </div>

                            <button
                                type="button"
                                class="rounded-2xl border border-white/10 p-3 text-zinc-400 transition hover:border-white/30 hover:text-white"
                                @click="closeModal"
                            >
                                ✕
                            </button>
                        </div>

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
                                <label class="mn-label">Reference Number</label>
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
                            </div>

                            <div>
                                <label class="mn-label">Category</label>
                                <input
                                    v-model="form.category"
                                    class="mn-input"
                                    placeholder="Diver, GMT, Dress, Chronograph"
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
                                <h3 class="text-lg font-semibold text-white">
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
                                    </div>
                                </div>
                            </div>

                            <div
                                class="rounded-[1.5rem] border border-white/10 bg-white/[0.03] p-5"
                            >
                                <h3 class="text-lg font-semibold text-white">
                                    Status & Display
                                </h3>

                                <div class="mt-5 space-y-5">
                                    <div>
                                        <label class="mn-label">Status</label>
                                        <select
                                            v-model="form.status"
                                            class="mn-input"
                                        >
                                            <option value="draft">Draft</option>
                                            <option value="available">
                                                Available
                                            </option>
                                            <option value="reserved">
                                                Reserved
                                            </option>
                                            <option value="sold">Sold</option>
                                            <option value="hidden">
                                                Hidden
                                            </option>
                                        </select>
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
                            <input
                                v-model="form.movement"
                                class="mn-input"
                                placeholder="Movement"
                            />
                            <input
                                v-model="form.case_size"
                                class="mn-input"
                                placeholder="Case Size"
                            />
                            <input
                                v-model="form.case_material"
                                class="mn-input"
                                placeholder="Case Material"
                            />
                            <input
                                v-model="form.dial_color"
                                class="mn-input"
                                placeholder="Dial Color"
                            />
                            <input
                                v-model="form.crystal"
                                class="mn-input"
                                placeholder="Crystal"
                            />
                            <input
                                v-model="form.bracelet_or_strap"
                                class="mn-input"
                                placeholder="Bracelet / Strap"
                            />
                            <input
                                v-model="form.water_resistance"
                                class="mn-input"
                                placeholder="Water Resistance"
                            />
                            <input
                                v-model="form.box_papers"
                                class="mn-input"
                                placeholder="Box and Papers"
                            />
                            <input
                                v-model="form.warranty_type"
                                class="mn-input md:col-span-2"
                                placeholder="Warranty Type"
                            />
                        </div>

                        <!-- PHOTOS -->
                        <div v-if="activeTab === 'photos'" class="space-y-6">
                            <label
                                class="flex cursor-pointer flex-col items-center justify-center rounded-[1.7rem] border border-dashed border-white/20 bg-white/[0.03] px-6 py-10 text-center transition hover:border-white/40"
                            >
                                <span class="text-sm font-semibold text-white">
                                    Upload More HD Photos
                                </span>

                                <span class="mt-2 text-xs text-zinc-500">
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

                            <div
                                v-if="imagePreviews.length"
                                class="grid grid-cols-2 gap-3 md:grid-cols-4"
                            >
                                <img
                                    v-for="preview in imagePreviews"
                                    :key="preview"
                                    :src="preview"
                                    class="aspect-square rounded-2xl border border-white/10 object-cover"
                                />
                            </div>

                            <div>
                                <h3
                                    class="mb-4 text-lg font-semibold text-white"
                                >
                                    Existing Photos
                                </h3>

                                <div
                                    v-if="watch.images?.length"
                                    class="grid grid-cols-2 gap-4 md:grid-cols-4"
                                >
                                    <div
                                        v-for="image in watch.images"
                                        :key="image.id"
                                        class="overflow-hidden rounded-2xl border border-white/10 bg-[#050505]"
                                    >
                                        <img
                                            :src="image.image_url"
                                            class="aspect-square w-full object-cover"
                                        />

                                        <div class="space-y-2 p-3">
                                            <div
                                                v-if="image.is_primary"
                                                class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-center text-xs text-emerald-300"
                                            >
                                                Primary
                                            </div>

                                            <button
                                                v-else
                                                type="button"
                                                class="w-full rounded-xl border border-white/10 px-3 py-2 text-xs text-zinc-300 hover:border-white/30"
                                                @click="setPrimaryImage(image)"
                                            >
                                                Set Primary
                                            </button>

                                            <button
                                                type="button"
                                                class="w-full rounded-xl border border-red-500/20 px-3 py-2 text-xs text-red-300 hover:bg-red-500/10"
                                                @click="deleteImage(image)"
                                            >
                                                Delete Photo
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-else
                                    class="rounded-2xl border border-white/10 bg-white/[0.03] p-8 text-center text-sm text-zinc-500"
                                >
                                    No photos uploaded yet.
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
                                <label class="mn-label">Section Title</label>
                                <input
                                    v-model="section.title"
                                    class="mn-input"
                                />

                                <label class="mn-label mt-4">Content</label>
                                <textarea
                                    v-model="section.content"
                                    rows="6"
                                    class="mn-input"
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

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-2xl bg-white px-6 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200 disabled:opacity-60"
                            >
                                {{
                                    form.processing
                                        ? "Saving Changes..."
                                        : "Save Changes"
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
