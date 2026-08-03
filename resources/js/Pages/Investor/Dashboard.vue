<script setup>
import { computed, onMounted, ref, watch } from "vue";

import VueApexCharts from "vue3-apexcharts";
import Swal from "sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";

import { Head, router, useForm, usePage } from "@inertiajs/vue3";

const props = defineProps({
    permissions: {
        type: Object,
        required: true,
    },

    settings: {
        type: Object,
        required: true,
    },

    summary: {
        type: Object,
        required: true,
    },

    waterfall: {
        type: Object,
        required: true,
    },

    analytics: {
        type: Object,
        required: true,
    },

    investors: {
        type: Array,
        default: () => [],
    },

    watches: {
        type: Array,
        default: () => [],
    },

    expenses: {
        type: Array,
        default: () => [],
    },

    startDate: {
        type: String,
        required: true,
    },
});

const page = usePage();

const activeTab = ref("overview");
const editingInvestorId = ref(null);

const selectedAnalyticsYear = ref(
    String(props.analytics?.selected_year ?? new Date().getFullYear()),
);

watch(
    () => props.analytics?.selected_year,
    (year) => {
        if (year !== undefined && year !== null) {
            selectedAnalyticsYear.value = String(year);
        }
    },
);

const currentUser = computed(() => {
    return page.props.auth?.user ?? null;
});

const successMessage = computed(() => {
    return page.props.flash?.success ?? null;
});

const errorMessage = computed(() => {
    return page.props.flash?.error ?? null;
});

const swalClasses = {
    popup: "mn-swal-popup",
    title: "mn-swal-title",
    htmlContainer: "mn-swal-text",
    confirmButton: "mn-swal-confirm",
    cancelButton: "mn-swal-cancel",
};

const showToast = (icon, title) => {
    Swal.fire({
        toast: true,
        position: "top-end",
        icon,
        title,
        showConfirmButton: false,
        timer: 2600,
        timerProgressBar: true,
        background: "#ffffff",
        color: "#0f172a",
        customClass: {
            popup: "mn-swal-toast",
        },
    });
};

const showRequestError = (errors, fallbackMessage) => {
    const firstError = Object.values(errors ?? {})[0] ?? fallbackMessage;

    showToast("error", Array.isArray(firstError) ? firstError[0] : firstError);
};

watch(successMessage, (message) => {
    if (message) {
        showToast("success", message);
    }
});

watch(errorMessage, (message) => {
    if (message) {
        showToast("error", message);
    }
});

onMounted(() => {
    if (successMessage.value) {
        showToast("success", successMessage.value);
    }

    if (errorMessage.value) {
        showToast("error", errorMessage.value);
    }
});

const settingsForm = useForm({
    brand_cut_percentage: props.settings.brand_cut_percentage ?? 50,
});

const investorForm = useForm({
    name: "",
    capital_amount: "",
});

const editInvestorForm = useForm({
    name: "",
    capital_amount: "",
});

const tabs = computed(() => [
    {
        key: "overview",
        label: "Overview",
        description: "Fund snapshot",
    },
    {
        key: "analytics",
        label: "Analytics",
        description: "Monthly and yearly trends",
    },
    {
        key: "investors",
        label: "Investors",
        description: "Capital partners",
        count: props.investors.length,
    },
    {
        key: "watches",
        label: "Watches",
        description: "Inventory and sales",
        count: props.watches.length,
    },
    {
        key: "expenses",
        label: "Expenses",
        description: "Operating costs",
        count: props.expenses.length,
    },
]);

const monthlyRecords = computed(() => {
    return props.analytics?.monthly ?? [];
});

const yearlyRecords = computed(() => {
    return props.analytics?.yearly ?? [];
});

const hasMonthlyActivity = computed(() => {
    if (props.analytics?.has_activity !== undefined) {
        return Boolean(props.analytics.has_activity);
    }

    return monthlyRecords.value.some(
        (record) =>
            Number(record.sales ?? 0) !== 0 ||
            Number(record.expenses ?? 0) !== 0 ||
            Number(record.gross_profit ?? 0) !== 0 ||
            Number(record.net_profit ?? 0) !== 0,
    );
});

const submitSettings = async () => {
    if (!props.permissions.can_edit) {
        return;
    }

    const percentage = Number(settingsForm.brand_cut_percentage ?? 0);

    const result = await Swal.fire({
        icon: "question",
        title: "Update Brand Cut?",
        html: `
            <p>
                Montre Nova will receive
                <strong>${percentage}%</strong>
                of every positive Net Profit.
            </p>
        `,
        showCancelButton: true,
        confirmButtonText: "Yes, update",
        cancelButtonText: "Cancel",
        reverseButtons: true,
        background: "#ffffff",
        color: "#0f172a",
        customClass: swalClasses,
        buttonsStyling: false,
    });

    if (!result.isConfirmed) {
        return;
    }

    settingsForm.put(route("investor.settings.update"), {
        preserveScroll: true,

        onSuccess: () => {
            settingsForm.clearErrors();
        },

        onError: (errors) => {
            showRequestError(errors, "Unable to update the Brand Cut.");
        },
    });
};

const addInvestor = () => {
    if (!props.permissions.can_manage_investors) {
        return;
    }

    investorForm.post(route("investor.participants.store"), {
        preserveScroll: true,

        onSuccess: () => {
            investorForm.reset();
            investorForm.clearErrors();
        },

        onError: (errors) => {
            showRequestError(errors, "Unable to add the investor.");
        },
    });
};

const beginEditInvestor = (investor) => {
    editingInvestorId.value = investor.id;

    editInvestorForm.name = investor.name;

    editInvestorForm.capital_amount = investor.capital_amount;

    editInvestorForm.clearErrors();
};

const cancelEditInvestor = () => {
    editingInvestorId.value = null;

    editInvestorForm.reset();
    editInvestorForm.clearErrors();
};

const updateInvestor = (investor) => {
    if (!props.permissions.can_manage_investors) {
        return;
    }

    editInvestorForm.patch(route("investor.participants.update", investor.id), {
        preserveScroll: true,

        onSuccess: () => {
            cancelEditInvestor();
        },

        onError: (errors) => {
            showRequestError(errors, "Unable to update the investor.");
        },
    });
};

const escapeHtml = (value) => {
    const element = document.createElement("div");

    element.textContent = String(value ?? "");

    return element.innerHTML;
};

const deleteInvestor = async (investor) => {
    if (!props.permissions.can_manage_investors) {
        return;
    }

    const result = await Swal.fire({
        icon: "warning",
        title: "Remove Investor?",
        html: `
            <p>
                You are about to remove
                <strong>${escapeHtml(investor.name)}</strong>.
            </p>
            <p class="mn-swal-note">
                Their capital will no longer be included
                in the profit allocation.
            </p>
        `,
        showCancelButton: true,
        confirmButtonText: "Remove investor",
        cancelButtonText: "Keep investor",
        reverseButtons: true,
        background: "#ffffff",
        color: "#0f172a",
        customClass: {
            ...swalClasses,
            confirmButton: "mn-swal-confirm mn-swal-danger",
        },
        buttonsStyling: false,
    });

    if (!result.isConfirmed) {
        return;
    }

    router.delete(route("investor.participants.destroy", investor.id), {
        preserveScroll: true,

        onError: () => {
            showToast("error", "Unable to remove the investor.");
        },
    });
};

