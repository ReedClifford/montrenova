<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import CreateWatchModal from "./CreateWatchModal.vue";
import EditWatchModal from "./EditWatchModal.vue";
import DeleteWatchModal from "./DeleteWatchModal.vue";
import MarkSoldModal from "./MarkSoldModal.vue";
import ReserveWatchModal from "./ReserveWatchModal.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from "vue";

const props = defineProps({
    watches: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
        }),
    },
    arrangeWatches: {
        type: Array,
        default: () => [],
    },
    warrantyWatches: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
        }),
    },
    filters: {
        type: Object,
        default: () => ({
            search: "",
            status: "",
        }),
    },
    summary: {
        type: Object,
        default: () => ({
            total_watches: 0,
            available_watches: 0,
            reserved_watches: 0,
            sold_watches: 0,
            draft_hidden_watches: 0,
            inventory_capital: 0,
            expected_sales_value: 0,
            expected_profit: 0,
        }),
    },
});

const activeTab = ref(
    typeof window !== "undefined"
        ? localStorage.getItem("watch_admin_active_tab") || "inventory"
        : "inventory",
);

const warrantyFilter = ref("all");

const search = ref(props.filters.search || "");
const status = ref(props.filters.status || "");
const actionFilter = ref("all");

const viewMode = ref(
    typeof window !== "undefined"
        ? localStorage.getItem("watch_stock_view") || "table"
        : "table",
);

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const showMarkSoldModal = ref(false);
const showReserveModal = ref(false);

const selectedWatch = ref(null);
const duplicateSource = ref(null);
const editModalKey = ref(0);

let searchDebounceTimer = null;

const isFiltering = ref(false);
const isSearchPending = ref(false);

const isArrangeMode = ref(false);
const arrangeItems = ref([]);
const isSavingOrder = ref(false);

const isBulkMode = ref(false);
const selectedWatchIds = ref([]);
const isBulkProcessing = ref(false);

const showMobileControls = ref(false);

const toggleMobileControls = () => {
    showMobileControls.value = !showMobileControls.value;
};

const closeMobileControls = () => {
    showMobileControls.value = false;
};

const clearSearchDebounce = () => {
    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = null;
    }
};

const applyServerFilters = ({
    debounce = 0,
    nextActionFilter = "all",
} = {}) => {
    clearSearchDebounce();

    const run = () => {
        isSearchPending.value = false;
        isFiltering.value = true;
        actionFilter.value = nextActionFilter;

        router.get(
            route("admin.watches.index"),
            {
                search: String(search.value || "").trim(),
                status: status.value,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: [
                    "watches",
                    "arrangeWatches",
                    "warrantyWatches",
                    "summary",
                    "filters",
                ],
                onFinish: () => {
                    isFiltering.value = false;
                },
                onCancel: () => {
                    isFiltering.value = false;
                },
                onError: () => {
                    isFiltering.value = false;
                },
            },
        );
    };

    if (debounce > 0) {
        isSearchPending.value = true;
        searchDebounceTimer = setTimeout(run, debounce);
        return;
    }

    run();
};

const peso = (value) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
};

const compactPeso = (value) => {
    const amount = Number(value || 0);

    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        notation: Math.abs(amount) >= 100000 ? "compact" : "standard",
        maximumFractionDigits: Math.abs(amount) >= 100000 ? 1 : 0,
    }).format(amount);
};

const formatDate = (value) => {
    if (!value) return "";

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

    if (Number.isNaN(date.getTime())) {
        return null;
    }

    date.setHours(0, 0, 0, 0);

    return date;
};

const todayDateOnly = () => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return today;
};

const diffDays = (startDate, endDate) => {
    if (!startDate || !endDate) return null;

    const diffMs = endDate.getTime() - startDate.getTime();
    const days = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    return Math.max(days, 0);
};

const rawDiffDays = (startDate, endDate) => {
    if (!startDate || !endDate) return null;

    const diffMs = endDate.getTime() - startDate.getTime();

    return Math.ceil(diffMs / (1000 * 60 * 60 * 24));
};

const addOneYearDate = (value) => {
    const date = parseDateOnly(value);

    if (!date) return null;

    date.setFullYear(date.getFullYear() + 1);

    return date;
};

const warrantyEndDate = (item) => {
    if (item.warranty_end_date) {
        return item.warranty_end_date;
    }

    const endDate = addOneYearDate(item.date_sold);

    if (!endDate) return null;

    return endDate.toISOString().slice(0, 10);
};

const warrantyDaysLeft = (item) => {
    if (
        item.warranty_days_left !== null &&
        item.warranty_days_left !== undefined
    ) {
        return Number(item.warranty_days_left);
    }

    const endDate = parseDateOnly(warrantyEndDate(item));

    if (!endDate) return null;

    return rawDiffDays(todayDateOnly(), endDate);
};

const warrantyStatus = (item) => {
    if (item.warranty_status) {
        return item.warranty_status;
    }

    const daysLeft = warrantyDaysLeft(item);

    if (daysLeft === null) return "unknown";
    if (daysLeft < 0) return "expired";
    if (daysLeft <= 30) return "expiring_soon";

    return "active";
};

const warrantyStatusLabel = (item) => {
    const currentStatus = warrantyStatus(item);

    if (currentStatus === "active") return "Active";
    if (currentStatus === "expiring_soon") return "Expiring Soon";
    if (currentStatus === "expired") return "Expired";

    return "No Date";
};

const warrantyStatusClass = (item) => {
    const currentStatus = warrantyStatus(item);

    if (currentStatus === "active") {
        return "border-emerald-500/20 bg-emerald-500/10 text-emerald-300";
    }

    if (currentStatus === "expiring_soon") {
        return "border-amber-500/20 bg-amber-500/10 text-amber-300";
    }

    if (currentStatus === "expired") {
        return "border-red-500/20 bg-red-500/10 text-red-300";
    }

    return "border-white/10 bg-white/[0.03] text-zinc-400";
};

const warrantyDaysLabel = (item) => {
    const daysLeft = warrantyDaysLeft(item);

    if (daysLeft === null) return "No warranty date";
    if (daysLeft < 0) return "Warranty expired";
    if (daysLeft === 0) return "Expires today";
    if (daysLeft === 1) return "1 day left";

    return `${daysLeft} days left`;
};

const warrantyProgress = (item) => {
    const soldDate = parseDateOnly(item.date_sold);
    const endDate = parseDateOnly(warrantyEndDate(item));

    if (!soldDate || !endDate) return 0;

    const totalDays = rawDiffDays(soldDate, endDate);
    const remainingDays = warrantyDaysLeft(item);

    if (!totalDays || totalDays <= 0) return 0;
    if (remainingDays <= 0) return 100;

    const usedDays = totalDays - remainingDays;

    return Math.min(Math.max((usedDays / totalDays) * 100, 0), 100);
};

const warrantyProgressClass = (item) => {
    const currentStatus = warrantyStatus(item);

    if (currentStatus === "active") return "bg-emerald-300";
    if (currentStatus === "expiring_soon") return "bg-amber-300";
    if (currentStatus === "expired") return "bg-red-300";

    return "bg-zinc-500";
};

const warrantyHelperText = (item) => {
    const currentStatus = warrantyStatus(item);

    if (currentStatus === "active") {
        return "Covered under Montre Nova warranty.";
    }

    if (currentStatus === "expiring_soon") {
        return "Warranty is close to expiry. Good to monitor.";
    }

    if (currentStatus === "expired") {
        return "Warranty coverage has ended.";
    }

    return "Warranty date is incomplete.";
};

const daysToSold = (watch) => {
    if (watch.status !== "sold" || !watch.created_at || !watch.date_sold) {
        return null;
    }

    const encodedDate = parseDateOnly(watch.created_at);
    const soldDate = parseDateOnly(watch.date_sold);

    return diffDays(encodedDate, soldDate);
};

const daysToSoldLabel = (watch) => {
    const days = daysToSold(watch);

    if (days === null) return "Not sold yet";
    if (days === 0) return "Same day";
    if (days === 1) return "1 day";

    return `${days} days`;
};

const daysInStock = (watch) => {
    if (watch.status === "sold" || !watch.created_at) {
        return null;
    }

    const encodedDate = parseDateOnly(watch.created_at);

    return diffDays(encodedDate, todayDateOnly());
};

const stockAgeLabel = (watch) => {
    const days = daysInStock(watch);

    if (watch.status === "sold") {
        const label = daysToSoldLabel(watch);

        return label === "Same day" ? "Sold same day" : `Sold in ${label}`;
    }

    if (days === null) return "No encoded date";
    if (days === 0) return "Encoded today";
    if (days === 1) return "1 day in stock";

    return `${days} days in stock`;
};

const stockAgeStage = (watch) => {
    if (watch.status === "sold") return "Sold";

    const days = daysInStock(watch);

    if (days === null) return "No Date";
    if (days <= 7) return "New Stock";
    if (days <= 30) return "Normal";
    if (days <= 60) return "Slow Moving";

    return "Dead Stock";
};

const stockAgeClass = (watch) => {
    if (watch.status === "sold") {
        return "border-emerald-500/20 bg-emerald-500/10 text-emerald-300";
    }

    const days = daysInStock(watch);

    if (days === null) {
        return "border-white/10 bg-white/[0.03] text-zinc-500";
    }

    if (days <= 7) {
        return "border-sky-500/20 bg-sky-500/10 text-sky-300";
    }

    if (days <= 30) {
        return "border-emerald-500/20 bg-emerald-500/10 text-emerald-300";
    }

    if (days <= 60) {
        return "border-amber-500/20 bg-amber-500/10 text-amber-300";
    }

    return "border-red-500/20 bg-red-500/10 text-red-300";
};

const listedPrice = (watch) => {
    return Number(watch.discounted_price || 0) > 0
        ? Number(watch.discounted_price)
        : Number(watch.selling_price || 0);
};

const soldPrice = (watch) => {
    if (Number(watch.sold_price || 0) > 0) {
        return Number(watch.sold_price);
    }

    if (Number(watch.discounted_price || 0) > 0) {
        return Number(watch.discounted_price);
    }

    return Number(watch.selling_price || 0);
};

const displayPrice = (watch) => {
    return watch.status === "sold" ? soldPrice(watch) : listedPrice(watch);
};

const expectedProfit = (watch) => {
    return listedPrice(watch) - Number(watch.capital_price || 0);
};

const actualProfit = (watch) => {
    return soldPrice(watch) - Number(watch.capital_price || 0);
};

const displayProfit = (watch) => {
    return watch.status === "sold"
        ? actualProfit(watch)
        : expectedProfit(watch);
};

const displayProfitLabel = (watch) => {
    return watch.status === "sold" ? "Actual Profit" : "Expected Profit";
};

const profitMargin = (watch) => {
    const price = displayPrice(watch);

    if (price <= 0) return 0;

    return (displayProfit(watch) / price) * 100;
};

const isSlowMoving = (watch) => {
    const days = daysInStock(watch);

    return watch.status !== "sold" && days !== null && days >= 31;
};

const hasNoPhoto = (watch) => {
    return !watch.primary_image;
};

const isLowMargin = (watch) => {
    if (watch.status === "sold") return false;

    return displayProfit(watch) <= 0 || profitMargin(watch) < 10;
};

const isReadyToPost = (watch) => {
    return (
        watch.status === "available" &&
        watch.is_visible &&
        Boolean(watch.primary_image)
    );
};

const isReservationOverdue = (watch) => {
    if (watch.status !== "reserved" || !watch.reservation_deadline) {
        return false;
    }

    const today = todayDateOnly();
    const deadline = parseDateOnly(watch.reservation_deadline);

    return deadline && deadline < today;
};

const recommendedAction = (watch) => {
    if (watch.status === "sold") {
        return {
            label: "Completed Sale",
            helper: stockAgeLabel(watch),
            className:
                "border-emerald-500/20 bg-emerald-500/10 text-emerald-300",
        };
    }

    if (isReservationOverdue(watch)) {
        return {
            label: "Follow Up Buyer",
            helper: "Reservation deadline has passed",
            className: "border-red-500/20 bg-red-500/10 text-red-300",
        };
    }

    if (hasNoPhoto(watch)) {
        return {
            label: "Upload Photo",
            helper: "Photo needed before posting",
            className: "border-red-500/20 bg-red-500/10 text-red-300",
        };
    }

    if (isLowMargin(watch)) {
        return {
            label: "Review Price",
            helper: "Low or negative margin",
            className: "border-red-500/20 bg-red-500/10 text-red-300",
        };
    }

    if (isSlowMoving(watch)) {
        return {
            label: "Repost / Discount",
            helper: stockAgeLabel(watch),
            className: "border-amber-500/20 bg-amber-500/10 text-amber-300",
        };
    }

    if (isReadyToPost(watch)) {
        return {
            label: "Ready to Post",
            helper: "Available, visible, with photo",
            className:
                "border-emerald-500/20 bg-emerald-500/10 text-emerald-300",
        };
    }

    return {
        label: "Monitor",
        helper: stockAgeLabel(watch),
        className: "border-white/10 bg-white/[0.03] text-zinc-400",
    };
};

const profitBadgeClass = (watch) => {
    const margin = profitMargin(watch);
    const profit = displayProfit(watch);

    if (profit <= 0) {
        return "border-red-500/20 bg-red-500/10 text-red-300";
    }

    if (margin >= 20) {
        return "border-emerald-500/20 bg-emerald-500/10 text-emerald-300";
    }

    if (margin >= 10) {
        return "border-amber-500/20 bg-amber-500/10 text-amber-300";
    }

    return "border-red-500/20 bg-red-500/10 text-red-300";
};

const profitBadgeLabel = (watch) => {
    if (displayProfit(watch) <= 0) return "No Profit";
    if (profitMargin(watch) >= 20) return "High Profit";
    if (profitMargin(watch) >= 10) return "Healthy";

    return "Low Margin";
};

const timelineClass = (watch) => {
    return stockAgeClass(watch);
};

const statusClass = (value) => {
    const classes = {
        available: "border-emerald-500/20 bg-emerald-500/10 text-emerald-300",
        reserved: "border-amber-500/20 bg-amber-500/10 text-amber-300",
        sold: "border-zinc-500/20 bg-zinc-500/10 text-zinc-300",
        hidden: "border-red-500/20 bg-red-500/10 text-red-300",
        draft: "border-white/10 bg-white/[0.05] text-zinc-400",
    };

    return classes[value] || classes.draft;
};

const visibilityClass = (watch) => {
    if (watch.status === "sold") {
        return "border-zinc-500/20 bg-zinc-500/10 text-zinc-400";
    }

    return watch.is_visible
        ? "border-emerald-500/20 bg-emerald-500/10 text-emerald-300"
        : "border-red-500/20 bg-red-500/10 text-red-300";
};

