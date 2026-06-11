<script setup>
import InputError from "@/Components/InputError.vue";

import { compressImageFile, formatFileSize } from "@/Utils/imageCompression";

import { router, useForm } from "@inertiajs/vue3";

import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from "vue";

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

const showPublicPreview = ref(false);

const originalSnapshot = ref("");

const isClosingAfterSave = ref(false);

const submitFeedback = ref("");

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

        helper: "Update brand, model, reference, condition, and category.",
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

const normalizeMoneyValue = (value) => {
    if (value === "" || value === null || value === undefined) {
        return "";
    }

    const amount = Number(value);

    if (Number.isNaN(amount)) {
        return String(value);
    }

    return String(amount);
};

const snapshotFormState = () => {
    return JSON.stringify({
        brand: String(form.brand ?? ""),

        model_name: String(form.model_name ?? ""),

        reference_number: String(form.reference_number ?? ""),

        condition: String(form.condition ?? ""),

        category: String(form.category ?? ""),

        description: String(form.description ?? ""),

        movement: String(form.movement ?? ""),

        case_size: String(form.case_size ?? ""),

        case_material: String(form.case_material ?? ""),

        dial_color: String(form.dial_color ?? ""),

        crystal: String(form.crystal ?? ""),

        bracelet_or_strap: String(form.bracelet_or_strap ?? ""),

        water_resistance: String(form.water_resistance ?? ""),

        box_papers: String(form.box_papers ?? ""),

        warranty_type: String(form.warranty_type ?? ""),

        capital_price: normalizeMoneyValue(form.capital_price),

        selling_price: normalizeMoneyValue(form.selling_price),

        discounted_price: normalizeMoneyValue(form.discounted_price),

        status: String(form.status ?? ""),

        is_featured: Boolean(form.is_featured),

        is_visible: Boolean(form.is_visible),

        display_price: Boolean(form.display_price),

        allow_inquiry: Boolean(form.allow_inquiry),

        sections: form.sections.map((section) => ({
            title: String(section.title ?? ""),

            content: String(section.content ?? ""),
        })),
    });
};

const hasUnsavedChanges = computed(() => {
    if (
        !props.show ||
        !props.watch ||
        !originalSnapshot.value ||
        isClosingAfterSave.value
    ) {
        return false;
    }

    if (imagePreviews.value.length > 0) {
        return true;
    }

    return snapshotFormState() !== originalSnapshot.value;
});

const confirmDiscardChanges = () => {
    if (!hasUnsavedChanges.value) {
        return true;
    }

    return window.confirm("You have unsaved changes. Close without saving?");
};

