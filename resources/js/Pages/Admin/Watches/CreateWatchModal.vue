<script setup>
import InputError from "@/Components/InputError.vue";
import { compressImageFile, formatFileSize } from "@/Utils/imageCompression";
import { useForm } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, ref, watch } from "vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close"]);

const MAX_IMAGES = 5;

const isCompressingImages = ref(false);
const activeTab = ref("basic");
const imagePreviews = ref([]);
const fileInput = ref(null);
const imageLimitMessage = ref("");

const form = useForm({
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
                "Accepted payment methods include cash, Maribank, GoTyme, QR code payments, and selected trade-ins subject to evaluation.",
        },
    ],
});

const tabs = [
    {
        key: "basic",
        label: "Basic",
        title: "Basic Information",
        helper: "Brand, model, condition, category, and description.",
    },
    {
        key: "pricing",
        label: "Pricing",
        title: "Pricing & Visibility",
        helper: "Set capital, selling price, stock status, and website display.",
    },
    {
        key: "specs",
        label: "Specs",
        title: "Watch Specifications",
        helper: "Movement, case size, material, strap, resistance, and warranty.",
    },
    {
        key: "photos",
        label: "Photos",
        title: "HD Product Photos",
        helper: "Upload up to 5 compressed photos. First photo is primary.",
    },
    {
        key: "terms",
        label: "Terms",
        title: "Public Listing Terms",
        helper: "Set purchase process, warranty, and payment instructions.",
    },
];

watch(
    () => props.show,
    (value) => {
        if (value) {
            activeTab.value = "basic";
        }
    },
);

const clean = (value) => String(value ?? "").trim();

const isZeroOrGreater = (value) => {
    if (value === "" || value === null || value === undefined) return false;

    return Number(value) >= 0;
};

const isPositive = (value) => {
    if (value === "" || value === null || value === undefined) return false;

    return Number(value) > 0;
};

const peso = (value) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
};

const remainingSlots = computed(() => {
    return Math.max(MAX_IMAGES - imagePreviews.value.length, 0);
});

const canAddMoreImages = computed(() => {
    return remainingSlots.value > 0;
});

const finalSellingPrice = computed(() => {
    if (Number(form.discounted_price || 0) > 0) {
        return Number(form.discounted_price || 0);
    }

    return Number(form.selling_price || 0);
});

const estimatedProfit = computed(() => {
    return finalSellingPrice.value - Number(form.capital_price || 0);
});

