<script setup>
import InputError from "@/Components/InputError.vue";
import { router, useForm } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, ref, watch } from "vue";

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

const MAX_IMAGES = 5;

const activeTab = ref("basic");
const imagePreviews = ref([]);
const existingImages = ref([]);
const fileInput = ref(null);
const imageLimitMessage = ref("");

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

const defaultSections = () => [
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

const normalizeExistingImage = (image) => {
    const url =
        image?.hd_url ||
        image?.image_url ||
        image?.thumbnail_url ||
        image?.url ||
        image?.full_url ||
        (image?.path ? `/storage/${image.path}` : null);

    return {
        ...image,
        url,
        is_primary: Boolean(image?.is_primary),
    };
};

const clean = (value) => String(value ?? "").trim();

const isZeroOrGreater = (value) => {
    if (value === "" || value === null || value === undefined) return false;

    return Number(value) >= 0;
};

const isPositive = (value) => {
    if (value === "" || value === null || value === undefined) return false;

    return Number(value) > 0;
};

const totalImageCount = computed(() => {
    return existingImages.value.length + imagePreviews.value.length;
});

const remainingSlots = computed(() => {
    return Math.max(MAX_IMAGES - totalImageCount.value, 0);
});

const canAddMoreImages = computed(() => {
    return remainingSlots.value > 0;
});

const basicComplete = computed(() => {
    return (
        clean(form.brand) !== "" &&
        clean(form.model_name) !== "" &&
        clean(form.condition) !== ""
    );
});

const pricingComplete = computed(() => {
    return (
        isZeroOrGreater(form.capital_price) &&
        isPositive(form.selling_price) &&
        clean(form.status) !== ""
    );
});

const specsComplete = computed(() => {
    return clean(form.warranty_type) !== "";
});

const photosComplete = computed(() => {
    return totalImageCount.value > 0 && totalImageCount.value <= MAX_IMAGES;
});

const termsComplete = computed(() => {
    return form.sections.every((section) => {
        return clean(section.title) !== "" && clean(section.content) !== "";
    });
});

const stepCompletion = computed(() => ({
    basic: basicComplete.value,
    pricing: pricingComplete.value,
    specs: specsComplete.value,
    photos: photosComplete.value,
    terms: termsComplete.value,
}));

const currentStepComplete = computed(() => {
    return stepCompletion.value[activeTab.value] === true;
});

const canSubmit = computed(() => {
    return (
        basicComplete.value &&
        pricingComplete.value &&
        specsComplete.value &&
        photosComplete.value &&
        termsComplete.value &&
        !form.processing
    );
});

const missingRequirements = computed(() => {
    const missing = [];

    if (!basicComplete.value) missing.push("Basic Info");
    if (!pricingComplete.value) missing.push("Pricing");
    if (!specsComplete.value) missing.push("Specs");
    if (!photosComplete.value) missing.push("HD Photos");
    if (!termsComplete.value) missing.push("Terms");

    return missing;
});

const getTabIndex = (key) => {
    return tabs.findIndex((tab) => tab.key === key);
};

const firstIncompleteTab = () => {
    const incomplete = tabs.find((tab) => !stepCompletion.value[tab.key]);

    return incomplete?.key || "terms";
};

const canAccessTab = (key) => {
    const targetIndex = getTabIndex(key);
    const currentIndex = getTabIndex(activeTab.value);

    if (targetIndex <= currentIndex) return true;

    const previousTabs = tabs.slice(0, targetIndex);

    return previousTabs.every((tab) => stepCompletion.value[tab.key]);
};

const goToTab = (key) => {
    if (!canAccessTab(key)) {
        activeTab.value = firstIncompleteTab();
        return;
    }

    activeTab.value = key;
};

const goToPreviousTab = () => {
    const currentIndex = getTabIndex(activeTab.value);
    const previousIndex = Math.max(0, currentIndex - 1);

    activeTab.value = tabs[previousIndex].key;
};

const goToNextTab = () => {
    if (!currentStepComplete.value) return;

    const currentIndex = getTabIndex(activeTab.value);
    const nextIndex = Math.min(tabs.length - 1, currentIndex + 1);

    activeTab.value = tabs[nextIndex].key;
};

const clearNewImages = () => {
    imagePreviews.value.forEach((image) => {
        if (image?.url) {
            URL.revokeObjectURL(image.url);
        }
    });

    imagePreviews.value = [];
    form.images = [];
    imageLimitMessage.value = "";

    if (fileInput.value) {
        fileInput.value.value = "";
    }
};

const syncNewImages = () => {
    form.images = imagePreviews.value.map((image) => image.file);
};

const handleImages = (event) => {
    imageLimitMessage.value = "";

    const files = Array.from(event.target.files || []);

    if (!files.length) return;

    if (remainingSlots.value <= 0) {
        imageLimitMessage.value = `Maximum of ${MAX_IMAGES} images only. Delete an existing photo before adding another.`;

        if (fileInput.value) {
            fileInput.value.value = "";
        }

        return;
    }

    const acceptedFiles = files.slice(0, remainingSlots.value);

    if (files.length > remainingSlots.value) {
        imageLimitMessage.value = `Only ${remainingSlots.value} more image(s) added. Maximum allowed is ${MAX_IMAGES} images total.`;
    }

    const newImages = acceptedFiles.map((file) => ({
        file,
        url: URL.createObjectURL(file),
        name: file.name,
        size: file.size,
    }));

    imagePreviews.value = [...imagePreviews.value, ...newImages];

    syncNewImages();

    if (fileInput.value) {
        fileInput.value.value = "";
    }
};

const removeNewImage = (index) => {
    const imageToRemove = imagePreviews.value[index];

    if (imageToRemove?.url) {
        URL.revokeObjectURL(imageToRemove.url);
    }

    imagePreviews.value = imagePreviews.value.filter((_, i) => i !== index);

    imageLimitMessage.value = "";

    syncNewImages();

    if (fileInput.value) {
        fileInput.value.value = "";
    }
};

const setPrimaryNewImage = (index) => {
    if (index === 0) return;

    const images = [...imagePreviews.value];
    const selected = images.splice(index, 1)[0];

    images.unshift(selected);

    imagePreviews.value = images;

    syncNewImages();
};

const loadWatchIntoForm = () => {
    if (!props.watch) return;

    activeTab.value = "basic";
    clearNewImages();
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

    form.capital_price = props.watch.capital_price ?? 0;
    form.selling_price = props.watch.selling_price ?? 0;
    form.discounted_price = props.watch.discounted_price || "";

    form.status = props.watch.status || "draft";
    form.is_featured = Boolean(props.watch.is_featured);
    form.is_visible =
        props.watch.is_visible === null || props.watch.is_visible === undefined
            ? true
            : Boolean(props.watch.is_visible);
    form.display_price =
        props.watch.display_price === null ||
        props.watch.display_price === undefined
            ? true
            : Boolean(props.watch.display_price);
    form.allow_inquiry =
        props.watch.allow_inquiry === null ||
        props.watch.allow_inquiry === undefined
            ? true
            : Boolean(props.watch.allow_inquiry);

    form.images = [];

    existingImages.value = Array.isArray(props.watch.images)
        ? props.watch.images
              .map((image) => normalizeExistingImage(image))
              .filter((image) => image.url)
        : [];

    form.sections = props.watch.sections?.length
        ? props.watch.sections.map((section) => ({
              title: section.title || "",
              content: section.content || "",
          }))
        : defaultSections();
};

watch(
    () => [props.show, props.watch],
    ([show]) => {
        if (!show || !props.watch) return;

        loadWatchIntoForm();
    },
    {
        immediate: false,
    },
);

const closeModal = () => {
    if (form.processing) return;

    clearNewImages();
    emit("close");
};

const submit = () => {
    if (!props.watch) return;

    if (!canSubmit.value) {
        activeTab.value = firstIncompleteTab();
        return;
    }

    form.post(route("admin.watches.update", props.watch.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            clearNewImages();
            emit("close");
            router.reload({ only: ["watches"] });
        },
    });
};