const currentPageWatches = computed(() => props.watches?.data || []);

const visibleWatchesCount = computed(() => {
    return currentPageWatches.value.filter(
        (watch) => watch.is_visible && watch.status !== "sold",
    ).length;
});

const watchActionStats = computed(() => {
    const stats = {
        visible: 0,
        needsPush: 0,
        noPhoto: 0,
        lowMargin: 0,
        reservationOverdue: 0,
        readyToPost: 0,
    };

    currentPageWatches.value.forEach((watch) => {
        if (watch.is_visible && watch.status !== "sold") stats.visible += 1;
        if (isSlowMoving(watch)) stats.needsPush += 1;
        if (hasNoPhoto(watch)) stats.noPhoto += 1;
        if (isLowMargin(watch)) stats.lowMargin += 1;
        if (isReservationOverdue(watch)) stats.reservationOverdue += 1;
        if (isReadyToPost(watch)) stats.readyToPost += 1;
    });

    return stats;
});

const warrantyRecords = computed(() => props.warrantyWatches?.data || []);

const warrantyActiveCount = computed(() => {
    return warrantyRecords.value.filter(
        (item) => warrantyStatus(item) === "active",
    ).length;
});

const warrantyExpiringSoonCount = computed(() => {
    return warrantyRecords.value.filter(
        (item) => warrantyStatus(item) === "expiring_soon",
    ).length;
});

const warrantyExpiredCount = computed(() => {
    return warrantyRecords.value.filter(
        (item) => warrantyStatus(item) === "expired",
    ).length;
});

const warrantyFilteredRecords = computed(() => {
    if (warrantyFilter.value === "all") {
        return warrantyRecords.value;
    }

    return warrantyRecords.value.filter(
        (item) => warrantyStatus(item) === warrantyFilter.value,
    );
});

const actionFilterMatches = (watch) => {
    if (actionFilter.value === "all") return true;
    if (actionFilter.value === "visible") {
        return watch.is_visible && watch.status !== "sold";
    }
    if (actionFilter.value === "needs_push") return isSlowMoving(watch);
    if (actionFilter.value === "no_photo") return hasNoPhoto(watch);
    if (actionFilter.value === "low_margin") return isLowMargin(watch);
    if (actionFilter.value === "reservation_overdue") {
        return isReservationOverdue(watch);
    }
    if (actionFilter.value === "ready_to_post") return isReadyToPost(watch);

    return true;
};

const displayedWatches = computed(() => {
    return currentPageWatches.value.filter((watch) =>
        actionFilterMatches(watch),
    );
});

const displayedWatchIds = computed(() => {
    return displayedWatches.value.map((watch) => watch.id);
});

const selectedBulkCount = computed(() => selectedWatchIds.value.length);

const selectedBulkWatches = computed(() => {
    const selectedIds = new Set(selectedWatchIds.value);

    return currentPageWatches.value.filter((watch) =>
        selectedIds.has(watch.id),
    );
});

const allDisplayedSelected = computed(() => {
    return (
        displayedWatchIds.value.length > 0 &&
        displayedWatchIds.value.every((id) =>
            selectedWatchIds.value.includes(id),
        )
    );
});

const hasDisplayedSelection = computed(() => {
    return displayedWatchIds.value.some((id) =>
        selectedWatchIds.value.includes(id),
    );
});

const bulkActionOptions = computed(() => [
    {
        value: "make_visible",
        label: "Set Visible",
        helper: "Show selected watches on the website",
        className: "border-emerald-500/20 bg-emerald-500/10 text-emerald-300",
        confirm: "Make the selected watches visible on the website?",
    },
    {
        value: "hide",
        label: "Hide",
        helper: "Hide selected watches from the website",
        className: "border-amber-500/20 bg-amber-500/10 text-amber-300",
        confirm: "Hide the selected watches from the website?",
    },
    {
        value: "mark_available",
        label: "Set Available",
        helper: "Mark selected watches as available and visible",
        className: "border-sky-500/20 bg-sky-500/10 text-sky-300",
        confirm: "Set the selected watches as Available and Visible?",
    },
    {
        value: "mark_draft",
        label: "Move to Draft",
        helper: "Set selected watches as draft and hidden",
        className: "border-white/10 bg-white/[0.04] text-zinc-300",
        confirm:
            "Move the selected watches to Draft and hide them from the website?",
    },
    {
        value: "feature",
        label: "Feature",
        helper: "Mark selected watches as featured",
        className: "border-white/10 bg-white/[0.04] text-white",
        confirm: "Feature the selected watches?",
    },
    {
        value: "unfeature",
        label: "Unfeature",
        helper: "Remove featured flag from selected watches",
        className: "border-white/10 bg-white/[0.04] text-zinc-300",
        confirm: "Remove featured status from the selected watches?",
    },
    {
        value: "delete",
        label: "Delete",
        helper: "Delete selected watches",
        className: "border-red-500/20 bg-red-500/10 text-red-300",
        confirm: "Delete the selected watches? This action cannot be undone.",
    },
]);

const isWatchSelected = (watch) => {
    return selectedWatchIds.value.includes(watch.id);
};

const startBulkMode = () => {
    activeTab.value = "inventory";
    closeMobileControls();
    cancelArrangeMode();
    isBulkMode.value = true;
};

const cancelBulkMode = () => {
    isBulkMode.value = false;
    selectedWatchIds.value = [];
    isBulkProcessing.value = false;
};

const toggleBulkMode = () => {
    if (isBulkMode.value) {
        cancelBulkMode();
        return;
    }

    startBulkMode();
};

const toggleWatchSelection = (watch) => {
    if (!watch?.id) return;

    if (!isBulkMode.value) {
        isBulkMode.value = true;
    }

    if (selectedWatchIds.value.includes(watch.id)) {
        selectedWatchIds.value = selectedWatchIds.value.filter(
            (id) => id !== watch.id,
        );
        return;
    }

    selectedWatchIds.value = [...selectedWatchIds.value, watch.id];
};

const expandedMobileWatchIds = ref([]);
const openMobileActionWatchId = ref(null);

const isMobileDetailsOpen = (watch) => {
    return expandedMobileWatchIds.value.includes(watch.id);
};

const toggleMobileDetails = (watch) => {
    if (!watch?.id) return;

    if (expandedMobileWatchIds.value.includes(watch.id)) {
        expandedMobileWatchIds.value = expandedMobileWatchIds.value.filter(
            (id) => id !== watch.id,
        );
        return;
    }

    expandedMobileWatchIds.value = [...expandedMobileWatchIds.value, watch.id];
};

const isMobileActionsOpen = (watch) => {
    return openMobileActionWatchId.value === watch.id;
};

const toggleMobileActions = (watch) => {
    if (!watch?.id) return;

    openMobileActionWatchId.value =
        openMobileActionWatchId.value === watch.id ? null : watch.id;
};

const closeMobileActions = () => {
    openMobileActionWatchId.value = null;
};

const mobileWarningBadges = (watch) => {
    const badges = [];

    if (hasNoPhoto(watch)) {
        badges.push({
            label: "No Photo",
            className: "border-red-500/20 bg-red-500/10 text-red-300",
        });
    }

    if (isLowMargin(watch)) {
        badges.push({
            label: "Low Margin",
            className: "border-red-500/20 bg-red-500/10 text-red-300",
        });
    }

    if (isReservationOverdue(watch)) {
        badges.push({
            label: "Overdue",
            className: "border-red-500/20 bg-red-500/10 text-red-300",
        });
    }

    if (!watch.is_visible && watch.status !== "sold") {
        badges.push({
            label: "Hidden",
            className: "border-zinc-500/20 bg-zinc-500/10 text-zinc-300",
        });
    }

    if (isReadyToPost(watch)) {
        badges.push({
            label: "Ready",
            className:
                "border-emerald-500/20 bg-emerald-500/10 text-emerald-300",
        });
    }

    return badges.slice(0, 3);
};

const toggleSelectDisplayedWatches = () => {
    if (!displayedWatchIds.value.length) return;

    if (allDisplayedSelected.value) {
        selectedWatchIds.value = selectedWatchIds.value.filter(
            (id) => !displayedWatchIds.value.includes(id),
        );
        return;
    }

    const selectedIds = new Set(selectedWatchIds.value);

    displayedWatchIds.value.forEach((id) => selectedIds.add(id));

    selectedWatchIds.value = Array.from(selectedIds);
};

const runBulkAction = (action) => {
    if (!selectedWatchIds.value.length || isBulkProcessing.value) return;

    const option = bulkActionOptions.value.find(
        (item) => item.value === action,
    );
    const actionLabel = option?.label || "Apply action";
    const message =
        option?.confirm || `Apply ${actionLabel} to selected watches?`;

    if (
        !confirm(
            `${message}\n\nSelected watches: ${selectedWatchIds.value.length}`,
        )
    ) {
        return;
    }

    isBulkProcessing.value = true;

    router.post(
        route("admin.watches.bulk-action"),
        {
            watch_ids: selectedWatchIds.value,
            action,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                cancelBulkMode();

                router.reload({
                    only: [
                        "watches",
                        "arrangeWatches",
                        "warrantyWatches",
                        "summary",
                    ],
                    preserveScroll: true,
                    preserveState: false,
                });
            },
            onFinish: () => {
                isBulkProcessing.value = false;
            },
            onError: () => {
                isBulkProcessing.value = false;
            },
        },
    );
};

const setActionFilter = (value) => {
    if (
        value === actionFilter.value &&
        !(value === "visible" && status.value !== "")
    ) {
        return;
    }

    if (value === "visible" && status.value !== "") {
        status.value = "";

        applyServerFilters({
            nextActionFilter: "visible",
        });

        return;
    }

    actionFilter.value = value;
};

const arrangeSourceWatches = computed(() => {
    return (props.arrangeWatches || []).filter((watch) => {
        return watch.status === "available" && Boolean(watch.is_visible);
    });
});

const canArrangeWatches = computed(() => {
    return arrangeSourceWatches.value.length > 1;
});

const hasArrangeChanges = computed(() => {
    const sourceIds = arrangeSourceWatches.value.map((watch) => watch.id);
    const arrangedIds = arrangeItems.value.map((watch) => watch.id);

    if (sourceIds.length !== arrangedIds.length) return true;

    return sourceIds.some((id, index) => id !== arrangedIds[index]);
});

const enterArrangeMode = () => {
    clearSearchDebounce();
    closeMobileControls();
    cancelBulkMode();

    activeTab.value = "inventory";

    if (typeof window !== "undefined") {
        localStorage.setItem("watch_admin_active_tab", "inventory");
    }

    isArrangeMode.value = true;
    arrangeItems.value = arrangeSourceWatches.value.map((watch) => ({
        ...watch,
    }));
};

const cancelArrangeMode = () => {
    isArrangeMode.value = false;
    arrangeItems.value = [];
    isSavingOrder.value = false;
};

const moveArrangeItem = (index, direction) => {
    const targetIndex = direction === "up" ? index - 1 : index + 1;

    if (targetIndex < 0 || targetIndex >= arrangeItems.value.length) return;

    const items = [...arrangeItems.value];
    const currentItem = items[index];

    items[index] = items[targetIndex];
    items[targetIndex] = currentItem;

    arrangeItems.value = items;
};

const moveArrangeItemToTop = (index) => {
    if (index <= 0) return;

    const items = [...arrangeItems.value];
    const selected = items.splice(index, 1)[0];

    items.unshift(selected);

    arrangeItems.value = items;
};

const saveArrangeOrder = () => {
    if (!arrangeItems.value.length || isSavingOrder.value) return;

    isSavingOrder.value = true;

    router.patch(
        route("admin.watches.reorder"),
        {
            watch_ids: arrangeItems.value.map((watch) => watch.id),
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                cancelArrangeMode();

                router.reload({
                    only: [
                        "watches",
                        "arrangeWatches",
                        "warrantyWatches",
                        "summary",
                    ],
                    preserveScroll: true,
                });
            },
            onFinish: () => {
                isSavingOrder.value = false;
            },
            onError: () => {
                isSavingOrder.value = false;
            },
        },
    );
};

const inventoryCards = computed(() => [
    {
        label: "Total",
        value: props.summary.total_watches,
        helper: "All stocks",
        valueClass: "text-white",
    },
    {
        label: "Available",
        value: props.summary.available_watches,
        helper: "Ready to sell",
        valueClass: "text-emerald-300",
    },
    {
        label: "Reserved",
        value: props.summary.reserved_watches,
        helper: "Pending buyers",
        valueClass: "text-amber-300",
    },
    {
        label: "Sold",
        value: props.summary.sold_watches,
        helper: "Completed sales",
        valueClass: "text-zinc-300",
    },
]);

const moneyCards = computed(() => [
    {
        label: "Inventory Capital",
        value: peso(props.summary.inventory_capital),
        compactValue: compactPeso(props.summary.inventory_capital),
        helper: "Capital tied to unsold watches",
        valueClass: "text-white",
    },
    {
        label: "Expected Sales",
        value: peso(props.summary.expected_sales_value),
        compactValue: compactPeso(props.summary.expected_sales_value),
        helper: "Estimated sales value",
        valueClass: "text-white",
    },
    {
        label: "Expected Profit",
        value: peso(props.summary.expected_profit),
        compactValue: compactPeso(props.summary.expected_profit),
        helper: "Sales minus capital",
        valueClass:
            Number(props.summary.expected_profit || 0) >= 0
                ? "text-emerald-300"
                : "text-red-300",
    },
    {
        label: "Draft / Hidden",
        value: props.summary.draft_hidden_watches,
        compactValue: props.summary.draft_hidden_watches,
        helper: "Not publicly active",
        valueClass: "text-zinc-300",
    },
]);

const warrantyCards = computed(() => [
    {
        label: "Warranty Records",
        value: warrantyRecords.value.length,
        helper: "Current loaded warranty page",
        valueClass: "text-white",
    },
    {
        label: "Active",
        value: warrantyActiveCount.value,
        helper: "Still covered",
        valueClass: "text-emerald-300",
    },
    {
        label: "Expiring Soon",
        value: warrantyExpiringSoonCount.value,
        helper: "30 days or less",
        valueClass: "text-amber-300",
    },
    {
        label: "Expired",
        value: warrantyExpiredCount.value,
        helper: "Past warranty date",
        valueClass: "text-red-300",
    },
]);

const warrantyFilterTabs = computed(() => [
    {
        label: "All",
        value: "all",
        count: warrantyRecords.value.length,
    },
    {
        label: "Active",
        value: "active",
        count: warrantyActiveCount.value,
    },
    {
        label: "Expiring",
        value: "expiring_soon",
        count: warrantyExpiringSoonCount.value,
    },
    {
        label: "Expired",
        value: "expired",
        count: warrantyExpiredCount.value,
    },
]);

