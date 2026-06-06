<script setup>
import InputError from "@/Components/InputError.vue";
import { compressImageFile, formatFileSize } from "@/Utils/imageCompression";
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
const pendingPrimaryImage = ref(null);
const fileInput = ref(null);
const imageLimitMessage = ref("");
const isCompressingImages = ref(false);

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
    primary_existing_image_id: "",
    primary_new_image_index: "",
    sections: [],
});

const tabs = [
    {
        key: "basic",
        label: "Basic",
        title: "Basic Information",
        helper: "Update brand, model, reference, condition, and description.",
    },
    {
        key: "pricing",
        label: "Pricing",
        title: "Pricing & Visibility",
        helper: "Update price, profit, status, and website visibility.",
    },
    {
        key: "specs",
        label: "Specs",
        title: "Watch Specifications",
        helper: "Update movement, case, strap, resistance, and warranty details.",
    },
    {
        key: "photos",
        label: "Photos",
        title: "Photo Manager",
        helper: "Manage existing photos and upload new compressed HD photos.",
    },
    {
        key: "terms",
        label: "Terms",
        title: "Public Listing Terms",
        helper: "Update purchase process, service warranty, and payment details.",
    },
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
            "Accepted payment methods include cash, Maribank, GoTyme, QR code payments, and selected trade-ins subject to evaluation.",
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

const peso = (value) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
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

const completedStepCount = computed(() => {
    return tabs.filter((tab) => stepCompletion.value[tab.key]).length;
});

const progressPercentage = computed(() => {
    return (completedStepCount.value / tabs.length) * 100;
});

const currentTabIndex = computed(() => {
    return tabs.findIndex((tab) => tab.key === activeTab.value);
});

const currentTab = computed(() => {
    return tabs[currentTabIndex.value] || tabs[0];
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
    const previousIndex = Math.max(0, currentTabIndex.value - 1);

    activeTab.value = tabs[previousIndex].key;
};

const goToNextTab = () => {
    if (!currentStepComplete.value) return;

    const nextIndex = Math.min(tabs.length - 1, currentTabIndex.value + 1);

    activeTab.value = tabs[nextIndex].key;
};

const makeNewImageKey = () => {
    return `new-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
};

const currentPrimaryExistingImage = computed(() => {
    return existingImages.value.find((image) => image.is_primary) || null;
});

const currentPrimaryNewImage = computed(() => {
    if (pendingPrimaryImage.value?.type !== "new") return null;

    return (
        imagePreviews.value.find(
            (image) => image.clientKey === pendingPrimaryImage.value.clientKey,
        ) || null
    );
});

const pendingPrimarySummary = computed(() => {
    if (
        pendingPrimaryImage.value?.type === "new" &&
        currentPrimaryNewImage.value
    ) {
        const index = imagePreviews.value.findIndex(
            (image) =>
                image.clientKey === currentPrimaryNewImage.value.clientKey,
        );

        return `New photo ${index + 1} will become primary after saving.`;
    }

    if (pendingPrimaryImage.value?.type === "existing") {
        const image = existingImages.value.find(
            (item) => item.id === pendingPrimaryImage.value.id,
        );

        if (image) {
            const index = existingImages.value.findIndex(
                (item) => item.id === image.id,
            );

            return `Existing photo ${index + 1} is the current primary photo.`;
        }
    }

    if (currentPrimaryExistingImage.value) {
        return "Current primary photo is unchanged.";
    }

    if (imagePreviews.value.length) {
        return "First new photo will be used as the primary photo after saving.";
    }

    return "No primary photo selected yet.";
});

const syncPhotoIntent = () => {
    if (pendingPrimaryImage.value?.type === "new") {
        const newIndex = imagePreviews.value.findIndex(
            (image) => image.clientKey === pendingPrimaryImage.value.clientKey,
        );

        form.primary_existing_image_id = "";
        form.primary_new_image_index = newIndex >= 0 ? newIndex : "";
        return;
    }

    if (pendingPrimaryImage.value?.type === "existing") {
        form.primary_existing_image_id = pendingPrimaryImage.value.id || "";
        form.primary_new_image_index = "";
        return;
    }

    form.primary_existing_image_id = "";
    form.primary_new_image_index = "";
};

const setFallbackPrimaryIntent = () => {
    const primaryExisting =
        currentPrimaryExistingImage.value || existingImages.value[0];

    if (primaryExisting?.id) {
        pendingPrimaryImage.value = {
            type: "existing",
            id: primaryExisting.id,
        };
    } else if (imagePreviews.value.length) {
        pendingPrimaryImage.value = {
            type: "new",
            clientKey: imagePreviews.value[0].clientKey,
        };
    } else {
        pendingPrimaryImage.value = null;
    }

    syncPhotoIntent();
};

const isExistingPrimary = (image) => {
    return (
        pendingPrimaryImage.value?.type === "existing" &&
        pendingPrimaryImage.value?.id === image.id
    );
};

const isNewPrimary = (image) => {
    return (
        pendingPrimaryImage.value?.type === "new" &&
        pendingPrimaryImage.value?.clientKey === image.clientKey
    );
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
    setFallbackPrimaryIntent();

    if (fileInput.value) {
        fileInput.value.value = "";
    }
};

const syncNewImages = () => {
    form.images = imagePreviews.value.map((image) => image.file);
    syncPhotoIntent();
};

const handleImages = async (event) => {
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
            clientKey: makeNewImageKey(),
            file,
            url: URL.createObjectURL(file),
            name: file.name,
            size: file.size,
            originalSize: acceptedFiles[index].size,
        }));

        imagePreviews.value = [...imagePreviews.value, ...newImages];

        if (!pendingPrimaryImage.value && newImages.length) {
            pendingPrimaryImage.value = {
                type: "new",
                clientKey: newImages[0].clientKey,
            };
        }

        syncNewImages();
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

const removeNewImage = (index) => {
    const imageToRemove = imagePreviews.value[index];

    if (imageToRemove?.url) {
        URL.revokeObjectURL(imageToRemove.url);
    }

    const removedWasPrimary = isNewPrimary(imageToRemove);

    imagePreviews.value = imagePreviews.value.filter((_, i) => i !== index);

    imageLimitMessage.value = "";

    if (removedWasPrimary) {
        setFallbackPrimaryIntent();
    }

    syncNewImages();

    if (fileInput.value) {
        fileInput.value.value = "";
    }
};

const setPrimaryNewImage = (index) => {
    const image = imagePreviews.value[index];

    if (!image) return;

    pendingPrimaryImage.value = {
        type: "new",
        clientKey: image.clientKey,
    };

    syncPhotoIntent();
};

const moveNewImage = (index, direction) => {
    const targetIndex = direction === "left" ? index - 1 : index + 1;

    if (targetIndex < 0 || targetIndex >= imagePreviews.value.length) return;

    const images = [...imagePreviews.value];
    const currentImage = images[index];

    images[index] = images[targetIndex];
    images[targetIndex] = currentImage;

    imagePreviews.value = images;

    syncNewImages();
};

const moveNewImageToFront = (index) => {
    if (index <= 0) return;

    const images = [...imagePreviews.value];
    const selected = images.splice(index, 1)[0];

    images.unshift(selected);
    imagePreviews.value = images;

    setPrimaryNewImage(0);
    syncNewImages();
};

const moveExistingImageLocally = (image, direction) => {
    const currentIndex = existingImages.value.findIndex(
        (item) => item.id === image.id,
    );

    if (currentIndex === -1) return;

    const targetIndex =
        direction === "left" || direction === "up"
            ? currentIndex - 1
            : currentIndex + 1;

    if (targetIndex < 0 || targetIndex >= existingImages.value.length) return;

    const images = [...existingImages.value];

    const currentImage = images[currentIndex];
    images[currentIndex] = images[targetIndex];
    images[targetIndex] = currentImage;

    existingImages.value = images;
};

const moveImage = (image, direction) => {
    if (!image?.id) return;

    router.patch(
        route("admin.watch-images.move", image.id),
        { direction },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                moveExistingImageLocally(image, direction);
            },
        },
    );
};

const deleteImage = (image) => {
    if (!image?.id) return;
    if (!confirm("Delete this photo?")) return;

    router.delete(route("admin.watch-images.destroy", image.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            const deletedWasPrimary = image.is_primary;

            existingImages.value = existingImages.value.filter(
                (item) => item.id !== image.id,
            );

            if (deletedWasPrimary && existingImages.value.length) {
                existingImages.value = existingImages.value.map(
                    (item, index) => ({
                        ...item,
                        is_primary: index === 0,
                    }),
                );
            }

            setFallbackPrimaryIntent();
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
            preserveState: true,
            onSuccess: () => {
                const selected = existingImages.value.find(
                    (item) => item.id === image.id,
                );

                const others = existingImages.value.filter(
                    (item) => item.id !== image.id,
                );

                if (!selected) return;

                pendingPrimaryImage.value = {
                    type: "existing",
                    id: image.id,
                };

                existingImages.value = [
                    {
                        ...selected,
                        is_primary: true,
                    },
                    ...others.map((item) => ({
                        ...item,
                        is_primary: false,
                    })),
                ];

                syncPhotoIntent();
            },
        },
    );
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
    form.primary_existing_image_id = "";
    form.primary_new_image_index = "";
    pendingPrimaryImage.value = null;

    existingImages.value = Array.isArray(props.watch.images)
        ? props.watch.images
              .map((image) => normalizeExistingImage(image))
              .filter((image) => image.url)
        : [];

    setFallbackPrimaryIntent();

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
    if (form.processing || isCompressingImages.value) return;

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
                        v-if="show && watch"
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
                                        Edit Watch
                                    </h2>

                                    <p
                                        class="mt-1 truncate text-xs text-zinc-500 sm:text-sm"
                                    >
                                        {{ form.brand }} {{ form.model_name }}
                                        <span v-if="form.reference_number">
                                            • Ref. {{ form.reference_number }}
                                        </span>
                                    </p>

                                    <p
                                        class="mt-2 hidden max-w-2xl text-sm leading-6 text-zinc-400 sm:block"
                                    >
                                        Update watch details, pricing, photos,
                                        status, and public terms.
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
                                    class="flex items-start justify-between gap-4"
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
                                            Brand, model name, and condition are
                                            required before moving to pricing.
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
                                </div>

                                <div>
                                    <label class="mn-label">Category</label>

                                    <input
                                        v-model="form.category"
                                        class="mn-input"
                                        placeholder="Diver, GMT, Dress..."
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
                                class="space-y-5"
                            >
                                <div
                                    class="overflow-hidden rounded-[1.4rem] border border-white/10 bg-white/[0.03]"
                                >
                                    <div
                                        class="grid gap-0 lg:grid-cols-[0.85fr_1.15fr]"
                                    >
                                        <label
                                            class="flex min-h-[220px] flex-col items-center justify-center border-b border-white/10 px-5 py-8 text-center transition lg:border-b-0 lg:border-r"
                                            :class="
                                                canAddMoreImages
                                                    ? 'cursor-pointer border-white/10 bg-[#050505]/60 hover:bg-white/[0.04]'
                                                    : 'cursor-not-allowed border-red-400/20 bg-red-400/10'
                                            "
                                        >
                                            <div
                                                class="flex h-14 w-14 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.04]"
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
                                                Add Photos
                                            </span>

                                            <span
                                                class="mt-2 max-w-sm text-xs leading-6 text-zinc-500"
                                            >
                                                Upload compressed HD photos, set
                                                the primary photo, and arrange
                                                new upload order before saving.
                                            </span>

                                            <span
                                                class="mt-4 rounded-xl border border-white/10 px-4 py-2 text-xs font-medium text-zinc-400"
                                            >
                                                {{ totalImageCount }} /
                                                {{ MAX_IMAGES }} total photos
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
                                                :disabled="
                                                    !canAddMoreImages ||
                                                    isCompressingImages
                                                "
                                                @change="handleImages"
                                            />
                                        </label>

                                        <div class="p-5">
                                            <div
                                                class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                                            >
                                                <div>
                                                    <p
                                                        class="text-xs font-bold uppercase tracking-[0.22em] text-zinc-600"
                                                    >
                                                        Photo Workflow
                                                    </p>

                                                    <h3
                                                        class="mt-2 text-lg font-semibold text-white"
                                                    >
                                                        Manage everything before
                                                        closing the modal.
                                                    </h3>

                                                    <p
                                                        class="mt-2 text-sm leading-6 text-zinc-500"
                                                    >
                                                        Existing photo actions
                                                        save immediately. New
                                                        photos, new order, and
                                                        new primary selection
                                                        are applied when you
                                                        click Save Changes.
                                                    </p>
                                                </div>

                                                <button
                                                    v-if="imagePreviews.length"
                                                    type="button"
                                                    class="shrink-0 rounded-xl border border-red-400/20 bg-red-400/10 px-3 py-2 text-xs font-semibold text-red-300 transition hover:border-red-400/40 hover:bg-red-400/15"
                                                    @click="clearNewImages"
                                                >
                                                    Remove New
                                                </button>
                                            </div>

                                            <div
                                                class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4"
                                            >
                                                <div class="mn-photo-stat">
                                                    <p>Existing</p>
                                                    <strong>
                                                        {{
                                                            existingImages.length
                                                        }}
                                                    </strong>
                                                </div>

                                                <div class="mn-photo-stat">
                                                    <p>New</p>
                                                    <strong>
                                                        {{
                                                            imagePreviews.length
                                                        }}
                                                    </strong>
                                                </div>

                                                <div class="mn-photo-stat">
                                                    <p>Total</p>
                                                    <strong>
                                                        {{ totalImageCount }}
                                                    </strong>
                                                </div>

                                                <div class="mn-photo-stat">
                                                    <p>Slots</p>
                                                    <strong>
                                                        {{ remainingSlots }}
                                                    </strong>
                                                </div>
                                            </div>

                                            <div
                                                class="mt-5 rounded-2xl border border-emerald-400/20 bg-emerald-400/10 p-4"
                                            >
                                                <p
                                                    class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-300"
                                                >
                                                    Primary Photo
                                                </p>

                                                <p
                                                    class="mt-2 text-sm leading-6 text-emerald-100/80"
                                                >
                                                    {{ pendingPrimarySummary }}
                                                </p>
                                            </div>

                                            <InputError
                                                class="mt-3"
                                                :message="
                                                    imageLimitMessage ||
                                                    form.errors.images
                                                "
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- NEW PHOTOS -->
                                <div
                                    v-if="imagePreviews.length"
                                    class="rounded-[1.5rem] border border-emerald-400/20 bg-emerald-400/[0.06] p-4 sm:p-5"
                                >
                                    <div
                                        class="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-end"
                                    >
                                        <div>
                                            <p
                                                class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-300"
                                            >
                                                New Upload Queue
                                            </p>

                                            <h3
                                                class="mt-2 text-lg font-semibold text-white"
                                            >
                                                Arrange new photos before saving
                                            </h3>

                                            <p
                                                class="mt-1 text-xs leading-5 text-zinc-400"
                                            >
                                                Use arrows to move photos. Use
                                                Make Primary so a newly added
                                                photo can become the main photo
                                                immediately after Save Changes.
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            class="rounded-xl border border-red-400/20 bg-red-400/10 px-3 py-2 text-xs font-semibold text-red-300 transition hover:border-red-400/40 hover:bg-red-400/15"
                                            @click="clearNewImages"
                                        >
                                            Clear Queue
                                        </button>
                                    </div>

                                    <div
                                        class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"
                                    >
                                        <div
                                            v-for="(
                                                preview, index
                                            ) in imagePreviews"
                                            :key="preview.clientKey"
                                            class="overflow-hidden rounded-2xl border bg-[#050505]"
                                            :class="
                                                isNewPrimary(preview)
                                                    ? 'border-emerald-400/60 ring-2 ring-emerald-400/20'
                                                    : 'border-white/10'
                                            "
                                        >
                                            <div class="relative">
                                                <img
                                                    :src="preview.url"
                                                    class="aspect-square w-full object-cover"
                                                    alt="New watch photo"
                                                />

                                                <div
                                                    class="absolute left-2 top-2 rounded-xl bg-black/70 px-2.5 py-1 text-[10px] font-bold text-white backdrop-blur"
                                                >
                                                    New {{ index + 1 }}
                                                </div>

                                                <div
                                                    v-if="isNewPrimary(preview)"
                                                    class="absolute right-2 top-2 rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-emerald-300 backdrop-blur"
                                                >
                                                    Primary After Save
                                                </div>

                                                <button
                                                    type="button"
                                                    class="absolute bottom-2 right-2 flex h-8 w-8 items-center justify-center rounded-full bg-red-500 text-sm font-bold text-white shadow-lg shadow-black/40"
                                                    @click="
                                                        removeNewImage(index)
                                                    "
                                                >
                                                    ×
                                                </button>
                                            </div>

                                            <div class="space-y-2 p-3">
                                                <div
                                                    class="rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2 text-[10px] leading-4 text-zinc-400"
                                                >
                                                    <p class="truncate">
                                                        {{ preview.name }}
                                                    </p>
                                                    <p>
                                                        {{
                                                            formatFileSize(
                                                                preview.size,
                                                            )
                                                        }}
                                                    </p>
                                                </div>

                                                <div
                                                    class="grid grid-cols-2 gap-2"
                                                >
                                                    <button
                                                        type="button"
                                                        :disabled="index === 0"
                                                        class="mn-photo-btn"
                                                        @click="
                                                            moveNewImage(
                                                                index,
                                                                'left',
                                                            )
                                                        "
                                                    >
                                                        ← Move
                                                    </button>

                                                    <button
                                                        type="button"
                                                        :disabled="
                                                            index ===
                                                            imagePreviews.length -
                                                                1
                                                        "
                                                        class="mn-photo-btn"
                                                        @click="
                                                            moveNewImage(
                                                                index,
                                                                'right',
                                                            )
                                                        "
                                                    >
                                                        Move →
                                                    </button>
                                                </div>

                                                <button
                                                    type="button"
                                                    class="mn-photo-btn w-full"
                                                    :class="
                                                        isNewPrimary(preview)
                                                            ? 'border-emerald-400/30 bg-emerald-400/10 text-emerald-300'
                                                            : ''
                                                    "
                                                    @click="
                                                        setPrimaryNewImage(
                                                            index,
                                                        )
                                                    "
                                                >
                                                    {{
                                                        isNewPrimary(preview)
                                                            ? "Selected Primary"
                                                            : "Make Primary"
                                                    }}
                                                </button>

                                                <button
                                                    v-if="index > 0"
                                                    type="button"
                                                    class="mn-photo-btn w-full"
                                                    @click="
                                                        moveNewImageToFront(
                                                            index,
                                                        )
                                                    "
                                                >
                                                    Move First + Primary
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- EXISTING PHOTOS -->
                                <div
                                    class="rounded-[1.5rem] border border-white/10 bg-white/[0.03] p-4 sm:p-5"
                                >
                                    <div
                                        class="mb-4 flex items-center justify-between gap-3"
                                    >
                                        <div>
                                            <p
                                                class="text-xs font-bold uppercase tracking-[0.22em] text-zinc-600"
                                            >
                                                Saved Photos
                                            </p>

                                            <h3
                                                class="mt-2 text-lg font-semibold text-white"
                                            >
                                                Existing Photos
                                            </h3>

                                            <p
                                                class="mt-1 text-xs leading-5 text-zinc-500"
                                            >
                                                Set primary, move order, or
                                                delete saved photos without
                                                leaving this modal.
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        v-if="existingImages.length"
                                        class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"
                                    >
                                        <div
                                            v-for="(
                                                image, index
                                            ) in existingImages"
                                            :key="`${image.id}-${index}`"
                                            class="overflow-hidden rounded-2xl border bg-[#050505]"
                                            :class="
                                                isExistingPrimary(image)
                                                    ? 'border-emerald-400/60 ring-2 ring-emerald-400/20'
                                                    : 'border-white/10'
                                            "
                                        >
                                            <div class="relative">
                                                <img
                                                    :src="image.url"
                                                    class="aspect-square w-full object-cover"
                                                    alt="Watch photo"
                                                />

                                                <div
                                                    class="absolute left-2 top-2 rounded-xl bg-black/70 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-white backdrop-blur"
                                                >
                                                    Photo {{ index + 1 }}
                                                </div>

                                                <div
                                                    v-if="
                                                        isExistingPrimary(image)
                                                    "
                                                    class="absolute right-2 top-2 rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-emerald-300 backdrop-blur"
                                                >
                                                    Primary
                                                </div>
                                            </div>

                                            <div class="space-y-2 p-3">
                                                <div
                                                    class="grid grid-cols-2 gap-2"
                                                >
                                                    <button
                                                        type="button"
                                                        :disabled="index === 0"
                                                        class="mn-photo-btn"
                                                        @click="
                                                            moveImage(
                                                                image,
                                                                'left',
                                                            )
                                                        "
                                                    >
                                                        ← Move
                                                    </button>

                                                    <button
                                                        type="button"
                                                        :disabled="
                                                            index ===
                                                            existingImages.length -
                                                                1
                                                        "
                                                        class="mn-photo-btn"
                                                        @click="
                                                            moveImage(
                                                                image,
                                                                'right',
                                                            )
                                                        "
                                                    >
                                                        Move →
                                                    </button>
                                                </div>

                                                <button
                                                    v-if="
                                                        !isExistingPrimary(
                                                            image,
                                                        )
                                                    "
                                                    type="button"
                                                    class="mn-photo-btn w-full"
                                                    @click="
                                                        setPrimaryExistingImage(
                                                            image,
                                                        )
                                                    "
                                                >
                                                    Make Primary Now
                                                </button>

                                                <button
                                                    v-else
                                                    type="button"
                                                    disabled
                                                    class="w-full rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-3 py-2 text-xs font-semibold text-emerald-300"
                                                >
                                                    Current Primary
                                                </button>

                                                <button
                                                    type="button"
                                                    class="w-full rounded-xl border border-red-500/20 px-3 py-2 text-xs font-semibold text-red-300 hover:bg-red-500/10"
                                                    @click="deleteImage(image)"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        v-else
                                        class="rounded-2xl border border-white/10 bg-white/[0.03] p-8 text-center"
                                    >
                                        <p
                                            class="text-sm font-medium text-white"
                                        >
                                            No existing photos found.
                                        </p>

                                        <p
                                            class="mt-2 text-sm leading-6 text-zinc-500"
                                        >
                                            Upload at least one photo before
                                            saving this watch.
                                        </p>
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
                                    <p
                                        class="mb-4 text-xs font-bold uppercase tracking-[0.2em] text-zinc-600"
                                    >
                                        Section {{ index + 1 }}
                                    </p>

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
                                        rows="5"
                                        class="mn-input"
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
                                        class="rounded-2xl border border-white/10 bg-white/[0.03] px-5 py-3 text-sm font-semibold text-zinc-300 transition hover:border-white/30 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                                        @click="goToNextTab"
                                    >
                                        Next
                                    </button>

                                    <button
                                        type="submit"
                                        :disabled="!canSubmit"
                                        class="col-span-2 rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200 disabled:cursor-not-allowed disabled:bg-zinc-700 disabled:text-zinc-400 sm:col-span-1"
                                    >
                                        {{
                                            isCompressingImages
                                                ? "Compressing..."
                                                : form.processing
                                                  ? "Saving..."
                                                  : "Save Changes"
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

.mn-photo-stat {
    border-radius: 1rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: rgb(255 255 255 / 0.03);
    padding: 1rem;
}

.mn-photo-stat p {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.16em;
    color: rgb(113 113 122);
}

.mn-photo-stat strong {
    margin-top: 0.5rem;
    display: block;
    font-size: 1.5rem;
    line-height: 1;
    color: white;
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
    opacity: 0.4;
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