const changeAnalyticsYear = () => {
    router.get(
        route("investor.dashboard"),
        {
            year: selectedAnalyticsYear.value,
        },
        {
            only: ["analytics"],

            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const logout = async () => {
    const result = await Swal.fire({
        icon: "question",
        title: "Log out?",
        text: "You will need to sign in again to access this dashboard.",
        showCancelButton: true,
        confirmButtonText: "Log out",
        cancelButtonText: "Stay signed in",
        reverseButtons: true,
        background: "#ffffff",
        color: "#0f172a",
        customClass: swalClasses,
        buttonsStyling: false,
    });

    if (!result.isConfirmed) {
        return;
    }

    router.post(route("logout"));
};

const formatCurrency = (value) => {
    const numericValue = Number(value ?? 0);

    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number.isFinite(numericValue) ? numericValue : 0);
};

const formatCompactCurrency = (value) => {
    const numericValue = Number(value ?? 0);

    return new Intl.NumberFormat("en-PH", {
        notation: "compact",
        compactDisplay: "short",
        maximumFractionDigits: 1,
    }).format(Number.isFinite(numericValue) ? numericValue : 0);
};

const formatPercentage = (value) => {
    const numericValue = Number(value ?? 0);

    return `${numericValue.toLocaleString("en-PH", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 4,
    })}%`;
};

const formatDate = (value) => {
    if (!value) {
        return "—";
    }

    const normalizedValue =
        typeof value === "string" ? value.replace(" ", "T") : value;

    const date = new Date(normalizedValue);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat("en-PH", {
        year: "numeric",
        month: "short",
        day: "2-digit",
    }).format(date);
};

const amountClass = (value) => {
    const amount = Number(value ?? 0);

    if (amount < 0) {
        return "text-red-600";
    }

    if (amount > 0) {
        return "text-emerald-700";
    }

    return "text-slate-950";
};

const statusLabel = (status) => {
    const labels = {
        draft: "Draft",
        available: "Available",
        reserved: "Reserved",
        sold: "Sold",
        hidden: "Hidden",
    };

    return labels[status] ?? status ?? "Unknown";
};

const statusClass = (status) => {
    const classes = {
        draft: "border-slate-200 bg-slate-100 text-slate-700",

        available: "border-emerald-200 bg-emerald-50 text-emerald-700",

        reserved: "border-amber-200 bg-amber-50 text-amber-700",

        sold: "border-indigo-200 bg-indigo-50 text-indigo-700",

        hidden: "border-zinc-200 bg-zinc-100 text-zinc-600",
    };

    return classes[status] ?? classes.draft;
};

const imageUrl = (image) => {
    const path =
        image?.thumbnail_path ?? image?.image_path ?? image?.hd_path ?? null;

    if (!path) {
        return null;
    }

    if (path.startsWith("http://") || path.startsWith("https://")) {
        return path;
    }

    if (path.startsWith("/storage/")) {
        return path;
    }

    const cleanPath = path.replace(/^storage\//, "").replace(/^\/+/, "");

    return `/storage/${cleanPath}`;
};

/*
|--------------------------------------------------------------------------
| Monthly Chart
|--------------------------------------------------------------------------
*/

const monthlyChartSeries = computed(() => [
    {
        name: "Gross Profit",

        data: monthlyRecords.value.map((record) =>
            Number(record.gross_profit ?? 0),
        ),
    },
    {
        name: "Expenses",

        data: monthlyRecords.value.map((record) =>
            Number(record.expenses ?? 0),
        ),
    },
    {
        name: "Net Profit",

        data: monthlyRecords.value.map((record) =>
            Number(record.net_profit ?? 0),
        ),
    },
]);

const monthlyChartOptions = computed(() => ({
    chart: {
        id: `monthly-performance-${props.analytics?.selected_year ?? "year"}`,
        type: "area",
        fontFamily: "inherit",
        background: "transparent",
        redrawOnParentResize: true,
        redrawOnWindowResize: true,

        toolbar: {
            show: false,
        },

        zoom: {
            enabled: false,
        },

        animations: {
            enabled: true,
            easing: "easeinout",
            speed: 500,
        },
    },

    colors: ["#6366f1", "#f43f5e", "#10b981"],

    dataLabels: {
        enabled: false,
    },

    stroke: {
        curve: "smooth",
        width: [2, 2, 4],
    },

    fill: {
        type: "gradient",

        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.28,
            opacityTo: 0.02,
            stops: [0, 95, 100],
        },
    },

    markers: {
        size: 4,

        hover: {
            size: 6,
        },
    },

    grid: {
        borderColor: "#e7eaf0",
        strokeDashArray: 4,

        padding: {
            left: 8,
            right: 8,
        },
    },

    legend: {
        show: true,
        position: "top",
        horizontalAlign: "left",

        labels: {
            colors: "#94a3b8",
        },
    },

    xaxis: {
        categories: monthlyRecords.value.map((record) => record.month_short),

        axisBorder: {
            show: false,
        },

        axisTicks: {
            show: false,
        },

        labels: {
            style: {
                colors: "#64748b",
                fontSize: "12px",
            },
        },
    },

    yaxis: {
        labels: {
            formatter: (value) => `₱${formatCompactCurrency(value)}`,

            style: {
                colors: "#64748b",
                fontSize: "12px",
            },
        },
    },

    tooltip: {
        theme: "light",
        shared: true,
        intersect: false,

        y: {
            formatter: (value) => formatCurrency(value),
        },
    },

    responsive: [
        {
            breakpoint: 640,
            options: {
                chart: {
                    height: 300,
                },
                legend: {
                    position: "bottom",
                    horizontalAlign: "center",
                    fontSize: "11px",
                    itemMargin: {
                        horizontal: 6,
                        vertical: 3,
                    },
                },
                markers: {
                    size: 2,
                },
                stroke: {
                    width: [2, 2, 3],
                },
                grid: {
                    padding: {
                        left: 0,
                        right: 0,
                    },
                },
                xaxis: {
                    labels: {
                        rotate: -45,
                        style: {
                            fontSize: "10px",
                        },
                    },
                },
                yaxis: {
                    labels: {
                        style: {
                            fontSize: "10px",
                        },
                    },
                },
            },
        },
    ],

    noData: {
        text: "No monthly financial data available.",
    },
}));

/*
|--------------------------------------------------------------------------
| Yearly Chart
|--------------------------------------------------------------------------
*/

const yearlyChartSeries = computed(() => [
    {
        name: "Net Profit",

        data: yearlyRecords.value.map((record) =>
            Number(record.net_profit ?? 0),
        ),
    },
    {
        name: "Expenses",

        data: yearlyRecords.value.map((record) => Number(record.expenses ?? 0)),
    },
]);

const yearlyChartOptions = computed(() => ({
    chart: {
        type: "bar",
        fontFamily: "inherit",
        background: "transparent",

        foreColor: "#64748b",

        toolbar: {
            show: false,
        },
    },

    colors: ["#6366f1", "#f43f5e"],

    plotOptions: {
        bar: {
            borderRadius: 6,
            columnWidth: "48%",
        },
    },

    dataLabels: {
        enabled: false,
    },

    grid: {
        borderColor: "#e7eaf0",
        strokeDashArray: 4,
    },

    legend: {
        show: true,
        position: "top",
        horizontalAlign: "left",

        labels: {
            colors: "#94a3b8",
        },
    },

    xaxis: {
        categories: yearlyRecords.value.map((record) => String(record.year)),

        axisBorder: {
            show: false,
        },

        axisTicks: {
            show: false,
        },

        labels: {
            style: {
                colors: "#64748b",
                fontSize: "12px",
            },
        },
    },

    yaxis: {
        labels: {
            formatter: (value) => `₱${formatCompactCurrency(value)}`,

            style: {
                colors: "#64748b",
                fontSize: "12px",
            },
        },
    },

    tooltip: {
        theme: "light",
        shared: true,
        intersect: false,

        y: {
            formatter: (value) => formatCurrency(value),
        },
    },

    responsive: [
        {
            breakpoint: 640,
            options: {
                chart: {
                    height: 290,
                },
                legend: {
                    position: "bottom",
                    horizontalAlign: "center",
                    fontSize: "11px",
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        columnWidth: "62%",
                    },
                },
                grid: {
                    padding: {
                        left: 0,
                        right: 0,
                    },
                },
                xaxis: {
                    labels: {
                        style: {
                            fontSize: "10px",
                        },
                    },
                },
                yaxis: {
                    labels: {
                        style: {
                            fontSize: "10px",
                        },
                    },
                },
            },
        },
    ],

    noData: {
        text: "No yearly financial data available.",
    },
}));
</script>

<template>
    <Head title="Second Term Investment Dashboard" />

    <div
        class="mn-dashboard min-h-screen overflow-x-hidden bg-[#f5f7fb] text-slate-900"
    >
        <!-- HEADER -->
        <header
            class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 text-slate-900 backdrop-blur-xl"
        >
            <div
                class="mx-auto flex max-w-none items-center justify-between gap-2 px-3 py-3 sm:gap-4 sm:px-6 sm:py-5 lg:pl-[284px] lg:pr-8"
            >
                <div class="min-w-0">
                    <p
                        class="text-xs font-semibold uppercase tracking-[0.25em] text-indigo-600"
                    >
                        Montre Nova
                    </p>

                    <h1 class="mt-1 truncate text-lg font-bold sm:text-2xl">
                        Investment 2 Dashboard
                    </h1>

                    <p class="mt-1 hidden text-sm text-slate-500 sm:block">
                        Second investment capital, profitability and investor
                        performance overview
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-semibold text-slate-900">
                            {{ currentUser?.name }}
                        </p>

                        <p class="text-xs capitalize text-slate-500">
                            {{ currentUser?.role }}
                        </p>
                    </div>

                    <span
                        v-if="permissions.can_edit"
                        class="hidden rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 md:inline-flex"
                    >
                        Owner Access
                    </span>

                    <span
                        v-else
                        class="hidden rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 md:inline-flex"
                    >
                        View Only
                    </span>

                    <button
                        type="button"
                        class="shrink-0 rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 sm:px-3 sm:text-sm"
                        @click="logout"
                    >
                        Logout
                    </button>
                </div>
            </div>
        </header>

        <main
            class="mx-auto max-w-none px-3 py-4 pb-28 sm:px-6 sm:py-6 sm:pb-28 lg:pb-6 lg:pl-[284px] lg:pr-8"
        >
            <div
                v-if="!permissions.can_edit"
                class="mb-5 rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3"
            >
                <p class="text-sm font-semibold text-indigo-900">
                    View-only investor access
                </p>

                <p class="mt-1 text-sm text-indigo-700">
                    Only the owner can update the Brand Cut and manage investor
                    capital records.
                </p>
            </div>

            <!-- NAVIGATION -->
            <div
                class="mn-sidebar-nav fixed inset-x-0 bottom-0 z-50 mb-0 overflow-hidden border-t border-slate-200 bg-white/95 shadow-[0_-12px_35px_rgba(15,23,42,0.10)] backdrop-blur-xl lg:inset-y-0 lg:left-0 lg:right-auto lg:w-[252px] lg:rounded-none lg:border-y-0 lg:border-l-0 lg:bg-white lg:shadow-sm"
            >
                <!-- DESKTOP SIDEBAR -->
                <div class="hidden h-full flex-col lg:flex">
                    <div class="border-b border-slate-100 px-5 py-5">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 via-indigo-600 to-violet-600 text-sm font-black text-white shadow-lg shadow-indigo-200/70"
                            >
                                MN
                            </div>

                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-bold tracking-tight text-slate-950"
                                >
                                    Montre Nova
                                </p>

                                <p
                                    class="mt-0.5 text-[11px] font-medium text-slate-400"
                                >
                                    Investor Portal
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto px-4 py-5">
                        <p
                            class="px-3 text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400"
                        >
                            Workspace
                        </p>

                        <nav class="mt-3 space-y-1.5">
                            <button
                                v-for="tab in tabs"
                                :key="tab.key"
                                type="button"
                                class="group relative flex w-full items-center gap-3 overflow-hidden rounded-2xl border px-3 py-3 text-left transition duration-200"
                                :class="
                                    activeTab === tab.key
                                        ? 'border-indigo-100 bg-gradient-to-r from-indigo-50 via-white to-violet-50 text-indigo-700 shadow-sm shadow-indigo-100/60'
                                        : 'border-transparent text-slate-500 hover:border-slate-200 hover:bg-slate-50 hover:text-slate-900'
                                "
                                @click="activeTab = tab.key"
                            >
                                <span
                                    class="absolute inset-y-2 left-0 w-1 rounded-r-full bg-gradient-to-b from-indigo-500 to-violet-500 transition"
                                    :class="
                                        activeTab === tab.key
                                            ? 'opacity-100'
                                            : 'opacity-0'
                                    "
                                ></span>

                                <span
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border transition"
                                    :class="
                                        activeTab === tab.key
                                            ? 'border-white bg-white text-indigo-600 shadow-sm'
                                            : 'border-slate-200 bg-white text-slate-400 group-hover:border-indigo-100 group-hover:text-indigo-600'
                                    "
                                >
                                    <svg
                                        v-if="tab.key === 'overview'"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        class="h-5 w-5"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="M4 13h6V4H4v9Zm10 7h6V11h-6v9ZM4 20h6v-4H4v4Zm10-12h6V4h-6v4Z"
                                        />
                                    </svg>

                                    <svg
                                        v-else-if="tab.key === 'analytics'"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        class="h-5 w-5"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="M4 19V9m5 10V5m5 14v-7m5 7V3"
                                        />
                                    </svg>

                                    <svg
                                        v-else-if="tab.key === 'investors'"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        class="h-5 w-5"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87m-2-12a4 4 0 0 1 0 7.75"
                                        />
                                    </svg>

                                    <svg
                                        v-else-if="tab.key === 'watches'"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        class="h-5 w-5"
                                    >
                                        <circle
                                            cx="12"
                                            cy="12"
                                            r="6"
                                            stroke-width="1.8"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="M9 2h6l1 4H8l1-4Zm0 20h6l1-4H8l1 4Zm3-13v3l2 1"
                                        />
                                    </svg>

                                    <svg
                                        v-else
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        class="h-5 w-5"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="M6 2h9l3 3v17H6V2Zm3 6h6m-6 4h6m-6 4h4"
                                        />
                                    </svg>
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span
                                        class="block truncate text-sm font-semibold"
                                    >
                                        {{ tab.label }}
                                    </span>

                                    <span
                                        class="mt-0.5 block truncate text-[11px] font-medium"
                                        :class="
                                            activeTab === tab.key
                                                ? 'text-indigo-400'
                                                : 'text-slate-400'
                                        "
                                    >
                                        {{ tab.description }}
                                    </span>
                                </span>

                                <span
                                    v-if="tab.count !== undefined"
                                    class="flex h-6 min-w-6 shrink-0 items-center justify-center rounded-full px-1.5 text-[10px] font-bold"
                                    :class="
                                        activeTab === tab.key
                                            ? 'bg-indigo-600 text-white'
                                            : 'bg-slate-100 text-slate-500'
                                    "
                                >
                                    {{ tab.count }}
                                </span>

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    class="h-4 w-4 shrink-0 transition"
                                    :class="
                                        activeTab === tab.key
                                            ? 'translate-x-0 text-indigo-400 opacity-100'
                                            : '-translate-x-1 text-slate-300 opacity-0 group-hover:translate-x-0 group-hover:opacity-100'
                                    "
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="m9 18 6-6-6-6"
                                    />
                                </svg>
                            </button>
                        </nav>
                    </div>

                    <div class="border-t border-slate-100 p-4">
                        <div
                            class="overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 via-white to-violet-50 p-3.5"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold text-white shadow-sm shadow-indigo-200"
                                >
                                    {{
                                        currentUser?.name
                                            ?.charAt(0)
                                            ?.toUpperCase() ?? "U"
                                    }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p
                                        class="truncate text-xs font-bold text-slate-900"
                                    >
                                        {{ currentUser?.name }}
                                    </p>

                                    <p
                                        class="mt-0.5 text-[10px] font-semibold capitalize text-indigo-500"
                                    >
                                        {{ currentUser?.role }} access
                                    </p>
                                </div>
                            </div>

                            <div class="my-3 h-px bg-indigo-100"></div>

                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span
                                    class="text-[10px] font-medium text-slate-400"
                                >
                                    Reporting since
                                </span>

                                <span
                                    class="text-[10px] font-bold text-slate-600"
                                >
                                    {{ formatDate(startDate) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MOBILE NAVIGATION -->
                <div class="mn-mobile-navigation p-1.5 lg:hidden">
                    <nav class="grid grid-cols-5 gap-1">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            type="button"
                            class="min-w-0 flex flex-col items-center justify-center gap-1 rounded-xl border px-1 py-2 text-[10px] font-semibold transition sm:flex-row sm:gap-2 sm:rounded-2xl sm:px-3.5 sm:py-2.5 sm:text-sm"
                            :class="
                                activeTab === tab.key
                                    ? 'border-indigo-600 bg-indigo-600 text-white shadow-sm shadow-indigo-200'
                                    : 'border-transparent bg-white text-slate-500 hover:border-slate-200 hover:bg-slate-50 hover:text-slate-900'
                            "
                            @click="activeTab = tab.key"
                        >
                            <span
                                class="h-1.5 w-1.5 shrink-0 rounded-full sm:h-2 sm:w-2"
                                :class="
                                    activeTab === tab.key
                                        ? 'bg-white'
                                        : 'bg-slate-300'
                                "
                            ></span>

                            {{ tab.label }}

                            <span
                                v-if="tab.count !== undefined"
                                class="hidden rounded-full px-1.5 py-0.5 text-[10px] font-bold sm:inline-flex"
                                :class="
                                    activeTab === tab.key
                                        ? 'bg-white/20 text-white'
                                        : 'bg-slate-100 text-slate-500'
                                "
                            >
                                {{ tab.count }}
                            </span>
                        </button>
                    </nav>
                </div>
            </div>

            <!-- OVERVIEW -->
            <div v-if="activeTab === 'overview'" class="space-y-4 sm:space-y-6">
                <!-- MAIN SUMMARY -->
                <section
                    class="overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-white via-white to-indigo-50 text-slate-900 shadow-sm"
                >
                    <div
                        class="grid gap-6 px-5 py-6 sm:px-7 lg:grid-cols-[1.3fr_1fr]"
                    >
                        <div>
                            <p
                                class="text-xs font-semibold uppercase tracking-[0.22em] text-indigo-600"
                            >
                                Current Fund Performance
                            </p>

                            <h2
                                class="mt-3 break-words text-2xl font-bold tracking-tight sm:text-4xl"
                            >
                                {{ formatCurrency(summary.current_fund_value) }}
                            </h2>

                            <p class="mt-2 text-sm text-slate-500">
                                Current cash plus unsold inventory valued at
                                capital cost.
                            </p>

                            <div class="mt-6 flex flex-wrap gap-2">
                                <span
                                    class="rounded-full border border-indigo-100 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 shadow-sm"
                                >
                                    {{ summary.total_investors }}
                                    investors
                                </span>

                                <span
                                    class="rounded-full border border-indigo-100 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 shadow-sm"
                                >
                                    {{ summary.sold_watches }}
                                    sold watches
                                </span>

                                <span
                                    class="rounded-full border border-indigo-100 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 shadow-sm"
                                >
                                    From
                                    {{ formatDate(startDate) }}
                                </span>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div
                                class="rounded-2xl border border-slate-200/80 bg-white/80 p-4 shadow-sm"
                            >
                                <p class="text-xs font-medium text-slate-500">
                                    Total Capital
                                </p>

                                <p class="mt-2 text-lg font-bold">
                                    {{ formatCurrency(summary.total_capital) }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-slate-200/80 bg-white/80 p-4 shadow-sm"
                            >
                                <p class="text-xs font-medium text-slate-500">
                                    Net Profit
                                </p>

                                <p
                                    class="mt-2 text-lg font-bold"
                                    :class="
                                        Number(summary.net_profit) < 0
                                            ? 'text-rose-600'
                                            : 'text-emerald-600'
                                    "
                                >
                                    {{ formatCurrency(summary.net_profit) }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-slate-200/80 bg-white/80 p-4 shadow-sm"
                            >
                                <p class="text-xs font-medium text-slate-500">
                                    Investor Profit
                                </p>

                                <p
                                    class="mt-2 text-lg font-bold text-indigo-600"
                                >
                                    {{
                                        formatCurrency(
                                            waterfall.total_investor_profit,
                                        )
                                    }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-slate-200/80 bg-white/80 p-4 shadow-sm"
                            >
                                <p class="text-xs font-medium text-slate-500">
                                    Brand Cut
                                </p>

                                <p class="mt-2 text-lg font-bold">
                                    {{ formatCurrency(waterfall.brand_cut) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- MONTHLY PERFORMANCE CHART -->
                <section
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white text-slate-900 shadow-sm"
                >
                    <div
                        class="flex flex-col gap-4 border-b border-slate-200 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6"
                    >
                        <div>
                            <p
                                class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600"
                            >
                                Monthly Performance
                            </p>

                            <h2 class="mt-1 text-xl font-bold text-slate-950">
                                Gross Profit, Expenses and Net Profit
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Based on sold watches and recorded expenses for
                                {{ analytics.selected_year }}.
                            </p>
                        </div>

                        <select
                            v-model="selectedAnalyticsYear"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 shadow-sm outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 sm:w-44"
                            @change="changeAnalyticsYear"
                        >
                            <option
                                v-for="year in analytics.available_years"
                                :key="year"
                                :value="String(year)"
                            >
                                {{ year }}
                            </option>
                        </select>
                    </div>

                    <div v-if="monthlyRecords.length > 0" class="p-3 sm:p-6">
                        <VueApexCharts
                            :key="`overview-monthly-${analytics.selected_year}`"
                            type="area"
                            height="360"
                            :options="monthlyChartOptions"
                            :series="monthlyChartSeries"
                        />
                    </div>

                    <div v-else class="px-6 py-14 text-center">
                        <p class="font-semibold text-slate-700">
                            No chart data received.
                        </p>

                        <p class="mt-2 text-sm text-slate-500">
                            The controller must return analytics.monthly.
                        </p>
                    </div>

                    <div
                        v-if="monthlyRecords.length > 0 && !hasMonthlyActivity"
                        class="border-t border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 sm:px-6"
                    >
                        No sale or expense activity was found for
                        {{ analytics.selected_year }}. Sold records need a
                        date_sold value, or the controller should use created_at
                        as fallback.
                    </div>
                </section>

                <!-- CAPITAL POSITION -->
                <section>
                    <div class="mb-4 flex items-end justify-between gap-4">
                        <div>
                            <p
                                class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500"
                            >
                                Financial Position
                            </p>

                            <h2 class="mt-1 text-xl font-bold text-slate-950">
                                Capital Overview
                            </h2>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <article
                            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm text-slate-500">
                                        Investor Capital
                                    </p>

                                    <p
                                        class="mt-3 text-2xl font-bold text-slate-950"
                                    >
                                        {{
                                            formatCurrency(
                                                summary.total_capital,
                                            )
                                        }}
                                    </p>
                                </div>

                                <span
                                    class="rounded-xl bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600"
                                >
                                    {{ summary.total_investors }}
                                </span>
                            </div>

                            <p class="mt-3 text-xs text-slate-500">
                                Total active investor contributions
                            </p>
                        </article>

                        <article
                            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                        >
                            <p class="text-sm text-slate-500">Cash On Hand</p>

                            <p
                                class="mt-3 text-2xl font-bold"
                                :class="
                                    amountClass(summary.current_on_hand_money)
                                "
                            >
                                {{
                                    formatCurrency(
                                        summary.current_on_hand_money,
                                    )
                                }}
                            </p>

                            <p class="mt-3 text-xs text-slate-500">
                                Capital + sales − inventory purchases − expenses
                            </p>
                        </article>

                        <article
                            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                        >
                            <p class="text-sm text-slate-500">
                                Inventory at Cost
                            </p>

                            <p class="mt-3 text-2xl font-bold text-slate-950">
                                {{ formatCurrency(summary.inventory_capital) }}
                            </p>

                            <p class="mt-3 text-xs text-slate-500">
                                {{ summary.inventory_watches }}
                                unsold watch(es)
                            </p>
                        </article>

                        <article
                            class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 shadow-sm"
                        >
                            <p class="text-sm text-indigo-700">
                                Current Fund Value
                            </p>

                            <p class="mt-3 text-2xl font-bold text-indigo-950">
                                {{ formatCurrency(summary.current_fund_value) }}
                            </p>

                            <p class="mt-3 text-xs text-indigo-600">
                                Cash plus inventory value
                            </p>
                        </article>
                    </div>
                </section>

                <!-- PROFIT SUMMARY -->
                <section>
                    <div class="mb-4">
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500"
                        >
                            All-Time Results
                        </p>

                        <h2 class="mt-1 text-xl font-bold text-slate-950">
                            Profit Summary
                        </h2>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <article
                            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                        >
                            <p class="text-sm text-slate-500">Total Sales</p>

                            <p class="mt-3 text-xl font-bold text-slate-950">
                                {{ formatCurrency(summary.total_sales) }}
                            </p>
                        </article>

                        <article
                            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                        >
                            <p class="text-sm text-slate-500">Gross Profit</p>

                            <p
                                class="mt-3 text-xl font-bold"
                                :class="amountClass(summary.gross_profit)"
                            >
                                {{ formatCurrency(summary.gross_profit) }}
                            </p>

                            <p class="mt-2 text-xs text-slate-500">
                                Sales minus sold watch capital
                            </p>
                        </article>

                        <article
                            class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm"
                        >
                            <p class="text-sm text-red-700">Expenses</p>

                            <p class="mt-3 text-xl font-bold text-red-700">
                                {{ formatCurrency(summary.total_expenses) }}
                            </p>
                        </article>

                        <article
                            class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm"
                        >
                            <p class="text-sm text-emerald-700">Net Profit</p>

                            <p
                                class="mt-3 text-xl font-bold"
                                :class="amountClass(summary.net_profit)"
                            >
                                {{ formatCurrency(summary.net_profit) }}
                            </p>

                            <p class="mt-2 text-xs text-emerald-600">
                                Gross profit minus expenses
                            </p>
                        </article>
                    </div>
                </section>

                <!-- PROFIT DISTRIBUTION -->
                <section
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                >
                    <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600"
                        >
                            Profit Allocation
                        </p>

                        <h2 class="mt-1 text-xl font-bold text-slate-950">
                            Profit Distribution
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Brand Cut is deducted from positive Net Profit. The
                            remainder is allocated to investors based on capital
                            share.
                        </p>
                    </div>

                    <div
                        class="grid gap-4 p-5 sm:grid-cols-2 sm:p-6 lg:grid-cols-4"
                    >
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                            >
                                Distribution Base
                            </p>

                            <p class="mt-2 text-xl font-bold text-slate-950">
                                {{
                                    formatCurrency(waterfall.distribution_base)
                                }}
                            </p>

                            <p class="mt-2 text-xs text-slate-500">
                                Positive Net Profit
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 p-4">
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                            >
                                Brand Cut
                            </p>

                            <p class="mt-2 text-xl font-bold text-slate-950">
                                {{ formatCurrency(waterfall.brand_cut) }}
                            </p>

                            <p class="mt-2 text-xs text-slate-500">
                                {{
                                    formatPercentage(
                                        waterfall.brand_cut_percentage,
                                    )
                                }}
                                of Net Profit
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-indigo-700"
                            >
                                Distributable Profit
                            </p>

                            <p class="mt-2 text-xl font-bold text-indigo-950">
                                {{
                                    formatCurrency(
                                        waterfall.distributable_profit,
                                    )
                                }}
                            </p>
                        </div>

                        <div
                            class="rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 p-4 text-white shadow-sm"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-slate-300"
                            >
                                Investor Profit
                            </p>

                            <p class="mt-2 text-xl font-bold">
                                {{
                                    formatCurrency(
                                        waterfall.total_investor_profit,
                                    )
                                }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="Number(waterfall.distribution_base) > 0"
                        class="border-t border-slate-200 px-5 py-5 sm:px-6"
                    >
                        <div
                            class="flex items-center justify-between gap-3 text-xs font-semibold"
                        >
                            <span class="text-slate-600"> Brand Cut </span>

                            <span class="text-slate-900">
                                {{
                                    formatPercentage(
                                        waterfall.brand_cut_percentage,
                                    )
                                }}
                            </span>
                        </div>

                        <div
                            class="mt-2 h-3 overflow-hidden rounded-full bg-blue-100"
                        >
                            <div
                                class="h-full rounded-full bg-indigo-600 transition-all"
                                :style="{
                                    width: `${Math.min(
                                        Number(
                                            waterfall.brand_cut_percentage ?? 0,
                                        ),
                                        100,
                                    )}%`,
                                }"
                            ></div>
                        </div>

                        <div
                            class="mt-2 flex flex-col gap-1 text-xs text-slate-500 sm:flex-row sm:justify-between"
                        >
                            <span>
                                Montre Nova:
                                {{ formatCurrency(waterfall.brand_cut) }}
                            </span>

                            <span>
                                Investors:
                                {{
                                    formatCurrency(
                                        waterfall.total_investor_profit,
                                    )
                                }}
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="Number(waterfall.unallocated_profit) > 0"
                        class="border-t border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 sm:px-6"
                    >
                        There is

                        <strong>
                            {{ formatCurrency(waterfall.unallocated_profit) }}
                        </strong>

                        of unallocated profit because no active investor capital
                        is available.
                    </div>
                </section>

                <!-- INVESTOR PROFIT CARDS -->
                <section
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                >
                    <div
                        class="flex flex-col gap-3 border-b border-slate-200 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6"
                    >
                        <div>
                            <p
                                class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600"
                            >
                                Investor Allocation
                            </p>

                            <h2 class="mt-1 text-xl font-bold text-slate-950">
                                Current Investor Profits
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Current profit allocation based on each
                                investor’s capital contribution.
                            </p>
                        </div>

                        <div class="w-fit rounded-2xl bg-indigo-50 px-4 py-3">
                            <p class="text-xs font-semibold text-indigo-700">
                                Total Investor Profit
                            </p>

                            <p class="mt-1 text-lg font-bold text-indigo-950">
                                {{
                                    formatCurrency(
                                        waterfall.total_investor_profit,
                                    )
                                }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="investors.length === 0"
                        class="px-6 py-14 text-center"
                    >
                        <p class="font-semibold text-slate-700">
                            No investors added yet.
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Add investor names and capital amounts to calculate
                            their profit shares.
                        </p>
                    </div>

                    <div
                        v-else
                        class="grid gap-4 p-5 sm:grid-cols-2 sm:p-6 lg:grid-cols-3"
                    >
                        <article
                            v-for="investor in investors"
                            :key="investor.id"
                            class="overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:border-indigo-300"
                        >
                            <div
                                class="flex items-start justify-between gap-3 border-b border-slate-100 p-4"
                            >
                                <div class="min-w-0">
                                    <p
                                        class="truncate font-bold text-slate-950"
                                    >
                                        {{ investor.name }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        Invested:
                                        {{
                                            formatCurrency(
                                                investor.capital_amount,
                                            )
                                        }}
                                    </p>
                                </div>

                                <span
                                    class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700"
                                >
                                    {{
                                        formatPercentage(
                                            investor.capital_share_percentage,
                                        )
                                    }}
                                </span>
                            </div>

                            <div class="p-4">
                                <p
                                    class="text-xs font-semibold uppercase tracking-wide text-indigo-600"
                                >
                                    Current Profit
                                </p>

                                <p
                                    class="mt-2 text-2xl font-bold text-indigo-950"
                                >
                                    {{ formatCurrency(investor.profit_share) }}
                                </p>

                                <div
                                    class="mt-4 flex flex-col items-start gap-1 border-t border-slate-100 pt-3 sm:flex-row sm:items-center sm:justify-between sm:gap-3"
                                >
                                    <span class="text-xs text-slate-500">
                                        Capital + Profit
                                    </span>

                                    <span
                                        class="text-sm font-bold text-slate-900"
                                    >
                                        {{
                                            formatCurrency(
                                                Number(
                                                    investor.capital_amount ??
                                                        0,
                                                ) +
                                                    Number(
                                                        investor.profit_share ??
                                                            0,
                                                    ),
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div
                        v-if="investors.length > 0"
                        class="grid border-t border-slate-200 bg-slate-50 sm:grid-cols-3"
                    >
                        <div class="p-4 sm:p-5">
                            <p class="text-xs text-slate-500">Total Capital</p>

                            <p class="mt-1 text-lg font-bold text-slate-950">
                                {{ formatCurrency(summary.total_capital) }}
                            </p>
                        </div>

                        <div
                            class="border-t border-slate-200 p-4 sm:border-l sm:border-t-0 sm:p-5"
                        >
                            <p class="text-xs text-slate-500">
                                Current Investor Profit
                            </p>

                            <p class="mt-1 text-lg font-bold text-indigo-700">
                                {{
                                    formatCurrency(
                                        waterfall.total_investor_profit,
                                    )
                                }}
                            </p>
                        </div>

                        <div
                            class="border-t border-slate-200 p-4 sm:border-l sm:border-t-0 sm:p-5"
                        >
                            <p class="text-xs text-slate-500">
                                Capital + Profit
                            </p>

                            <p class="mt-1 text-lg font-bold text-slate-950">
                                {{
                                    formatCurrency(
                                        Number(summary.total_capital ?? 0) +
                                            Number(
                                                waterfall.total_investor_profit ??
                                                    0,
                                            ),
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- SETTINGS -->
                <section
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                >
                    <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
                        <h2 class="text-lg font-bold text-slate-950">
                            Profit Distribution Settings
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Records created from
                            {{ formatDate(startDate) }}
                            onwards are included.
                        </p>
                    </div>

                    <form
                        v-if="permissions.can_edit"
                        class="flex flex-col gap-4 p-5 sm:flex-row sm:items-end sm:p-6"
                        @submit.prevent="submitSettings"
                    >
                        <div class="w-full max-w-sm">
                            <label
                                for="brand_cut_percentage"
                                class="text-sm font-semibold text-slate-700"
                            >
                                Montre Nova Brand Cut
                            </label>

                            <div class="relative mt-2">
                                <input
                                    id="brand_cut_percentage"
                                    v-model="settingsForm.brand_cut_percentage"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 pl-3 pr-10 text-sm outline-none focus:border-slate-950 focus:ring-2 focus:ring-slate-950/10"
                                />

                                <span
                                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-sm font-semibold text-slate-500"
                                >
                                    %
                                </span>
                            </div>

                            <p
                                v-if="settingsForm.errors.brand_cut_percentage"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ settingsForm.errors.brand_cut_percentage }}
                            </p>
                        </div>

                        <button
                            type="submit"
                            :disabled="settingsForm.processing"
                            class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {{
                                settingsForm.processing
                                    ? "Saving..."
                                    : "Save Brand Cut"
                            }}
                        </button>
                    </form>

                    <div v-else class="p-5 sm:p-6">
                        <p class="text-sm text-slate-500">
                            Montre Nova Brand Cut
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-950">
                            {{
                                formatPercentage(settings.brand_cut_percentage)
                            }}
                        </p>
                    </div>
                </section>
            </div>

            <!-- ANALYTICS -->
            <section
                v-else-if="activeTab === 'analytics'"
                class="space-y-4 sm:space-y-6"
            >
                <!-- ANALYTICS HEADER -->
                <div
                    class="overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-white via-white to-indigo-50 text-slate-900 shadow-sm"
                >
                    <div
                        class="flex flex-col gap-5 px-5 py-6 sm:flex-row sm:items-center sm:justify-between sm:px-7"
                    >
                        <div>
                            <p
                                class="text-xs font-semibold uppercase tracking-[0.22em] text-indigo-600"
                            >
                                Performance Analytics
                            </p>

                            <h2 class="mt-2 text-2xl font-bold sm:text-3xl">
                                Profit and Expense Trends
                            </h2>

                            <p class="mt-2 max-w-2xl text-sm text-slate-500">
                                Monthly sales and profit use the watch date
                                sold. Expenses use the spent date, with created
                                date as fallback.
                            </p>
                        </div>

                        <div class="w-full sm:w-48">
                            <label
                                for="analytics_year"
                                class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                            >
                                Reporting Year
                            </label>

                            <select
                                id="analytics_year"
                                v-model="selectedAnalyticsYear"
                                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 shadow-sm outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                                @change="changeAnalyticsYear"
                            >
                                <option
                                    v-for="year in analytics.available_years"
                                    :key="year"
                                    :value="String(year)"
                                >
                                    {{ year }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- YEAR KPIS -->
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <article
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <p class="text-sm text-slate-500">Annual Sales</p>

                        <p class="mt-3 text-xl font-bold text-slate-950">
                            {{ formatCurrency(analytics.year_summary.sales) }}
                        </p>

                        <p class="mt-2 text-xs text-slate-500">
                            {{ analytics.year_summary.sold_watches }}
                            watch(es) sold
                        </p>
                    </article>

                    <article
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <p class="text-sm text-slate-500">Gross Profit</p>

                        <p
                            class="mt-3 text-xl font-bold"
                            :class="
                                amountClass(analytics.year_summary.gross_profit)
                            "
                        >
                            {{
                                formatCurrency(
                                    analytics.year_summary.gross_profit,
                                )
                            }}
                        </p>
                    </article>

                    <article
                        class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm"
                    >
                        <p class="text-sm text-red-700">Expenses</p>

                        <p class="mt-3 text-xl font-bold text-red-700">
                            {{
                                formatCurrency(analytics.year_summary.expenses)
                            }}
                        </p>

                        <p class="mt-2 text-xs text-red-500">
                            {{ analytics.year_summary.expense_records }}
                            expense record(s)
                        </p>
                    </article>

                    <article
                        class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm"
                    >
                        <p class="text-sm text-emerald-700">Net Profit</p>

                        <p
                            class="mt-3 text-xl font-bold"
                            :class="
                                amountClass(analytics.year_summary.net_profit)
                            "
                        >
                            {{
                                formatCurrency(
                                    analytics.year_summary.net_profit,
                                )
                            }}
                        </p>
                    </article>

                    <article
                        class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 shadow-sm"
                    >
                        <p class="text-sm text-indigo-700">Distributable</p>

                        <p class="mt-3 text-xl font-bold text-indigo-950">
                            {{
                                formatCurrency(
                                    analytics.year_summary.distributable_profit,
                                )
                            }}
                        </p>
                    </article>
                </div>

                <!-- MONTHLY CHART -->
                <section
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                >
                    <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600"
                        >
                            Monthly Trend
                        </p>

                        <h2 class="mt-1 text-xl font-bold text-slate-950">
                            Gross Profit, Expenses and Net Profit
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Monthly performance for
                            {{ analytics.selected_year }}.
                        </p>
                    </div>

                    <div class="p-3 sm:p-6">
                        <VueApexCharts
                            :key="`analytics-monthly-${analytics.selected_year}`"
                            type="area"
                            height="360"
                            :options="monthlyChartOptions"
                            :series="monthlyChartSeries"
                        />
                    </div>
                </section>

                <!-- BEST/WORST MONTH -->
                <div class="grid gap-4 lg:grid-cols-2">
                    <article
                        class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm"
                    >
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700"
                        >
                            Best Month
                        </p>

                        <template v-if="analytics.best_month">
                            <div
                                class="mt-3 flex flex-col items-start gap-2 sm:flex-row sm:items-end sm:justify-between sm:gap-4"
                            >
                                <div>
                                    <p
                                        class="text-xl font-bold text-emerald-950"
                                    >
                                        {{ analytics.best_month.month }}
                                    </p>

                                    <p class="mt-1 text-sm text-emerald-700">
                                        Highest monthly Net Profit
                                    </p>
                                </div>

                                <p class="text-xl font-bold text-emerald-950">
                                    {{
                                        formatCurrency(
                                            analytics.best_month.net_profit,
                                        )
                                    }}
                                </p>
                            </div>
                        </template>

                        <p v-else class="mt-3 text-sm text-emerald-700">
                            No activity recorded for this year.
                        </p>
                    </article>

                    <article
                        class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm"
                    >
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.18em] text-red-700"
                        >
                            Lowest Month
                        </p>

                        <template v-if="analytics.worst_month">
                            <div
                                class="mt-3 flex flex-col items-start gap-2 sm:flex-row sm:items-end sm:justify-between sm:gap-4"
                            >
                                <div>
                                    <p class="text-xl font-bold text-red-950">
                                        {{ analytics.worst_month.month }}
                                    </p>

                                    <p class="mt-1 text-sm text-red-700">
                                        Lowest monthly Net Profit
                                    </p>
                                </div>

                                <p class="text-xl font-bold text-red-950">
                                    {{
                                        formatCurrency(
                                            analytics.worst_month.net_profit,
                                        )
                                    }}
                                </p>
                            </div>
                        </template>

                        <p v-else class="mt-3 text-sm text-red-700">
                            No activity recorded for this year.
                        </p>
                    </article>
                </div>

                <!-- YEARLY CHART -->
                <section
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                >
                    <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500"
                        >
                            Annual Comparison
                        </p>

                        <h2 class="mt-1 text-xl font-bold text-slate-950">
                            Net Profit and Expenses by Year
                        </h2>
                    </div>

                    <div class="p-3 sm:p-6">
                        <VueApexCharts
                            type="bar"
                            height="340"
                            :options="yearlyChartOptions"
                            :series="yearlyChartSeries"
                        />
                    </div>
                </section>

                <!-- MONTHLY BREAKDOWN TABLE -->
                <section
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                >
                    <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
                        <h2 class="text-xl font-bold text-slate-950">
                            Monthly Breakdown
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Complete monthly performance for
                            {{ analytics.selected_year }}.
                        </p>
                    </div>

                    <div class="mn-table-scroll overflow-x-auto">
                        <table
                            class="mn-responsive-table min-w-full divide-y divide-slate-200"
                        >
                            <thead class="bg-slate-50">
                                <tr>
                                    <th
                                        class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                                    >
                                        Month
                                    </th>

                                    <th
                                        class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"
                                    >
                                        Sales
                                    </th>

                                    <th
                                        class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"
                                    >
                                        Sold Capital
                                    </th>

                                    <th
                                        class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"
                                    >
                                        Gross Profit
                                    </th>

                                    <th
                                        class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"
                                    >
                                        Expenses
                                    </th>

                                    <th
                                        class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"
                                    >
                                        Net Profit
                                    </th>

                                    <th
                                        class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"
                                    >
                                        Distributable
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="record in analytics.monthly"
                                    :key="record.month_number"
                                    class="transition hover:bg-slate-50"
                                >
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-slate-900">
                                            {{ record.month }}
                                        </p>

                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ record.sold_watches }}
                                            sold ·
                                            {{ record.expense_records }}
                                            expense(s)
                                        </p>
                                    </td>

                                    <td
                                        class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold text-slate-700"
                                    >
                                        {{ formatCurrency(record.sales) }}
                                    </td>

                                    <td
                                        class="whitespace-nowrap px-5 py-4 text-right text-sm text-slate-600"
                                    >
                                        {{
                                            formatCurrency(record.sold_capital)
                                        }}
                                    </td>

                                    <td
                                        class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold"
                                        :class="
                                            amountClass(record.gross_profit)
                                        "
                                    >
                                        {{
                                            formatCurrency(record.gross_profit)
                                        }}
                                    </td>

                                    <td
                                        class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold text-red-600"
                                    >
                                        {{ formatCurrency(record.expenses) }}
                                    </td>

                                    <td
                                        class="whitespace-nowrap px-5 py-4 text-right text-sm font-bold"
                                        :class="amountClass(record.net_profit)"
                                    >
                                        {{ formatCurrency(record.net_profit) }}
                                    </td>

                                    <td
                                        class="whitespace-nowrap px-5 py-4 text-right text-sm font-bold text-indigo-700"
                                    >
                                        {{
                                            formatCurrency(
                                                record.distributable_profit,
                                            )
                                        }}
                                    </td>
                                </tr>
                            </tbody>

                            <tfoot
                                class="border-t border-slate-300 bg-slate-50"
                            >
                                <tr>
                                    <td
                                        class="px-5 py-4 font-bold text-slate-900"
                                    >
                                        {{ analytics.selected_year }}
                                        Total
                                    </td>

                                    <td
                                        class="px-5 py-4 text-right font-bold text-slate-900"
                                    >
                                        {{
                                            formatCurrency(
                                                analytics.year_summary.sales,
                                            )
                                        }}
                                    </td>

                                    <td
                                        class="px-5 py-4 text-right font-bold text-slate-900"
                                    >
                                        {{
                                            formatCurrency(
                                                analytics.year_summary
                                                    .sold_capital,
                                            )
                                        }}
                                    </td>

                                    <td
                                        class="px-5 py-4 text-right font-bold"
                                        :class="
                                            amountClass(
                                                analytics.year_summary
                                                    .gross_profit,
                                            )
                                        "
                                    >
                                        {{
                                            formatCurrency(
                                                analytics.year_summary
                                                    .gross_profit,
                                            )
                                        }}
                                    </td>

                                    <td
                                        class="px-5 py-4 text-right font-bold text-red-600"
                                    >
                                        {{
                                            formatCurrency(
                                                analytics.year_summary.expenses,
                                            )
                                        }}
                                    </td>

                                    <td
                                        class="px-5 py-4 text-right font-bold"
                                        :class="
                                            amountClass(
                                                analytics.year_summary
                                                    .net_profit,
                                            )
                                        "
                                    >
                                        {{
                                            formatCurrency(
                                                analytics.year_summary
                                                    .net_profit,
                                            )
                                        }}
                                    </td>

                                    <td
                                        class="px-5 py-4 text-right font-bold text-indigo-700"
                                    >
                                        {{
                                            formatCurrency(
                                                analytics.year_summary
                                                    .distributable_profit,
                                            )
                                        }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>

                <!-- YEARLY TABLE -->
                <section
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                >
                    <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
                        <h2 class="text-xl font-bold text-slate-950">
                            Yearly Breakdown
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Compare profit and expenses per year.
                        </p>
                    </div>

                    <div class="mn-table-scroll overflow-x-auto">
                        <table
                            class="mn-responsive-table min-w-full divide-y divide-slate-200"
                        >
                            <thead class="bg-slate-50">
                                <tr>
                                    <th
                                        class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500"
                                    >
                                        Year
                                    </th>

                                    <th
                                        class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500"
                                    >
                                        Sales
                                    </th>

                                    <th
                                        class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500"
                                    >
                                        Gross Profit
                                    </th>

                                    <th
                                        class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500"
                                    >
                                        Expenses
                                    </th>

                                    <th
                                        class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500"
                                    >
                                        Net Profit
                                    </th>

                                    <th
                                        class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500"
                                    >
                                        Distributable
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="record in analytics.yearly"
                                    :key="record.year"
                                    class="hover:bg-slate-50"
                                >
                                    <td class="px-5 py-4">
                                        <p class="font-bold text-slate-900">
                                            {{ record.year }}
                                        </p>

                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ record.sold_watches }}
                                            sold watch(es)
                                        </p>
                                    </td>

                                    <td
                                        class="px-5 py-4 text-right font-semibold text-slate-700"
                                    >
                                        {{ formatCurrency(record.sales) }}
                                    </td>

                                    <td
                                        class="px-5 py-4 text-right font-semibold"
                                        :class="
                                            amountClass(record.gross_profit)
                                        "
                                    >
                                        {{
                                            formatCurrency(record.gross_profit)
                                        }}
                                    </td>

                                    <td
                                        class="px-5 py-4 text-right font-semibold text-red-600"
                                    >
                                        {{ formatCurrency(record.expenses) }}
                                    </td>

                                    <td
                                        class="px-5 py-4 text-right font-bold"
                                        :class="amountClass(record.net_profit)"
                                    >
                                        {{ formatCurrency(record.net_profit) }}
                                    </td>

                                    <td
                                        class="px-5 py-4 text-right font-bold text-indigo-700"
                                    >
                                        {{
                                            formatCurrency(
                                                record.distributable_profit,
                                            )
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </section>

            <!-- INVESTORS -->
            <section
                v-else-if="activeTab === 'investors'"
                class="space-y-4 sm:space-y-5"
            >
                <form
                    v-if="permissions.can_manage_investors"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"
                    @submit.prevent="addInvestor"
                >
                    <p
                        class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600"
                    >
                        Investor Management
                    </p>

                    <h2 class="mt-1 text-xl font-bold text-slate-950">
                        Add Investor
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Capital share and profit allocation will be calculated
                        automatically.
                    </p>

                    <div
                        class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_auto]"
                    >
                        <div>
                            <label
                                for="investor_name"
                                class="text-sm font-semibold text-slate-700"
                            >
                                Investor Name
                            </label>

                            <input
                                id="investor_name"
                                v-model="investorForm.name"
                                type="text"
                                class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-slate-950 focus:ring-2 focus:ring-slate-950/10"
                            />

                            <p
                                v-if="investorForm.errors.name"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ investorForm.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="investor_capital"
                                class="text-sm font-semibold text-slate-700"
                            >
                                Capital Amount
                            </label>

                            <input
                                id="investor_capital"
                                v-model="investorForm.capital_amount"
                                type="number"
                                min="0.01"
                                step="0.01"
                                class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-slate-950 focus:ring-2 focus:ring-slate-950/10"
                            />

                            <p
                                v-if="investorForm.errors.capital_amount"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ investorForm.errors.capital_amount }}
                            </p>
                        </div>

                        <div class="flex items-end">
                            <button
                                type="submit"
                                :disabled="investorForm.processing"
                                class="w-full rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60 lg:w-auto"
                            >
                                {{
                                    investorForm.processing
                                        ? "Adding..."
                                        : "Add Investor"
                                }}
                            </button>
                        </div>
                    </div>
                </form>

                <div
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                >
                    <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
                        <h2 class="text-xl font-bold text-slate-950">
                            Investor Allocation
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Profit shares are calculated based on each
                            investor’s contribution.
                        </p>
                    </div>

                    <div
                        v-if="investors.length === 0"
                        class="px-6 py-14 text-center text-slate-600"
                    >
                        No investors have been added.
                    </div>

                    <div v-else class="mn-table-scroll overflow-x-auto">
                        <table
                            class="mn-responsive-table min-w-full divide-y divide-slate-200"
                        >
                            <thead class="bg-slate-50">
                                <tr>
                                    <th
                                        class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500"
                                    >
                                        Investor
                                    </th>

                                    <th
                                        class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500"
                                    >
                                        Capital
                                    </th>

                                    <th
                                        class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500"
                                    >
                                        Capital Share
                                    </th>

                                    <th
                                        class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500"
                                    >
                                        Current Profit
                                    </th>

                                    <th
                                        class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500"
                                    >
                                        Capital + Profit
                                    </th>

                                    <th
                                        v-if="permissions.can_manage_investors"
                                        class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="investor in investors"
                                    :key="investor.id"
                                    class="hover:bg-slate-50"
                                >
                                    <template
                                        v-if="editingInvestorId === investor.id"
                                    >
                                        <td class="px-5 py-4">
                                            <input
                                                v-model="editInvestorForm.name"
                                                type="text"
                                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                            />

                                            <p
                                                v-if="
                                                    editInvestorForm.errors.name
                                                "
                                                class="mt-1 text-xs text-red-600"
                                            >
                                                {{
                                                    editInvestorForm.errors.name
                                                }}
                                            </p>
                                        </td>

                                        <td class="px-5 py-4">
                                            <input
                                                v-model="
                                                    editInvestorForm.capital_amount
                                                "
                                                type="number"
                                                min="0.01"
                                                step="0.01"
                                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-right text-sm"
                                            />

                                            <p
                                                v-if="
                                                    editInvestorForm.errors
                                                        .capital_amount
                                                "
                                                class="mt-1 text-xs text-red-600"
                                            >
                                                {{
                                                    editInvestorForm.errors
                                                        .capital_amount
                                                }}
                                            </p>
                                        </td>

                                        <td
                                            colspan="3"
                                            class="px-5 py-4 text-center text-sm text-slate-500"
                                        >
                                            Values recalculate after saving.
                                        </td>

                                        <td
                                            class="whitespace-nowrap px-5 py-4 text-right"
                                        >
                                            <button
                                                type="button"
                                                class="mr-3 text-sm font-semibold text-emerald-700"
                                                :disabled="
                                                    editInvestorForm.processing
                                                "
                                                @click="
                                                    updateInvestor(investor)
                                                "
                                            >
                                                Save
                                            </button>

                                            <button
                                                type="button"
                                                class="text-sm font-semibold text-slate-500"
                                                @click="cancelEditInvestor"
                                            >
                                                Cancel
                                            </button>
                                        </td>
                                    </template>

                                    <template v-else>
                                        <td class="px-5 py-4">
                                            <p
                                                class="font-semibold text-slate-950"
                                            >
                                                {{ investor.name }}
                                            </p>
                                        </td>

                                        <td
                                            class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold text-slate-800"
                                        >
                                            {{
                                                formatCurrency(
                                                    investor.capital_amount,
                                                )
                                            }}
                                        </td>

                                        <td
                                            class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold text-slate-800"
                                        >
                                            {{
                                                formatPercentage(
                                                    investor.capital_share_percentage,
                                                )
                                            }}
                                        </td>

                                        <td
                                            class="whitespace-nowrap px-5 py-4 text-right text-sm font-bold text-indigo-700"
                                        >
                                            {{
                                                formatCurrency(
                                                    investor.profit_share,
                                                )
                                            }}
                                        </td>

                                        <td
                                            class="whitespace-nowrap px-5 py-4 text-right text-sm font-bold text-slate-900"
                                        >
                                            {{
                                                formatCurrency(
                                                    Number(
                                                        investor.capital_amount ??
                                                            0,
                                                    ) +
                                                        Number(
                                                            investor.profit_share ??
                                                                0,
                                                        ),
                                                )
                                            }}
                                        </td>

                                        <td
                                            v-if="
                                                permissions.can_manage_investors
                                            "
                                            class="whitespace-nowrap px-5 py-4 text-right"
                                        >
                                            <button
                                                type="button"
                                                class="mr-3 text-sm font-semibold text-slate-700"
                                                @click="
                                                    beginEditInvestor(investor)
                                                "
                                            >
                                                Edit
                                            </button>

                                            <button
                                                type="button"
                                                class="text-sm font-semibold text-red-600"
                                                @click="
                                                    deleteInvestor(investor)
                                                "
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </template>
                                </tr>
                            </tbody>

                            <tfoot class="bg-slate-50">
                                <tr>
                                    <td
                                        class="px-5 py-4 font-bold text-slate-900"
                                    >
                                        Total
                                    </td>

                                    <td
                                        class="px-5 py-4 text-right font-bold text-slate-900"
                                    >
                                        {{
                                            formatCurrency(
                                                summary.total_capital,
                                            )
                                        }}
                                    </td>

                                    <td
                                        class="px-5 py-4 text-right font-bold text-slate-900"
                                    >
                                        {{ investors.length ? "100%" : "0%" }}
                                    </td>

                                    <td
                                        class="px-5 py-4 text-right font-bold text-indigo-700"
                                    >
                                        {{
                                            formatCurrency(
                                                waterfall.total_investor_profit,
                                            )
                                        }}
                                    </td>

                                    <td
                                        class="px-5 py-4 text-right font-bold text-slate-900"
                                    >
                                        {{
                                            formatCurrency(
                                                Number(
                                                    summary.total_capital ?? 0,
                                                ) +
                                                    Number(
                                                        waterfall.total_investor_profit ??
                                                            0,
                                                    ),
                                            )
                                        }}
                                    </td>

                                    <td
                                        v-if="permissions.can_manage_investors"
                                    ></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </section>

            <!-- WATCHES -->
            <section
                v-else-if="activeTab === 'watches'"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
                    <h2 class="text-xl font-bold text-slate-950">Watches</h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Watches created from
                        {{ formatDate(startDate) }}
                        onwards.
                    </p>
                </div>

                <div
                    v-if="watches.length === 0"
                    class="px-6 py-14 text-center text-slate-600"
                >
                    No watch records found.
                </div>

                <div v-else class="mn-table-scroll overflow-x-auto">
                    <table
                        class="mn-responsive-table min-w-full divide-y divide-slate-200"
                    >
                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500"
                                >
                                    Watch
                                </th>

                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500"
                                >
                                    Status
                                </th>

                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500"
                                >
                                    Capital
                                </th>

                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500"
                                >
                                    Sold Price
                                </th>

                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500"
                                >
                                    Profit
                                </th>

                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500"
                                >
                                    Date
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="watchItem in watches"
                                :key="watchItem.id"
                                class="hover:bg-slate-50"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-100"
                                        >
                                            <img
                                                v-if="
                                                    imageUrl(
                                                        watchItem.primary_image,
                                                    )
                                                "
                                                :src="
                                                    imageUrl(
                                                        watchItem.primary_image,
                                                    )
                                                "
                                                :alt="
                                                    watchItem.model_name ??
                                                    'Watch'
                                                "
                                                class="h-full w-full object-cover"
                                            />

                                            <div
                                                v-else
                                                class="flex h-full w-full items-center justify-center text-[10px] text-slate-500"
                                            >
                                                No image
                                            </div>
                                        </div>

                                        <div class="min-w-0">
                                            <p
                                                class="font-semibold text-slate-950"
                                            >
                                                {{
                                                    [
                                                        watchItem.brand,
                                                        watchItem.model_name,
                                                    ]
                                                        .filter(Boolean)
                                                        .join(" ")
                                                }}
                                            </p>

                                            <p
                                                class="mt-1 text-xs text-slate-500"
                                            >
                                                {{
                                                    watchItem.reference_number ||
                                                    "No reference"
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold"
                                        :class="statusClass(watchItem.status)"
                                    >
                                        {{ statusLabel(watchItem.status) }}
                                    </span>
                                </td>

                                <td
                                    class="whitespace-nowrap px-5 py-4 text-right font-semibold"
                                >
                                    {{
                                        formatCurrency(watchItem.capital_price)
                                    }}
                                </td>

                                <td
                                    class="whitespace-nowrap px-5 py-4 text-right"
                                >
                                    {{
                                        watchItem.status === "sold"
                                            ? formatCurrency(
                                                  watchItem.sold_price,
                                              )
                                            : "—"
                                    }}
                                </td>

                                <td
                                    class="whitespace-nowrap px-5 py-4 text-right font-bold"
                                    :class="
                                        amountClass(
                                            watchItem.status === 'sold'
                                                ? watchItem.gross_profit
                                                : watchItem.potential_profit,
                                        )
                                    "
                                >
                                    {{
                                        formatCurrency(
                                            watchItem.status === "sold"
                                                ? watchItem.gross_profit
                                                : watchItem.potential_profit,
                                        )
                                    }}

                                    <p
                                        class="text-[11px] font-normal text-slate-500"
                                    >
                                        {{
                                            watchItem.status === "sold"
                                                ? "Realized"
                                                : "Potential"
                                        }}
                                    </p>
                                </td>

                                <td
                                    class="whitespace-nowrap px-5 py-4 text-sm text-slate-600"
                                >
                                    {{
                                        watchItem.status === "sold"
                                            ? formatDate(watchItem.date_sold)
                                            : formatDate(watchItem.created_at)
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- EXPENSES -->
            <section
                v-else-if="activeTab === 'expenses'"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
                    <h2 class="text-xl font-bold text-slate-950">Expenses</h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Expenses created from
                        {{ formatDate(startDate) }}
                        onwards.
                    </p>
                </div>

                <div
                    v-if="expenses.length === 0"
                    class="px-6 py-14 text-center text-slate-600"
                >
                    No expense records found.
                </div>

                <div v-else class="mn-table-scroll overflow-x-auto">
                    <table
                        class="mn-responsive-table min-w-full divide-y divide-slate-200"
                    >
                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500"
                                >
                                    Expense
                                </th>

                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500"
                                >
                                    Category
                                </th>

                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500"
                                >
                                    Date
                                </th>

                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500"
                                >
                                    Amount
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="expense in expenses"
                                :key="expense.id"
                                class="hover:bg-slate-50"
                            >
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-950">
                                        {{ expense.title }}
                                    </p>

                                    <p
                                        v-if="expense.notes"
                                        class="mt-1 max-w-md text-xs text-slate-500"
                                    >
                                        {{ expense.notes }}
                                    </p>
                                </td>

                                <td class="px-5 py-4 text-sm text-slate-600">
                                    {{ expense.category || "Uncategorized" }}
                                </td>

                                <td
                                    class="whitespace-nowrap px-5 py-4 text-sm text-slate-600"
                                >
                                    {{
                                        formatDate(
                                            expense.spent_at ||
                                                expense.created_at,
                                        )
                                    }}
                                </td>

                                <td
                                    class="whitespace-nowrap px-5 py-4 text-right font-bold text-red-600"
                                >
                                    {{ formatCurrency(expense.amount) }}
                                </td>
                            </tr>
                        </tbody>

                        <tfoot class="bg-slate-50">
                            <tr>
                                <td
                                    colspan="3"
                                    class="px-5 py-4 text-right font-bold text-slate-800"
                                >
                                    Total Expenses
                                </td>

                                <td
                                    class="px-5 py-4 text-right font-bold text-red-600"
                                >
                                    {{ formatCurrency(summary.total_expenses) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        </main>
    </div>
</template>

<style>
.mn-dashboard {
    color-scheme: light;
    min-height: 100vh;
    background:
        radial-gradient(
            circle at top left,
            rgba(99, 102, 241, 0.08),
            transparent 28rem
        ),
        radial-gradient(
            circle at top right,
            rgba(139, 92, 246, 0.06),
            transparent 24rem
        ),
        #f5f7fb;
    font-feature-settings: "cv02", "cv03", "cv04", "cv11";
}

.mn-dashboard .bg-white {
    background-color: rgba(255, 255, 255, 0.96);
}

.mn-dashboard .shadow-sm {
    box-shadow:
        0 1px 2px rgba(15, 23, 42, 0.03),
        0 12px 32px rgba(15, 23, 42, 0.06);
}

.mn-dashboard .rounded-3xl,
.mn-dashboard .rounded-2xl {
    border-color: #e6e9f0;
}

.mn-dashboard input,
.mn-dashboard select {
    border-color: #dfe3eb;
    background-color: #ffffff;
    color: #0f172a;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
    transition:
        border-color 160ms ease,
        box-shadow 160ms ease,
        background-color 160ms ease;
}

.mn-dashboard input:focus,
.mn-dashboard select:focus {
    border-color: #818cf8 !important;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12) !important;
}

.mn-dashboard input::placeholder {
    color: #94a3b8;
}

.mn-dashboard table thead,
.mn-dashboard table tfoot {
    background-color: #f8fafc;
}

.mn-dashboard table tbody tr {
    border-color: #edf0f5;
    transition:
        background-color 150ms ease,
        box-shadow 150ms ease;
}

.mn-dashboard table tbody tr:hover {
    background-color: #fafbff;
}

.mn-dashboard .apexcharts-tooltip,
.mn-dashboard .apexcharts-xaxistooltip {
    border: 1px solid #e2e8f0 !important;
    border-radius: 12px !important;
    background: #ffffff !important;
    color: #0f172a !important;
    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.12) !important;
}

.mn-dashboard .apexcharts-tooltip-title {
    border-bottom: 1px solid #eef2f7 !important;
    background: #f8fafc !important;
}

.mn-dashboard .apexcharts-legend-text {
    color: #64748b !important;
}

.mn-dashboard .apexcharts-text {
    fill: #64748b;
}

.mn-dashboard * {
    scrollbar-width: thin;
    scrollbar-color: #c7cddd transparent;
}

.mn-dashboard *::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.mn-dashboard *::-webkit-scrollbar-track {
    background: transparent;
}

.mn-dashboard *::-webkit-scrollbar-thumb {
    border: 2px solid transparent;
    border-radius: 999px;
    background: #c7cddd;
    background-clip: padding-box;
}

.mn-dashboard article {
    transition:
        transform 160ms ease,
        box-shadow 160ms ease,
        border-color 160ms ease;
}

.mn-dashboard article:hover {
    transform: translateY(-1px);
}

@media (max-width: 1023px) {
    .mn-sidebar-nav {
        padding-bottom: env(safe-area-inset-bottom);
    }
}

@media (max-width: 639px) {
    .mn-dashboard {
        padding-bottom: env(safe-area-inset-bottom);
    }

    .mn-dashboard header {
        padding-top: env(safe-area-inset-top);
    }

    .mn-dashboard section,
    .mn-dashboard form,
    .mn-dashboard article {
        scroll-margin-top: 5rem;
    }

    .mn-dashboard section > div[class*="border-b"],
    .mn-dashboard form {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .mn-dashboard h2 {
        line-height: 1.2;
    }

    .mn-dashboard article:hover {
        transform: none;
    }

    .mn-dashboard .apexcharts-canvas,
    .mn-dashboard .apexcharts-svg {
        max-width: 100% !important;
    }

    .mn-dashboard .apexcharts-legend {
        padding-inline: 0.25rem !important;
    }

    .mn-table-scroll {
        position: relative;
        width: 100%;
        overscroll-behavior-x: contain;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
    }

    .mn-responsive-table {
        min-width: 760px;
    }

    .mn-responsive-table th,
    .mn-responsive-table td {
        padding: 0.75rem 0.875rem !important;
    }

    .mn-responsive-table th:first-child,
    .mn-responsive-table td:first-child {
        position: sticky;
        left: 0;
        z-index: 2;
        box-shadow: 1px 0 0 #e2e8f0;
    }

    .mn-responsive-table thead th:first-child,
    .mn-responsive-table tfoot td:first-child {
        background: #f8fafc;
    }

    .mn-responsive-table tbody td:first-child {
        background: #ffffff;
    }

    .mn-responsive-table tbody tr:hover td:first-child {
        background: #fafbff;
    }

    .mn-swal-popup {
        width: calc(100vw - 1.5rem) !important;
        border-radius: 18px !important;
        padding: 1rem !important;
    }

    .mn-swal-confirm,
    .mn-swal-cancel {
        width: 100%;
        margin: 0.35rem 0 0 !important;
    }

    .swal2-actions {
        width: 100% !important;
        flex-direction: column-reverse !important;
    }
}

@media (min-width: 1024px) {
    .mn-sidebar-nav {
        box-shadow: 16px 0 48px rgba(15, 23, 42, 0.07);
    }

    .mn-sidebar-nav::after {
        position: absolute;
        top: 0;
        right: 0;
        width: 1px;
        height: 100%;
        content: "";
        background: linear-gradient(
            to bottom,
            transparent,
            rgba(99, 102, 241, 0.2),
            transparent
        );
    }
}

.mn-swal-popup {
    width: min(92vw, 420px) !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 22px !important;
    background: #ffffff !important;
    box-shadow: 0 28px 80px rgba(15, 23, 42, 0.18) !important;
}

.mn-swal-title {
    color: #0f172a !important;
    font-size: 1.25rem !important;
}

.mn-swal-text {
    color: #64748b !important;
    font-size: 0.9rem !important;
    line-height: 1.65 !important;
}

.mn-swal-text strong {
    color: #312e81;
}

.mn-swal-note {
    margin-top: 0.5rem;
    color: #94a3b8;
    font-size: 0.8rem;
}

.mn-swal-confirm,
.mn-swal-cancel {
    border: 0;
    border-radius: 12px;
    padding: 0.72rem 1rem;
    font-size: 0.875rem;
    font-weight: 700;
    transition:
        opacity 150ms ease,
        transform 150ms ease,
        box-shadow 150ms ease;
}

.mn-swal-confirm {
    background: #4f46e5;
    color: #ffffff;
    box-shadow: 0 8px 18px rgba(79, 70, 229, 0.22);
}

.mn-swal-danger {
    background: #e11d48;
    color: #ffffff;
    box-shadow: 0 8px 18px rgba(225, 29, 72, 0.2);
}

.mn-swal-cancel {
    margin-right: 0.6rem;
    background: #f1f5f9;
    color: #475569;
}

.mn-swal-confirm:hover,
.mn-swal-cancel:hover {
    opacity: 0.92;
}

.mn-swal-confirm:active,
.mn-swal-cancel:active {
    transform: scale(0.98);
}

.mn-swal-toast {
    border: 1px solid #e5e7eb !important;
    border-radius: 16px !important;
    background: #ffffff !important;
    color: #0f172a !important;
    box-shadow: 0 18px 50px rgba(15, 23, 42, 0.14) !important;
}

.swal2-timer-progress-bar {
    background: #6366f1 !important;
}
</style>