const estimatedMargin = computed(() => {
    if (finalSellingPrice.value <= 0) return 0;

    return (estimatedProfit.value / finalSellingPrice.value) * 100;
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
    return (
        imagePreviews.value.length > 0 &&
        imagePreviews.value.length <= MAX_IMAGES
    );
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

const completedStepCount = computed(() => {
    return tabs.filter((tab) => stepCompletion.value[tab.key]).length;
});

const progressPercentage = computed(() => {
    return (completedStepCount.value / tabs.length) * 100;
});

const currentTab = computed(() => {
    return tabs.find((tab) => tab.key === activeTab.value) || tabs[0];
});

const currentTabIndex = computed(() => {
    return tabs.findIndex((tab) => tab.key === activeTab.value);
});

const canSubmit = computed(() => {
    return (
        basicComplete.value &&
        pricingComplete.value &&
        specsComplete.value &&
        photosComplete.value &&
        termsComplete.value &&
        !form.processing &&
        !isCompressingImages.value
    );
});

const missingRequirements = computed(() => {
    const missing = [];

    if (!basicComplete.value) missing.push("Basic Info");
    if (!pricingComplete.value) missing.push("Pricing");
    if (!specsComplete.value) missing.push("Specs");
    if (!photosComplete.value) missing.push("Photos");
    if (!termsComplete.value) missing.push("Terms");

    return missing;
});

const firstIncompleteTab = () => {
    const incomplete = tabs.find((tab) => !stepCompletion.value[tab.key]);

    return incomplete?.key || "terms";
};

const getTabIndex = (key) => {
    return tabs.findIndex((tab) => tab.key === key);
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
    const previousIndex = Math.max(0, currentTabIndex.value - 1);

    activeTab.value = tabs[previousIndex].key;
};

const goToNextTab = () => {
    if (!currentStepComplete.value) return;

    const nextIndex = Math.min(tabs.length - 1, currentTabIndex.value + 1);

    activeTab.value = tabs[nextIndex].key;
};

const syncImages = () => {
    form.images = imagePreviews.value.map((image) => image.file);
};

const handleImages = async (event) => {
    imageLimitMessage.value = "";

    const files = Array.from(event.target.files || []);

    if (!files.length) return;

    if (remainingSlots.value <= 0) {
        imageLimitMessage.value = `Maximum of ${MAX_IMAGES} images only. Remove an image before adding another.`;

        if (fileInput.value) {
            fileInput.value.value = "";
        }

        return;
    }

    const acceptedFiles = files.slice(0, remainingSlots.value);

    if (files.length > remainingSlots.value) {
        imageLimitMessage.value = `Only ${remainingSlots.value} more image(s) added. Maximum allowed is ${MAX_IMAGES} images.`;
    }

    isCompressingImages.value = true;

    try {
        const compressedFiles = await Promise.all(
            acceptedFiles.map((file) =>
                compressImageFile(file, {
                    maxWidth: 1600,
                    maxHeight: 1600,
                    quality: 0.78,
                }),
            ),
        );

        const newImages = compressedFiles.map((file, index) => ({
            file,
            url: URL.createObjectURL(file),
            name: file.name,
            size: file.size,
            originalSize: acceptedFiles[index].size,
        }));

        imagePreviews.value = [...imagePreviews.value, ...newImages];

        syncImages();
    } catch (error) {
        console.error(error);

        imageLimitMessage.value =
            "Something went wrong while compressing images. Please try again.";
    } finally {
        isCompressingImages.value = false;

        if (fileInput.value) {
            fileInput.value.value = "";
        }
    }
};

const removeImage = (index) => {
    const imageToRemove = imagePreviews.value[index];

    if (imageToRemove?.url) {
        URL.revokeObjectURL(imageToRemove.url);
    }

    imagePreviews.value = imagePreviews.value.filter((_, i) => i !== index);

    imageLimitMessage.value = "";

    syncImages();

    if (fileInput.value) {
        fileInput.value.value = "";
    }
};

const clearImages = () => {
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

const setPrimaryImage = (index) => {
    if (index === 0) return;

    const images = [...imagePreviews.value];
    const selected = images.splice(index, 1)[0];

    images.unshift(selected);

    imagePreviews.value = images;

    syncImages();
};

const resetModalState = () => {
    clearImages();
    form.reset();
    form.clearErrors();
    activeTab.value = "basic";
};

const closeModal = () => {
    if (form.processing) return;

    resetModalState();
    emit("close");
};

const submit = () => {
    if (!canSubmit.value) {
        activeTab.value = firstIncompleteTab();
        return;
    }

    form.post(route("admin.watches.store"), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            resetModalState();
            emit("close");
        },
    });
};

onBeforeUnmount(() => {
    clearImages();
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
                v-if="show"
                class="fixed inset-0 z-[999] flex items-end justify-center bg-black/80 px-2 py-2 backdrop-blur-sm sm:items-center sm:px-4 sm:py-6"
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
                        v-if="show"
                        @submit.prevent="submit"
                        class="relative flex h-[94svh] w-full max-w-6xl flex-col overflow-hidden rounded-t-[2rem] border border-white/10 bg-[#080808] shadow-2xl shadow-black sm:h-auto sm:max-h-[92vh] sm:rounded-[2rem]"
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
                            class="border-b border-white/10 bg-[#0B0B0D] px-4 py-4 sm:px-6 sm:py-5"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.26em] text-zinc-600 sm:text-xs"
                                    >
                                        Montre Nova Inventory
                                    </p>

                                    <h2
                                        class="mt-2 truncate text-xl font-semibold tracking-tight text-white sm:text-2xl"
                                    >
                                        Add New Watch
                                    </h2>

                                    <p
                                        class="mt-2 hidden max-w-2xl text-sm leading-6 text-zinc-400 sm:block"
                                    >
                                        Encode stock details, pricing,
                                        specifications, terms, and up to 5 HD
                                        photos.
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

                            <!-- PROGRESS -->
                            <div class="mt-4">
                                <div
                                    class="flex items-center justify-between gap-4"
                                >
                                    <div>
                                        <p
                                            class="text-xs font-semibold text-white"
                                        >
                                            Step {{ currentTabIndex + 1 }} of
                                            {{ tabs.length }}:
                                            {{ currentTab.title }}
                                        </p>

                                        <p
                                            class="mt-1 text-xs leading-5 text-zinc-500"
                                        >
                                            {{ currentTab.helper }}
                                        </p>
                                    </div>

                                    <div
                                        class="hidden rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-xs font-semibold text-zinc-400 sm:block"
                                    >
                                        {{ completedStepCount }} /
                                        {{ tabs.length }} done
                                    </div>
                                </div>

                                <div
                                    class="mt-4 h-2 overflow-hidden rounded-full bg-zinc-900"
                                >
                                    <div
                                        class="h-full rounded-full bg-white transition-all duration-300"
                                        :style="{
                                            width: `${progressPercentage}%`,
                                        }"
                                    ></div>
                                </div>
                            </div>

                            <!-- STEP PILLS -->
                            <div
                                class="thin-scrollbar mt-4 flex gap-2 overflow-x-auto pb-1"
                            >
                                <button
                                    v-for="(tab, index) in tabs"
                                    :key="tab.key"
                                    type="button"
                                    :disabled="!canAccessTab(tab.key)"
                                    class="flex shrink-0 items-center gap-2 rounded-2xl border px-3 py-2 text-xs font-bold transition disabled:cursor-not-allowed disabled:opacity-40 sm:px-4 sm:text-sm"
                                    :class="
                                        activeTab === tab.key
                                            ? 'border-white bg-white text-black'
                                            : stepCompletion[tab.key]
                                              ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-300'
                                              : 'border-white/10 bg-white/[0.03] text-zinc-400 hover:border-white/30 hover:text-white'
                                    "
                                    @click="goToTab(tab.key)"
                                >
                                    <span
                                        class="flex h-5 w-5 items-center justify-center rounded-full text-[10px] font-black"
                                        :class="
                                            activeTab === tab.key
                                                ? 'bg-black text-white'
                                                : stepCompletion[tab.key]
                                                  ? 'bg-emerald-400 text-black'
                                                  : 'bg-white/10 text-zinc-400'
                                        "
                                    >
                                        {{
                                            stepCompletion[tab.key]
                                                ? "✓"
                                                : index + 1
                                        }}
                                    </span>

                                    <span>{{ tab.label }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- BODY -->
                        <div
                            class="thin-scrollbar flex-1 overflow-y-auto px-4 py-5 sm:px-6 sm:py-6"
                        >
                            <!-- BASIC -->
                            <div
                                v-if="activeTab === 'basic'"
                                class="grid gap-4 md:grid-cols-2 sm:gap-5"
                            >
                                <div class="md:col-span-2">
                                    <div
                                        class="rounded-[1.4rem] border border-white/10 bg-white/[0.03] p-4"
                                    >
                                        <p
                                            class="text-xs font-bold uppercase tracking-[0.2em] text-zinc-600"
                                        >
                                            Required
                                        </p>

                                        <p
                                            class="mt-2 text-sm leading-6 text-zinc-400"
                                        >
                                            Start with the brand, model, and
                                            condition. These are required before
                                            moving to pricing.
                                        </p>
                                    </div>
                                </div>

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
                                        placeholder="Diver, GMT, Dress..."
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
                                        rows="5"
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
                                class="grid gap-5 lg:grid-cols-[1fr_0.75fr]"
                            >
                                <div
                                    class="rounded-[1.5rem] border border-white/10 bg-white/[0.03] p-4 sm:p-5"
                                >
                                    <h3
                                        class="text-lg font-semibold text-white"
                                    >
                                        Pricing
                                    </h3>

                                    <p
                                        class="mt-2 text-sm leading-6 text-zinc-500"
                                    >
                                        Capital price can be zero, but selling
                                        price must be greater than zero.
                                    </p>

                                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                                        <div>
                                            <label class="mn-label">
                                                Capital Price
                                                <span class="text-red-400"
                                                    >*</span
                                                >
                                            </label>

                                            <input
                                                v-model="form.capital_price"
                                                type="number"
                                                step="0.01"
                                                min="0"
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
                                            <label class="mn-label">
                                                Selling Price
                                                <span class="text-red-400"
                                                    >*</span
                                                >
                                            </label>

                                            <input
                                                v-model="form.selling_price"
                                                type="number"
                                                step="0.01"
                                                min="1"
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

                                            <InputError
                                                class="mt-2"
                                                :message="
                                                    form.errors.discounted_price
                                                "
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div
                                        class="rounded-[1.5rem] border border-white/10 bg-white/[0.03] p-4 sm:p-5"
                                    >
                                        <h3
                                            class="text-lg font-semibold text-white"
                                        >
                                            Profit Preview
                                        </h3>

                                        <div class="mt-5 grid gap-3">
                                            <div class="mn-preview-row">
                                                <span>Final Price</span>
                                                <strong>
                                                    {{
                                                        peso(finalSellingPrice)
                                                    }}
                                                </strong>
                                            </div>

                                            <div class="mn-preview-row">
                                                <span>Estimated Profit</span>
                                                <strong
                                                    :class="
                                                        estimatedProfit >= 0
                                                            ? 'text-emerald-300'
                                                            : 'text-red-300'
                                                    "
                                                >
                                                    {{ peso(estimatedProfit) }}
                                                </strong>
                                            </div>

                                            <div class="mn-preview-row">
                                                <span>Margin</span>
                                                <strong>
                                                    {{
                                                        estimatedMargin.toFixed(
                                                            1,
                                                        )
                                                    }}%
                                                </strong>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="rounded-[1.5rem] border border-white/10 bg-white/[0.03] p-4 sm:p-5"
                                    >
                                        <h3
                                            class="text-lg font-semibold text-white"
                                        >
                                            Status & Display
                                        </h3>

                                        <div class="mt-5 space-y-4">
                                            <div>
                                                <label class="mn-label">
                                                    Status
                                                    <span class="text-red-400"
                                                        >*</span
                                                    >
                                                </label>

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
                                                    :message="
                                                        form.errors.status
                                                    "
                                                />
                                            </div>

                                            <div class="space-y-3">
                                                <label class="mn-toggle">
                                                    <span
                                                        >Visible on
                                                        website</span
                                                    >
                                                    <input
                                                        v-model="
                                                            form.is_visible
                                                        "
                                                        type="checkbox"
                                                        class="mn-checkbox"
                                                    />
                                                </label>

                                                <label class="mn-toggle">
                                                    <span>Featured watch</span>
                                                    <input
                                                        v-model="
                                                            form.is_featured
                                                        "
                                                        type="checkbox"
                                                        class="mn-checkbox"
                                                    />
                                                </label>

                                                <label class="mn-toggle">
                                                    <span>Display price</span>
                                                    <input
                                                        v-model="
                                                            form.display_price
                                                        "
                                                        type="checkbox"
                                                        class="mn-checkbox"
                                                    />
                                                </label>

                                                <label class="mn-toggle">
                                                    <span>Allow inquiry</span>
                                                    <input
                                                        v-model="
                                                            form.allow_inquiry
                                                        "
                                                        type="checkbox"
                                                        class="mn-checkbox"
                                                    />
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SPECS -->
                            <div
                                v-if="activeTab === 'specs'"
                                class="grid gap-4 md:grid-cols-2 sm:gap-5"
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
                                    <label class="mn-label">
                                        Case Material
                                    </label>
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
                                    <label class="mn-label">
                                        Bracelet / Strap
                                    </label>
                                    <input
                                        v-model="form.bracelet_or_strap"
                                        class="mn-input"
                                        placeholder="Steel bracelet, leather strap..."
                                    />
                                </div>

                                <div>
                                    <label class="mn-label">
                                        Water Resistance
                                    </label>
                                    <input
                                        v-model="form.water_resistance"
                                        class="mn-input"
                                        placeholder="100m, 200m..."
                                    />
                                </div>

                                <div>
                                    <label class="mn-label">
                                        Box and Papers
                                    </label>
                                    <input
                                        v-model="form.box_papers"
                                        class="mn-input"
                                        placeholder="Complete Set, Watch Only..."
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
                                        placeholder="Montre Card 1 Year Service Warranty"
                                    />
                                </div>
                            </div>

                            <!-- PHOTOS -->
                            <div
                                v-if="activeTab === 'photos'"
                                class="grid gap-5 lg:grid-cols-[0.75fr_1fr]"
                            >
                                <div>
                                    <label
                                        class="flex min-h-[230px] flex-col items-center justify-center rounded-[1.7rem] border border-dashed px-5 py-8 text-center transition sm:min-h-[320px]"
                                        :class="
                                            canAddMoreImages
                                                ? 'cursor-pointer border-white/20 bg-white/[0.03] hover:border-white/40 hover:bg-white/[0.05]'
                                                : 'cursor-not-allowed border-red-400/20 bg-red-400/10'
                                        "
                                    >
                                        <div
                                            class="flex h-14 w-14 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.04] sm:h-16 sm:w-16"
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
                                            Maximum of 5 photos. First photo is
                                            used as the primary image.
                                        </span>

                                        <span
                                            class="mt-4 rounded-full border border-white/10 px-4 py-2 text-xs font-medium text-zinc-400"
                                        >
                                            {{ imagePreviews.length }} /
                                            {{ MAX_IMAGES }} selected
                                        </span>

                                        <span
                                            v-if="isCompressingImages"
                                            class="mt-3 text-xs font-semibold text-emerald-300"
                                        >
                                            Compressing images...
                                        </span>

                                        <span
                                            v-else-if="canAddMoreImages"
                                            class="mt-3 text-xs text-zinc-500"
                                        >
                                            {{ remainingSlots }} slot(s)
                                            remaining
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

                                <div>
                                    <div v-if="imagePreviews.length">
                                        <div
                                            class="mb-4 flex items-center justify-between gap-3"
                                        >
                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-white"
                                                >
                                                    Selected Photos
                                                </p>

                                                <p
                                                    class="mt-1 text-xs text-zinc-500"
                                                >
                                                    Tap a photo to make it
                                                    primary.
                                                </p>
                                            </div>

                                            <button
                                                type="button"
                                                class="rounded-xl border border-red-400/20 bg-red-400/10 px-3 py-2 text-xs font-semibold text-red-300 transition hover:border-red-400/40 hover:bg-red-400/15"
                                                @click="clearImages"
                                            >
                                                Remove All
                                            </button>
                                        </div>

                                        <div
                                            class="grid grid-cols-2 gap-3 sm:grid-cols-3"
                                        >
                                            <div
                                                v-for="(
                                                    preview, index
                                                ) in imagePreviews"
                                                :key="preview.url"
                                                class="group relative overflow-hidden rounded-2xl border border-white/10 bg-[#050505]"
                                            >
                                                <button
                                                    type="button"
                                                    class="block w-full"
                                                    @click="
                                                        setPrimaryImage(index)
                                                    "
                                                >
                                                    <img
                                                        :src="preview.url"
                                                        class="aspect-square w-full object-cover"
                                                    />
                                                </button>

                                                <div
                                                    class="absolute left-2 top-2 rounded-full bg-black/70 px-2.5 py-1 text-[10px] font-bold text-white backdrop-blur"
                                                >
                                                    {{
                                                        index === 0
                                                            ? "Primary"
                                                            : `Photo ${index + 1}`
                                                    }}
                                                </div>

                                                <div
                                                    class="absolute bottom-2 left-2 right-2 rounded-xl bg-black/70 px-2 py-1 text-[10px] text-zinc-300 backdrop-blur"
                                                >
                                                    {{
                                                        formatFileSize(
                                                            preview.size,
                                                        )
                                                    }}
                                                </div>

                                                <button
                                                    type="button"
                                                    class="absolute right-2 top-2 flex h-8 w-8 items-center justify-center rounded-full bg-red-500 text-sm font-bold text-white"
                                                    @click.stop="
                                                        removeImage(index)
                                                    "
                                                >
                                                    ×
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        v-else
                                        class="flex min-h-[230px] items-center justify-center rounded-[1.7rem] border border-white/10 bg-[#050505] text-center sm:min-h-[320px]"
                                    >
                                        <div class="px-6">
                                            <p
                                                class="text-sm font-medium text-white"
                                            >
                                                No photos selected yet.
                                            </p>

                                            <p
                                                class="mt-2 text-sm leading-6 text-zinc-500"
                                            >
                                                Add at least 1 photo before
                                                saving this watch.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TERMS -->
                            <div v-if="activeTab === 'terms'" class="space-y-4">
                                <div
                                    v-for="(section, index) in form.sections"
                                    :key="index"
                                    class="rounded-[1.5rem] border border-white/10 bg-white/[0.03] p-4 sm:p-5"
                                >
                                    <div
                                        class="mb-4 flex items-center justify-between"
                                    >
                                        <p
                                            class="text-xs font-bold uppercase tracking-[0.2em] text-zinc-600"
                                        >
                                            Section {{ index + 1 }}
                                        </p>
                                    </div>

                                    <label class="mn-label">
                                        Section Title
                                        <span class="text-red-400">*</span>
                                    </label>

                                    <input
                                        v-model="section.title"
                                        class="mn-input"
                                        placeholder="Section Title"
                                    />

                                    <label class="mn-label mt-4">
                                        Content
                                        <span class="text-red-400">*</span>
                                    </label>

                                    <textarea
                                        v-model="section.content"
                                        rows="5"
                                        class="mn-input"
                                        placeholder="Section content..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div
                            class="safe-bottom border-t border-white/10 bg-[#0B0B0D] px-4 py-4 sm:px-6 sm:py-5"
                        >
                            <div
                                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                            >
                                <div class="text-xs leading-5">
                                    <p
                                        v-if="canSubmit"
                                        class="font-semibold text-emerald-300"
                                    >
                                        All required steps are complete.
                                    </p>

                                    <p
                                        v-else
                                        class="font-semibold text-zinc-400"
                                    >
                                        Missing:
                                        <span class="text-red-300">
                                            {{ missingRequirements.join(", ") }}
                                        </span>
                                    </p>
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
                                        class="rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200 disabled:cursor-not-allowed disabled:bg-zinc-700 disabled:text-zinc-400"
                                        @click="goToNextTab"
                                    >
                                        Next
                                    </button>

                                    <button
                                        v-if="activeTab === 'terms'"
                                        type="submit"
                                        :disabled="!canSubmit"
                                        class="rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200 disabled:cursor-not-allowed disabled:bg-zinc-700 disabled:text-zinc-400"
                                    >
                                        {{
                                            isCompressingImages
                                                ? "Compressing..."
                                                : form.processing
                                                  ? "Saving..."
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
    height: 1.1rem;
    width: 1.1rem;
    border-radius: 0.375rem;
    border-color: rgb(255 255 255 / 0.2);
    background: black;
    color: white;
}

.mn-preview-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    border-bottom: 1px solid rgb(255 255 255 / 0.08);
    padding-bottom: 0.85rem;
    font-size: 0.875rem;
    color: rgb(113 113 122);
}

.mn-preview-row:last-child {
    border-bottom: 0;
    padding-bottom: 0;
}

.mn-preview-row strong {
    color: white;
    font-weight: 700;
    text-align: right;
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
