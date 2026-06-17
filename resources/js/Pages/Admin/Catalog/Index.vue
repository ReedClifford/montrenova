<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
    catalogWatches: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({
            search: "",
        }),
    },
});

const showForm = ref(false);
const editingWatch = ref(null);
const previewUrl = ref(null);
const search = ref(props.filters.search || "");

const form = useForm({
    brand: "Seiko",
    model_name: "",
    reference_number: "",
    category: "",
    photo: null,
    is_visible: true,
    sort_order: 0,
});

const watches = computed(() => props.catalogWatches?.data || []);

const resetForm = () => {
    form.reset();
    form.clearErrors();

    form.brand = "Seiko";
    form.model_name = "";
    form.reference_number = "";
    form.category = "";
    form.photo = null;
    form.is_visible = true;
    form.sort_order = 0;

    previewUrl.value = null;
    editingWatch.value = null;
};

const openCreateForm = () => {
    resetForm();
    showForm.value = true;
};

const openEditForm = (watch) => {
    resetForm();

    editingWatch.value = watch;
    form.brand = watch.brand || "Seiko";
    form.model_name = watch.model_name || "";
    form.reference_number = watch.reference_number || "";
    form.category = watch.category || "";
    form.is_visible = Boolean(watch.is_visible);
    form.sort_order = Number(watch.sort_order || 0);
    previewUrl.value = watch.image_url || null;

    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    resetForm();
};

const handlePhoto = (event) => {
    const file = event.target.files?.[0] || null;

    form.photo = file;

    if (previewUrl.value && previewUrl.value.startsWith("blob:")) {
        URL.revokeObjectURL(previewUrl.value);
    }

    previewUrl.value = file
        ? URL.createObjectURL(file)
        : editingWatch.value?.image_url || null;
};

const submit = () => {
    if (editingWatch.value) {
        form.transform((data) => ({
            ...data,
            _method: "PATCH",
        })).post(
            route("admin.catalog.update", { catalog: editingWatch.value.id }),
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => closeForm(),
            },
        );

        return;
    }

    form.post(route("admin.catalog.store"), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => closeForm(),
    });
};

const deleteWatch = (watch) => {
    if (!confirm(`Delete ${watch.model_name}?`)) {
        return;
    }

    router.delete(route("admin.catalog.destroy", watch.id), {
        preserveScroll: true,
    });
};