const actionCards = computed(() => [
    {
        label: "Needs Push",
        value: watchActionStats.value.needsPush,
        helper: "Slow moving or dead stock",
        filter: "needs_push",
        className: "border-amber-500/20 bg-amber-500/10 text-amber-300",
    },
    {
        label: "No Photo",
        value: watchActionStats.value.noPhoto,
        helper: "Needs product images",
        filter: "no_photo",
        className: "border-red-500/20 bg-red-500/10 text-red-300",
    },
    {
        label: "Low Margin",
        value: watchActionStats.value.lowMargin,
        helper: "Review price or cost",
        filter: "low_margin",
        className: "border-red-500/20 bg-red-500/10 text-red-300",
    },
    {
        label: "Ready to Post",
        value: watchActionStats.value.readyToPost,
        helper: "Available with photos",
        filter: "ready_to_post",
        className: "border-emerald-500/20 bg-emerald-500/10 text-emerald-300",
    },
]);

const quickActionFilters = computed(() => [
    {
        label: "All",
        value: "all",
        count: currentPageWatches.value.length,
    },
    {
        label: "Visible",
        value: "visible",
        count: watchActionStats.value.visible,
    },
    {
        label: "Needs Push",
        value: "needs_push",
        count: watchActionStats.value.needsPush,
    },
    {
        label: "No Photo",
        value: "no_photo",
        count: watchActionStats.value.noPhoto,
    },
    {
        label: "Low Margin",
        value: "low_margin",
        count: watchActionStats.value.lowMargin,
    },
    {
        label: "Overdue",
        value: "reservation_overdue",
        count: watchActionStats.value.reservationOverdue,
    },
    {
        label: "Ready",
        value: "ready_to_post",
        count: watchActionStats.value.readyToPost,
    },
]);

const statusTabs = computed(() => [
    { label: "All", value: "", count: props.summary.total_watches },
    {
        label: "Available",
        value: "available",
        count: props.summary.available_watches,
    },
    {
        label: "Reserved",
        value: "reserved",
        count: props.summary.reserved_watches,
    },
    { label: "Sold", value: "sold", count: props.summary.sold_watches },
    { label: "Draft", value: "draft", count: null },
    { label: "Hidden", value: "hidden", count: null },
]);

const selectedStatusLabel = computed(() => {
    return (
        statusTabs.value.find((tab) => tab.value === status.value)?.label ||
        "All"
    );
});

const selectedActionFilterLabel = computed(() => {
    return (
        quickActionFilters.value.find(
            (filter) => filter.value === actionFilter.value,
        )?.label || "All"
    );
});

const hasActiveFilters = computed(() => {
    return (
        String(search.value || "").trim() !== "" ||
        status.value !== "" ||
        actionFilter.value !== "all"
    );
});

const filterStateLabel = computed(() => {
    if (isSearchPending.value) return "Typing… applying search shortly";
    if (isFiltering.value) return "Applying filters…";
    if (!hasActiveFilters.value) return "Showing all loaded watches";

    const parts = [];

    if (String(search.value || "").trim()) {
        parts.push(`Search: ${String(search.value).trim()}`);
    }

    if (selectedStatusLabel.value !== "All") {
        parts.push(`Status: ${selectedStatusLabel.value}`);
    }

    if (selectedActionFilterLabel.value !== "All") {
        parts.push(`Quick: ${selectedActionFilterLabel.value}`);
    }

    return parts.join(" • ");
});

onMounted(() => {
    const params = new URLSearchParams(window.location.search);

    if (params.get("create") === "1") {
        showCreateModal.value = true;
    }
});

watch(search, () => {
    applyServerFilters({
        debounce: 500,
        nextActionFilter: "all",
    });
});

onBeforeUnmount(() => {
    clearSearchDebounce();
});

const setStatusFilter = (value) => {
    if (status.value === value && actionFilter.value === "all") return;

    status.value = value;

    applyServerFilters({
        nextActionFilter: "all",
    });
};

const setActiveTab = (tab) => {
    activeTab.value = tab;

    if (typeof window !== "undefined") {
        localStorage.setItem("watch_admin_active_tab", tab);
    }

    if (tab === "warranty") {
        actionFilter.value = "all";
        cancelArrangeMode();
        cancelBulkMode();
    }
};

const setViewMode = (mode) => {
    viewMode.value = mode;

    if (typeof window !== "undefined") {
        localStorage.setItem("watch_stock_view", mode);
    }
};

const openCreateModal = () => {
    closeMobileControls();
    duplicateSource.value = null;
    showCreateModal.value = true;
};

const openDuplicateModal = (watch) => {
    if (!watch) return;

    closeMobileControls();
    cancelArrangeMode();
    activeTab.value = "inventory";
    duplicateSource.value = watch;
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    duplicateSource.value = null;

    router.reload({
        only: ["watches", "arrangeWatches", "warrantyWatches", "summary"],
        preserveScroll: true,
    });
};

const cloneWatchForEdit = (watch) => {
    const images =
        Array.isArray(watch.images) && watch.images.length
            ? watch.images
            : watch.primary_image
              ? [
                    {
                        ...watch.primary_image,
                        is_primary: true,
                    },
                ]
              : [];

    return {
        ...watch,
        primary_image: watch.primary_image ? { ...watch.primary_image } : null,
        images: images.map((image) => ({ ...image })),
        sections: Array.isArray(watch.sections)
            ? watch.sections.map((section) => ({ ...section }))
            : [],
    };
};

const openEditModal = async (watch) => {
    if (!watch?.id) return;

    closeMobileControls();
    closeMobileActions();
    cancelArrangeMode();

    const fullWatch =
        currentPageWatches.value.find((item) => item.id === watch.id) || watch;

    /*
    |--------------------------------------------------------------------------
    | Force a fresh edit modal instance
    |--------------------------------------------------------------------------
    | This prevents Vue from reusing an old modal instance and prevents the edit
    | form from opening with blank/default data after mobile card updates.
    */
    showEditModal.value = false;
    selectedWatch.value = null;

    await nextTick();

    selectedWatch.value = cloneWatchForEdit(fullWatch);
    editModalKey.value += 1;
    showEditModal.value = true;
};

const closeEditModal = async (payload = {}) => {
    /*
    |--------------------------------------------------------------------------
    | Parent owns the modal state
    |--------------------------------------------------------------------------
    | Close and destroy the modal first. Reload only after a successful save so
    | the modal does not reopen with stale props or blank values.
    */
    showEditModal.value = false;
    selectedWatch.value = null;

    await nextTick();

    if (payload?.saved) {
        window.setTimeout(() => {
            router.reload({
                only: [
                    "watches",
                    "arrangeWatches",
                    "warrantyWatches",
                    "summary",
                ],
                preserveScroll: true,
                preserveState: false,
            });
        }, 100);
    }
};

const openDeleteModal = (watch) => {
    selectedWatch.value = watch;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedWatch.value = null;

    router.reload({
        only: ["watches", "arrangeWatches", "warrantyWatches", "summary"],
        preserveScroll: true,
    });
};

const openMarkSoldModal = (watch) => {
    selectedWatch.value = watch;
    showMarkSoldModal.value = true;
};

const closeMarkSoldModal = () => {
    showMarkSoldModal.value = false;
    selectedWatch.value = null;

    router.reload({
        only: ["watches", "arrangeWatches", "warrantyWatches", "summary"],
        preserveScroll: true,
    });
};

const openReserveModal = (watch) => {
    selectedWatch.value = watch;
    showReserveModal.value = true;
};

const closeReserveModal = () => {
    showReserveModal.value = false;
    selectedWatch.value = null;

    router.reload({
        only: ["watches", "arrangeWatches", "warrantyWatches", "summary"],
        preserveScroll: true,
    });
};

const clearReservation = (watch) => {
    if (!confirm(`Clear reservation for ${watch.brand} ${watch.model_name}?`)) {
        return;
    }

    router.patch(
        route("admin.watches.clear-reservation", watch.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({
                    only: [
                        "watches",
                        "arrangeWatches",
                        "warrantyWatches",
                        "summary",
                    ],
                    preserveScroll: true,
                });
            },
        },
    );
};
</script>

