<script setup>
import InputError from "@/Components/InputError.vue";
import { compressImageFile, formatFileSize } from "@/Utils/imageCompression";
import { useForm } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    duplicateSource: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(["close"]);

const MAX_IMAGES = 5;

const isCompressingImages = ref(false);
const activeTab = ref("basic");
const imagePreviews = ref([]);
const fileInput = ref(null);
const imageLimitMessage = ref("");
const saveMode = ref("publish");
const initialFormSnapshot = ref("");
const showPublicPreview = ref(false);

const isDuplicateMode = computed(() => Boolean(props.duplicateSource));

const form = useForm({
    brand: "Seiko",
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

    status: "available",
    is_featured: false,
    is_visible: true,
    display_price: true,
    allow_inquiry: true,
    save_mode: "publish",

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

const clean = (value) => String(value ?? "").trim();

const defaultSections = () => [
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
];

const normalizeSections = (sections) => {
    if (!Array.isArray(sections) || !sections.length) {
        return defaultSections();
    }

    return sections.map((section) => ({
        title: section?.title || "",
        content: section?.content || "",
    }));
};

const applyDefaultPublicState = () => {
    form.status = "available";
    form.is_visible = true;
    form.display_price = true;
    form.allow_inquiry = true;
    form.is_featured = false;
    form.save_mode = "publish";
    saveMode.value = "publish";
};

const applyDuplicateSource = () => {
    const source = props.duplicateSource;

    if (!source) return;

    form.brand = source.brand || "Seiko";
    form.model_name = source.model_name || "";

    // Avoid accidentally reusing the exact same reference number.
    form.reference_number = "";

    form.condition = source.condition || "Brand New";
    form.category = source.category || "";
    form.description = source.description || "";

    form.movement = source.movement || "";
    form.case_size = source.case_size || "";
    form.case_material = source.case_material || "";
    form.dial_color = source.dial_color || "";
    form.crystal = source.crystal || "";
    form.bracelet_or_strap = source.bracelet_or_strap || "";
    form.water_resistance = source.water_resistance || "";
    form.box_papers = source.box_papers || "";
    form.warranty_type =
        source.warranty_type || "Montre Card 1 Year Service Warranty";

    form.capital_price = source.capital_price ?? 0;
    form.selling_price = source.selling_price ?? 0;
    form.discounted_price = source.discounted_price || "";

    form.date_acquired = "";
    form.date_sold = "";
    form.images = [];
    form.sections = normalizeSections(source.sections);

    applyDefaultPublicState();
};

const getFormSnapshot = () => {
    return JSON.stringify({
        brand: form.brand || "",
        model_name: form.model_name || "",
        reference_number: form.reference_number || "",
        condition: form.condition || "",
        category: form.category || "",
        description: form.description || "",

        movement: form.movement || "",
        case_size: form.case_size || "",
        case_material: form.case_material || "",
        dial_color: form.dial_color || "",
        crystal: form.crystal || "",
        bracelet_or_strap: form.bracelet_or_strap || "",
        water_resistance: form.water_resistance || "",
        box_papers: form.box_papers || "",
        warranty_type: form.warranty_type || "",

        capital_price: form.capital_price ?? "",
        selling_price: form.selling_price ?? "",
        discounted_price: form.discounted_price ?? "",

        status: form.status || "",
        is_featured: Boolean(form.is_featured),
        is_visible: Boolean(form.is_visible),
        display_price: Boolean(form.display_price),
        allow_inquiry: Boolean(form.allow_inquiry),

        date_acquired: form.date_acquired || "",
        date_sold: form.date_sold || "",

        sections: form.sections.map((section) => ({
            title: section.title || "",
            content: section.content || "",
        })),
    });
};

const rememberCleanState = () => {
    initialFormSnapshot.value = getFormSnapshot();
};

const hasUnsavedChanges = computed(() => {
    if (!props.show || !initialFormSnapshot.value) return false;

    return (
        getFormSnapshot() !== initialFormSnapshot.value ||
        imagePreviews.value.length > 0
    );
});

const confirmCloseIfDirty = () => {
    if (!hasUnsavedChanges.value) return true;

    return window.confirm(
        "You have unsaved watch details. Close without saving?",
    );
};

watch(
    () => [props.show, props.duplicateSource],
    ([value]) => {
        if (value) {
            activeTab.value = "basic";
            clearImages();
            form.reset();
            applyDefaultPublicState();
            form.sections = defaultSections();
            form.clearErrors();
            applyDuplicateSource();
            rememberCleanState();
        }
    },
);

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

const canSaveDraft = computed(() => {
    return (
        basicComplete.value && !form.processing && !isCompressingImages.value
    );
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

const saveStatusLabel = computed(() => {
    if (form.processing) return "Saving...";
    if (isCompressingImages.value) return "Compressing photos...";
    if (canSubmit.value) return "Ready to publish.";
    if (canSaveDraft.value) return "Draft can be saved now.";

    return "Add brand, model, and condition to save a draft.";
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

const previewPrimaryImage = computed(() => {
    return imagePreviews.value[0]?.url || null;
});

const previewTitle = computed(() => {
    const title = `${clean(form.brand)} ${clean(form.model_name)}`.trim();

    return title || "Untitled Watch";
});

const previewPriceLabel = computed(() => {
    if (!form.display_price) return "Price on inquiry";
    if (finalSellingPrice.value <= 0) return "Price not set";

    return peso(finalSellingPrice.value);
});

const previewStatusLabel = computed(() => {
    if (form.status === "available" && form.is_visible)
        return "Available Online";
    if (form.status === "draft") return "Draft Preview";
    if (form.status === "hidden" || !form.is_visible) return "Hidden Preview";
    if (form.status === "reserved") return "Reserved Preview";
    if (form.status === "sold") return "Sold Preview";

    return "Listing Preview";
});

const previewStatusClass = computed(() => {
    if (form.status === "available" && form.is_visible) {
        return "border-emerald-400/20 bg-emerald-400/10 text-emerald-300";
    }

    if (form.status === "reserved") {
        return "border-amber-400/20 bg-amber-400/10 text-amber-300";
    }

    if (form.status === "sold") {
        return "border-zinc-400/20 bg-zinc-400/10 text-zinc-300";
    }

    return "border-white/10 bg-white/[0.04] text-zinc-400";
});

const previewSpecs = computed(() => {
    return [
        { label: "Movement", value: form.movement },
        { label: "Case Size", value: form.case_size },
        { label: "Case Material", value: form.case_material },
        { label: "Dial Color", value: form.dial_color },
        { label: "Crystal", value: form.crystal },
        { label: "Bracelet / Strap", value: form.bracelet_or_strap },
        { label: "Water Resistance", value: form.water_resistance },
        { label: "Box & Papers", value: form.box_papers },
        { label: "Warranty", value: form.warranty_type },
    ].filter((item) => clean(item.value) !== "");
});

const previewSections = computed(() => {
    return form.sections.filter((section) => {
        return clean(section.title) !== "" && clean(section.content) !== "";
    });
});

const previewWarnings = computed(() => {
    const warnings = [];

    if (!form.is_visible)
        warnings.push("Hidden from website until visibility is turned on.");
    if (form.status !== "available")
        warnings.push(`Status is ${form.status || "not set"}.`);
    if (!previewPrimaryImage.value)
        warnings.push("No product photo selected yet.");
    if (form.display_price && finalSellingPrice.value <= 0)
        warnings.push("Display price is on but final price is not set.");
    if (!form.allow_inquiry)
        warnings.push("Customer inquiry button is disabled.");

    return warnings;
});

const openPublicPreview = () => {
    showPublicPreview.value = true;
};

const closePublicPreview = () => {
    showPublicPreview.value = false;
};

const resetModalState = () => {
    clearImages();
    form.reset();

    // Keep every newly added watch public by default.
    applyDefaultPublicState();
    form.sections = defaultSections();

    form.clearErrors();
    activeTab.value = "basic";
    rememberCleanState();
};

const closeModal = () => {
    if (form.processing || isCompressingImages.value) return;

    if (showPublicPreview.value) {
        closePublicPreview();
        return;
    }

    if (!confirmCloseIfDirty()) return;

    resetModalState();
    emit("close");
};

const submit = (mode = "publish") => {
    saveMode.value = mode;
    form.save_mode = mode;

    if (mode === "draft") {
        if (!canSaveDraft.value) {
            activeTab.value = "basic";
            return;
        }

        form.status = "draft";
        form.is_visible = false;
        form.is_featured = false;
    } else {
        if (!canSubmit.value) {
            activeTab.value = firstIncompleteTab();
            return;
        }

        form.status = "available";
        form.is_visible = true;
        form.display_price = true;
        form.allow_inquiry = true;
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

const handleBeforeUnload = (event) => {
    if (!props.show || !hasUnsavedChanges.value || form.processing) return;

    event.preventDefault();
    event.returnValue = "";
};

onMounted(() => {
    rememberCleanState();
    window.addEventListener("beforeunload", handleBeforeUnload);
});

onBeforeUnmount(() => {
    window.removeEventListener("beforeunload", handleBeforeUnload);
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
                class="fixed inset-0 z-[999] flex items-end justify-center bg-black/90 px-0 py-0 backdrop-blur-sm sm:items-center sm:px-4 sm:py-6"
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
                        @submit.prevent="submit('publish')"
                        class="relative flex h-[100svh] w-full max-w-6xl flex-col overflow-hidden rounded-none border border-white/10 bg-[#080808] shadow-2xl shadow-black sm:h-auto sm:max-h-[92vh] sm:rounded-[2rem]"
                    >
                        <!-- MOBILE HANDLE -->
                        <div class="hidden">
                            <div
                                class="h-1.5 w-12 rounded-full bg-white/20"
                            ></div>
                        </div>

                        <!-- HEADER -->
                        <div
                            class="border-b border-white/10 bg-[#0B0B0D] px-4 py-3 sm:px-6 sm:py-5"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.26em] text-zinc-600 sm:text-xs"
                                    >
                                        Montre Nova Inventory
                                    </p>

                                    <h2
                                        class="mt-1 truncate text-lg font-semibold tracking-tight text-white sm:mt-2 sm:text-2xl"
                                    >
                                        {{
                                            isDuplicateMode
                                                ? "Duplicate Watch"
                                                : "Add New Watch"
                                        }}
                                    </h2>

                                    <p
                                        class="mt-2 hidden max-w-2xl text-sm leading-6 text-zinc-400 sm:block"
                                    >
                                        {{
                                            isDuplicateMode
                                                ? "Copied details are ready. Add new photos and review price before publishing."
                                                : "Encode stock details, pricing, specifications, terms, and up to 5 HD photos."
                                        }}
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
                            <div class="mt-3 sm:mt-4">
                                <!-- Mobile compact stepper -->
                                <div class="sm:hidden">
                                    <div
                                        class="flex items-center justify-between gap-3"
                                    >
                                        <div class="min-w-0">
                                            <p
                                                class="truncate text-xs font-semibold text-white"
                                            >
                                                {{ currentTab.title }}
                                            </p>

                                            <p
                                                class="mt-0.5 text-[11px] text-zinc-500"
                                            >
                                                Step
                                                {{ currentTabIndex + 1 }} of
                                                {{ tabs.length }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-full border border-white/10 bg-white/[0.04] px-3 py-1 text-[11px] font-bold text-zinc-300"
                                        >
                                            {{ completedStepCount }}/{{
                                                tabs.length
                                            }}
                                            done
                                        </div>
                                    </div>

                                    <div
                                        class="mt-3 h-1.5 overflow-hidden rounded-full bg-zinc-900"
                                    >
                                        <div
                                            class="h-full rounded-full bg-white transition-all duration-300"
                                            :style="{
                                                width: `${progressPercentage}%`,
                                            }"
                                        ></div>
                                    </div>

                                    <div class="mt-3 grid grid-cols-5 gap-1.5">
                                        <button
                                            v-for="(tab, index) in tabs"
                                            :key="tab.key"
                                            type="button"
                                            :disabled="!canAccessTab(tab.key)"
                                            class="h-9 rounded-xl border text-[11px] font-black transition disabled:cursor-not-allowed disabled:opacity-30"
                                            :class="
                                                activeTab === tab.key
                                                    ? 'border-white bg-white text-black'
                                                    : stepCompletion[tab.key]
                                                      ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-300'
                                                      : 'border-white/10 bg-white/[0.03] text-zinc-500'
                                            "
                                            @click="goToTab(tab.key)"
                                        >
                                            {{
                                                stepCompletion[tab.key]
                                                    ? "✓"
                                                    : index + 1
                                            }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Desktop progress -->
                                <div class="hidden sm:block">
                                    <div
                                        class="flex items-center justify-between gap-4"
                                    >
                                        <div>
                                            <p
                                                class="text-xs font-semibold text-white"
                                            >
                                                Step
                                                {{ currentTabIndex + 1 }} of
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
                                            class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-xs font-semibold text-zinc-400"
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
                            </div>

                            <!-- STEP PILLS -->
                            <div
                                class="thin-scrollbar mt-4 hidden gap-2 overflow-x-auto pb-1 sm:flex"
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
                            class="thin-scrollbar flex-1 overflow-y-auto px-4 py-4 sm:px-6 sm:py-6"
                        >
                            <!-- BASIC -->
                            <div
                                v-if="activeTab === 'basic'"
                                class="grid gap-4 md:grid-cols-2 sm:gap-5"
                            >
                                <div
                                    v-if="isDuplicateMode"
                                    class="md:col-span-2 rounded-[1.25rem] border border-amber-400/20 bg-amber-400/10 p-4"
                                >
                                    <p
                                        class="text-xs font-bold uppercase tracking-[0.2em] text-amber-300"
                                    >
                                        Duplicate Mode
                                    </p>

                                    <p
                                        class="mt-2 text-sm leading-6 text-amber-100/80"
                                    >
                                        Details were copied from the selected
                                        watch. Reference number and photos were
                                        intentionally cleared to prevent
                                        accidental duplicate listings.
                                    </p>
                                </div>

                                <div class="md:col-span-2">
                                    <div
                                        class="hidden rounded-[1.4rem] border border-white/10 bg-white/[0.03] p-4 sm:block"
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
                                    class="rounded-[1.25rem] border border-white/10 bg-white/[0.03] p-4 sm:rounded-[1.5rem] sm:p-5"
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

                                    <div class="mt-4 grid gap-4 md:grid-cols-2">
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
                                        class="rounded-[1.25rem] border border-white/10 bg-white/[0.03] p-4 sm:rounded-[1.5rem] sm:p-5"
                                    >
                                        <h3
                                            class="text-lg font-semibold text-white"
                                        >
                                            Profit Preview
                                        </h3>

                                        <div class="mt-4 grid gap-3">
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
                                        class="rounded-[1.25rem] border border-white/10 bg-white/[0.03] p-4 sm:rounded-[1.5rem] sm:p-5"
                                    >
                                        <div
                                            class="flex items-center justify-between gap-3"
                                        >
                                            <h3
                                                class="text-lg font-semibold text-white"
                                            >
                                                Status & Display
                                            </h3>

                                            <span
                                                v-if="form.is_visible"
                                                class="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-emerald-300"
                                            >
                                                Live by default
                                            </span>
                                        </div>

                                        <div class="mt-4 space-y-3">
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
                                class="space-y-4"
                            >
                                <div
                                    class="rounded-[1.35rem] border border-white/10 bg-white/[0.03] p-4 sm:rounded-[1.6rem] sm:p-5"
                                >
                                    <div
                                        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div class="min-w-0">
                                            <p
                                                class="text-xs font-bold uppercase tracking-[0.22em] text-zinc-600"
                                            >
                                                Product Photos
                                            </p>

                                            <h3
                                                class="mt-2 text-lg font-semibold text-white"
                                            >
                                                Upload and arrange photos
                                            </h3>

                                            <p
                                                class="mt-1 text-xs leading-5 text-zinc-500 sm:text-sm sm:leading-6"
                                            >
                                                Add up to
                                                {{ MAX_IMAGES }} photos. The
                                                first photo is the public
                                                primary image.
                                            </p>
                                        </div>

                                        <label
                                            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl border px-4 py-3 text-sm font-semibold transition"
                                            :class="
                                                canAddMoreImages
                                                    ? 'cursor-pointer border-white bg-white text-black hover:bg-zinc-200'
                                                    : 'cursor-not-allowed border-red-400/20 bg-red-400/10 text-red-300'
                                            "
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="1.8"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M12 4.5v15m7.5-7.5h-15"
                                                />
                                            </svg>
                                            <span>
                                                {{
                                                    canAddMoreImages
                                                        ? "Add Photos"
                                                        : "Limit Reached"
                                                }}
                                            </span>

                                            <input
                                                ref="fileInput"
                                                type="file"
                                                multiple
                                                accept="image/*"
                                                class="hidden"
                                                :disabled="
                                                    !canAddMoreImages ||
                                                    isCompressingImages
                                                "
                                                @change="handleImages"
                                            />
                                        </label>
                                    </div>

                                    <div
                                        class="mt-4 grid grid-cols-3 gap-2 rounded-2xl border border-white/10 bg-[#050505] p-2 text-center"
                                    >
                                        <div
                                            class="rounded-xl bg-white/[0.03] px-2 py-2"
                                        >
                                            <p
                                                class="text-[10px] font-bold uppercase tracking-[0.14em] text-zinc-600"
                                            >
                                                Selected
                                            </p>
                                            <p
                                                class="mt-1 text-sm font-bold text-white"
                                            >
                                                {{ imagePreviews.length }}/{{
                                                    MAX_IMAGES
                                                }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-xl bg-white/[0.03] px-2 py-2"
                                        >
                                            <p
                                                class="text-[10px] font-bold uppercase tracking-[0.14em] text-zinc-600"
                                            >
                                                Slots
                                            </p>
                                            <p
                                                class="mt-1 text-sm font-bold text-white"
                                            >
                                                {{ remainingSlots }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-xl bg-white/[0.03] px-2 py-2"
                                        >
                                            <p
                                                class="text-[10px] font-bold uppercase tracking-[0.14em] text-zinc-600"
                                            >
                                                Primary
                                            </p>
                                            <p
                                                class="mt-1 text-sm font-bold text-white"
                                            >
                                                {{
                                                    imagePreviews.length
                                                        ? "Set"
                                                        : "None"
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        v-if="isCompressingImages"
                                        class="mt-3 rounded-2xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-xs font-semibold text-emerald-300"
                                    >
                                        Compressing photos. Please wait...
                                    </div>

                                    <InputError
                                        class="mt-3"
                                        :message="
                                            imageLimitMessage ||
                                            form.errors.images
                                        "
                                    />
                                </div>

                                <div
                                    class="rounded-[1.35rem] border border-white/10 bg-white/[0.03] p-4 sm:rounded-[1.6rem] sm:p-5"
                                >
                                    <div
                                        class="mb-4 flex items-center justify-between gap-3"
                                    >
                                        <div class="min-w-0">
                                            <p
                                                class="text-sm font-semibold text-white"
                                            >
                                                Selected Photos
                                            </p>

                                            <p
                                                class="mt-1 text-xs text-zinc-500"
                                            >
                                                Tap Make Primary to move a photo
                                                to the first slot.
                                            </p>
                                        </div>

                                        <button
                                            v-if="imagePreviews.length"
                                            type="button"
                                            class="shrink-0 rounded-xl border border-red-400/20 bg-red-400/10 px-3 py-2 text-xs font-semibold text-red-300 transition hover:border-red-400/40 hover:bg-red-400/15"
                                            @click="clearImages"
                                        >
                                            Clear
                                        </button>
                                    </div>

                                    <div
                                        v-if="imagePreviews.length"
                                        class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5"
                                    >
                                        <article
                                            v-for="(
                                                preview, index
                                            ) in imagePreviews"
                                            :key="preview.url"
                                            class="overflow-hidden rounded-2xl border bg-[#050505]"
                                            :class="
                                                index === 0
                                                    ? 'border-emerald-400/60 ring-2 ring-emerald-400/15'
                                                    : 'border-white/10'
                                            "
                                        >
                                            <div class="relative">
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
                                                        alt="Watch photo"
                                                    />
                                                </button>

                                                <div
                                                    class="absolute left-2 top-2 rounded-xl bg-black/70 px-2.5 py-1 text-[10px] font-bold text-white backdrop-blur"
                                                >
                                                    {{
                                                        index === 0
                                                            ? "Primary"
                                                            : `Photo ${index + 1}`
                                                    }}
                                                </div>

                                                <button
                                                    type="button"
                                                    class="absolute right-2 top-2 flex h-8 w-8 items-center justify-center rounded-full bg-red-500 text-sm font-bold text-white shadow-lg shadow-black/40"
                                                    @click.stop="
                                                        removeImage(index)
                                                    "
                                                    aria-label="Remove photo"
                                                >
                                                    ×
                                                </button>
                                            </div>

                                            <div class="space-y-2 p-2.5">
                                                <p
                                                    class="truncate text-[10px] text-zinc-500"
                                                >
                                                    {{
                                                        formatFileSize(
                                                            preview.size,
                                                        )
                                                    }}
                                                </p>

                                                <button
                                                    type="button"
                                                    :disabled="index === 0"
                                                    class="mn-photo-btn w-full"
                                                    :class="
                                                        index === 0
                                                            ? 'border-emerald-400/30 bg-emerald-400/10 text-emerald-300 opacity-100'
                                                            : ''
                                                    "
                                                    @click="
                                                        setPrimaryImage(index)
                                                    "
                                                >
                                                    {{
                                                        index === 0
                                                            ? "Primary"
                                                            : "Make Primary"
                                                    }}
                                                </button>
                                            </div>
                                        </article>
                                    </div>

                                    <div
                                        v-else
                                        class="rounded-2xl border border-dashed border-white/10 bg-[#050505] p-8 text-center"
                                    >
                                        <p
                                            class="text-sm font-medium text-white"
                                        >
                                            No photos selected yet.
                                        </p>

                                        <p
                                            class="mt-2 text-sm leading-6 text-zinc-500"
                                        >
                                            {{
                                                isDuplicateMode
                                                    ? "Duplicated watches need new photos before publishing."
                                                    : "Add at least 1 photo before publishing this watch."
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- TERMS -->
                            <div v-if="activeTab === 'terms'" class="space-y-4">
                                <div
                                    v-for="(section, index) in form.sections"
                                    :key="index"
                                    class="rounded-[1.25rem] border border-white/10 bg-white/[0.03] p-4 sm:rounded-[1.5rem] sm:p-5"
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
                            class="safe-bottom border-t border-white/10 bg-[#0B0B0D] px-4 py-3 sm:px-6 sm:py-5"
                        >
                            <div
                                class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
                            >
                                <div class="hidden text-xs leading-5 sm:block">
                                    <p
                                        class="font-semibold"
                                        :class="
                                            canSubmit
                                                ? 'text-emerald-300'
                                                : canSaveDraft
                                                  ? 'text-zinc-300'
                                                  : 'text-zinc-500'
                                        "
                                    >
                                        {{ saveStatusLabel }}
                                    </p>

                                    <p
                                        v-if="
                                            !canSubmit &&
                                            missingRequirements.length
                                        "
                                        class="mt-1 text-zinc-500"
                                    >
                                        Publish missing:
                                        <span class="text-red-300">
                                            {{ missingRequirements.join(", ") }}
                                        </span>
                                    </p>
                                </div>

                                <div class="text-[11px] leading-5 sm:hidden">
                                    <p
                                        class="truncate font-semibold"
                                        :class="
                                            canSubmit
                                                ? 'text-emerald-300'
                                                : canSaveDraft
                                                  ? 'text-zinc-300'
                                                  : 'text-zinc-500'
                                        "
                                    >
                                        {{ saveStatusLabel }}
                                    </p>

                                    <p
                                        v-if="
                                            !canSubmit &&
                                            missingRequirements.length
                                        "
                                        class="truncate text-zinc-600"
                                    >
                                        Publish missing:
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
                                        class="hidden rounded-2xl border border-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30 sm:inline-flex"
                                        @click="closeModal"
                                    >
                                        Cancel
                                    </button>

                                    <button
                                        type="button"
                                        class="rounded-2xl border border-white/10 bg-white/[0.03] px-5 py-3 text-sm font-semibold text-zinc-200 transition hover:border-white/30 hover:bg-white/[0.06] hover:text-white"
                                        @click="openPublicPreview"
                                    >
                                        Preview
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
                                        type="button"
                                        :disabled="!canSaveDraft"
                                        class="rounded-2xl border border-white/10 bg-white/[0.03] px-5 py-3 text-sm font-semibold text-zinc-200 transition hover:border-white/30 hover:bg-white/[0.06] disabled:cursor-not-allowed disabled:opacity-50"
                                        :class="
                                            activeTab !== 'basic'
                                                ? 'order-3 col-span-2 sm:order-none sm:col-span-1'
                                                : ''
                                        "
                                        @click="submit('draft')"
                                    >
                                        {{
                                            form.processing &&
                                            saveMode === "draft"
                                                ? "Saving..."
                                                : "Save Draft"
                                        }}
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
                                                : form.processing &&
                                                    saveMode === "publish"
                                                  ? "Publishing..."
                                                  : "Save & Publish"
                                        }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- PUBLIC LISTING PREVIEW -->
                        <div
                            v-if="showPublicPreview"
                            class="absolute inset-0 z-50 flex flex-col bg-[#070707]"
                        >
                            <div
                                class="safe-top flex items-start justify-between gap-4 border-b border-white/10 bg-[#0B0B0D] px-4 py-4 sm:px-6 sm:py-5"
                            >
                                <div class="min-w-0">
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.26em] text-zinc-600 sm:text-xs"
                                    >
                                        Customer View
                                    </p>

                                    <h3
                                        class="mt-1 truncate text-lg font-semibold tracking-tight text-white sm:text-2xl"
                                    >
                                        Public Listing Preview
                                    </h3>

                                    <p
                                        class="mt-1 text-xs leading-5 text-zinc-500 sm:text-sm"
                                    >
                                        This is a preview only. It will not
                                        publish until you save.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="shrink-0 rounded-2xl border border-white/10 bg-white/[0.03] p-3 text-zinc-400 transition hover:border-white/30 hover:text-white"
                                    @click="closePublicPreview"
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

                            <div
                                class="thin-scrollbar flex-1 overflow-y-auto px-4 py-5 sm:px-6 sm:py-7"
                            >
                                <div
                                    class="mx-auto grid max-w-6xl gap-5 lg:grid-cols-[0.9fr_1.1fr]"
                                >
                                    <div
                                        class="overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D]"
                                    >
                                        <div
                                            class="relative aspect-[4/5] bg-[#050505]"
                                        >
                                            <img
                                                v-if="previewPrimaryImage"
                                                :src="previewPrimaryImage"
                                                class="h-full w-full object-cover"
                                                alt="Watch preview"
                                            />

                                            <div
                                                v-else
                                                class="flex h-full w-full items-center justify-center px-8 text-center"
                                            >
                                                <div>
                                                    <p
                                                        class="text-xs font-bold uppercase tracking-[0.32em] text-zinc-700"
                                                    >
                                                        Montre Nova
                                                    </p>
                                                    <p
                                                        class="mt-3 text-sm text-zinc-500"
                                                    >
                                                        No product photo
                                                        selected yet.
                                                    </p>
                                                </div>
                                            </div>

                                            <div
                                                class="absolute left-4 top-4 flex flex-wrap gap-2"
                                            >
                                                <span
                                                    class="rounded-full border px-3 py-1 text-xs font-bold backdrop-blur"
                                                    :class="previewStatusClass"
                                                >
                                                    {{ previewStatusLabel }}
                                                </span>

                                                <span
                                                    v-if="form.condition"
                                                    class="rounded-full border border-white/10 bg-black/60 px-3 py-1 text-xs font-bold text-white backdrop-blur"
                                                >
                                                    {{ form.condition }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        <div
                                            class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5 sm:p-6"
                                        >
                                            <p
                                                class="text-xs font-bold uppercase tracking-[0.24em] text-zinc-600"
                                            >
                                                Montre Nova
                                            </p>

                                            <h2
                                                class="mt-3 text-2xl font-semibold tracking-tight text-white sm:text-4xl"
                                            >
                                                {{ previewTitle }}
                                            </h2>

                                            <p
                                                v-if="form.reference_number"
                                                class="mt-2 text-sm text-zinc-500"
                                            >
                                                Ref. {{ form.reference_number }}
                                            </p>

                                            <div
                                                class="mt-5 flex flex-wrap items-center gap-3"
                                            >
                                                <p
                                                    class="text-2xl font-semibold text-white sm:text-3xl"
                                                >
                                                    {{ previewPriceLabel }}
                                                </p>

                                                <span
                                                    v-if="
                                                        form.discounted_price &&
                                                        Number(
                                                            form.discounted_price,
                                                        ) > 0 &&
                                                        form.display_price
                                                    "
                                                    class="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-xs font-bold text-emerald-300"
                                                >
                                                    Below SRP
                                                </span>
                                            </div>

                                            <p
                                                v-if="form.description"
                                                class="mt-5 text-sm leading-7 text-zinc-400"
                                            >
                                                {{ form.description }}
                                            </p>

                                            <p
                                                v-else
                                                class="mt-5 text-sm leading-7 text-zinc-600"
                                            >
                                                No description added yet.
                                            </p>

                                            <div
                                                class="mt-5 grid grid-cols-2 gap-3"
                                            >
                                                <button
                                                    type="button"
                                                    class="rounded-2xl bg-white px-4 py-3 text-sm font-bold text-black"
                                                >
                                                    Inquire Now
                                                </button>

                                                <button
                                                    type="button"
                                                    class="rounded-2xl border border-white/10 px-4 py-3 text-sm font-bold text-white"
                                                >
                                                    Reserve Watch
                                                </button>
                                            </div>
                                        </div>

                                        <div
                                            v-if="previewWarnings.length"
                                            class="rounded-[1.4rem] border border-amber-400/20 bg-amber-400/10 p-4"
                                        >
                                            <p
                                                class="text-xs font-bold uppercase tracking-[0.2em] text-amber-300"
                                            >
                                                Preview Notes
                                            </p>

                                            <ul
                                                class="mt-3 space-y-2 text-sm leading-6 text-amber-100/80"
                                            >
                                                <li
                                                    v-for="warning in previewWarnings"
                                                    :key="warning"
                                                >
                                                    • {{ warning }}
                                                </li>
                                            </ul>
                                        </div>

                                        <div
                                            v-if="previewSpecs.length"
                                            class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5 sm:p-6"
                                        >
                                            <h3
                                                class="text-lg font-semibold text-white"
                                            >
                                                Specifications
                                            </h3>

                                            <div
                                                class="mt-4 grid gap-3 sm:grid-cols-2"
                                            >
                                                <div
                                                    v-for="spec in previewSpecs"
                                                    :key="spec.label"
                                                    class="rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                                                >
                                                    <p
                                                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-zinc-600"
                                                    >
                                                        {{ spec.label }}
                                                    </p>

                                                    <p
                                                        class="mt-2 text-sm font-semibold text-zinc-200"
                                                    >
                                                        {{ spec.value }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            v-if="previewSections.length"
                                            class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5 sm:p-6"
                                        >
                                            <h3
                                                class="text-lg font-semibold text-white"
                                            >
                                                Listing Terms
                                            </h3>

                                            <div class="mt-4 space-y-3">
                                                <div
                                                    v-for="section in previewSections"
                                                    :key="section.title"
                                                    class="rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                                                >
                                                    <p
                                                        class="text-sm font-semibold text-white"
                                                    >
                                                        {{ section.title }}
                                                    </p>

                                                    <p
                                                        class="mt-2 text-sm leading-6 text-zinc-500"
                                                    >
                                                        {{ section.content }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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

.safe-top {
    padding-top: max(1rem, env(safe-area-inset-top));
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

.mn-photo-btn {
    border-radius: 0.75rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    padding: 0.6rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: rgb(212 212 216);
    transition:
        border-color 150ms ease,
        background-color 150ms ease,
        color 150ms ease;
}

.mn-photo-btn:hover {
    border-color: rgb(255 255 255 / 0.3);
    color: white;
}

.mn-photo-btn:disabled {
    cursor: not-allowed;
    opacity: 0.55;
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