const handleBeforeUnload = (event) => {
    if (
        !hasUnsavedChanges.value ||
        form.processing ||
        isCompressingImages.value
    ) {
        return;
    }

    event.preventDefault();

    event.returnValue = "";
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

const hasAnyPhoto = computed(() => {
    return totalImageCount.value > 0 && totalImageCount.value <= MAX_IMAGES;
});

const publicListingNeedsPhoto = computed(() => {
    return form.is_visible && !["draft", "hidden"].includes(form.status);
});

const photosComplete = computed(() => {
    if (!publicListingNeedsPhoto.value) {
        return totalImageCount.value <= MAX_IMAGES;
    }

    return hasAnyPhoto.value;
});

const stepCompletion = computed(() => ({
    basic: basicComplete.value,

    pricing: pricingComplete.value,

    specs: specsComplete.value,

    photos: photosComplete.value,
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

const isLastTab = computed(() => {
    return currentTabIndex.value === tabs.length - 1;
});

const canSubmit = computed(() => {
    return (
        basicComplete.value &&
        pricingComplete.value &&
        specsComplete.value &&
        photosComplete.value &&
        !form.processing &&
        !isCompressingImages.value
    );
});

const missingRequirements = computed(() => {
    const missing = [];

    if (!basicComplete.value) missing.push("Basic Info");

    if (!pricingComplete.value) missing.push("Pricing");

    if (!specsComplete.value) missing.push("Specs");

    if (!photosComplete.value) {
        missing.push(
            publicListingNeedsPhoto.value
                ? "Photos for visible listing"
                : "Photos",
        );
    }

    return missing;
});

const getTabIndex = (key) => {
    return tabs.findIndex((tab) => tab.key === key);
};

const firstIncompleteTab = () => {
    const incomplete = tabs.find((tab) => !stepCompletion.value[tab.key]);

    return incomplete?.key || "photos";
};

const canAccessTab = () => {
    /*

    |--------------------------------------------------------------------------

    | Edit mode tab access

    |--------------------------------------------------------------------------

    | Editing should not feel like a locked wizard. Users may jump directly to

    | Pricing, Specs, or Photos, then save once the required data is valid.

    */

    return true;
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

const previewPrimaryImage = computed(() => {
    if (currentPrimaryNewImage.value?.url)
        return currentPrimaryNewImage.value.url;

    if (pendingPrimaryImage.value?.type === "existing") {
        const selected = existingImages.value.find(
            (image) => image.id === pendingPrimaryImage.value.id,
        );

        if (selected?.url) return selected.url;
    }

    if (currentPrimaryExistingImage.value?.url) {
        return currentPrimaryExistingImage.value.url;
    }

    return existingImages.value[0]?.url || imagePreviews.value[0]?.url || null;
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

const previewWarnings = computed(() => {
    const warnings = [];

    if (!form.is_visible)
        warnings.push("Hidden from website until visibility is turned on.");

    if (form.status !== "available")
        warnings.push(`Status is ${form.status || "not set"}.`);

    if (!previewPrimaryImage.value)
        warnings.push("No product photo selected yet.");
    if (finalSellingPrice.value <= 0)
        warnings.push("Final price is not set yet.");

    if (imagePreviews.value.length)
        warnings.push("New photo changes will appear after saving.");

    return warnings;
});

const openPublicPreview = () => {
    showPublicPreview.value = true;
};

const closePublicPreview = () => {
    showPublicPreview.value = false;
};

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

const photoCards = computed(() => [
    ...existingImages.value.map((image, index) => ({
        key: `existing-${image.id}-${index}`,

        type: "existing",

        index,

        image,

        url: image.url,

        label: `Saved ${index + 1}`,

        sizeLabel: "Saved photo",

        isPrimary: isExistingPrimary(image),
    })),

    ...imagePreviews.value.map((image, index) => ({
        key: image.clientKey,

        type: "new",

        index,

        image,

        url: image.url,

        label: `New ${index + 1}`,

        sizeLabel: formatFileSize(image.size),

        isPrimary: isNewPrimary(image),
    })),
]);

const canMovePhotoCard = (photo, direction) => {
    if (photo.type === "existing") {
        if (direction === "left") return photo.index > 0;

        return photo.index < existingImages.value.length - 1;
    }

    if (direction === "left") return photo.index > 0;

    return photo.index < imagePreviews.value.length - 1;
};

const setPrimaryPhotoCard = (photo) => {
    if (photo.type === "existing") {
        setPrimaryExistingImage(photo.image);

        return;
    }

    setPrimaryNewImage(photo.index);
};

const movePhotoCard = (photo, direction) => {
    if (photo.type === "existing") {
        moveImage(photo.image, direction);

        return;
    }

    moveNewImage(photo.index, direction);
};

const removePhotoCard = (photo) => {
    if (photo.type === "existing") {
        deleteImage(photo.image);

        return;
    }

    removeNewImage(photo.index);
};

const loadWatchIntoForm = () => {
    if (!props.watch) return;

    activeTab.value = "basic";

    clearNewImages();

    form.clearErrors();

    submitFeedback.value = "";

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

    form.status = props.watch.status || "available";

    form.is_featured = Boolean(props.watch.is_featured);

    form.is_visible =
        props.watch.is_visible === null || props.watch.is_visible === undefined
            ? true
            : Boolean(props.watch.is_visible);

    // These are no longer editable in the modal. Keep them enabled so
    // listings remain straightforward: price shown and inquiry available.
    form.display_price = true;
    form.allow_inquiry = true;

    form.images = [];

    form.primary_existing_image_id = "";

    form.primary_new_image_index = "";

    pendingPrimaryImage.value = null;

    const imageSource =
        Array.isArray(props.watch.images) && props.watch.images.length
            ? props.watch.images
            : props.watch.primary_image
              ? [
                    {
                        ...props.watch.primary_image,

                        is_primary: true,
                    },
                ]
              : [];

    existingImages.value = imageSource

        .map((image) => normalizeExistingImage(image))

        .filter((image) => image.url);

    setFallbackPrimaryIntent();

    form.sections = props.watch.sections?.length
        ? props.watch.sections.map((section) => ({
              title: section.title || "",

              content: section.content || "",
          }))
        : defaultSections();

    originalSnapshot.value = snapshotFormState();

    isClosingAfterSave.value = false;
};

watch(
    () => [props.show, props.watch?.id, props.watch?.updated_at],

    async ([show]) => {
        if (!show || !props.watch?.id) return;

        /*

        |--------------------------------------------------------------------------

        | Hard fix for blank edit form

        |--------------------------------------------------------------------------

        | The parent renders this modal with v-if and passes the selected watch at

        | the same time. Using immediate + flush post + nextTick guarantees the form

        | is filled after Vue has mounted the modal and resolved the latest prop.

        */

        await nextTick();

        loadWatchIntoForm();
    },

    {
        immediate: true,

        flush: "post",
    },
);

watch(
    () => form.status,

    (value, oldValue) => {
        if (value === "available" && oldValue && oldValue !== "available") {
            form.is_visible = true;

            form.display_price = true;

            form.allow_inquiry = true;
        }
    },
);

const closeWithoutPrompt = () => {
    clearNewImages();

    originalSnapshot.value = "";

    isClosingAfterSave.value = false;

    emit("close");
};

const closeModal = () => {
    if (form.processing || isCompressingImages.value) return;

    if (showPublicPreview.value) {
        closePublicPreview();

        return;
    }

    if (!confirmDiscardChanges()) return;

    closeWithoutPrompt();
};

const firstErrorTab = (errors = {}) => {
    const keys = Object.keys(errors || {});

    if (
        keys.some((key) =>
            [
                "brand",

                "model_name",

                "reference_number",

                "condition",

                "category",
            ].some((field) => key.startsWith(field)),
        )
    ) {
        return "basic";
    }

    if (
        keys.some((key) =>
            [
                "capital_price",

                "selling_price",

                "discounted_price",

                "status",

                "is_featured",

                "is_visible",
            ].some((field) => key.startsWith(field)),
        )
    ) {
        return "pricing";
    }

    if (
        keys.some((key) =>
            [
                "movement",

                "case_size",

                "case_material",

                "dial_color",

                "crystal",

                "bracelet_or_strap",

                "water_resistance",

                "box_papers",

                "warranty_type",
            ].some((field) => key.startsWith(field)),
        )
    ) {
        return "specs";
    }

    if (keys.some((key) => key.startsWith("images") || key.includes("image"))) {
        return "photos";
    }

    if (keys.some((key) => key.startsWith("sections"))) {
        return "photos";
    }

    return firstIncompleteTab();
};

const submit = () => {
    if (!props.watch?.id) {
        submitFeedback.value =
            "Unable to save because the selected watch was not loaded properly.";

        return;
    }

    submitFeedback.value = "";

    form.clearErrors();

    if (!canSubmit.value) {
        activeTab.value = firstIncompleteTab();

        submitFeedback.value = `Please complete: ${missingRequirements.value.join(", ")}.`;

        return;
    }

    form.post(route("admin.watches.update", props.watch.id), {
        forceFormData: true,

        preserveScroll: true,

        onStart: () => {
            submitFeedback.value = "Saving changes...";
        },

        onSuccess: () => {
            /*

            |--------------------------------------------------------------------------

            | Let the parent close and reload

            |--------------------------------------------------------------------------

            | Do not call router.reload() inside the modal after saving. The parent owns

            | showEditModal/selectedWatch, so it must close the modal first, destroy the

            | modal instance, then reload the list. This prevents the modal from being

            | re-opened by preserved Inertia state or refreshed props.

            */

            isClosingAfterSave.value = true;

            showPublicPreview.value = false;

            originalSnapshot.value = "";

            clearNewImages();

            submitFeedback.value = "";

            emit("close", { saved: true });
        },

        onError: (errors) => {
            activeTab.value = firstErrorTab(errors);

            submitFeedback.value =
                "Please review the highlighted fields before saving.";

            console.error("Watch update validation failed:", errors);
        },

        onFinish: () => {
            if (submitFeedback.value === "Saving changes...") {
                submitFeedback.value = "";
            }
        },
    });
};

onMounted(() => {
    if (props.show && props.watch?.id) {
        loadWatchIntoForm();
    }

    if (typeof window !== "undefined") {
        window.addEventListener("beforeunload", handleBeforeUnload);
    }
});

onBeforeUnmount(() => {
    clearNewImages();

    if (typeof window !== "undefined") {
        window.removeEventListener("beforeunload", handleBeforeUnload);
    }
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
                v-if="show && watch && !isClosingAfterSave"
                class="fixed inset-0 z-[999] flex items-stretch justify-center bg-black/90 p-0 backdrop-blur-sm sm:items-center sm:px-4 sm:py-6"
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
                        v-if="show && watch && !isClosingAfterSave"
                        @submit.prevent="submit"
                        class="relative flex h-[100dvh] max-h-[100dvh] w-full max-w-6xl flex-col overflow-hidden rounded-none border border-white/10 bg-[#080808] shadow-2xl shadow-black sm:h-auto sm:max-h-[92vh] sm:rounded-[2rem]"
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
                                        Update watch details, pricing, specs,
                                        photos, and website visibility.
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
                                            class="mt-1 hidden text-xs leading-5 text-zinc-500 sm:block"
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

                            <!-- MOBILE STEP DOTS -->

                            <div class="mt-4 grid grid-cols-4 gap-2 sm:hidden">
                                <button
                                    v-for="(tab, index) in tabs"
                                    :key="`mobile-${tab.key}`"
                                    type="button"
                                    class="flex h-10 items-center justify-center rounded-2xl border text-xs font-black transition"
                                    :class="
                                        activeTab === tab.key
                                            ? 'border-white bg-white text-black'
                                            : stepCompletion[tab.key]
                                              ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-300'
                                              : 'border-white/10 bg-white/[0.03] text-zinc-500'
                                    "
                                    @click="goToTab(tab.key)"
                                    :aria-label="tab.label"
                                >
                                    {{
                                        stepCompletion[tab.key]
                                            ? "✓"
                                            : index + 1
                                    }}
                                </button>
                            </div>
                        </div>

                        <!-- BODY -->

                        <div
                            class="thin-scrollbar flex-1 overflow-y-auto px-3 py-4 sm:px-6 sm:py-6"
                        >
                            <!-- BASIC -->

                            <div
                                v-if="activeTab === 'basic'"
                                class="grid gap-4 md:grid-cols-2 sm:gap-5"
                            >
                                <div class="hidden md:col-span-2 sm:block">
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
                                        class="mt-2 hidden text-sm leading-6 text-zinc-500 sm:block"
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
                                                Photo Manager
                                            </p>

                                            <h3
                                                class="mt-2 text-lg font-semibold text-white"
                                            >
                                                Manage photos in one grid
                                            </h3>

                                            <p
                                                class="mt-1 text-xs leading-5 text-zinc-500 sm:text-sm sm:leading-6"
                                            >
                                                Saved photos update immediately.
                                                New photos and new primary
                                                selection apply after Save
                                                Changes.
                                            </p>
                                        </div>

                                        <div class="flex shrink-0 gap-2">
                                            <button
                                                v-if="imagePreviews.length"
                                                type="button"
                                                class="rounded-2xl border border-red-400/20 bg-red-400/10 px-4 py-3 text-sm font-semibold text-red-300 transition hover:border-red-400/40 hover:bg-red-400/15"
                                                @click="clearNewImages"
                                            >
                                                Clear New
                                            </button>

                                            <label
                                                class="inline-flex items-center justify-center gap-2 rounded-2xl border px-4 py-3 text-sm font-semibold transition"
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
                                    </div>

                                    <div
                                        class="mt-4 grid grid-cols-4 gap-2 rounded-2xl border border-white/10 bg-[#050505] p-2 text-center"
                                    >
                                        <div
                                            class="rounded-xl bg-white/[0.03] px-2 py-2"
                                        >
                                            <p
                                                class="text-[10px] font-bold uppercase tracking-[0.14em] text-zinc-600"
                                            >
                                                Saved
                                            </p>

                                            <p
                                                class="mt-1 text-sm font-bold text-white"
                                            >
                                                {{ existingImages.length }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-xl bg-white/[0.03] px-2 py-2"
                                        >
                                            <p
                                                class="text-[10px] font-bold uppercase tracking-[0.14em] text-zinc-600"
                                            >
                                                New
                                            </p>

                                            <p
                                                class="mt-1 text-sm font-bold text-white"
                                            >
                                                {{ imagePreviews.length }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-xl bg-white/[0.03] px-2 py-2"
                                        >
                                            <p
                                                class="text-[10px] font-bold uppercase tracking-[0.14em] text-zinc-600"
                                            >
                                                Total
                                            </p>

                                            <p
                                                class="mt-1 text-sm font-bold text-white"
                                            >
                                                {{ totalImageCount }}/{{
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
                                    </div>

                                    <div
                                        class="mt-4 rounded-2xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3"
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
                                                All Photos
                                            </p>

                                            <p
                                                class="mt-1 text-xs leading-5 text-zinc-500"
                                            >
                                                New photos are marked as New.
                                                Saved photos are already stored.
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        v-if="photoCards.length"
                                        class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5"
                                    >
                                        <article
                                            v-for="photo in photoCards"
                                            :key="photo.key"
                                            class="overflow-hidden rounded-2xl border bg-[#050505]"
                                            :class="
                                                photo.isPrimary
                                                    ? 'border-emerald-400/60 ring-2 ring-emerald-400/15'
                                                    : photo.type === 'new'
                                                      ? 'border-emerald-400/20'
                                                      : 'border-white/10'
                                            "
                                        >
                                            <div class="relative">
                                                <img
                                                    :src="photo.url"
                                                    class="aspect-square w-full object-cover"
                                                    alt="Watch photo"
                                                />

                                                <div
                                                    class="absolute left-2 top-2 rounded-xl bg-black/70 px-2.5 py-1 text-[10px] font-bold text-white backdrop-blur"
                                                >
                                                    {{ photo.label }}
                                                </div>

                                                <div
                                                    v-if="photo.isPrimary"
                                                    class="absolute right-2 top-2 rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-emerald-300 backdrop-blur"
                                                >
                                                    Primary
                                                </div>

                                                <div
                                                    v-else-if="
                                                        photo.type === 'new'
                                                    "
                                                    class="absolute right-2 top-2 rounded-xl border border-white/10 bg-black/70 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-zinc-300 backdrop-blur"
                                                >
                                                    New
                                                </div>
                                            </div>

                                            <div class="space-y-2 p-2.5">
                                                <p
                                                    class="truncate text-[10px] text-zinc-500"
                                                >
                                                    {{ photo.sizeLabel }}
                                                </p>

                                                <div
                                                    class="grid grid-cols-2 gap-2"
                                                >
                                                    <button
                                                        type="button"
                                                        :disabled="
                                                            !canMovePhotoCard(
                                                                photo,

                                                                'left',
                                                            )
                                                        "
                                                        class="mn-photo-btn"
                                                        @click="
                                                            movePhotoCard(
                                                                photo,

                                                                'left',
                                                            )
                                                        "
                                                    >
                                                        ←
                                                    </button>

                                                    <button
                                                        type="button"
                                                        :disabled="
                                                            !canMovePhotoCard(
                                                                photo,

                                                                'right',
                                                            )
                                                        "
                                                        class="mn-photo-btn"
                                                        @click="
                                                            movePhotoCard(
                                                                photo,

                                                                'right',
                                                            )
                                                        "
                                                    >
                                                        →
                                                    </button>
                                                </div>

                                                <button
                                                    type="button"
                                                    :disabled="photo.isPrimary"
                                                    class="mn-photo-btn w-full"
                                                    :class="
                                                        photo.isPrimary
                                                            ? 'border-emerald-400/30 bg-emerald-400/10 text-emerald-300 opacity-100'
                                                            : ''
                                                    "
                                                    @click="
                                                        setPrimaryPhotoCard(
                                                            photo,
                                                        )
                                                    "
                                                >
                                                    {{
                                                        photo.isPrimary
                                                            ? "Primary"
                                                            : "Make Primary"
                                                    }}
                                                </button>

                                                <button
                                                    type="button"
                                                    class="w-full rounded-xl border border-red-500/20 px-3 py-2 text-xs font-semibold text-red-300 hover:bg-red-500/10"
                                                    @click="
                                                        removePhotoCard(photo)
                                                    "
                                                >
                                                    {{
                                                        photo.type === "new"
                                                            ? "Remove"
                                                            : "Delete"
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
                                            No photos found.
                                        </p>

                                        <p
                                            class="mt-2 text-sm leading-6 text-zinc-500"
                                        >
                                            Upload at least one photo before
                                            making this watch visible to
                                            customers.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER -->

                        <div
                            class="safe-bottom border-t border-white/10 bg-[#0B0B0D] px-3 py-3 sm:px-6 sm:py-5"
                        >
                            <div
                                v-if="submitFeedback"
                                class="mb-3 rounded-2xl border px-4 py-3 text-xs font-semibold leading-5"
                                :class="
                                    submitFeedback === 'Saving changes...'
                                        ? 'border-white/10 bg-white/[0.04] text-zinc-300'
                                        : 'border-red-400/20 bg-red-400/10 text-red-300'
                                "
                            >
                                {{ submitFeedback }}
                            </div>

                            <!-- MOBILE FOOTER -->
                            <div class="sm:hidden">
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <div class="min-w-0 text-xs leading-5">
                                        <p
                                            v-if="canSubmit"
                                            class="font-semibold text-emerald-300"
                                        >
                                            Ready to save changes.
                                        </p>

                                        <p
                                            v-else
                                            class="truncate font-semibold text-zinc-400"
                                        >
                                            Missing:
                                            <span class="text-red-300">
                                                {{
                                                    missingRequirements.join(
                                                        ", ",
                                                    )
                                                }}
                                            </span>
                                        </p>

                                        <p
                                            class="mt-0.5 text-[11px] text-zinc-600"
                                        >
                                            Step {{ currentTabIndex + 1 }} /
                                            {{ tabs.length }} •
                                            {{ currentTab.label }}
                                        </p>
                                    </div>

                                    <div
                                        class="shrink-0 rounded-full border border-white/10 bg-white/[0.03] px-2.5 py-1 text-[11px] font-bold text-zinc-400"
                                    >
                                        {{ completedStepCount }}/{{
                                            tabs.length
                                        }}
                                    </div>
                                </div>

                                <div class="mt-3 flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="h-12 min-w-[5.5rem] rounded-2xl border border-white/10 px-4 text-sm font-semibold text-zinc-300 transition active:scale-[0.98]"
                                        @click="
                                            activeTab === 'basic'
                                                ? closeModal()
                                                : goToPreviousTab()
                                        "
                                    >
                                        {{
                                            activeTab === "basic"
                                                ? "Close"
                                                : "Back"
                                        }}
                                    </button>

                                    <button
                                        type="button"
                                        class="h-12 flex-1 rounded-2xl border border-white/10 bg-white/[0.03] px-4 text-sm font-semibold text-zinc-200 transition active:scale-[0.98]"
                                        @click="openPublicPreview"
                                    >
                                        Preview
                                    </button>

                                    <button
                                        v-if="!isLastTab"
                                        type="button"
                                        :disabled="!currentStepComplete"
                                        class="ml-auto h-12 min-w-[6rem] rounded-2xl bg-white px-4 text-sm font-bold text-black transition active:scale-[0.98] disabled:cursor-not-allowed disabled:bg-zinc-700 disabled:text-zinc-400"
                                        @click="goToNextTab"
                                    >
                                        Next
                                    </button>

                                    <button
                                        v-else
                                        type="submit"
                                        :disabled="
                                            form.processing ||
                                            isCompressingImages
                                        "
                                        class="ml-auto h-12 min-w-[7.5rem] rounded-2xl bg-white px-4 text-sm font-bold text-black transition active:scale-[0.98] disabled:cursor-not-allowed disabled:bg-zinc-700 disabled:text-zinc-400"
                                    >
                                        {{
                                            isCompressingImages
                                                ? "Compressing..."
                                                : form.processing
                                                  ? "Saving..."
                                                  : "Save"
                                        }}
                                    </button>
                                </div>

                                <button
                                    v-if="!isLastTab"
                                    type="submit"
                                    :disabled="
                                        form.processing || isCompressingImages
                                    "
                                    class="mt-2 h-12 w-full rounded-2xl border border-white/10 bg-white/[0.03] px-4 text-sm font-semibold text-white transition active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-50"
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

                            <!-- DESKTOP FOOTER -->
                            <div
                                class="hidden flex-col gap-4 sm:flex lg:flex-row lg:items-center lg:justify-between"
                            >
                                <div class="text-xs leading-5">
                                    <p
                                        v-if="canSubmit"
                                        class="font-semibold text-emerald-300"
                                    >
                                        All required details are complete.
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
                                    class="flex items-center justify-end gap-3"
                                >
                                    <button
                                        type="button"
                                        class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30"
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
                                        v-if="!isLastTab"
                                        type="submit"
                                        :disabled="
                                            form.processing ||
                                            isCompressingImages
                                        "
                                        class="rounded-2xl border border-white/10 bg-white/[0.03] px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/[0.06] disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {{
                                            isCompressingImages
                                                ? "Compressing..."
                                                : form.processing
                                                  ? "Saving..."
                                                  : "Save Changes"
                                        }}
                                    </button>

                                    <button
                                        v-if="!isLastTab"
                                        type="button"
                                        :disabled="!currentStepComplete"
                                        class="rounded-2xl bg-white px-6 py-3 text-sm font-bold text-black transition hover:bg-zinc-200 disabled:cursor-not-allowed disabled:bg-zinc-700 disabled:text-zinc-400"
                                        @click="goToNextTab"
                                    >
                                        Next
                                    </button>

                                    <button
                                        v-else
                                        type="submit"
                                        :disabled="
                                            form.processing ||
                                            isCompressingImages
                                        "
                                        class="rounded-2xl bg-white px-6 py-3 text-sm font-bold text-black transition hover:bg-zinc-200 disabled:cursor-not-allowed disabled:bg-zinc-700 disabled:text-zinc-400"
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
                                        This preview reflects your current
                                        edits. Saved listing changes apply after
                                        Save Changes.
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

@media (max-width: 640px) {
    .mn-label {
        margin-bottom: 0.35rem;

        font-size: 0.62rem;

        letter-spacing: 0.14em;
    }

    .mn-input {
        border-radius: 0.9rem;

        padding: 0.78rem 0.9rem;

        font-size: 0.85rem;
    }

    .mn-toggle {
        border-radius: 0.9rem;

        padding: 0.78rem 0.9rem;

        font-size: 0.82rem;
    }

    .mn-preview-row {
        padding-bottom: 0.7rem;

        font-size: 0.8rem;
    }

    .mn-photo-btn {
        padding: 0.55rem 0.65rem;

        font-size: 0.7rem;
    }
}
</style>