<template>
    <Head title="Watch Stocks | Montre Nova" />

    <AuthenticatedLayout title="Watch Stocks">
        <div class="space-y-5 pb-28 sm:space-y-7 md:pb-0">
            <!-- HEADER -->
            <section
                class="relative overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5 shadow-2xl shadow-black/30 sm:rounded-[2rem] sm:p-8"
            >
                <div class="pointer-events-none absolute inset-0">
                    <div
                        class="absolute right-[-12rem] top-[-12rem] h-[30rem] w-[30rem] rounded-full bg-white/[0.04] blur-3xl"
                    ></div>
                </div>

                <div
                    class="relative flex flex-col justify-between gap-6 lg:flex-row lg:items-end"
                >
                    <div>
                        <p
                            class="text-xs uppercase tracking-[0.28em] text-zinc-600"
                        >
                            Inventory Command Center
                        </p>

                        <h2
                            class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-5xl"
                        >
                            Watch Stocks
                        </h2>

                        <p
                            class="mt-4 max-w-2xl text-sm leading-7 text-zinc-400"
                        >
                            Manage pricing, photos, reservations, aging, profit,
                            sales conversion, and warranty records in one
                            dashboard.
                        </p>
                    </div>

                    <div
                        v-if="activeTab === 'inventory'"
                        class="hidden items-center gap-3 sm:flex"
                    >
                        <button
                            type="button"
                            class="rounded-2xl border border-white/10 bg-white/[0.03] px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/[0.06]"
                            @click="enterArrangeMode"
                        >
                            Arrange Display
                        </button>

                        <button
                            type="button"
                            class="rounded-2xl border border-white/10 bg-white/[0.03] px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/[0.06]"
                            :class="
                                isBulkMode
                                    ? 'border-white/30 bg-white/[0.08]'
                                    : ''
                            "
                            @click="toggleBulkMode"
                        >
                            {{ isBulkMode ? "Exit Select" : "Bulk Select" }}
                        </button>

                        <button
                            type="button"
                            class="rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200"
                            @click="openCreateModal"
                        >
                            Add Watch
                        </button>
                    </div>
                </div>
            </section>

            <!-- MAIN TABS -->
            <section
                class="sticky top-3 z-30 hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D]/95 p-3 shadow-2xl shadow-black/30 backdrop-blur md:block"
            >
                <div class="grid gap-2 sm:grid-cols-2">
                    <button
                        type="button"
                        class="rounded-[1.2rem] px-5 py-4 text-sm font-bold transition"
                        :class="
                            activeTab === 'inventory'
                                ? 'bg-white text-black'
                                : 'text-zinc-500 hover:bg-white/[0.04] hover:text-white'
                        "
                        @click="setActiveTab('inventory')"
                    >
                        Inventory
                    </button>

                    <button
                        type="button"
                        class="rounded-[1.2rem] px-5 py-4 text-sm font-bold transition"
                        :class="
                            activeTab === 'warranty'
                                ? 'bg-white text-black'
                                : 'text-zinc-500 hover:bg-white/[0.04] hover:text-white'
                        "
                        @click="setActiveTab('warranty')"
                    >
                        Warranty
                    </button>
                </div>
            </section>

            <template v-if="activeTab === 'inventory'">
                <!-- SUMMARY -->
                <section class="grid gap-3 xl:grid-cols-[0.9fr_1.1fr]">
                    <div
                        class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5 sm:p-6"
                    >
                        <div class="mb-4">
                            <p
                                class="text-xs uppercase tracking-[0.24em] text-zinc-600"
                            >
                                Stock Summary
                            </p>
                            <h3 class="mt-2 text-xl font-semibold text-white">
                                Current inventory status
                            </h3>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div
                                v-for="card in inventoryCards"
                                :key="card.label"
                                class="rounded-[1.3rem] border border-white/10 bg-white/[0.03] p-4"
                            >
                                <p class="mn-mini-label">
                                    {{ card.label }}
                                </p>

                                <p
                                    class="mt-2 text-3xl font-semibold tracking-tight"
                                    :class="card.valueClass"
                                >
                                    {{ card.value }}
                                </p>

                                <p class="mt-2 text-xs text-zinc-500">
                                    {{ card.helper }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5 sm:p-6"
                    >
                        <div class="mb-4">
                            <p
                                class="text-xs uppercase tracking-[0.24em] text-zinc-600"
                            >
                                Money Summary
                            </p>
                            <h3 class="mt-2 text-xl font-semibold text-white">
                                Capital and expected profit
                            </h3>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div
                                v-for="card in moneyCards"
                                :key="card.label"
                                class="rounded-[1.3rem] border border-white/10 bg-white/[0.03] p-4"
                            >
                                <p class="mn-mini-label">
                                    {{ card.label }}
                                </p>

                                <p
                                    class="mt-2 text-2xl font-semibold tracking-tight"
                                    :class="card.valueClass"
                                >
                                    {{ card.compactValue }}
                                </p>

                                <p class="mt-1 text-xs text-zinc-600">
                                    {{ card.value }}
                                </p>

                                <p class="mt-3 text-xs leading-5 text-zinc-500">
                                    {{ card.helper }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ARRANGE DISPLAY MODE -->
                <section
                    v-if="isArrangeMode"
                    class="relative overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5 shadow-2xl shadow-black/30 sm:p-6"
                >
                    <div class="pointer-events-none absolute inset-0">
                        <div
                            class="absolute left-[-10rem] top-[-10rem] h-80 w-80 rounded-full bg-white/[0.045] blur-3xl"
                        ></div>
                        <div
                            class="absolute bottom-[-12rem] right-[-12rem] h-96 w-96 rounded-full bg-emerald-400/[0.035] blur-3xl"
                        ></div>
                    </div>

                    <div class="relative">
                        <div
                            class="flex flex-col gap-4 border-b border-white/10 pb-5 lg:flex-row lg:items-end lg:justify-between"
                        >
                            <div>
                                <p
                                    class="text-xs uppercase tracking-[0.24em] text-zinc-600"
                                >
                                    Arrange Mode
                                </p>

                                <h3
                                    class="mt-2 text-2xl font-semibold tracking-tight text-white"
                                >
                                    Arrange website display order
                                </h3>

                                <p
                                    class="mt-3 max-w-2xl text-sm leading-7 text-zinc-400"
                                >
                                    Move watches up or down, then save. This
                                    updates the display_order used by your
                                    public website. This list shows all
                                    available and visible watches, not just the
                                    current page.
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-3 sm:flex">
                                <button
                                    type="button"
                                    class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-semibold text-zinc-300 transition hover:border-white/30 hover:text-white"
                                    @click="cancelArrangeMode"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="button"
                                    :disabled="
                                        isSavingOrder ||
                                        !arrangeItems.length ||
                                        !hasArrangeChanges
                                    "
                                    class="rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200 disabled:cursor-not-allowed disabled:bg-zinc-700 disabled:text-zinc-400"
                                    @click="saveArrangeOrder"
                                >
                                    {{
                                        isSavingOrder
                                            ? "Saving..."
                                            : hasArrangeChanges
                                              ? "Save Order"
                                              : "No Changes"
                                    }}
                                </button>
                            </div>
                        </div>

                        <div
                            class="mt-5 grid gap-3 rounded-2xl border border-amber-500/20 bg-amber-500/10 p-4 text-sm leading-6 text-amber-200 sm:grid-cols-[auto_1fr]"
                        >
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-amber-300 text-sm font-black text-black"
                            >
                                !
                            </div>

                            <div>
                                <p class="font-semibold">
                                    Public website arrangement
                                </p>

                                <p
                                    class="mt-1 text-xs leading-5 text-amber-100/80"
                                >
                                    This arranges all watches that are available
                                    and visible on the public website, even if
                                    they are not on the current paginated admin
                                    page.
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="!arrangeItems.length"
                            class="mt-5 rounded-2xl border border-white/10 bg-white/[0.03] p-8 text-center"
                        >
                            <p class="text-sm font-semibold text-white">
                                No watches available to arrange.
                            </p>

                            <p class="mt-2 text-sm text-zinc-500">
                                Make at least one watch Available and Visible
                                first.
                            </p>
                        </div>

                        <div v-else class="mt-5 space-y-3">
                            <div
                                v-for="(watch, index) in arrangeItems"
                                :key="watch.id"
                                class="group overflow-hidden rounded-[1.35rem] border border-white/10 bg-white/[0.03] transition hover:border-white/20"
                            >
                                <div
                                    class="grid gap-4 p-4 md:grid-cols-[auto_1fr_auto]"
                                >
                                    <div
                                        class="flex items-center gap-3 md:flex-col md:items-center md:justify-center"
                                    >
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-2xl border border-white/10 bg-black text-sm font-black text-white"
                                        >
                                            {{ index + 1 }}
                                        </div>

                                        <div
                                            class="hidden h-full w-px bg-white/10 md:block"
                                        ></div>
                                    </div>

                                    <div class="flex min-w-0 gap-4">
                                        <div
                                            class="relative h-20 w-20 shrink-0 overflow-hidden rounded-2xl border border-white/10 bg-[#050505]"
                                        >
                                            <img
                                                v-if="watch.primary_image"
                                                :src="
                                                    watch.primary_image
                                                        .image_url
                                                "
                                                class="h-full w-full object-cover"
                                                alt=""
                                            />

                                            <div
                                                v-else
                                                class="flex h-full w-full items-center justify-center text-xs font-semibold text-zinc-600"
                                            >
                                                MN
                                            </div>

                                            <div
                                                class="absolute bottom-1 right-1 rounded-full bg-black/80 px-2 py-0.5 text-[10px] font-bold text-white"
                                            >
                                                #{{
                                                    watch.display_order || "—"
                                                }}
                                            </div>
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
                                            >
                                                <div class="min-w-0">
                                                    <p
                                                        class="truncate text-sm font-semibold text-white sm:text-base"
                                                    >
                                                        {{ watch.brand }}
                                                        {{ watch.model_name }}
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

                                                <div
                                                    class="flex shrink-0 flex-wrap gap-2"
                                                >
                                                    <span
                                                        class="rounded-full border px-2.5 py-1 text-[11px] capitalize"
                                                        :class="
                                                            statusClass(
                                                                watch.status,
                                                            )
                                                        "
                                                    >
                                                        {{ watch.status }}
                                                    </span>

                                                    <span
                                                        class="rounded-full border px-2.5 py-1 text-[11px]"
                                                        :class="
                                                            visibilityClass(
                                                                watch,
                                                            )
                                                        "
                                                    >
                                                        {{
                                                            watch.status ===
                                                            "sold"
                                                                ? "Sold"
                                                                : watch.is_visible
                                                                  ? "Visible"
                                                                  : "Hidden"
                                                        }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div
                                                class="mt-3 grid grid-cols-3 gap-2 text-xs"
                                            >
                                                <div>
                                                    <p class="text-zinc-600">
                                                        Price
                                                    </p>
                                                    <p
                                                        class="mt-1 font-semibold text-white"
                                                    >
                                                        {{
                                                            compactPeso(
                                                                displayPrice(
                                                                    watch,
                                                                ),
                                                            )
                                                        }}
                                                    </p>
                                                </div>

                                                <div>
                                                    <p class="text-zinc-600">
                                                        Profit
                                                    </p>
                                                    <p
                                                        class="mt-1 font-semibold"
                                                        :class="
                                                            displayProfit(
                                                                watch,
                                                            ) >= 0
                                                                ? 'text-emerald-300'
                                                                : 'text-red-300'
                                                        "
                                                    >
                                                        {{
                                                            compactPeso(
                                                                displayProfit(
                                                                    watch,
                                                                ),
                                                            )
                                                        }}
                                                    </p>
                                                </div>

                                                <div>
                                                    <p class="text-zinc-600">
                                                        Age
                                                    </p>
                                                    <p
                                                        class="mt-1 truncate font-semibold text-zinc-300"
                                                    >
                                                        {{
                                                            stockAgeLabel(watch)
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="grid grid-cols-3 gap-2 md:w-44 md:grid-cols-1"
                                    >
                                        <button
                                            type="button"
                                            :disabled="index === 0"
                                            class="mn-arrange-btn"
                                            @click="
                                                moveArrangeItem(index, 'up')
                                            "
                                        >
                                            Move Up
                                        </button>

                                        <button
                                            type="button"
                                            :disabled="index === 0"
                                            class="mn-arrange-btn"
                                            @click="moveArrangeItemToTop(index)"
                                        >
                                            Top
                                        </button>

                                        <button
                                            type="button"
                                            :disabled="
                                                index ===
                                                arrangeItems.length - 1
                                            "
                                            class="mn-arrange-btn"
                                            @click="
                                                moveArrangeItem(index, 'down')
                                            "
                                        >
                                            Move Down
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="sticky bottom-0 mt-5 rounded-2xl border border-white/10 bg-[#050505]/95 p-3 shadow-2xl shadow-black/60 backdrop-blur md:hidden"
                        >
                            <div class="grid grid-cols-2 gap-3">
                                <button
                                    type="button"
                                    class="rounded-xl border border-white/10 px-4 py-3 text-sm font-semibold text-zinc-300"
                                    @click="cancelArrangeMode"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="button"
                                    :disabled="
                                        isSavingOrder ||
                                        !arrangeItems.length ||
                                        !hasArrangeChanges
                                    "
                                    class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-black disabled:bg-zinc-700 disabled:text-zinc-400"
                                    @click="saveArrangeOrder"
                                >
                                    {{
                                        isSavingOrder
                                            ? "Saving..."
                                            : "Save Order"
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ACTION NEEDED -->
                <section
                    v-if="!isArrangeMode"
                    class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5 sm:p-6"
                >
                    <div
                        class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-end"
                    >
                        <div>
                            <p
                                class="text-xs uppercase tracking-[0.24em] text-zinc-600"
                            >
                                Action Needed
                            </p>

                            <h3 class="mt-2 text-xl font-semibold text-white">
                                Inventory priorities
                            </h3>
                        </div>

                        <p class="text-xs text-zinc-500">
                            Based on the current loaded page.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                        <button
                            v-for="card in actionCards"
                            :key="card.label"
                            type="button"
                            class="rounded-[1.3rem] border p-4 text-left transition hover:-translate-y-0.5 sm:p-5"
                            :class="[
                                card.className,
                                actionFilter === card.filter
                                    ? 'ring-2 ring-white/30'
                                    : '',
                            ]"
                            @click="setActionFilter(card.filter)"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.18em] opacity-80 sm:text-xs"
                                    >
                                        {{ card.label }}
                                    </p>

                                    <p class="mt-3 text-3xl font-semibold">
                                        {{ card.value }}
                                    </p>
                                </div>
                            </div>

                            <p
                                class="mt-3 text-xs leading-5 opacity-80 sm:text-sm"
                            >
                                {{ card.helper }}
                            </p>
                        </button>
                    </div>
                </section>

                <!-- FILTERS -->
                <section
                    v-if="!isArrangeMode"
                    class="relative hidden overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5 sm:p-6 md:block"
                >
                    <div
                        v-if="isFiltering || isSearchPending"
                        class="absolute inset-x-0 top-0 h-0.5 overflow-hidden bg-white/[0.05]"
                    >
                        <div
                            class="h-full w-1/2 animate-[mn-filter-bar_1.1s_ease-in-out_infinite] rounded-full bg-white"
                        ></div>
                    </div>

                    <div class="flex flex-col gap-5">
                        <div
                            class="grid gap-3 lg:grid-cols-[1fr_auto] lg:items-center"
                        >
                            <div>
                                <p
                                    class="mb-3 text-xs uppercase tracking-[0.24em] text-zinc-600"
                                >
                                    Search Stocks
                                </p>

                                <div class="relative">
                                    <input
                                        v-model="search"
                                        type="text"
                                        placeholder="Search brand, model, reference, buyer, serial..."
                                        class="mn-input pr-24"
                                    />

                                    <div
                                        v-if="isSearchPending || isFiltering"
                                        class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-zinc-400"
                                    >
                                        {{
                                            isSearchPending
                                                ? "Typing"
                                                : "Syncing"
                                        }}
                                    </div>
                                </div>
                            </div>

                            <div>
                                <p
                                    class="mb-3 text-xs uppercase tracking-[0.24em] text-zinc-600"
                                >
                                    View
                                </p>

                                <div
                                    class="grid grid-cols-2 rounded-2xl border border-white/10 bg-white/[0.03] p-1"
                                >
                                    <button
                                        type="button"
                                        class="rounded-xl px-4 py-2 text-sm font-bold transition"
                                        :class="
                                            viewMode === 'table'
                                                ? 'bg-white text-black'
                                                : 'text-zinc-500 hover:text-white'
                                        "
                                        @click="setViewMode('table')"
                                    >
                                        List
                                    </button>

                                    <button
                                        type="button"
                                        class="rounded-xl px-4 py-2 text-sm font-bold transition"
                                        :class="
                                            viewMode === 'gallery'
                                                ? 'bg-white text-black'
                                                : 'text-zinc-500 hover:text-white'
                                        "
                                        @click="setViewMode('gallery')"
                                    >
                                        Gallery
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <p
                                class="mb-3 text-xs uppercase tracking-[0.24em] text-zinc-600"
                            >
                                Status
                            </p>

                            <div
                                class="thin-scrollbar flex gap-2 overflow-x-auto pb-1"
                            >
                                <button
                                    v-for="tab in statusTabs"
                                    :key="tab.value || 'all'"
                                    type="button"
                                    class="shrink-0 rounded-2xl border px-4 py-2 text-sm font-medium transition"
                                    :class="
                                        status === tab.value
                                            ? 'border-white bg-white text-black'
                                            : 'border-white/10 bg-white/[0.03] text-zinc-400 hover:border-white/30 hover:text-white'
                                    "
                                    @click="setStatusFilter(tab.value)"
                                >
                                    {{ tab.label }}
                                    <span
                                        v-if="tab.count !== null"
                                        class="ml-1 opacity-70"
                                    >
                                        {{ tab.count }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div>
                            <p
                                class="mb-3 text-xs uppercase tracking-[0.24em] text-zinc-600"
                            >
                                Quick Filters
                            </p>

                            <div
                                class="thin-scrollbar flex gap-2 overflow-x-auto pb-1"
                            >
                                <button
                                    v-for="filter in quickActionFilters"
                                    :key="filter.value"
                                    type="button"
                                    class="shrink-0 rounded-2xl border px-4 py-2 text-sm font-medium transition"
                                    :class="
                                        actionFilter === filter.value
                                            ? 'border-white bg-white text-black'
                                            : 'border-white/10 bg-white/[0.03] text-zinc-400 hover:border-white/30 hover:text-white'
                                    "
                                    @click="setActionFilter(filter.value)"
                                >
                                    {{ filter.label }}
                                    <span class="ml-1 opacity-70">
                                        {{ filter.count }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div
                            class="flex flex-col gap-3 rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-zinc-400 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <span>
                                    Showing
                                    <strong class="text-white">
                                        {{ displayedWatches.length }}
                                    </strong>
                                    of
                                    <strong class="text-white">
                                        {{ currentPageWatches.length }}
                                    </strong>
                                    loaded watches
                                </span>

                                <p class="mt-1 text-xs text-zinc-600">
                                    {{ filterStateLabel }}
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <span
                                    v-if="isFiltering || isSearchPending"
                                    class="inline-flex items-center gap-2 text-xs font-semibold text-zinc-300"
                                >
                                    <span
                                        class="h-1.5 w-1.5 animate-pulse rounded-full bg-white"
                                    ></span>
                                    {{
                                        isSearchPending ? "Waiting" : "Applying"
                                    }}
                                </span>

                                <button
                                    v-if="hasActiveFilters"
                                    type="button"
                                    class="text-xs font-semibold text-white underline underline-offset-4"
                                    @click="
                                        search = '';
                                        setStatusFilter('');
                                    "
                                >
                                    Clear filters
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- BULK ACTIONS -->
                <section
                    v-if="
                        !isArrangeMode &&
                        activeTab === 'inventory' &&
                        isBulkMode
                    "
                    class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-4 shadow-2xl shadow-black/20 sm:p-5"
                >
                    <div
                        class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between"
                    >
                        <div class="min-w-0">
                            <p
                                class="text-xs uppercase tracking-[0.24em] text-zinc-600"
                            >
                                Bulk Actions
                            </p>

                            <h3
                                class="mt-2 text-lg font-semibold text-white sm:text-xl"
                            >
                                {{ selectedBulkCount }} selected
                            </h3>

                            <p
                                class="mt-1 text-xs leading-5 text-zinc-500 sm:text-sm"
                            >
                                Select watches on this page, then apply one
                                action to all selected items.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="rounded-xl border border-white/10 bg-white/[0.04] px-3 py-2 text-xs font-bold text-white transition hover:border-white/30"
                                @click="toggleSelectDisplayedWatches"
                            >
                                {{
                                    allDisplayedSelected
                                        ? "Unselect Page"
                                        : hasDisplayedSelection
                                          ? "Select Rest"
                                          : "Select Page"
                                }}
                            </button>

                            <button
                                type="button"
                                class="rounded-xl border border-white/10 px-3 py-2 text-xs font-bold text-zinc-300 transition hover:border-white/30 hover:text-white"
                                @click="cancelBulkMode"
                            >
                                Done
                            </button>
                        </div>
                    </div>

                    <div
                        class="mt-4 grid grid-cols-2 gap-2 md:grid-cols-4 xl:grid-cols-7"
                    >
                        <button
                            v-for="option in bulkActionOptions"
                            :key="option.value"
                            type="button"
                            :disabled="!selectedBulkCount || isBulkProcessing"
                            class="rounded-2xl border p-3 text-left transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-40"
                            :class="option.className"
                            @click="runBulkAction(option.value)"
                        >
                            <p
                                class="text-xs font-black uppercase tracking-[0.14em]"
                            >
                                {{ option.label }}
                            </p>

                            <p
                                class="mt-1 hidden text-[11px] leading-4 opacity-75 sm:block"
                            >
                                {{ option.helper }}
                            </p>
                        </button>
                    </div>

                    <div
                        v-if="selectedBulkCount"
                        class="mt-4 thin-scrollbar flex gap-2 overflow-x-auto pb-1"
                    >
                        <span
                            v-for="watch in selectedBulkWatches"
                            :key="`selected-${watch.id}`"
                            class="shrink-0 rounded-full border border-white/10 bg-white/[0.04] px-3 py-1.5 text-xs font-semibold text-zinc-300"
                        >
                            {{ watch.brand }} {{ watch.model_name }}
                        </span>
                    </div>
                </section>

                <!-- GALLERY VIEW -->
                <section
                    v-if="!isArrangeMode && viewMode === 'gallery'"
                    class="grid gap-4 transition-opacity sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
                    :class="isFiltering ? 'opacity-60' : 'opacity-100'"
                >
                    <div
                        v-for="watch in displayedWatches"
                        :key="watch.id"
                        class="overflow-hidden rounded-[1.7rem] border bg-[#0B0B0D] transition hover:border-white/20"
                        :class="
                            isWatchSelected(watch)
                                ? 'border-white ring-2 ring-white/20'
                                : 'border-white/10'
                        "
                    >
                        <div class="relative aspect-[4/5] bg-[#050505]">
                            <button
                                v-if="isBulkMode"
                                type="button"
                                class="absolute right-4 top-4 z-20 flex h-10 w-10 items-center justify-center rounded-full border text-sm font-black backdrop-blur transition active:scale-95"
                                :class="
                                    isWatchSelected(watch)
                                        ? 'border-white bg-white text-black'
                                        : 'border-white/20 bg-black/70 text-white'
                                "
                                @click.stop="toggleWatchSelection(watch)"
                                :aria-label="
                                    isWatchSelected(watch)
                                        ? 'Unselect watch'
                                        : 'Select watch'
                                "
                            >
                                {{ isWatchSelected(watch) ? "✓" : "" }}
                            </button>
                            <img
                                v-if="watch.primary_image"
                                :src="watch.primary_image.image_url"
                                class="h-full w-full object-cover"
                                alt=""
                            />

                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center text-sm font-semibold tracking-[0.3em] text-zinc-700"
                            >
                                MONTRE NOVA
                            </div>

                            <div
                                class="absolute left-4 top-4 flex flex-wrap gap-2"
                            >
                                <span
                                    class="rounded-full border px-3 py-1 text-xs font-medium capitalize backdrop-blur"
                                    :class="statusClass(watch.status)"
                                >
                                    {{ watch.status }}
                                </span>

                                <span
                                    class="rounded-full border px-3 py-1 text-xs font-medium backdrop-blur"
                                    :class="stockAgeClass(watch)"
                                >
                                    {{ stockAgeStage(watch) }}
                                </span>
                            </div>

                            <div
                                class="absolute bottom-4 right-4 rounded-full bg-black/80 px-3 py-1 text-xs font-medium text-white backdrop-blur"
                            >
                                {{ watch.images_count || 0 }} photos
                            </div>
                        </div>

                        <div class="p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-base font-semibold text-white"
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
                                    class="shrink-0 rounded-full border px-3 py-1 text-xs font-medium"
                                    :class="visibilityClass(watch)"
                                >
                                    {{
                                        watch.status === "sold"
                                            ? "Sold"
                                            : watch.is_visible
                                              ? "Visible"
                                              : "Hidden"
                                    }}
                                </span>
                            </div>

                            <div
                                class="mt-5 rounded-2xl border p-4"
                                :class="recommendedAction(watch).className"
                            >
                                <p
                                    class="text-xs font-semibold uppercase tracking-[0.18em]"
                                >
                                    Recommended Action
                                </p>

                                <p class="mt-2 text-sm font-semibold">
                                    {{ recommendedAction(watch).label }}
                                </p>

                                <p class="mt-1 text-xs opacity-80">
                                    {{ recommendedAction(watch).helper }}
                                </p>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <div class="mn-info-box">
                                    <p class="mn-info-label">Price</p>
                                    <p class="mn-info-value">
                                        {{ compactPeso(displayPrice(watch)) }}
                                    </p>
                                </div>

                                <div class="mn-info-box">
                                    <p class="mn-info-label">Profit</p>
                                    <p
                                        class="mn-info-value"
                                        :class="
                                            displayProfit(watch) >= 0
                                                ? 'text-emerald-300'
                                                : 'text-red-300'
                                        "
                                    >
                                        {{ compactPeso(displayProfit(watch)) }}
                                    </p>
                                </div>

                                <div class="mn-info-box">
                                    <p class="mn-info-label">Margin</p>
                                    <p class="mn-info-value">
                                        {{ profitMargin(watch).toFixed(1) }}%
                                    </p>
                                </div>

                                <div class="mn-info-box">
                                    <p class="mn-info-label">Age</p>
                                    <p class="mn-info-value">
                                        {{ stockAgeLabel(watch) }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 grid grid-cols-2 gap-2">
                                <button
                                    v-if="watch.status !== 'sold'"
                                    type="button"
                                    class="mn-action-btn border-amber-500/20 text-amber-300 hover:bg-amber-500/10"
                                    @click="openReserveModal(watch)"
                                >
                                    {{
                                        watch.status === "reserved"
                                            ? "Edit Reserve"
                                            : "Reserve"
                                    }}
                                </button>

                                <button
                                    v-if="watch.status !== 'sold'"
                                    type="button"
                                    class="mn-action-btn border-emerald-500/20 text-emerald-300 hover:bg-emerald-500/10"
                                    @click="openMarkSoldModal(watch)"
                                >
                                    Mark Sold
                                </button>

                                <button
                                    type="button"
                                    class="mn-action-btn border-white/10 text-zinc-300 hover:border-white/30 hover:text-white"
                                    @click="openEditModal(watch)"
                                >
                                    Edit
                                </button>

                                <button
                                    type="button"
                                    class="mn-action-btn border-sky-500/20 text-sky-300 hover:bg-sky-500/10"
                                    @click="openDuplicateModal(watch)"
                                >
                                    Duplicate
                                </button>

                                <button
                                    type="button"
                                    class="mn-action-btn border-red-500/20 text-red-300 hover:bg-red-500/10"
                                    @click="openDeleteModal(watch)"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- LIST VIEW: MOBILE CLEAN CARDS -->
                <section
                    v-if="
                        !isArrangeMode &&
                        viewMode === 'table' &&
                        displayedWatches.length
                    "
                    class="transition-opacity md:hidden"
                    :class="isFiltering ? 'opacity-60' : 'opacity-100'"
                >
                    <div class="mb-3 flex items-end justify-between gap-3">
                        <div class="min-w-0">
                            <p
                                class="text-xs uppercase tracking-[0.24em] text-zinc-600"
                            >
                                Mobile Inventory
                            </p>

                            <h3 class="mt-1 text-lg font-semibold text-white">
                                Clean stock cards
                            </h3>

                            <p class="mt-1 text-xs leading-5 text-zinc-500">
                                {{ displayedWatches.length }} watch{{
                                    displayedWatches.length === 1 ? "" : "es"
                                }}
                                loaded. Main actions are visible, extras are
                                inside More.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="shrink-0 rounded-full border px-3 py-1.5 text-[11px] font-bold transition active:scale-95"
                            :class="
                                isBulkMode
                                    ? 'border-white bg-white text-black'
                                    : 'border-white/10 bg-white/[0.04] text-zinc-300'
                            "
                            @click="toggleBulkMode"
                        >
                            {{ isBulkMode ? "Done" : "Select" }}
                        </button>
                    </div>

                    <div class="space-y-3">
                        <article
                            v-for="(watch, index) in displayedWatches"
                            :key="watch.id"
                            class="overflow-hidden rounded-[1.45rem] border bg-[#0B0B0D] shadow-xl shadow-black/20 transition"
                            :class="
                                isWatchSelected(watch)
                                    ? 'border-white ring-2 ring-white/20'
                                    : 'border-white/10'
                            "
                        >
                            <div class="p-3">
                                <div class="grid grid-cols-[92px_1fr] gap-3">
                                    <div
                                        class="relative overflow-hidden rounded-2xl border border-white/10 bg-[#050505]"
                                    >
                                        <button
                                            v-if="isBulkMode"
                                            type="button"
                                            class="absolute right-2 top-2 z-20 flex h-9 w-9 items-center justify-center rounded-full border text-sm font-black backdrop-blur transition active:scale-95"
                                            :class="
                                                isWatchSelected(watch)
                                                    ? 'border-white bg-white text-black'
                                                    : 'border-white/20 bg-black/70 text-white'
                                            "
                                            @click.stop="
                                                toggleWatchSelection(watch)
                                            "
                                            :aria-label="
                                                isWatchSelected(watch)
                                                    ? 'Unselect watch'
                                                    : 'Select watch'
                                            "
                                        >
                                            {{
                                                isWatchSelected(watch)
                                                    ? "✓"
                                                    : ""
                                            }}
                                        </button>

                                        <div class="aspect-square">
                                            <img
                                                v-if="watch.primary_image"
                                                :src="
                                                    watch.primary_image
                                                        .image_url
                                                "
                                                class="h-full w-full object-cover"
                                                alt=""
                                            />

                                            <div
                                                v-else
                                                class="flex h-full w-full items-center justify-center text-[10px] font-semibold tracking-[0.24em] text-zinc-700"
                                            >
                                                MN
                                            </div>
                                        </div>

                                        <div
                                            class="absolute bottom-2 left-2 rounded-full bg-black/75 px-2 py-0.5 text-[10px] font-bold text-white backdrop-blur"
                                        >
                                            {{ index + 1 }}/{{
                                                displayedWatches.length
                                            }}
                                        </div>
                                    </div>

                                    <div class="min-w-0">
                                        <div
                                            class="flex items-start justify-between gap-2"
                                        >
                                            <div class="min-w-0">
                                                <p
                                                    class="truncate text-sm font-semibold text-white"
                                                >
                                                    {{ watch.brand }}
                                                    {{ watch.model_name }}
                                                </p>

                                                <p
                                                    class="mt-1 truncate text-[11px] text-zinc-500"
                                                >
                                                    Ref.
                                                    {{
                                                        watch.reference_number ||
                                                        "No reference"
                                                    }}
                                                </p>
                                            </div>

                                            <button
                                                type="button"
                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/[0.04] text-zinc-300 transition active:scale-95"
                                                @click="
                                                    toggleMobileActions(watch)
                                                "
                                                aria-label="More actions"
                                            >
                                                <svg
                                                    class="h-5 w-5"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke-width="1.8"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M12 6.75h.008v.008H12V6.75zm0 5.25h.008v.008H12V12zm0 5.25h.008v.008H12v-.008z"
                                                    />
                                                </svg>
                                            </button>
                                        </div>

                                        <div
                                            class="mt-2 flex flex-wrap gap-1.5"
                                        >
                                            <span
                                                class="rounded-full border px-2.5 py-1 text-[10px] font-bold capitalize"
                                                :class="
                                                    statusClass(watch.status)
                                                "
                                            >
                                                {{ watch.status }}
                                            </span>

                                            <span
                                                class="rounded-full border px-2.5 py-1 text-[10px] font-bold"
                                                :class="visibilityClass(watch)"
                                            >
                                                {{
                                                    watch.status === "sold"
                                                        ? "Sold"
                                                        : watch.is_visible
                                                          ? "Visible"
                                                          : "Hidden"
                                                }}
                                            </span>

                                            <span
                                                v-for="badge in mobileWarningBadges(
                                                    watch,
                                                )"
                                                :key="badge.label"
                                                class="rounded-full border px-2.5 py-1 text-[10px] font-bold"
                                                :class="badge.className"
                                            >
                                                {{ badge.label }}
                                            </span>
                                        </div>

                                        <div
                                            class="mt-3 grid grid-cols-2 gap-2"
                                        >
                                            <div
                                                class="rounded-2xl border border-white/10 bg-white/[0.03] p-3"
                                            >
                                                <p
                                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-zinc-600"
                                                >
                                                    Price
                                                </p>
                                                <p
                                                    class="mt-1 truncate text-sm font-semibold text-white"
                                                >
                                                    {{
                                                        compactPeso(
                                                            displayPrice(watch),
                                                        )
                                                    }}
                                                </p>
                                            </div>

                                            <div
                                                class="rounded-2xl border border-white/10 bg-white/[0.03] p-3"
                                            >
                                                <p
                                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-zinc-600"
                                                >
                                                    Profit
                                                </p>
                                                <p
                                                    class="mt-1 truncate text-sm font-semibold"
                                                    :class="
                                                        displayProfit(watch) >=
                                                        0
                                                            ? 'text-emerald-300'
                                                            : 'text-red-300'
                                                    "
                                                >
                                                    {{
                                                        compactPeso(
                                                            displayProfit(
                                                                watch,
                                                            ),
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="isMobileActionsOpen(watch)"
                                    class="mt-3 rounded-2xl border border-white/10 bg-[#050505] p-3"
                                >
                                    <div
                                        class="mb-3 flex items-center justify-between gap-3"
                                    >
                                        <p
                                            class="text-xs font-bold uppercase tracking-[0.18em] text-zinc-500"
                                        >
                                            More Actions
                                        </p>

                                        <button
                                            type="button"
                                            class="text-xs font-semibold text-zinc-400"
                                            @click="closeMobileActions"
                                        >
                                            Close
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <button
                                            v-if="watch.status !== 'sold'"
                                            type="button"
                                            class="mn-action-btn border-amber-500/20 text-amber-300"
                                            @click="
                                                closeMobileActions();
                                                openReserveModal(watch);
                                            "
                                        >
                                            {{
                                                watch.status === "reserved"
                                                    ? "Edit Reserve"
                                                    : "Reserve"
                                            }}
                                        </button>

                                        <button
                                            v-if="watch.status === 'reserved'"
                                            type="button"
                                            class="mn-action-btn border-white/10 text-zinc-300"
                                            @click="
                                                closeMobileActions();
                                                clearReservation(watch);
                                            "
                                        >
                                            Clear Reserve
                                        </button>

                                        <button
                                            type="button"
                                            class="mn-action-btn border-sky-500/20 text-sky-300"
                                            @click="
                                                closeMobileActions();
                                                openDuplicateModal(watch);
                                            "
                                        >
                                            Duplicate
                                        </button>

                                        <button
                                            type="button"
                                            class="mn-action-btn border-red-500/20 text-red-300"
                                            @click="
                                                closeMobileActions();
                                                openDeleteModal(watch);
                                            "
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-3 grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm font-semibold text-zinc-200 transition active:scale-[0.99]"
                                        @click="openEditModal(watch)"
                                    >
                                        Edit
                                    </button>

                                    <button
                                        v-if="watch.status !== 'sold'"
                                        type="button"
                                        class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-semibold text-emerald-300 transition active:scale-[0.99]"
                                        @click="openMarkSoldModal(watch)"
                                    >
                                        Mark Sold
                                    </button>

                                    <button
                                        v-else
                                        type="button"
                                        class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm font-semibold text-zinc-300 transition active:scale-[0.99]"
                                        @click="toggleMobileDetails(watch)"
                                    >
                                        Details
                                    </button>
                                </div>

                                <button
                                    type="button"
                                    class="mt-3 flex w-full items-center justify-between rounded-2xl border border-white/10 bg-white/[0.02] px-4 py-3 text-left transition active:scale-[0.99]"
                                    @click="toggleMobileDetails(watch)"
                                >
                                    <span>
                                        <span
                                            class="block text-xs font-bold uppercase tracking-[0.18em] text-zinc-600"
                                        >
                                            Details
                                        </span>
                                        <span
                                            class="mt-1 block text-sm font-semibold text-white"
                                        >
                                            {{ recommendedAction(watch).label }}
                                        </span>
                                    </span>

                                    <span
                                        class="text-xs font-bold text-zinc-500"
                                    >
                                        {{
                                            isMobileDetailsOpen(watch)
                                                ? "Hide"
                                                : "View"
                                        }}
                                    </span>
                                </button>

                                <div
                                    v-if="isMobileDetailsOpen(watch)"
                                    class="mt-3 rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                                >
                                    <div
                                        class="rounded-2xl border p-3"
                                        :class="
                                            recommendedAction(watch).className
                                        "
                                    >
                                        <p
                                            class="text-[11px] font-bold uppercase tracking-[0.16em]"
                                        >
                                            Recommended Action
                                        </p>

                                        <p class="mt-1 text-sm font-semibold">
                                            {{ recommendedAction(watch).label }}
                                        </p>

                                        <p
                                            class="mt-1 text-xs leading-5 opacity-80"
                                        >
                                            {{
                                                recommendedAction(watch).helper
                                            }}
                                        </p>
                                    </div>

                                    <div
                                        class="mt-4 grid grid-cols-2 gap-3 text-xs"
                                    >
                                        <div>
                                            <p class="text-zinc-600">Age</p>
                                            <p
                                                class="mt-1 font-semibold text-zinc-200"
                                            >
                                                {{ stockAgeLabel(watch) }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-zinc-600">Margin</p>
                                            <p
                                                class="mt-1 font-semibold text-zinc-200"
                                            >
                                                {{
                                                    profitMargin(watch).toFixed(
                                                        1,
                                                    )
                                                }}%
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-zinc-600">Photos</p>
                                            <p
                                                class="mt-1 font-semibold text-zinc-200"
                                            >
                                                {{ watch.images_count || 0 }}
                                                photo{{
                                                    (watch.images_count ||
                                                        0) === 1
                                                        ? ""
                                                        : "s"
                                                }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-zinc-600">Stage</p>
                                            <p
                                                class="mt-1 font-semibold text-zinc-200"
                                            >
                                                {{ stockAgeStage(watch) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>

                <!-- LIST VIEW: DESKTOP TABLE -->
                <section
                    v-if="!isArrangeMode && viewMode === 'table'"
                    class="hidden overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] transition-opacity md:block"
                    :class="isFiltering ? 'opacity-60' : 'opacity-100'"
                >
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-white/10">
                            <thead>
                                <tr class="bg-white/[0.02]">
                                    <th v-if="isBulkMode" class="mn-th w-14">
                                        <button
                                            type="button"
                                            class="flex h-9 w-9 items-center justify-center rounded-full border text-xs font-black transition"
                                            :class="
                                                allDisplayedSelected
                                                    ? 'border-white bg-white text-black'
                                                    : 'border-white/10 bg-white/[0.04] text-zinc-400'
                                            "
                                            @click="
                                                toggleSelectDisplayedWatches
                                            "
                                            aria-label="Select all displayed watches"
                                        >
                                            {{
                                                allDisplayedSelected ? "✓" : ""
                                            }}
                                        </button>
                                    </th>
                                    <th class="mn-th">Watch</th>
                                    <th class="mn-th">Action</th>
                                    <th class="mn-th">Pricing</th>
                                    <th class="mn-th">Timeline</th>
                                    <th class="mn-th">Health</th>
                                    <th class="mn-th text-right">Manage</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-white/10">
                                <tr
                                    v-for="watch in displayedWatches"
                                    :key="watch.id"
                                    class="transition hover:bg-white/[0.02]"
                                    :class="
                                        isWatchSelected(watch)
                                            ? 'bg-white/[0.04]'
                                            : ''
                                    "
                                >
                                    <td
                                        v-if="isBulkMode"
                                        class="px-4 py-5 align-top"
                                    >
                                        <button
                                            type="button"
                                            class="flex h-10 w-10 items-center justify-center rounded-full border text-sm font-black transition"
                                            :class="
                                                isWatchSelected(watch)
                                                    ? 'border-white bg-white text-black'
                                                    : 'border-white/10 bg-white/[0.04] text-zinc-400 hover:border-white/30 hover:text-white'
                                            "
                                            @click="toggleWatchSelection(watch)"
                                            :aria-label="
                                                isWatchSelected(watch)
                                                    ? 'Unselect watch'
                                                    : 'Select watch'
                                            "
                                        >
                                            {{
                                                isWatchSelected(watch)
                                                    ? "✓"
                                                    : ""
                                            }}
                                        </button>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div
                                            class="flex min-w-[300px] items-center gap-4"
                                        >
                                            <div
                                                class="relative h-20 w-20 shrink-0 overflow-hidden rounded-2xl border border-white/10 bg-[#050505]"
                                            >
                                                <img
                                                    v-if="watch.primary_image"
                                                    :src="
                                                        watch.primary_image
                                                            .image_url
                                                    "
                                                    class="h-full w-full object-cover"
                                                    alt=""
                                                />

                                                <div
                                                    v-else
                                                    class="flex h-full w-full items-center justify-center text-xs font-semibold text-zinc-600"
                                                >
                                                    MN
                                                </div>

                                                <div
                                                    class="absolute bottom-1 right-1 rounded-full bg-black/80 px-2 py-0.5 text-[10px] font-medium text-white"
                                                >
                                                    {{
                                                        watch.images_count || 0
                                                    }}
                                                </div>
                                            </div>

                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-white"
                                                >
                                                    {{ watch.brand }}
                                                    {{ watch.model_name }}
                                                </p>

                                                <p
                                                    class="mt-1 text-xs text-zinc-500"
                                                >
                                                    Ref.
                                                    {{
                                                        watch.reference_number ||
                                                        "No reference"
                                                    }}
                                                </p>

                                                <div
                                                    class="mt-3 flex flex-wrap gap-2"
                                                >
                                                    <span
                                                        class="rounded-full border px-2.5 py-1 text-[11px] capitalize"
                                                        :class="
                                                            statusClass(
                                                                watch.status,
                                                            )
                                                        "
                                                    >
                                                        {{ watch.status }}
                                                    </span>

                                                    <span
                                                        class="rounded-full border px-2.5 py-1 text-[11px]"
                                                        :class="
                                                            visibilityClass(
                                                                watch,
                                                            )
                                                        "
                                                    >
                                                        {{
                                                            watch.status ===
                                                            "sold"
                                                                ? "Sold / Hidden"
                                                                : watch.is_visible
                                                                  ? "Visible"
                                                                  : "Hidden"
                                                        }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5">
                                        <div
                                            class="min-w-[190px] rounded-2xl border p-4"
                                            :class="
                                                recommendedAction(watch)
                                                    .className
                                            "
                                        >
                                            <p
                                                class="text-xs font-semibold uppercase tracking-[0.18em]"
                                            >
                                                {{
                                                    recommendedAction(watch)
                                                        .label
                                                }}
                                            </p>

                                            <p class="mt-2 text-xs opacity-80">
                                                {{
                                                    recommendedAction(watch)
                                                        .helper
                                                }}
                                            </p>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5">
                                        <div class="min-w-[180px] space-y-3">
                                            <div>
                                                <p
                                                    class="text-xs text-zinc-600"
                                                >
                                                    Capital
                                                </p>
                                                <p
                                                    class="text-sm font-semibold text-white"
                                                >
                                                    {{
                                                        peso(
                                                            watch.capital_price,
                                                        )
                                                    }}
                                                </p>
                                            </div>

                                            <div>
                                                <p
                                                    class="text-xs text-zinc-600"
                                                >
                                                    {{
                                                        watch.status === "sold"
                                                            ? "Sold Price"
                                                            : "Listed Price"
                                                    }}
                                                </p>
                                                <p
                                                    class="text-sm font-semibold text-white"
                                                >
                                                    {{
                                                        peso(
                                                            displayPrice(watch),
                                                        )
                                                    }}
                                                </p>
                                            </div>

                                            <div>
                                                <p
                                                    class="text-xs"
                                                    :class="
                                                        displayProfit(watch) >=
                                                        0
                                                            ? 'text-emerald-300'
                                                            : 'text-red-300'
                                                    "
                                                >
                                                    {{
                                                        displayProfitLabel(
                                                            watch,
                                                        )
                                                    }}
                                                </p>

                                                <p
                                                    class="text-sm font-semibold"
                                                    :class="
                                                        displayProfit(watch) >=
                                                        0
                                                            ? 'text-emerald-300'
                                                            : 'text-red-300'
                                                    "
                                                >
                                                    {{
                                                        peso(
                                                            displayProfit(
                                                                watch,
                                                            ),
                                                        )
                                                    }}
                                                </p>

                                                <p
                                                    class="text-xs text-zinc-500"
                                                >
                                                    {{
                                                        profitMargin(
                                                            watch,
                                                        ).toFixed(1)
                                                    }}% margin
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5">
                                        <div
                                            class="min-w-[170px] space-y-3 text-xs"
                                        >
                                            <div>
                                                <p class="text-zinc-600">
                                                    Encoded
                                                </p>
                                                <p
                                                    class="font-semibold text-white"
                                                >
                                                    {{
                                                        formatDate(
                                                            watch.created_at,
                                                        ) || "—"
                                                    }}
                                                </p>
                                            </div>

                                            <div>
                                                <p class="text-zinc-600">
                                                    Sold
                                                </p>
                                                <p
                                                    class="font-semibold text-white"
                                                >
                                                    {{
                                                        watch.status === "sold"
                                                            ? formatDate(
                                                                  watch.date_sold,
                                                              )
                                                            : "—"
                                                    }}
                                                </p>
                                            </div>

                                            <div
                                                class="w-fit rounded-full border px-3 py-1 text-xs font-medium"
                                                :class="timelineClass(watch)"
                                            >
                                                {{ stockAgeLabel(watch) }}
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5">
                                        <div
                                            class="flex min-w-[160px] flex-col gap-2"
                                        >
                                            <span
                                                class="w-fit rounded-full border px-3 py-1 text-xs font-medium"
                                                :class="profitBadgeClass(watch)"
                                            >
                                                {{ profitBadgeLabel(watch) }}
                                            </span>

                                            <span
                                                class="w-fit rounded-full border px-3 py-1 text-xs font-medium"
                                                :class="stockAgeClass(watch)"
                                            >
                                                {{ stockAgeStage(watch) }}
                                            </span>

                                            <span
                                                v-if="isSlowMoving(watch)"
                                                class="w-fit rounded-full border border-amber-500/20 bg-amber-500/10 px-3 py-1 text-xs font-medium text-amber-300"
                                            >
                                                Needs Push
                                            </span>

                                            <span
                                                v-if="!watch.primary_image"
                                                class="w-fit rounded-full border border-red-500/20 bg-red-500/10 px-3 py-1 text-xs font-medium text-red-300"
                                            >
                                                No Photo
                                            </span>

                                            <span
                                                v-if="isReadyToPost(watch)"
                                                class="w-fit rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-300"
                                            >
                                                Ready to Post
                                            </span>

                                            <span
                                                v-if="
                                                    isReservationOverdue(watch)
                                                "
                                                class="w-fit rounded-full border border-red-500/20 bg-red-500/10 px-3 py-1 text-xs font-medium text-red-300"
                                            >
                                                Reservation Overdue
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5">
                                        <div
                                            class="flex flex-wrap justify-end gap-2"
                                        >
                                            <button
                                                v-if="watch.status !== 'sold'"
                                                type="button"
                                                class="mn-action-btn border-amber-500/20 text-amber-300"
                                                @click="openReserveModal(watch)"
                                            >
                                                {{
                                                    watch.status === "reserved"
                                                        ? "Edit Reserve"
                                                        : "Reserve"
                                                }}
                                            </button>

                                            <button
                                                v-if="
                                                    watch.status === 'reserved'
                                                "
                                                type="button"
                                                class="mn-action-btn border-white/10 text-zinc-300"
                                                @click="clearReservation(watch)"
                                            >
                                                Clear
                                            </button>

                                            <button
                                                v-if="watch.status !== 'sold'"
                                                type="button"
                                                class="mn-action-btn border-emerald-500/20 text-emerald-300"
                                                @click="
                                                    openMarkSoldModal(watch)
                                                "
                                            >
                                                Mark Sold
                                            </button>

                                            <button
                                                type="button"
                                                class="mn-action-btn border-white/10 text-zinc-300"
                                                @click="openEditModal(watch)"
                                            >
                                                Edit
                                            </button>

                                            <button
                                                type="button"
                                                class="mn-action-btn border-sky-500/20 text-sky-300"
                                                @click="
                                                    openDuplicateModal(watch)
                                                "
                                            >
                                                Duplicate
                                            </button>

                                            <button
                                                type="button"
                                                class="mn-action-btn border-red-500/20 text-red-300"
                                                @click="openDeleteModal(watch)"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- EMPTY STATE -->
                <section
                    v-if="!isArrangeMode && !displayedWatches.length"
                    class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-10 text-center"
                >
                    <p class="text-sm font-medium text-white">
                        No watch stocks found for this filter.
                    </p>

                    <p class="mt-2 text-sm text-zinc-500">
                        Try clearing the action filter or changing the status.
                    </p>

                    <button
                        type="button"
                        class="mt-5 rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200"
                        @click="setActionFilter('all')"
                    >
                        Clear Action Filter
                    </button>
                </section>

                <!-- PAGINATION -->
                <section
                    v-if="!isArrangeMode && watches.links?.length > 3"
                    class="thin-scrollbar flex gap-2 overflow-x-auto rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-4 sm:flex-wrap sm:p-5"
                >
                    <Link
                        v-for="link in watches.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        v-html="link.label"
                        preserve-scroll
                        preserve-state
                        class="shrink-0 rounded-xl border px-3 py-2 text-sm"
                        :class="[
                            link.active
                                ? 'border-white bg-white text-black'
                                : 'border-white/10 text-zinc-400 hover:border-white/30 hover:text-white',
                            !link.url ? 'pointer-events-none opacity-40' : '',
                        ]"
                    />
                </section>
            </template>

            <template v-if="activeTab === 'warranty'">
                <!-- WARRANTY HERO -->
                <section
                    class="relative overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5 sm:p-6"
                >
                    <div class="pointer-events-none absolute inset-0">
                        <div
                            class="absolute left-[-8rem] top-[-8rem] h-72 w-72 rounded-full bg-emerald-400/[0.05] blur-3xl"
                        ></div>

                        <div
                            class="absolute bottom-[-10rem] right-[-10rem] h-80 w-80 rounded-full bg-amber-400/[0.04] blur-3xl"
                        ></div>
                    </div>

                    <div class="relative">
                        <div
                            class="flex flex-col justify-between gap-5 lg:flex-row lg:items-end"
                        >
                            <div>
                                <p
                                    class="text-xs uppercase tracking-[0.24em] text-zinc-600"
                                >
                                    Warranty Center
                                </p>

                                <h3
                                    class="mt-2 text-2xl font-semibold tracking-tight text-white sm:text-3xl"
                                >
                                    Buyer warranty records
                                </h3>

                                <p
                                    class="mt-3 max-w-2xl text-sm leading-7 text-zinc-400"
                                >
                                    Each sold watch automatically receives a
                                    one-year warranty based on its date sold.
                                </p>
                            </div>

                            <button
                                type="button"
                                class="w-full rounded-2xl bg-white px-5 py-3 text-sm font-bold text-black transition hover:bg-zinc-200 sm:w-auto"
                                @click="setActiveTab('inventory')"
                            >
                                Back to Inventory
                            </button>
                        </div>

                        <div class="mt-6 grid grid-cols-2 gap-3 xl:grid-cols-4">
                            <div
                                v-for="card in warrantyCards"
                                :key="card.label"
                                class="rounded-[1.3rem] border border-white/10 bg-white/[0.03] p-4"
                            >
                                <p class="mn-mini-label">
                                    {{ card.label }}
                                </p>

                                <p
                                    class="mt-2 text-3xl font-semibold tracking-tight"
                                    :class="card.valueClass"
                                >
                                    {{ card.value }}
                                </p>

                                <p class="mt-2 text-xs leading-5 text-zinc-500">
                                    {{ card.helper }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- WARRANTY SEARCH + FILTER -->
                <section
                    class="sticky top-[5.8rem] z-20 hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D]/95 p-4 shadow-2xl shadow-black/30 backdrop-blur sm:p-5 md:block"
                >
                    <div
                        class="grid gap-4 xl:grid-cols-[1fr_auto] xl:items-end"
                    >
                        <div>
                            <p
                                class="mb-3 text-xs uppercase tracking-[0.24em] text-zinc-600"
                            >
                                Search Warranty
                            </p>

                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search buyer, brand, model, reference, serial..."
                                class="mn-input"
                            />
                        </div>

                        <div>
                            <p
                                class="mb-3 text-xs uppercase tracking-[0.24em] text-zinc-600"
                            >
                                Warranty Status
                            </p>

                            <div
                                class="thin-scrollbar flex gap-2 overflow-x-auto rounded-2xl border border-white/10 bg-white/[0.03] p-1"
                            >
                                <button
                                    v-for="tab in warrantyFilterTabs"
                                    :key="tab.value"
                                    type="button"
                                    class="shrink-0 rounded-xl px-4 py-2 text-sm font-bold transition"
                                    :class="
                                        warrantyFilter === tab.value
                                            ? 'bg-white text-black'
                                            : 'text-zinc-500 hover:text-white'
                                    "
                                    @click="warrantyFilter = tab.value"
                                >
                                    {{ tab.label }}
                                    <span class="ml-1 opacity-70">
                                        {{ tab.count }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-4 flex flex-col gap-2 rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-zinc-400 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <span>
                            Showing
                            <strong class="text-white">
                                {{ warrantyFilteredRecords.length }}
                            </strong>
                            of
                            <strong class="text-white">
                                {{ warrantyRecords.length }}
                            </strong>
                            warranty records
                        </span>

                        <button
                            v-if="warrantyFilter !== 'all'"
                            type="button"
                            class="text-xs font-semibold text-white underline underline-offset-4"
                            @click="warrantyFilter = 'all'"
                        >
                            Clear warranty filter
                        </button>
                    </div>
                </section>

                <!-- WARRANTY MOBILE CARDS -->
                <section class="space-y-4 md:hidden">
                    <div
                        v-for="item in warrantyFilteredRecords"
                        :key="item.id"
                        class="overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D]"
                    >
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="mn-mini-label">Buyer</p>

                                    <p
                                        class="mt-2 truncate text-lg font-semibold text-white"
                                    >
                                        {{ item.buyer_name || "No buyer name" }}
                                    </p>

                                    <p class="mt-1 text-xs text-zinc-500">
                                        Sold
                                        {{ formatDate(item.date_sold) || "—" }}
                                    </p>
                                </div>

                                <span
                                    class="shrink-0 rounded-full border px-3 py-1 text-xs font-bold"
                                    :class="warrantyStatusClass(item)"
                                >
                                    {{ warrantyStatusLabel(item) }}
                                </span>
                            </div>

                            <div
                                class="mt-5 rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                            >
                                <p class="text-base font-semibold text-white">
                                    {{ item.brand }} {{ item.model_name }}
                                </p>

                                <div class="mt-3 grid grid-cols-2 gap-3">
                                    <div>
                                        <p
                                            class="text-[11px] uppercase tracking-[0.16em] text-zinc-600"
                                        >
                                            Reference
                                        </p>
                                        <p
                                            class="mt-1 truncate text-sm font-semibold text-zinc-300"
                                        >
                                            {{
                                                item.reference_number ||
                                                "No reference"
                                            }}
                                        </p>
                                    </div>

                                    <div>
                                        <p
                                            class="text-[11px] uppercase tracking-[0.16em] text-zinc-600"
                                        >
                                            Serial
                                        </p>
                                        <p
                                            class="mt-1 truncate text-sm font-semibold text-zinc-300"
                                        >
                                            {{
                                                item.serial_number ||
                                                "No serial"
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <div class="mn-info-box">
                                    <p class="mn-info-label">Warranty Start</p>
                                    <p class="mn-info-value">
                                        {{
                                            formatDate(
                                                item.warranty_start_date ||
                                                    item.date_sold,
                                            ) || "—"
                                        }}
                                    </p>
                                </div>

                                <div class="mn-info-box">
                                    <p class="mn-info-label">Warranty End</p>
                                    <p class="mn-info-value">
                                        {{
                                            formatDate(warrantyEndDate(item)) ||
                                            "—"
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5">
                                <div
                                    class="mb-2 flex items-center justify-between gap-3"
                                >
                                    <p
                                        class="text-xs font-semibold text-zinc-400"
                                    >
                                        {{ warrantyDaysLabel(item) }}
                                    </p>

                                    <p class="text-xs text-zinc-600">
                                        1 year coverage
                                    </p>
                                </div>

                                <div
                                    class="h-2 overflow-hidden rounded-full bg-white/[0.06]"
                                >
                                    <div
                                        class="h-full rounded-full transition-all"
                                        :class="warrantyProgressClass(item)"
                                        :style="{
                                            width: `${warrantyProgress(item)}%`,
                                        }"
                                    ></div>
                                </div>

                                <p class="mt-3 text-xs leading-5 text-zinc-500">
                                    {{ warrantyHelperText(item) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- WARRANTY DESKTOP TABLE -->
                <section
                    class="hidden overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] md:block"
                >
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-white/10">
                            <thead>
                                <tr class="bg-white/[0.02]">
                                    <th class="mn-th">Buyer</th>
                                    <th class="mn-th">Watch Details</th>
                                    <th class="mn-th">Sale Date</th>
                                    <th class="mn-th">Warranty Period</th>
                                    <th class="mn-th">Coverage</th>
                                    <th class="mn-th">Status</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-white/10">
                                <tr
                                    v-for="item in warrantyFilteredRecords"
                                    :key="item.id"
                                    class="transition hover:bg-white/[0.02]"
                                >
                                    <td class="px-6 py-5">
                                        <div class="min-w-[190px]">
                                            <p class="text-xs text-zinc-600">
                                                Buyer Name
                                            </p>

                                            <p
                                                class="mt-1 text-sm font-semibold text-white"
                                            >
                                                {{
                                                    item.buyer_name ||
                                                    "No buyer name"
                                                }}
                                            </p>

                                            <p
                                                v-if="item.sold_price"
                                                class="mt-3 text-xs text-zinc-500"
                                            >
                                                Sold Price:
                                                <span
                                                    class="font-semibold text-zinc-300"
                                                >
                                                    {{ peso(item.sold_price) }}
                                                </span>
                                            </p>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5">
                                        <div class="min-w-[260px]">
                                            <p
                                                class="text-sm font-semibold text-white"
                                            >
                                                {{ item.brand }}
                                                {{ item.model_name }}
                                            </p>

                                            <div
                                                class="mt-3 grid grid-cols-2 gap-3"
                                            >
                                                <div
                                                    class="rounded-2xl border border-white/10 bg-white/[0.03] p-3"
                                                >
                                                    <p
                                                        class="text-[10px] uppercase tracking-[0.16em] text-zinc-600"
                                                    >
                                                        Reference
                                                    </p>
                                                    <p
                                                        class="mt-1 truncate text-xs font-semibold text-zinc-300"
                                                    >
                                                        {{
                                                            item.reference_number ||
                                                            "No reference"
                                                        }}
                                                    </p>
                                                </div>

                                                <div
                                                    class="rounded-2xl border border-white/10 bg-white/[0.03] p-3"
                                                >
                                                    <p
                                                        class="text-[10px] uppercase tracking-[0.16em] text-zinc-600"
                                                    >
                                                        Serial
                                                    </p>
                                                    <p
                                                        class="mt-1 truncate text-xs font-semibold text-zinc-300"
                                                    >
                                                        {{
                                                            item.serial_number ||
                                                            "No serial"
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5">
                                        <div class="min-w-[140px]">
                                            <p class="text-xs text-zinc-600">
                                                Date Sold
                                            </p>

                                            <p
                                                class="mt-1 text-sm font-semibold text-white"
                                            >
                                                {{
                                                    formatDate(
                                                        item.date_sold,
                                                    ) || "—"
                                                }}
                                            </p>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5">
                                        <div class="min-w-[190px] space-y-3">
                                            <div>
                                                <p
                                                    class="text-xs text-zinc-600"
                                                >
                                                    Start
                                                </p>

                                                <p
                                                    class="mt-1 text-sm font-semibold text-white"
                                                >
                                                    {{
                                                        formatDate(
                                                            item.warranty_start_date ||
                                                                item.date_sold,
                                                        ) || "—"
                                                    }}
                                                </p>
                                            </div>

                                            <div>
                                                <p
                                                    class="text-xs text-zinc-600"
                                                >
                                                    End
                                                </p>

                                                <p
                                                    class="mt-1 text-sm font-semibold text-white"
                                                >
                                                    {{
                                                        formatDate(
                                                            warrantyEndDate(
                                                                item,
                                                            ),
                                                        ) || "—"
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5">
                                        <div class="min-w-[210px]">
                                            <div
                                                class="mb-2 flex items-center justify-between gap-3"
                                            >
                                                <p
                                                    class="text-xs font-semibold text-zinc-400"
                                                >
                                                    {{
                                                        warrantyDaysLabel(item)
                                                    }}
                                                </p>

                                                <p
                                                    class="text-xs text-zinc-600"
                                                >
                                                    {{
                                                        Math.round(
                                                            warrantyProgress(
                                                                item,
                                                            ),
                                                        )
                                                    }}%
                                                </p>
                                            </div>

                                            <div
                                                class="h-2 overflow-hidden rounded-full bg-white/[0.06]"
                                            >
                                                <div
                                                    class="h-full rounded-full transition-all"
                                                    :class="
                                                        warrantyProgressClass(
                                                            item,
                                                        )
                                                    "
                                                    :style="{
                                                        width: `${warrantyProgress(
                                                            item,
                                                        )}%`,
                                                    }"
                                                ></div>
                                            </div>

                                            <p
                                                class="mt-3 text-xs leading-5 text-zinc-500"
                                            >
                                                {{ warrantyHelperText(item) }}
                                            </p>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5">
                                        <div class="min-w-[160px]">
                                            <span
                                                class="inline-flex rounded-full border px-3 py-1 text-xs font-bold"
                                                :class="
                                                    warrantyStatusClass(item)
                                                "
                                            >
                                                {{ warrantyStatusLabel(item) }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="!warrantyFilteredRecords.length">
                                    <td
                                        colspan="6"
                                        class="px-6 py-14 text-center"
                                    >
                                        <p
                                            class="text-sm font-semibold text-white"
                                        >
                                            No warranty records found.
                                        </p>

                                        <p class="mt-2 text-sm text-zinc-500">
                                            Try changing the warranty status
                                            filter or search keyword.
                                        </p>

                                        <button
                                            type="button"
                                            class="mt-5 rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200"
                                            @click="warrantyFilter = 'all'"
                                        >
                                            Clear Warranty Filter
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- WARRANTY EMPTY STATE MOBILE -->
                <section
                    v-if="!warrantyFilteredRecords.length"
                    class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-10 text-center md:hidden"
                >
                    <p class="text-sm font-medium text-white">
                        No warranty records found.
                    </p>

                    <p class="mt-2 text-sm text-zinc-500">
                        Try changing the warranty status filter or search
                        keyword.
                    </p>

                    <button
                        type="button"
                        class="mt-5 rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200"
                        @click="warrantyFilter = 'all'"
                    >
                        Clear Warranty Filter
                    </button>
                </section>

                <!-- WARRANTY PAGINATION -->
                <section
                    v-if="warrantyWatches.links?.length > 3"
                    class="thin-scrollbar flex gap-2 overflow-x-auto rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-4 sm:flex-wrap sm:p-5"
                >
                    <Link
                        v-for="link in warrantyWatches.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        v-html="link.label"
                        preserve-scroll
                        preserve-state
                        class="shrink-0 rounded-xl border px-3 py-2 text-sm"
                        :class="[
                            link.active
                                ? 'border-white bg-white text-black'
                                : 'border-white/10 text-zinc-400 hover:border-white/30 hover:text-white',
                            !link.url ? 'pointer-events-none opacity-40' : '',
                        ]"
                    />
                </section>
            </template>
        </div>

        <!-- MOBILE FLOATING CONTROLS TOGGLE -->
        <div
            v-if="showMobileControls && !isArrangeMode"
            class="fixed inset-0 z-[90] bg-black/70 backdrop-blur-sm md:hidden"
            @click="closeMobileControls"
        ></div>

        <div
            v-if="!showMobileControls && !isArrangeMode"
            class="fixed bottom-[calc(env(safe-area-inset-bottom)+5.75rem)] right-4 z-[100] md:hidden"
        >
            <button
                type="button"
                class="group relative flex h-14 w-14 items-center justify-center rounded-full border border-white/35 bg-[#050505] text-white shadow-[0_0_28px_rgba(255,255,255,0.22)] ring-1 ring-white/15 backdrop-blur-xl transition hover:border-white/60 hover:bg-white hover:text-black hover:shadow-[0_0_34px_rgba(255,255,255,0.32)] active:scale-95"
                aria-label="Open mobile controls"
                @click="toggleMobileControls"
            >
                <span
                    class="pointer-events-none absolute inset-[-6px] rounded-full bg-white/10 blur-md"
                ></span>
                <span
                    class="pointer-events-none absolute inset-0 rounded-full border border-white/25"
                ></span>
                <span
                    class="pointer-events-none absolute inset-0 rounded-full border border-white/20 opacity-40 motion-safe:animate-ping"
                ></span>

                <span class="sr-only">Open mobile controls</span>

                <span
                    v-if="hasActiveFilters || activeTab === 'warranty'"
                    class="absolute right-1.5 top-1.5 z-10 h-3 w-3 rounded-full border-2 border-[#050505] bg-white shadow-[0_0_12px_rgba(255,255,255,0.85)]"
                ></span>

                <svg
                    class="relative z-10 h-6 w-6"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                >
                    <path
                        d="M4 7H20"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                    />
                    <path
                        d="M4 12H20"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                    />
                    <path
                        d="M4 17H20"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                    />
                    <circle cx="9" cy="7" r="2" fill="currentColor" />
                    <circle cx="15" cy="12" r="2" fill="currentColor" />
                    <circle cx="11" cy="17" r="2" fill="currentColor" />
                </svg>
            </button>
        </div>

        <div
            v-if="showMobileControls && !isArrangeMode"
            class="fixed inset-x-3 bottom-[calc(env(safe-area-inset-bottom)+5.75rem)] z-[100] md:hidden"
        >
            <section
                class="mx-auto max-h-[74vh] max-w-md overflow-hidden rounded-[1.6rem] border border-white/10 bg-[#050505]/98 shadow-2xl shadow-black/80 backdrop-blur-xl"
                @click.stop
            >
                <div
                    class="flex items-center justify-between gap-3 border-b border-white/10 px-4 py-3"
                >
                    <div class="min-w-0">
                        <p
                            class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500"
                        >
                            Controls
                        </p>

                        <p class="mt-0.5 truncate text-sm font-bold text-white">
                            {{
                                activeTab === "inventory"
                                    ? filterStateLabel
                                    : warrantyFilteredRecords.length +
                                      " warranty records shown"
                            }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/[0.04] text-white transition active:scale-95"
                        aria-label="Close mobile controls"
                        @click="closeMobileControls"
                    >
                        <span class="sr-only">Close mobile controls</span>
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                            aria-hidden="true"
                        >
                            <path
                                d="M6 6L18 18M18 6L6 18"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                            />
                        </svg>
                    </button>
                </div>

                <div
                    class="max-h-[calc(74vh-4.25rem)] overflow-y-auto p-3 thin-scrollbar"
                >
                    <div
                        class="grid grid-cols-2 gap-2 rounded-2xl border border-white/10 bg-white/[0.03] p-1"
                    >
                        <button
                            type="button"
                            class="rounded-xl px-4 py-3 text-sm font-black transition"
                            :class="
                                activeTab === 'inventory'
                                    ? 'bg-white text-black'
                                    : 'text-zinc-500 hover:text-white'
                            "
                            @click="setActiveTab('inventory')"
                        >
                            Inventory
                        </button>

                        <button
                            type="button"
                            class="rounded-xl px-4 py-3 text-sm font-black transition"
                            :class="
                                activeTab === 'warranty'
                                    ? 'bg-white text-black'
                                    : 'text-zinc-500 hover:text-white'
                            "
                            @click="setActiveTab('warranty')"
                        >
                            Warranty
                        </button>
                    </div>

                    <template v-if="activeTab === 'inventory'">
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                class="rounded-2xl border border-white/10 bg-white/[0.04] px-3 py-3 text-xs font-black text-white transition active:scale-[0.98]"
                                @click="startBulkMode"
                            >
                                Bulk Select
                            </button>

                            <button
                                type="button"
                                class="rounded-2xl border border-white/10 bg-white/[0.04] px-3 py-3 text-xs font-black text-white transition active:scale-[0.98]"
                                @click="enterArrangeMode"
                            >
                                Arrange
                            </button>

                            <button
                                type="button"
                                class="rounded-2xl border border-amber-500/20 bg-amber-500/10 px-3 py-3 text-xs font-black text-amber-200 transition active:scale-[0.98]"
                                @click="setActionFilter('needs_push')"
                            >
                                Needs Push
                            </button>
                        </div>

                        <div class="mt-4">
                            <p class="mn-mobile-panel-label">Search</p>

                            <div class="relative mt-2">
                                <svg
                                    class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.8"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m21 21-4.35-4.35m1.6-5.4a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"
                                    />
                                </svg>

                                <input
                                    v-model="search"
                                    type="search"
                                    inputmode="search"
                                    autocomplete="off"
                                    placeholder="Search brand, model, ref..."
                                    class="w-full rounded-xl border border-white/10 bg-white/[0.04] py-3 pl-9 pr-16 text-sm font-medium text-white outline-none transition placeholder:text-zinc-600 focus:border-white/35 focus:bg-white/[0.06]"
                                />

                                <button
                                    v-if="String(search || '').trim()"
                                    type="button"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg border border-white/10 bg-black/60 px-2 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-zinc-300 transition active:scale-[0.98]"
                                    @click="search = ''"
                                >
                                    Clear
                                </button>

                                <div
                                    v-else-if="isSearchPending || isFiltering"
                                    class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 rounded-lg border border-white/10 bg-black/60 px-2 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-zinc-400"
                                >
                                    {{ isSearchPending ? "Typing" : "Sync" }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <p class="mn-mobile-panel-label">View</p>

                            <div
                                class="mt-2 grid grid-cols-2 rounded-2xl border border-white/10 bg-white/[0.03] p-1"
                            >
                                <button
                                    type="button"
                                    class="rounded-xl px-4 py-2.5 text-sm font-black transition"
                                    :class="
                                        viewMode === 'table'
                                            ? 'bg-white text-black'
                                            : 'text-zinc-500 hover:text-white'
                                    "
                                    @click="setViewMode('table')"
                                >
                                    List
                                </button>

                                <button
                                    type="button"
                                    class="rounded-xl px-4 py-2.5 text-sm font-black transition"
                                    :class="
                                        viewMode === 'gallery'
                                            ? 'bg-white text-black'
                                            : 'text-zinc-500 hover:text-white'
                                    "
                                    @click="setViewMode('gallery')"
                                >
                                    Gallery
                                </button>
                            </div>
                        </div>

                        <div class="mt-4">
                            <p class="mn-mobile-panel-label">Status</p>

                            <div class="mt-2 grid grid-cols-2 gap-2">
                                <button
                                    v-for="tab in statusTabs"
                                    :key="tab.value || 'all-mobile'"
                                    type="button"
                                    class="rounded-xl border px-3 py-2.5 text-xs font-black transition active:scale-[0.98]"
                                    :class="
                                        status === tab.value &&
                                        actionFilter === 'all'
                                            ? 'border-white bg-white text-black shadow-lg shadow-white/10'
                                            : 'border-white/10 bg-white/[0.04] text-zinc-400'
                                    "
                                    @click="setStatusFilter(tab.value)"
                                >
                                    <span>{{ tab.label }}</span>
                                    <span
                                        v-if="tab.count !== null"
                                        class="ml-1 opacity-60"
                                    >
                                        {{ tab.count }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div class="mt-4">
                            <p class="mn-mobile-panel-label">Quick Filters</p>

                            <div class="mt-2 grid grid-cols-2 gap-2">
                                <button
                                    v-for="filter in quickActionFilters"
                                    :key="filter.value"
                                    type="button"
                                    class="rounded-xl border px-3 py-2.5 text-xs font-black transition active:scale-[0.98]"
                                    :class="
                                        actionFilter === filter.value
                                            ? 'border-white bg-white text-black shadow-lg shadow-white/10'
                                            : 'border-white/10 bg-white/[0.04] text-zinc-400'
                                    "
                                    @click="setActionFilter(filter.value)"
                                >
                                    <span>{{ filter.label }}</span>
                                    <span class="ml-1 opacity-60">{{
                                        filter.count
                                    }}</span>
                                </button>
                            </div>
                        </div>

                        <button
                            v-if="hasActiveFilters"
                            type="button"
                            class="mt-4 w-full rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3 text-sm font-black text-white transition active:scale-[0.98]"
                            @click="
                                search = '';
                                setStatusFilter('');
                            "
                        >
                            Clear All Filters
                        </button>
                    </template>

                    <template v-else>
                        <div class="mt-4">
                            <p class="mn-mobile-panel-label">Search Warranty</p>

                            <input
                                v-model="search"
                                type="search"
                                inputmode="search"
                                autocomplete="off"
                                placeholder="Search buyer, brand, model, ref..."
                                class="mt-2 w-full rounded-xl border border-white/10 bg-white/[0.04] px-4 py-3 text-sm font-medium text-white outline-none transition placeholder:text-zinc-600 focus:border-white/35 focus:bg-white/[0.06]"
                            />
                        </div>

                        <div class="mt-4">
                            <p class="mn-mobile-panel-label">Warranty Status</p>

                            <div class="mt-2 grid grid-cols-2 gap-2">
                                <button
                                    v-for="tab in warrantyFilterTabs"
                                    :key="tab.value"
                                    type="button"
                                    class="rounded-xl border px-3 py-2.5 text-xs font-black transition active:scale-[0.98]"
                                    :class="
                                        warrantyFilter === tab.value
                                            ? 'border-white bg-white text-black shadow-lg shadow-white/10'
                                            : 'border-white/10 bg-white/[0.04] text-zinc-400'
                                    "
                                    @click="warrantyFilter = tab.value"
                                >
                                    <span>{{ tab.label }}</span>
                                    <span class="ml-1 opacity-60">{{
                                        tab.count
                                    }}</span>
                                </button>
                            </div>
                        </div>

                        <button
                            v-if="
                                warrantyFilter !== 'all' ||
                                String(search || '').trim()
                            "
                            type="button"
                            class="mt-4 w-full rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3 text-sm font-black text-white transition active:scale-[0.98]"
                            @click="
                                warrantyFilter = 'all';
                                search = '';
                            "
                        >
                            Clear Warranty Filters
                        </button>
                    </template>
                </div>
            </section>
        </div>

        <CreateWatchModal
            :show="showCreateModal"
            :duplicate-source="duplicateSource"
            @close="closeCreateModal"
        />

        <EditWatchModal
            v-if="showEditModal && selectedWatch"
            :key="`edit-watch-${selectedWatch.id}-${editModalKey}`"
            :show="showEditModal"
            :watch="selectedWatch"
            @close="closeEditModal"
        />

        <DeleteWatchModal
            :show="showDeleteModal"
            :watch="selectedWatch"
            @close="closeDeleteModal"
        />

        <MarkSoldModal
            :show="showMarkSoldModal"
            :watch="selectedWatch"
            @close="closeMarkSoldModal"
        />

        <ReserveWatchModal
            :show="showReserveModal"
            :watch="selectedWatch"
            @close="closeReserveModal"
        />
    </AuthenticatedLayout>
</template>

<style scoped>
.mn-input {
    width: 100%;
    border-radius: 1rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: #050505;
    padding: 0.85rem 1rem;
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

.mn-mini-label {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.16em;
    color: rgb(113 113 122);
}

.mn-mobile-panel-label {
    font-size: 0.68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    color: rgb(113 113 122);
}

.mn-info-box {
    border-radius: 1rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: rgb(255 255 255 / 0.03);
    padding: 0.9rem;
}

.mn-info-label {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    color: rgb(113 113 122);
}

.mn-info-value {
    margin-top: 0.35rem;
    font-size: 0.8rem;
    font-weight: 700;
    color: white;
}

.mn-action-btn {
    border-radius: 0.85rem;
    border-width: 1px;
    padding: 0.65rem 0.85rem;
    font-size: 0.75rem;
    font-weight: 700;
    transition:
        border-color 150ms ease,
        background-color 150ms ease,
        color 150ms ease;
}

.mn-arrange-btn {
    border-radius: 0.9rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: rgb(255 255 255 / 0.03);
    padding: 0.75rem 0.85rem;
    font-size: 0.75rem;
    font-weight: 800;
    color: rgb(212 212 216);
    transition:
        transform 150ms ease,
        border-color 150ms ease,
        background-color 150ms ease,
        color 150ms ease;
}

.mn-arrange-btn:hover {
    transform: translateY(-1px);
    border-color: rgb(255 255 255 / 0.3);
    background: rgb(255 255 255 / 0.06);
    color: white;
}

.mn-arrange-btn:disabled {
    cursor: not-allowed;
    transform: none;
    opacity: 0.35;
}

.mn-th {
    padding: 1rem 1.5rem;
    text-align: left;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.22em;
    color: rgb(82 82 91);
}

.thin-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: rgb(255 255 255 / 0.2) transparent;
}

.thin-scrollbar::-webkit-scrollbar {
    height: 5px;
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

.mn-mobile-swipe-row {
    scroll-snap-type: x mandatory;
    scroll-padding-left: 1rem;
    -webkit-overflow-scrolling: touch;
}

.mn-mobile-swipe-card {
    scroll-snap-align: start;
}

@keyframes mn-filter-bar {
    0% {
        transform: translateX(-110%);
    }

    50% {
        transform: translateX(60%);
    }

    100% {
        transform: translateX(220%);
    }
}
</style>
