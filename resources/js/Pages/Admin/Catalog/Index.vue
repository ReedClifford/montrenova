<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";

const props = defineProps({
    catalogWatches: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: "" }) },
});

const showForm = ref(false);
const editingWatch = ref(null);
const previewUrl = ref(null);
const search = ref(props.filters?.search || "");
const fileInput = ref(null);
const photoError = ref("");
const deletingId = ref(null);
const watches = computed(() => props.catalogWatches?.data || []);
const visibleCount = computed(
    () => watches.value.filter((watch) => Boolean(watch.is_visible)).length,
);
const hasSearch = computed(() => Boolean(props.filters?.search));
const form = useForm({
    brand: "Seiko",
    model_name: "",
    reference_number: "",
    category: "",
    photo: null,
    is_visible: true,
    sort_order: 0,
});

watch(
    () => props.filters?.search,
    (value) => {
        search.value = value || "";
    },
);

function releasePreview() {
    if (previewUrl.value?.startsWith("blob:"))
        URL.revokeObjectURL(previewUrl.value);
}

function resetForm() {
    releasePreview();
    photoError.value = "";
    form.defaults({
        brand: "Seiko",
        model_name: "",
        reference_number: "",
        category: "",
        photo: null,
        is_visible: true,
        sort_order: 0,
    });
    form.reset();
    form.clearErrors();
    editingWatch.value = null;
    previewUrl.value = null;
    if (fileInput.value) fileInput.value.value = "";
}

function openCreateForm() {
    resetForm();
    showForm.value = true;
}

function openEditForm(watch) {
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
}

function closeForm(force = false) {
    if (form.processing && force !== true) return;
    showForm.value = false;
    resetForm();
}

function handlePhoto(event) {
    const file = event.target.files?.[0] || null;
    if (!file) return;
    if (!["image/jpeg", "image/png", "image/webp"].includes(file.type)) {
        photoError.value = "Choose a JPG, PNG, or WEBP image.";
        event.target.value = "";
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        photoError.value = "Image must not exceed 5 MB.";
        event.target.value = "";
        return;
    }
    photoError.value = "";
    releasePreview();
    form.photo = file;
    previewUrl.value = URL.createObjectURL(file);
    form.clearErrors("photo");
}

function submit() {
    if (form.processing || photoError.value) return;
    // Laravel accepts PATCH method spoofing for multipart uploads.
    if (editingWatch.value) {
        form.transform((data) => ({ ...data, _method: "PATCH" })).post(
            route("admin.catalog.update", { catalog: editingWatch.value.id }),
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => closeForm(true),
            },
        );
    } else {
        form.transform((data) => data).post(route("admin.catalog.store"), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => closeForm(true),
        });
    }
}

