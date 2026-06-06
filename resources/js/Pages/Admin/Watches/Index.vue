<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import CreateWatchModal from "./CreateWatchModal.vue";
import EditWatchModal from "./EditWatchModal.vue";
import DeleteWatchModal from "./DeleteWatchModal.vue";
import MarkSoldModal from "./MarkSoldModal.vue";
import ReserveWatchModal from "./ReserveWatchModal.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, onMounted, ref, watch } from "vue";

const props = defineProps({
    watches: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
        }),
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

let timeout = null;

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

const setActionFilter = (value) => {
    actionFilter.value = value;
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
        value: currentPageWatches.value.filter((watch) => isSlowMoving(watch))
            .length,
        helper: "Slow moving or dead stock",
        filter: "needs_push",
        className: "border-amber-500/20 bg-amber-500/10 text-amber-300",
    },
    {
        label: "No Photo",
        value: currentPageWatches.value.filter((watch) => hasNoPhoto(watch))
            .length,
        helper: "Needs product images",
        filter: "no_photo",
        className: "border-red-500/20 bg-red-500/10 text-red-300",
    },
    {
        label: "Low Margin",
        value: currentPageWatches.value.filter((watch) => isLowMargin(watch))
            .length,
        helper: "Review price or cost",
        filter: "low_margin",
        className: "border-red-500/20 bg-red-500/10 text-red-300",
    },
    {
        label: "Ready to Post",
        value: currentPageWatches.value.filter((watch) => isReadyToPost(watch))
            .length,
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
        count: visibleWatchesCount.value,
    },
    {
        label: "Needs Push",
        value: "needs_push",
        count: currentPageWatches.value.filter((watch) => isSlowMoving(watch))
            .length,
    },
    {
        label: "No Photo",
        value: "no_photo",
        count: currentPageWatches.value.filter((watch) => hasNoPhoto(watch))
            .length,
    },
    {
        label: "Low Margin",
        value: "low_margin",
        count: currentPageWatches.value.filter((watch) => isLowMargin(watch))
            .length,
    },
    {
        label: "Overdue",
        value: "reservation_overdue",
        count: currentPageWatches.value.filter((watch) =>
            isReservationOverdue(watch),
        ).length,
    },
    {
        label: "Ready",
        value: "ready_to_post",
        count: currentPageWatches.value.filter((watch) => isReadyToPost(watch))
            .length,
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

onMounted(() => {
    const params = new URLSearchParams(window.location.search);

    if (params.get("create") === "1") {
        showCreateModal.value = true;
    }
});

watch([search, status], () => {
    clearTimeout(timeout);
    actionFilter.value = "all";

    timeout = setTimeout(() => {
        router.get(
            route("admin.watches.index"),
            {
                search: search.value,
                status: status.value,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, 350);
});

const setStatusFilter = (value) => {
    status.value = value;
};

const setActiveTab = (tab) => {
    activeTab.value = tab;

    if (typeof window !== "undefined") {
        localStorage.setItem("watch_admin_active_tab", tab);
    }

    if (tab === "warranty") {
        actionFilter.value = "all";
    }
};

const setViewMode = (mode) => {
    viewMode.value = mode;

    if (typeof window !== "undefined") {
        localStorage.setItem("watch_stock_view", mode);
    }
};

const openCreateModal = () => {
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;

    router.reload({
        only: ["watches", "warrantyWatches", "summary"],
        preserveScroll: true,
    });
};

const openEditModal = (watch) => {
    selectedWatch.value = watch;
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    selectedWatch.value = null;

    router.reload({
        only: ["watches", "warrantyWatches", "summary"],
        preserveScroll: true,
    });
};

const openDeleteModal = (watch) => {
    selectedWatch.value = watch;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedWatch.value = null;

    router.reload({
        only: ["watches", "warrantyWatches", "summary"],
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
        only: ["watches", "warrantyWatches", "summary"],
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
        only: ["watches", "warrantyWatches", "summary"],
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
                    only: ["watches", "warrantyWatches", "summary"],
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
            <!-- MOBILE QUICK ACTION -->
            <section
                v-if="activeTab === 'inventory'"
                class="grid grid-cols-2 gap-3 sm:hidden"
            >
                <button
                    type="button"
                    class="rounded-2xl bg-white px-4 py-3 text-sm font-bold text-black"
                    @click="openCreateModal"
                >
                    Add Watch
                </button>

                <button
                    type="button"
                    class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm font-bold text-white"
                    @click="setActionFilter('needs_push')"
                >
                    Needs Push
                </button>
            </section>

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

                    <button
                        v-if="activeTab === 'inventory'"
                        type="button"
                        class="hidden rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200 sm:inline-flex"
                        @click="openCreateModal"
                    >
                        Add Watch
                    </button>
                </div>
            </section>

            <!-- MAIN TABS -->
            <section
                class="sticky top-3 z-30 rounded-[1.7rem] border border-white/10 bg-[#0B0B0D]/95 p-3 shadow-2xl shadow-black/30 backdrop-blur"
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

                <!-- ACTION NEEDED -->
                <section
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
                    class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-5 sm:p-6"
                >
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

                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Search brand, model, reference, buyer, serial..."
                                    class="mn-input"
                                />
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
                            class="flex flex-col gap-2 rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-zinc-400 sm:flex-row sm:items-center sm:justify-between"
                        >
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

                            <button
                                v-if="actionFilter !== 'all'"
                                type="button"
                                class="text-xs font-semibold text-white underline underline-offset-4"
                                @click="setActionFilter('all')"
                            >
                                Clear action filter
                            </button>
                        </div>
                    </div>
                </section>

                <!-- GALLERY VIEW -->
                <section
                    v-if="viewMode === 'gallery'"
                    class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
                >
                    <div
                        v-for="watch in displayedWatches"
                        :key="watch.id"
                        class="overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] transition hover:border-white/20"
                    >
                        <div class="relative aspect-[4/5] bg-[#050505]">
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
                                    class="mn-action-btn border-red-500/20 text-red-300 hover:bg-red-500/10"
                                    @click="openDeleteModal(watch)"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- LIST VIEW: MOBILE CARDS -->
                <section
                    v-if="viewMode === 'table'"
                    class="space-y-4 md:hidden"
                >
                    <div
                        v-for="watch in displayedWatches"
                        :key="watch.id"
                        class="overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D]"
                    >
                        <div class="flex gap-4 p-4">
                            <div
                                class="relative h-24 w-24 shrink-0 overflow-hidden rounded-2xl border border-white/10 bg-[#050505]"
                            >
                                <img
                                    v-if="watch.primary_image"
                                    :src="watch.primary_image.image_url"
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
                                    {{ watch.images_count || 0 }}
                                </div>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="truncate text-sm font-semibold text-white"
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

                                    <span
                                        class="shrink-0 rounded-full border px-2.5 py-1 text-[11px] capitalize"
                                        :class="statusClass(watch.status)"
                                    >
                                        {{ watch.status }}
                                    </span>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span
                                        class="rounded-full border px-2.5 py-1 text-[11px]"
                                        :class="stockAgeClass(watch)"
                                    >
                                        {{ stockAgeStage(watch) }}
                                    </span>

                                    <span
                                        class="rounded-full border px-2.5 py-1 text-[11px]"
                                        :class="profitBadgeClass(watch)"
                                    >
                                        {{ profitBadgeLabel(watch) }}
                                    </span>
                                </div>

                                <p class="mt-3 text-xs leading-5 text-zinc-500">
                                    {{ stockAgeLabel(watch) }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="grid grid-cols-3 gap-2 border-y border-white/10 p-4"
                        >
                            <div>
                                <p class="mn-info-label">Price</p>
                                <p class="mn-info-value">
                                    {{ compactPeso(displayPrice(watch)) }}
                                </p>
                            </div>

                            <div>
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

                            <div>
                                <p class="mn-info-label">Margin</p>
                                <p class="mn-info-value">
                                    {{ profitMargin(watch).toFixed(1) }}%
                                </p>
                            </div>
                        </div>

                        <div class="border-b border-white/10 p-4">
                            <div
                                class="rounded-2xl border p-4"
                                :class="recommendedAction(watch).className"
                            >
                                <p
                                    class="text-xs font-semibold uppercase tracking-[0.18em]"
                                >
                                    {{ recommendedAction(watch).label }}
                                </p>

                                <p class="mt-1 text-xs opacity-80">
                                    {{ recommendedAction(watch).helper }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 p-4">
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
                                v-if="watch.status === 'reserved'"
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
                                @click="openMarkSoldModal(watch)"
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
                                class="mn-action-btn border-red-500/20 text-red-300"
                                @click="openDeleteModal(watch)"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </section>

                <!-- LIST VIEW: DESKTOP TABLE -->
                <section
                    v-if="viewMode === 'table'"
                    class="hidden overflow-hidden rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] md:block"
                >
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-white/10">
                            <thead>
                                <tr class="bg-white/[0.02]">
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
                                >
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
                    v-if="!displayedWatches.length"
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
                    v-if="watches.links?.length > 3"
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
                    class="sticky top-[5.8rem] z-20 rounded-[1.7rem] border border-white/10 bg-[#0B0B0D]/95 p-4 shadow-2xl shadow-black/30 backdrop-blur sm:p-5"
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

        <!-- MOBILE STICKY FILTER DOCK -->
        <div
            v-if="activeTab === 'inventory'"
            class="fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-[#050505]/95 px-3 pb-[calc(env(safe-area-inset-bottom)+0.75rem)] pt-3 backdrop-blur-xl md:hidden"
        >
            <div class="mx-auto max-w-md">
                <div class="mb-2 flex items-center justify-between gap-3 px-1">
                    <p
                        class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500"
                    >
                        Quick Filter
                    </p>

                    <p class="text-[10px] font-semibold text-zinc-500">
                        {{ displayedWatches.length }} shown
                    </p>
                </div>

                <div class="grid grid-cols-4 gap-2">
                    <button
                        type="button"
                        class="rounded-xl border px-2 py-2.5 text-xs font-bold transition active:scale-[0.98]"
                        :class="
                            status === '' && actionFilter === 'all'
                                ? 'border-white bg-white text-black'
                                : 'border-white/10 bg-white/[0.04] text-zinc-400'
                        "
                        @click="
                            status = '';
                            actionFilter = 'all';
                        "
                    >
                        <span class="block">All</span>
                        <span class="mt-0.5 block text-[10px] opacity-60">
                            {{ currentPageWatches.length }}
                        </span>
                    </button>

                    <button
                        type="button"
                        class="rounded-xl border px-2 py-2.5 text-xs font-bold transition active:scale-[0.98]"
                        :class="
                            status === '' && actionFilter === 'visible'
                                ? 'border-white bg-white text-black'
                                : 'border-white/10 bg-white/[0.04] text-zinc-400'
                        "
                        @click="
                            status = '';
                            actionFilter = 'visible';
                        "
                    >
                        <span class="block">Visible</span>
                        <span class="mt-0.5 block text-[10px] opacity-60">
                            {{ visibleWatchesCount }}
                        </span>
                    </button>

                    <button
                        type="button"
                        class="rounded-xl border px-2 py-2.5 text-xs font-bold transition active:scale-[0.98]"
                        :class="
                            status === 'available' && actionFilter === 'all'
                                ? 'border-white bg-white text-black'
                                : 'border-white/10 bg-white/[0.04] text-zinc-400'
                        "
                        @click="setStatusFilter('available')"
                    >
                        <span class="block">Available</span>
                        <span class="mt-0.5 block text-[10px] opacity-60">
                            {{ props.summary.available_watches }}
                        </span>
                    </button>

                    <button
                        type="button"
                        class="rounded-xl border px-2 py-2.5 text-xs font-bold transition active:scale-[0.98]"
                        :class="
                            status === 'sold' && actionFilter === 'all'
                                ? 'border-white bg-white text-black'
                                : 'border-white/10 bg-white/[0.04] text-zinc-400'
                        "
                        @click="setStatusFilter('sold')"
                    >
                        <span class="block">Sold</span>
                        <span class="mt-0.5 block text-[10px] opacity-60">
                            {{ props.summary.sold_watches }}
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <CreateWatchModal :show="showCreateModal" @close="closeCreateModal" />

        <EditWatchModal
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
</style>