const applySearch = () => {
    router.get(
        route("admin.catalog.index"),
        {
            search: search.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const clearSearch = () => {
    search.value = "";

    router.get(
        route("admin.catalog.index"),
        {},
        {
            preserveState: true,
            replace: true,
        },
    );
};

const paginationUrl = (url) => {
    return url || "#";
};

const cleanPaginationLabel = (label) => {
    return String(label)
        .replace("&laquo; Previous", "‹ Prev")
        .replace("Next &raquo;", "Next ›");
};
</script>

<template>
    <Head title="Catalog Watches" />

    <AuthenticatedLayout title="Catalog Watches">
        <div class="mx-auto max-w-7xl">
            <div
                class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-end"
            >
                <div>
                    <p
                        class="text-xs font-black uppercase tracking-[0.24em] text-zinc-500"
                    >
                        Admin Catalog
                    </p>

                    <h1
                        class="mt-2 text-3xl font-black tracking-[-0.04em] text-white sm:text-4xl"
                    >
                        Catalog Watches
                    </h1>

                    <p class="mt-3 max-w-2xl text-sm leading-7 text-zinc-400">
                        Manage catalog-only watches. These are separate from
                        your actual inventory.
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <Link
                        :href="route('public.catalog')"
                        target="_blank"
                        class="inline-flex items-center justify-center rounded-xl border border-white/10 px-5 py-3 text-xs font-black uppercase tracking-[0.16em] text-zinc-300 transition hover:border-white/30 hover:text-white"
                    >
                        View Public Catalog
                    </Link>

                    <button
                        type="button"
                        class="rounded-xl bg-white px-5 py-3 text-xs font-black uppercase tracking-[0.16em] text-black transition hover:bg-zinc-200"
                        @click="openCreateForm"
                    >
                        Add Catalog Watch
                    </button>
                </div>
            </div>

            <div
                class="mb-6 rounded-2xl border border-white/10 bg-white/[0.04] p-4"
            >
                <div class="flex flex-col gap-3 sm:flex-row">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search model, reference, brand, category..."
                        class="min-h-12 flex-1 rounded-xl border border-white/10 bg-black/40 px-4 text-sm text-white outline-none placeholder:text-zinc-600 focus:border-white/30"
                        @keyup.enter="applySearch"
                    />

                    <button
                        type="button"
                        class="rounded-xl bg-white px-5 py-3 text-xs font-black uppercase tracking-[0.16em] text-black transition hover:bg-zinc-200"
                        @click="applySearch"
                    >
                        Search
                    </button>

                    <button
                        v-if="search"
                        type="button"
                        class="rounded-xl border border-white/10 px-5 py-3 text-xs font-black uppercase tracking-[0.16em] text-zinc-300 transition hover:border-white/30 hover:text-white"
                        @click="clearSearch"
                    >
                        Clear
                    </button>
                </div>
            </div>

            <div
                v-if="showForm"
                class="mb-6 overflow-hidden rounded-2xl border border-white/10 bg-zinc-900 shadow-2xl shadow-black/40"
            >
                <div class="border-b border-white/10 p-5">
                    <h2 class="text-xl font-black text-white">
                        {{
                            editingWatch
                                ? "Edit Catalog Watch"
                                : "Add Catalog Watch"
                        }}
                    </h2>
                </div>

                <form
                    class="grid gap-5 p-5 lg:grid-cols-[260px_1fr]"
                    @submit.prevent="submit"
                >
                    <div>
                        <label
                            class="flex min-h-[320px] cursor-pointer items-center justify-center overflow-hidden rounded-2xl border border-dashed border-white/15 bg-black/35 transition hover:border-white/35"
                        >
                            <img
                                v-if="previewUrl"
                                :src="previewUrl"
                                alt="Preview"
                                class="h-full min-h-[320px] w-full object-cover"
                            />

                            <div v-else class="p-6 text-center">
                                <p class="text-sm font-bold text-zinc-300">
                                    Upload photo
                                </p>
                                <p class="mt-2 text-xs leading-6 text-zinc-500">
                                    JPG, PNG, or WEBP up to 5MB
                                </p>
                            </div>

                            <input
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                class="hidden"
                                @change="handlePhoto"
                            />
                        </label>

                        <p
                            v-if="form.errors.photo"
                            class="mt-2 text-xs text-red-300"
                        >
                            {{ form.errors.photo }}
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                class="text-xs font-black uppercase tracking-[0.18em] text-zinc-500"
                            >
                                Brand
                            </label>

                            <input
                                v-model="form.brand"
                                type="text"
                                class="mt-2 min-h-12 w-full rounded-xl border border-white/10 bg-black/40 px-4 text-sm text-white outline-none focus:border-white/30"
                            />

                            <p
                                v-if="form.errors.brand"
                                class="mt-2 text-xs text-red-300"
                            >
                                {{ form.errors.brand }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="text-xs font-black uppercase tracking-[0.18em] text-zinc-500"
                            >
                                Model Name
                            </label>

                            <input
                                v-model="form.model_name"
                                type="text"
                                class="mt-2 min-h-12 w-full rounded-xl border border-white/10 bg-black/40 px-4 text-sm text-white outline-none focus:border-white/30"
                            />

                            <p
                                v-if="form.errors.model_name"
                                class="mt-2 text-xs text-red-300"
                            >
                                {{ form.errors.model_name }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="text-xs font-black uppercase tracking-[0.18em] text-zinc-500"
                            >
                                Reference Number
                            </label>

                            <input
                                v-model="form.reference_number"
                                type="text"
                                class="mt-2 min-h-12 w-full rounded-xl border border-white/10 bg-black/40 px-4 text-sm text-white outline-none focus:border-white/30"
                            />

                            <p
                                v-if="form.errors.reference_number"
                                class="mt-2 text-xs text-red-300"
                            >
                                {{ form.errors.reference_number }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="text-xs font-black uppercase tracking-[0.18em] text-zinc-500"
                            >
                                Category
                            </label>

                            <input
                                v-model="form.category"
                                type="text"
                                placeholder="GMT, Diver, Presage, Speedtimer..."
                                class="mt-2 min-h-12 w-full rounded-xl border border-white/10 bg-black/40 px-4 text-sm text-white outline-none placeholder:text-zinc-600 focus:border-white/30"
                            />

                            <p
                                v-if="form.errors.category"
                                class="mt-2 text-xs text-red-300"
                            >
                                {{ form.errors.category }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="text-xs font-black uppercase tracking-[0.18em] text-zinc-500"
                            >
                                Sort Order
                            </label>

                            <input
                                v-model="form.sort_order"
                                type="number"
                                min="0"
                                class="mt-2 min-h-12 w-full rounded-xl border border-white/10 bg-black/40 px-4 text-sm text-white outline-none focus:border-white/30"
                            />

                            <p
                                v-if="form.errors.sort_order"
                                class="mt-2 text-xs text-red-300"
                            >
                                {{ form.errors.sort_order }}
                            </p>
                        </div>

                        <div class="flex items-end">
                            <label
                                class="flex min-h-12 w-full cursor-pointer items-center justify-between rounded-xl border border-white/10 bg-black/40 px-4"
                            >
                                <span class="text-sm font-bold text-zinc-300">
                                    Visible on public catalog
                                </span>

                                <input
                                    v-model="form.is_visible"
                                    type="checkbox"
                                    class="h-5 w-5 rounded border-white/20 bg-black"
                                />
                            </label>
                        </div>

                        <div class="flex gap-3 sm:col-span-2">
                            <button
                                type="submit"
                                class="rounded-xl bg-white px-6 py-3 text-xs font-black uppercase tracking-[0.16em] text-black transition hover:bg-zinc-200 disabled:opacity-60"
                                :disabled="form.processing"
                            >
                                {{
                                    form.processing
                                        ? "Saving..."
                                        : editingWatch
                                          ? "Save Changes"
                                          : "Add Watch"
                                }}
                            </button>

                            <button
                                type="button"
                                class="rounded-xl border border-white/10 px-6 py-3 text-xs font-black uppercase tracking-[0.16em] text-zinc-300 transition hover:border-white/30 hover:text-white"
                                @click="closeForm"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div
                v-if="watches.length"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <article
                    v-for="watch in watches"
                    :key="watch.id"
                    class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.04]"
                >
                    <div class="relative aspect-[4/5] bg-black">
                        <img
                            v-if="watch.image_url"
                            :src="watch.image_url"
                            :alt="watch.model_name"
                            class="h-full w-full object-cover"
                            loading="lazy"
                        />

                        <div
                            v-else
                            class="flex h-full items-center justify-center text-sm font-bold text-zinc-600"
                        >
                            No photo
                        </div>

                        <div class="absolute left-3 top-3 flex gap-2">
                            <span
                                class="rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-[0.14em]"
                                :class="
                                    watch.is_visible
                                        ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-200'
                                        : 'border-zinc-400/20 bg-zinc-400/10 text-zinc-400'
                                "
                            >
                                {{ watch.is_visible ? "Visible" : "Hidden" }}
                            </span>
                        </div>
                    </div>

                    <div class="p-4">
                        <p
                            class="text-xs font-black uppercase tracking-[0.22em] text-zinc-500"
                        >
                            {{ watch.brand || "Montre Nova" }}
                        </p>

                        <h3
                            class="mt-2 line-clamp-2 text-lg font-black text-white"
                        >
                            {{ watch.model_name }}
                        </h3>

                        <p class="mt-1 text-sm text-zinc-400">
                            Ref. {{ watch.reference_number || "No reference" }}
                        </p>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <span
                                v-if="watch.category"
                                class="rounded-full border border-white/10 bg-black/30 px-3 py-1 text-[10px] font-black uppercase tracking-[0.14em] text-zinc-300"
                            >
                                {{ watch.category }}
                            </span>

                            <span
                                class="rounded-full border border-white/10 bg-black/30 px-3 py-1 text-[10px] font-black uppercase tracking-[0.14em] text-zinc-300"
                            >
                                Order {{ watch.sort_order || 0 }}
                            </span>
                        </div>

                        <div class="mt-4 flex gap-2">
                            <button
                                type="button"
                                class="flex-1 rounded-xl border border-white/10 px-4 py-2.5 text-xs font-black uppercase tracking-[0.14em] text-zinc-300 transition hover:border-white/30 hover:text-white"
                                @click="openEditForm(watch)"
                            >
                                Edit
                            </button>

                            <button
                                type="button"
                                class="flex-1 rounded-xl border border-red-400/20 bg-red-500/10 px-4 py-2.5 text-xs font-black uppercase tracking-[0.14em] text-red-200 transition hover:bg-red-500/20"
                                @click="deleteWatch(watch)"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </article>
            </div>

            <div
                v-else
                class="rounded-2xl border border-white/10 bg-white/[0.04] p-10 text-center"
            >
                <h3 class="text-xl font-black text-white">
                    No catalog watches yet.
                </h3>
                <p class="mt-3 text-sm text-zinc-500">
                    Add your first catalog watch to show it on the public
                    catalog.
                </p>
            </div>

            <div
                v-if="catalogWatches.links?.length > 3"
                class="mt-6 flex flex-wrap justify-center gap-2"
            >
                <template
                    v-for="link in catalogWatches.links"
                    :key="link.label"
                >
                    <span
                        v-if="!link.url"
                        class="rounded-lg border border-white/5 px-4 py-2 text-sm text-zinc-700"
                    >
                        {{ cleanPaginationLabel(link.label) }}
                    </span>

                    <Link
                        v-else
                        :href="paginationUrl(link.url)"
                        preserve-scroll
                        preserve-state
                        class="rounded-lg border px-4 py-2 text-sm font-bold transition"
                        :class="
                            link.active
                                ? 'border-white bg-white text-black'
                                : 'border-white/10 bg-white/[0.04] text-zinc-400 hover:border-white/30 hover:text-white'
                        "
                    >
                        {{ cleanPaginationLabel(link.label) }}
                    </Link>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