const deleteImage = (image) => {
    if (!image?.id) return;
    if (!confirm("Delete this photo?")) return;

    router.delete(route("admin.watch-images.destroy", image.id), {
        preserveScroll: true,
        onSuccess: () => {
            existingImages.value = existingImages.value.filter(
                (item) => item.id !== image.id,
            );

            router.reload({ only: ["watches"] });
        },
    });
};

const setPrimaryExistingImage = (image) => {
    if (!image?.id) return;

    router.patch(
        route("admin.watch-images.primary", image.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                existingImages.value = existingImages.value.map((item) => ({
                    ...item,
                    is_primary: item.id === image.id,
                }));

                router.reload({ only: ["watches"] });
            },
        },
    );
};

onBeforeUnmount(() => {
    clearNewImages();
});
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
                                    and terms. Maximum of 5 total photos only.
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

                        <!-- TABS -->
                        <div class="mt-6 flex gap-2 overflow-x-auto">
                            <button
                                v-for="tab in tabs"
                                :key="tab.key"
                                type="button"
                                :disabled="!canAccessTab(tab.key)"
                                class="flex items-center gap-2 whitespace-nowrap rounded-2xl px-4 py-2 text-sm font-medium transition disabled:cursor-not-allowed disabled:opacity-40"
                                :class="
                                    activeTab === tab.key
                                        ? 'bg-white text-black'
                                        : stepCompletion[tab.key]
                                          ? 'border border-emerald-400/20 bg-emerald-400/10 text-emerald-300 hover:border-emerald-400/40'
                                          : 'border border-white/10 bg-white/[0.03] text-zinc-400 hover:border-white/30 hover:text-white'
                                "
                                @click="goToTab(tab.key)"
                            >
                                <span>{{ tab.label }}</span>

                                <span v-if="stepCompletion[tab.key]">✓</span>

                                <span
                                    v-else-if="!canAccessTab(tab.key)"
                                    class="text-xs"
                                >
                                    locked
                                </span>
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
                                <label class="mn-label">
                                    Brand
                                    <span class="text-red-400">*</span>
                                </label>
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
                                <label class="mn-label">
                                    Model Name
                                    <span class="text-red-400">*</span>
                                </label>
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
                                <label class="mn-label">
                                    Reference Number
                                </label>
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
                                <label class="mn-label">
                                    Condition
                                    <span class="text-red-400">*</span>
                                </label>
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

                                <p class="mt-2 text-sm text-zinc-500">
                                    Capital price can be zero, but selling price
                                    must be greater than zero.
                                </p>

                                <div class="mt-5 grid gap-5 md:grid-cols-2">
                                    <div>
                                        <label class="mn-label">
                                            Capital Price
                                            <span class="text-red-400">*</span>
                                        </label>
                                        <input
                                            v-model="form.capital_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="mn-input"
                                            placeholder="0.00"
                                        />
                                    </div>

                                    <div>
                                        <label class="mn-label">
                                            Selling Price
                                            <span class="text-red-400">*</span>
                                        </label>
                                        <input
                                            v-model="form.selling_price"
                                            type="number"
                                            step="0.01"
                                            min="1"
                                            class="mn-input"
                                            placeholder="0.00"
                                        />
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="mn-label">
                                            Discounted Price
                                        </label>
                                        <input
                                            v-model="form.discounted_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
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
                                        <label class="mn-label">
                                            Status
                                            <span class="text-red-400">*</span>
                                        </label>
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
                            <div>
                                <label class="mn-label">Movement</label>
                                <input
                                    v-model="form.movement"
                                    class="mn-input"
                                    placeholder="Movement"
                                />
                            </div>

                            <div>
                                <label class="mn-label">Case Size</label>
                                <input
                                    v-model="form.case_size"
                                    class="mn-input"
                                    placeholder="Case Size"
                                />
                            </div>

                            <div>
                                <label class="mn-label">Case Material</label>
                                <input
                                    v-model="form.case_material"
                                    class="mn-input"
                                    placeholder="Case Material"
                                />
                            </div>

                            <div>
                                <label class="mn-label">Dial Color</label>
                                <input
                                    v-model="form.dial_color"
                                    class="mn-input"
                                    placeholder="Dial Color"
                                />
                            </div>

                            <div>
                                <label class="mn-label">Crystal</label>
                                <input
                                    v-model="form.crystal"
                                    class="mn-input"
                                    placeholder="Crystal"
                                />
                            </div>

                            <div>
                                <label class="mn-label">
                                    Bracelet / Strap
                                </label>
                                <input
                                    v-model="form.bracelet_or_strap"
                                    class="mn-input"
                                    placeholder="Bracelet / Strap"
                                />
                            </div>

                            <div>
                                <label class="mn-label">
                                    Water Resistance
                                </label>
                                <input
                                    v-model="form.water_resistance"
                                    class="mn-input"
                                    placeholder="Water Resistance"
                                />
                            </div>

                            <div>
                                <label class="mn-label">Box and Papers</label>
                                <input
                                    v-model="form.box_papers"
                                    class="mn-input"
                                    placeholder="Box and Papers"
                                />
                            </div>

                            <div class="md:col-span-2">
                                <label class="mn-label">
                                    Warranty Type
                                    <span class="text-red-400">*</span>
                                </label>
                                <input
                                    v-model="form.warranty_type"
                                    class="mn-input"
                                    placeholder="Warranty Type"
                                />
                            </div>
                        </div>

                        <!-- PHOTOS -->
                        <div v-if="activeTab === 'photos'" class="space-y-6">
                            <div class="grid gap-6 lg:grid-cols-[0.8fr_1fr]">
                                <div>
                                    <label
                                        class="flex min-h-[260px] flex-col items-center justify-center rounded-[1.7rem] border border-dashed px-6 py-10 text-center transition"
                                        :class="
                                            canAddMoreImages
                                                ? 'cursor-pointer border-white/20 bg-white/[0.03] hover:border-white/40'
                                                : 'cursor-not-allowed border-red-400/20 bg-red-400/10'
                                        "
                                    >
                                        <span
                                            class="text-sm font-semibold text-white"
                                        >
                                            Upload More HD Photos
                                        </span>

                                        <span
                                            class="mt-2 text-xs leading-6 text-zinc-500"
                                        >
                                            JPG, PNG, WEBP up to 10MB each.
                                            Maximum of 5 photos total.
                                        </span>

                                        <span
                                            class="mt-4 rounded-full border border-white/10 px-4 py-2 text-xs font-medium text-zinc-400"
                                        >
                                            {{ totalImageCount }} /
                                            {{ MAX_IMAGES }} total photos
                                        </span>

                                        <span
                                            v-if="canAddMoreImages"
                                            class="mt-3 text-xs text-zinc-500"
                                        >
                                            You can still add
                                            {{ remainingSlots }} image(s).
                                        </span>

                                        <span
                                            v-else
                                            class="mt-3 text-xs font-semibold text-red-300"
                                        >
                                            Maximum image limit reached.
                                        </span>

                                        <input
                                            ref="fileInput"
                                            type="file"
                                            multiple
                                            accept="image/*"
                                            class="hidden"
                                            :disabled="!canAddMoreImages"
                                            @change="handleImages"
                                        />
                                    </label>

                                    <InputError
                                        class="mt-2"
                                        :message="
                                            imageLimitMessage ||
                                            form.errors.images
                                        "
                                    />
                                </div>

                                <div
                                    class="rounded-[1.7rem] border border-white/10 bg-white/[0.03] p-5"
                                >
                                    <h3
                                        class="text-lg font-semibold text-white"
                                    >
                                        Photo Requirement
                                    </h3>

                                    <p
                                        class="mt-3 text-sm leading-6 text-zinc-400"
                                    >
                                        This watch must have at least one photo
                                        and a maximum of five photos. Existing
                                        photos are shown below. Newly selected
                                        photos will be uploaded after saving
                                        changes.
                                    </p>
                                </div>
                            </div>

                            <!-- EXISTING PHOTOS -->
                            <div>
                                <div
                                    class="mb-4 flex items-center justify-between gap-3"
                                >
                                    <div>
                                        <h3
                                            class="text-lg font-semibold text-white"
                                        >
                                            Existing Photos
                                        </h3>

                                        <p class="mt-1 text-xs text-zinc-500">
                                            These are already saved in the
                                            database.
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-if="existingImages.length"
                                    class="grid grid-cols-2 gap-4 md:grid-cols-4"
                                >
                                    <div
                                        v-for="image in existingImages"
                                        :key="image.id"
                                        class="overflow-hidden rounded-2xl border border-white/10 bg-[#050505]"
                                    >
                                        <img
                                            :src="image.url"
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
                                                @click="
                                                    setPrimaryExistingImage(
                                                        image,
                                                    )
                                                "
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
                                    No existing photos found. If this watch has
                                    photos in the database, make sure your
                                    controller loads the images relationship.
                                </div>
                            </div>

                            <!-- NEW PHOTOS -->
                            <div v-if="imagePreviews.length">
                                <div
                                    class="mb-4 flex items-center justify-between gap-3"
                                >
                                    <div>
                                        <h3
                                            class="text-lg font-semibold text-white"
                                        >
                                            New Photos to Upload
                                        </h3>

                                        <p class="mt-1 text-xs text-zinc-500">
                                            These photos will be uploaded after
                                            saving changes.
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        class="rounded-xl border border-red-400/20 bg-red-400/10 px-3 py-2 text-xs font-semibold text-red-300 transition hover:border-red-400/40 hover:bg-red-400/15"
                                        @click="clearNewImages"
                                    >
                                        Remove New
                                    </button>
                                </div>

                                <div
                                    class="grid grid-cols-2 gap-3 md:grid-cols-4"
                                >
                                    <div
                                        v-for="(
                                            preview, index
                                        ) in imagePreviews"
                                        :key="preview.url"
                                        class="group relative overflow-hidden rounded-2xl border border-white/10 bg-[#050505]"
                                    >
                                        <img
                                            :src="preview.url"
                                            class="aspect-square w-full object-cover"
                                        />

                                        <div
                                            class="absolute left-3 top-3 rounded-full bg-black/70 px-3 py-1 text-xs font-medium text-white backdrop-blur"
                                        >
                                            New {{ index + 1 }}
                                        </div>

                                        <div
                                            class="absolute inset-x-2 bottom-2 flex gap-2 opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100"
                                        >
                                            <button
                                                v-if="index !== 0"
                                                type="button"
                                                class="flex-1 rounded-xl bg-white px-3 py-2 text-[11px] font-semibold text-black transition hover:bg-zinc-200"
                                                @click="
                                                    setPrimaryNewImage(index)
                                                "
                                            >
                                                Move First
                                            </button>

                                            <button
                                                type="button"
                                                class="flex-1 rounded-xl bg-red-500 px-3 py-2 text-[11px] font-semibold text-white transition hover:bg-red-600"
                                                @click="removeNewImage(index)"
                                            >
                                                Remove
                                            </button>
                                        </div>
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
                                <label class="mn-label">
                                    Section Title
                                    <span class="text-red-400">*</span>
                                </label>
                                <input
                                    v-model="section.title"
                                    class="mn-input"
                                />

                                <label class="mn-label mt-4">
                                    Content
                                    <span class="text-red-400">*</span>
                                </label>
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
                            class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                        >
                            <div class="text-xs leading-5">
                                <p
                                    v-if="canSubmit"
                                    class="font-semibold text-emerald-300"
                                >
                                    All required steps are complete. You can now
                                    save changes.
                                </p>

                                <p v-else class="font-semibold text-zinc-400">
                                    Complete required steps before saving:
                                    <span class="text-red-300">
                                        {{ missingRequirements.join(", ") }}
                                    </span>
                                </p>
                            </div>

                            <div
                                class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"
                            >
                                <button
                                    type="button"
                                    class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30"
                                    @click="closeModal"
                                >
                                    Cancel
                                </button>

                                <button
                                    v-if="activeTab !== 'basic'"
                                    type="button"
                                    class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-semibold text-zinc-300 transition hover:border-white/30 hover:text-white"
                                    @click="goToPreviousTab"
                                >
                                    Previous
                                </button>

                                <button
                                    v-if="activeTab !== 'terms'"
                                    type="button"
                                    :disabled="!currentStepComplete"
                                    class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-semibold text-zinc-300 transition hover:border-white/30 hover:text-white disabled:cursor-not-allowed disabled:opacity-40"
                                    @click="goToNextTab"
                                >
                                    Next
                                </button>

                                <button
                                    type="submit"
                                    :disabled="!canSubmit"
                                    class="rounded-2xl bg-white px-6 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200 disabled:cursor-not-allowed disabled:bg-zinc-700 disabled:text-zinc-400"
                                >
                                    {{
                                        form.processing
                                            ? "Saving Changes..."
                                            : "Save Changes"
                                    }}
                                </button>
                            </div>
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