function deleteWatch(watch) {
    if (!window.confirm(`Delete ${watch.model_name}? This cannot be undone.`))
        return;
    if (deletingId.value !== null) return;
    deletingId.value = watch.id;
    router.delete(route("admin.catalog.destroy", watch.id), {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
}

function applySearch() {
    router.get(
        route("admin.catalog.index"),
        search.value.trim() ? { search: search.value.trim() } : {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}
function clearSearch() {
    search.value = "";
    applySearch();
}
function paginationLabel(label) {
    return String(label)
        .replace(/&laquo;|&#171;/g, "‹")
        .replace(/&raquo;|&#187;/g, "›")
        .replace(/<[^>]+>/g, "");
}
function onKeyDown(event) {
    if (event.key === "Escape" && showForm.value) closeForm();
}
let previousBodyOverflow = "";
watch(showForm, (open) => {
    if (typeof document === "undefined") return;
    if (open) {
        previousBodyOverflow = document.body.style.overflow;
        document.body.style.overflow = "hidden";
    } else {
        document.body.style.overflow = previousBodyOverflow;
    }
});
onMounted(() => window.addEventListener("keydown", onKeyDown));
onBeforeUnmount(() => {
    releasePreview();
    if (typeof document !== "undefined" && showForm.value)
        document.body.style.overflow = previousBodyOverflow;
    window.removeEventListener("keydown", onKeyDown);
});
</script>

<template>
    <Head title="Catalog Watches" />
    <AuthenticatedLayout title="Catalog Watches">
        <main
            class="catalog-page mx-auto w-full min-w-0 max-w-7xl pb-12 text-zinc-100"
        >
            <header
                class="mb-5 flex flex-col gap-5 sm:mb-7 lg:flex-row lg:items-end lg:justify-between"
            >
                <div class="min-w-0">
                    <p class="eyebrow">MONTRE NOVA / ADMIN</p>
                    <h1
                        class="mt-2 text-[clamp(1.65rem,4vw,2.5rem)] font-semibold leading-tight tracking-[-.045em] text-white"
                    >
                        Catalog Watches
                    </h1>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-zinc-400">
                        Manage the watches displayed in your public catalog,
                        separately from your inventory.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                    <Link
                        :href="route('public.catalog')"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn-secondary text-center"
                        >View catalog <span aria-hidden="true">↗</span></Link
                    >
                    <button
                        type="button"
                        class="btn-primary"
                        @click="openCreateForm"
                    >
                        <span class="mr-1 text-base" aria-hidden="true">+</span>
                        Add watch
                    </button>
                </div>
            </header>

            <section
                class="mb-5 grid grid-cols-2 gap-2.5 sm:mb-6 sm:gap-3"
                aria-label="Catalog summary"
            >
                <div class="surface px-4 py-3.5 sm:p-5">
                    <p class="eyebrow">On this page</p>
                    <p
                        class="mt-2 text-2xl font-semibold tabular-nums tracking-tight text-white"
                    >
                        {{ watches.length }}
                    </p>
                    <p class="mt-1 text-xs text-zinc-500">Catalog watches</p>
                </div>
                <div class="surface px-4 py-3.5 sm:p-5">
                    <p class="eyebrow">Public visibility</p>
                    <p
                        class="mt-2 text-2xl font-semibold tabular-nums tracking-tight text-white"
                    >
                        {{ visibleCount }}
                    </p>
                    <p class="mt-1 text-xs text-zinc-500">
                        Visible on this page
                    </p>
                </div>
            </section>

            <section
                class="surface mb-5 p-3 sm:mb-6 sm:p-4"
                aria-label="Search catalog"
            >
                <form
                    class="flex flex-col gap-2 sm:flex-row"
                    @submit.prevent="applySearch"
                >
                    <div class="relative min-w-0 flex-1">
                        <span
                            aria-hidden="true"
                            class="catalog-search-icon"
                        ></span>
                        <input
                            v-model="search"
                            type="search"
                            aria-label="Search watches"
                            placeholder="Search model, reference, brand or category"
                            class="field w-full pl-11"
                            autocomplete="off"
                        />
                    </div>
                    <div class="flex gap-2">
                        <button
                            class="btn-primary flex-1 sm:flex-none"
                            type="submit"
                        >
                            Search</button
                        ><button
                            v-if="search || hasSearch"
                            type="button"
                            class="btn-secondary flex-1 sm:flex-none"
                            @click="clearSearch"
                        >
                            Clear
                        </button>
                    </div>
                </form>
            </section>

            <section
                v-if="watches.length"
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 xl:grid-cols-3 2xl:grid-cols-4"
                aria-label="Catalog watches"
            >
                <article
                    v-for="watch in watches"
                    :key="watch.id"
                    class="watch-card group flex min-w-0 overflow-hidden rounded-2xl border border-white/10 bg-zinc-900/70 sm:flex-col"
                >
                    <div
                        class="relative w-[112px] shrink-0 bg-zinc-950 min-[380px]:w-[136px] sm:aspect-[5/4] sm:w-full"
                    >
                        <img
                            v-if="watch.image_url"
                            :src="watch.image_url"
                            :alt="watch.model_name || 'Watch photo'"
                            class="h-full w-full object-contain object-center transition-transform duration-300 motion-safe:group-hover:scale-[1.025]"
                            loading="lazy"
                        />
                        <div
                            v-else
                            class="flex h-full min-h-36 items-center justify-center px-2 text-center text-xs text-zinc-600"
                        >
                            No photo uploaded
                        </div>
                        <span
                            class="absolute bottom-2 left-2 hidden rounded-full border px-2.5 py-1 text-[10px] font-semibold backdrop-blur-md sm:inline-flex"
                            :class="
                                watch.is_visible
                                    ? 'border-emerald-300/20 bg-emerald-950/80 text-emerald-200'
                                    : 'border-zinc-400/20 bg-zinc-900/90 text-zinc-300'
                            "
                            >{{ watch.is_visible ? "Visible" : "Hidden" }}</span
                        >
                    </div>
                    <div class="flex min-w-0 flex-1 flex-col p-3 sm:p-4">
                        <div
                            class="flex min-w-0 items-start justify-between gap-2"
                        >
                            <p
                                class="truncate text-[10px] font-semibold uppercase tracking-[.16em] text-zinc-400"
                            >
                                {{ watch.brand || "Montre Nova" }}
                            </p>
                            <span
                                class="shrink-0 text-[10px] font-medium sm:hidden"
                                :class="
                                    watch.is_visible
                                        ? 'text-emerald-300'
                                        : 'text-zinc-500'
                                "
                                >{{
                                    watch.is_visible ? "● Visible" : "○ Hidden"
                                }}</span
                            >
                        </div>
                        <h2
                            class="mt-1 line-clamp-2 break-words text-sm font-semibold leading-snug text-white sm:text-base"
                        >
                            {{ watch.model_name }}
                        </h2>
                        <p
                            class="mt-1 truncate text-xs text-zinc-400"
                            :title="watch.reference_number || ''"
                        >
                            Ref. {{ watch.reference_number || "Not set" }}
                        </p>
                        <div
                            class="mt-2 flex flex-wrap gap-1.5 text-[10px] text-zinc-400 sm:mt-3"
                        >
                            <span
                                v-if="watch.category"
                                class="tag max-w-full truncate"
                                >{{ watch.category }}</span
                            ><span class="tag"
                                >Order {{ watch.sort_order ?? 0 }}</span
                            >
                        </div>
                        <div
                            class="mt-auto grid grid-cols-2 gap-2 pt-3 sm:pt-4"
                        >
                            <button
                                type="button"
                                class="card-action border-white/15 bg-white/[.06] text-white hover:bg-white/10"
                                :aria-label="`Edit ${watch.model_name}`"
                                @click="openEditForm(watch)"
                            >
                                Edit
                            </button>
                            <button
                                type="button"
                                class="card-action border-red-400/20 bg-red-400/[.07] text-red-200 hover:bg-red-400/15"
                                :disabled="deletingId !== null"
                                :aria-label="`Delete ${watch.model_name}`"
                                @click="deleteWatch(watch)"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </article>
            </section>
            <section
                v-else
                class="surface flex flex-col items-center px-5 py-14 text-center sm:py-20"
            >
                <div
                    class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-xl"
                    aria-hidden="true"
                >
                    ⌚
                </div>
                <h2 class="text-lg font-semibold text-white">
                    {{
                        hasSearch
                            ? "No matching watches"
                            : "Your catalog is empty"
                    }}
                </h2>
                <p class="mt-2 max-w-sm text-sm leading-6 text-zinc-400">
                    {{
                        hasSearch
                            ? "Try another model, brand or reference number."
                            : "Add your first catalog watch to display it on your public website."
                    }}
                </p>
                <button
                    type="button"
                    class="btn-primary mt-5"
                    @click="hasSearch ? clearSearch() : openCreateForm()"
                >
                    {{ hasSearch ? "Clear search" : "Add first watch" }}
                </button>
            </section>

            <nav
                v-if="catalogWatches.links?.length > 3"
                class="mt-6 flex flex-wrap items-center justify-center gap-1.5 sm:gap-2"
                aria-label="Catalog pages"
            >
                <template
                    v-for="(link, index) in catalogWatches.links"
                    :key="`${index}-${link.label}`"
                >
                    <span
                        v-if="!link.url"
                        class="page-link cursor-not-allowed opacity-35"
                        v-text="paginationLabel(link.label)"
                    />
                    <Link
                        v-else
                        :href="link.url"
                        preserve-scroll
                        preserve-state
                        class="page-link"
                        :class="
                            link.active
                                ? 'border-white bg-white text-black'
                                : 'border-white/10 bg-zinc-900 text-zinc-300 hover:border-white/30 hover:text-white'
                        "
                        :aria-current="link.active ? 'page' : undefined"
                        v-text="paginationLabel(link.label)"
                    />
                </template>
            </nav>
        </main>

        <Teleport to="body">
            <div
                v-if="showForm"
                class="fixed inset-0 z-[100] flex items-end justify-center bg-black/80 sm:items-center sm:p-5"
                role="presentation"
                @click.self="closeForm"
            >
                <section
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="catalog-form-heading"
                    class="modal-panel flex h-[100dvh] w-full min-w-0 flex-col overflow-hidden border border-white/10 bg-zinc-950 text-zinc-100 shadow-2xl sm:h-auto sm:max-h-[min(90dvh,900px)] sm:max-w-3xl sm:rounded-2xl"
                >
                    <div
                        class="flex shrink-0 items-center justify-between gap-3 border-b border-white/10 px-4 py-4 sm:px-6"
                    >
                        <div class="min-w-0">
                            <p class="eyebrow">CATALOG MANAGEMENT</p>
                            <h2
                                id="catalog-form-heading"
                                class="mt-1 text-lg font-semibold tracking-tight text-white sm:text-xl"
                            >
                                {{
                                    editingWatch
                                        ? "Edit catalog watch"
                                        : "Add catalog watch"
                                }}
                            </h2>
                        </div>
                        <button
                            type="button"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/10 text-xl text-zinc-400 transition hover:bg-white/10 hover:text-white"
                            aria-label="Close form"
                            :disabled="form.processing"
                            @click="closeForm"
                        >
                            ×
                        </button>
                    </div>
                    <form
                        id="catalog-watch-form"
                        class="min-h-0 flex-1 overflow-y-auto overscroll-contain"
                        @submit.prevent="submit"
                    >
                        <div
                            class="grid gap-5 p-4 pb-7 sm:grid-cols-[200px_minmax(0,1fr)] sm:gap-6 sm:p-6 lg:grid-cols-[230px_minmax(0,1fr)]"
                        >
                            <div class="min-w-0">
                                <label
                                    class="mb-2 block text-xs font-semibold text-zinc-300"
                                    for="catalog-photo"
                                    >Watch photo</label
                                >
                                <label
                                    for="catalog-photo"
                                    class="relative flex h-44 cursor-pointer items-center justify-center overflow-hidden rounded-xl border border-dashed border-white/20 bg-zinc-900 transition hover:border-white/40 sm:h-60"
                                >
                                    <img
                                        v-if="previewUrl"
                                        :src="previewUrl"
                                        alt="Selected watch photo preview"
                                        class="h-full w-full object-contain"
                                    />
                                    <span
                                        v-else
                                        class="px-4 text-center text-sm text-zinc-400"
                                        ><span
                                            class="mb-2 block text-2xl"
                                            aria-hidden="true"
                                            >＋</span
                                        >Tap to upload a photo</span
                                    >
                                    <span
                                        v-if="previewUrl"
                                        class="absolute bottom-2 left-2 rounded-lg bg-black/75 px-2.5 py-1.5 text-[11px] font-medium text-white"
                                        >Change photo</span
                                    >
                                </label>
                                <input
                                    id="catalog-photo"
                                    ref="fileInput"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="sr-only"
                                    @change="handlePhoto"
                                />
                                <p class="mt-2 text-xs text-zinc-500">
                                    JPG, PNG or WEBP · up to 5MB
                                </p>
                                <p
                                    v-if="photoError || form.errors.photo"
                                    class="mt-1 text-xs text-red-300"
                                    role="alert"
                                >
                                    {{ photoError || form.errors.photo }}
                                </p>
                            </div>
                            <div
                                class="grid min-w-0 content-start gap-4 sm:grid-cols-2"
                            >
                                <div class="min-w-0">
                                    <label class="form-label" for="watch-brand"
                                        >Brand</label
                                    ><input
                                        id="watch-brand"
                                        v-model="form.brand"
                                        class="field w-full"
                                        autocomplete="off"
                                        placeholder="e.g. Tissot"
                                    />
                                    <p
                                        v-if="form.errors.brand"
                                        class="form-error"
                                    >
                                        {{ form.errors.brand }}
                                    </p>
                                </div>
                                <div class="min-w-0">
                                    <label class="form-label" for="watch-model"
                                        >Model name</label
                                    ><input
                                        id="watch-model"
                                        v-model="form.model_name"
                                        class="field w-full"
                                        autocomplete="off"
                                        placeholder="e.g. PRX Powermatic 80"
                                    />
                                    <p
                                        v-if="form.errors.model_name"
                                        class="form-error"
                                    >
                                        {{ form.errors.model_name }}
                                    </p>
                                </div>
                                <div class="min-w-0">
                                    <label
                                        class="form-label"
                                        for="watch-reference"
                                        >Reference number</label
                                    ><input
                                        id="watch-reference"
                                        v-model="form.reference_number"
                                        class="field w-full"
                                        autocomplete="off"
                                        placeholder="Watch reference"
                                    />
                                    <p
                                        v-if="form.errors.reference_number"
                                        class="form-error"
                                    >
                                        {{ form.errors.reference_number }}
                                    </p>
                                </div>
                                <div class="min-w-0">
                                    <label
                                        class="form-label"
                                        for="watch-category"
                                        >Category</label
                                    ><input
                                        id="watch-category"
                                        v-model="form.category"
                                        class="field w-full"
                                        autocomplete="off"
                                        placeholder="e.g. PRX, Ballade"
                                    />
                                    <p
                                        v-if="form.errors.category"
                                        class="form-error"
                                    >
                                        {{ form.errors.category }}
                                    </p>
                                </div>
                                <div class="min-w-0">
                                    <label class="form-label" for="watch-order"
                                        >Sort order</label
                                    ><input
                                        id="watch-order"
                                        v-model.number="form.sort_order"
                                        type="number"
                                        min="0"
                                        inputmode="numeric"
                                        class="field w-full"
                                    />
                                    <p
                                        v-if="form.errors.sort_order"
                                        class="form-error"
                                    >
                                        {{ form.errors.sort_order }}
                                    </p>
                                </div>
                                <div class="flex items-end">
                                    <label
                                        class="flex min-h-12 w-full cursor-pointer items-center justify-between gap-3 rounded-xl border border-white/10 bg-zinc-900 px-3.5 py-3"
                                        ><span
                                            class="text-xs font-medium text-zinc-200"
                                            >Visible publicly</span
                                        ><input
                                            v-model="form.is_visible"
                                            type="checkbox"
                                            class="h-5 w-5 shrink-0 accent-white"
                                    /></label>
                                    <p
                                        v-if="form.errors.is_visible"
                                        class="form-error"
                                    >
                                        {{ form.errors.is_visible }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </form>
                    <footer
                        class="grid shrink-0 grid-cols-2 gap-2 border-t border-white/10 bg-zinc-950 px-4 py-3 pb-[max(12px,env(safe-area-inset-bottom))] sm:flex sm:justify-end sm:px-6"
                    >
                        <button
                            type="button"
                            class="btn-secondary order-1 sm:order-none"
                            :disabled="form.processing"
                            @click="closeForm"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            form="catalog-watch-form"
                            class="btn-primary order-2 sm:order-none"
                            :disabled="form.processing || Boolean(photoError)"
                        >
                            {{
                                form.processing
                                    ? "Saving…"
                                    : editingWatch
                                      ? "Save changes"
                                      : "Add watch"
                            }}
                        </button>
                    </footer>
                </section>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.catalog-page {
    padding-inline: clamp(0px, 1vw, 12px);
}
/* CSS-only icon: avoids inherited/global svg sizing rules in the admin layout. */
.catalog-search-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    display: block;
    width: 15px;
    height: 15px;
    border: 2px solid #a1a1aa;
    border-radius: 50%;
    transform: translateY(-60%);
    pointer-events: none;
    box-sizing: border-box;
}
.catalog-search-icon::after {
    content: "";
    position: absolute;
    width: 7px;
    height: 2px;
    right: -5px;
    bottom: -3px;
    background: #a1a1aa;
    border-radius: 1px;
    transform: rotate(45deg);
    transform-origin: left center;
}
.eyebrow {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.16em;
    line-height: 1.5;
    color: #a1a1aa;
}
.surface {
    min-width: 0;
    border: 1px solid rgb(255 255 255 / 0.09);
    border-radius: 16px;
    background: rgb(255 255 255 / 0.035);
}
.btn-primary,
.btn-secondary {
    display: inline-flex;
    min-height: 44px;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 16px;
    border-radius: 11px;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.25;
    transition:
        background-color 0.18s,
        border-color 0.18s,
        color 0.18s;
}
.btn-primary {
    background: #fafafa;
    color: #09090b;
    border: 1px solid #fafafa;
}
.btn-primary:hover:not(:disabled) {
    background: #d4d4d8;
    border-color: #d4d4d8;
}
.btn-secondary {
    border: 1px solid rgb(255 255 255 / 0.14);
    color: #e4e4e7;
    background: rgb(255 255 255 / 0.035);
}
.btn-secondary:hover:not(:disabled) {
    background: rgb(255 255 255 / 0.1);
    border-color: rgb(255 255 255 / 0.28);
}
button:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}
.field {
    display: block;
    min-width: 0;
    min-height: 46px;
    border: 1px solid rgb(255 255 255 / 0.13);
    border-radius: 11px;
    background: #18181b;
    padding: 10px 13px;
    color: white;
    font-size: 14px;
    outline: none;
    transition:
        border-color 0.18s,
        box-shadow 0.18s;
}
.field::placeholder {
    color: #71717a;
}
.field:focus {
    border-color: #a1a1aa;
    box-shadow: 0 0 0 3px rgb(255 255 255 / 0.07);
}
.form-label {
    display: block;
    margin-bottom: 7px;
    font-size: 12px;
    font-weight: 600;
    color: #d4d4d8;
}
.form-error {
    margin-top: 5px;
    font-size: 11px;
    color: #fca5a5;
}
.tag {
    border: 1px solid rgb(255 255 255 / 0.11);
    border-radius: 7px;
    padding: 4px 7px;
    background: rgb(0 0 0 / 0.2);
}
.card-action {
    display: inline-flex;
    min-width: 0;
    min-height: 44px;
    align-items: center;
    justify-content: center;
    border-width: 1px;
    border-style: solid;
    border-radius: 9px;
    font-size: 11px;
    font-weight: 600;
    transition: background-color 0.18s;
}
.page-link {
    display: inline-flex;
    min-width: 40px;
    min-height: 40px;
    align-items: center;
    justify-content: center;
    border: 1px solid rgb(255 255 255 / 0.1);
    border-radius: 10px;
    padding: 7px 10px;
    font-size: 12px;
    font-weight: 600;
    transition: background-color 0.18s;
}
@media (max-width: 359px) {
    .watch-card {
        flex-direction: column;
    }
    .watch-card > div:first-child {
        width: 100%;
        aspect-ratio: 5/3;
    }
}
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        transition-duration: 0.01ms !important;
        animation-duration: 0.01ms !important;
    }
}
</style>
