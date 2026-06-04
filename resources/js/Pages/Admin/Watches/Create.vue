<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputError from "@/Components/InputError.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

const imagePreviews = ref([]);

const form = useForm({
    stock_code: "",
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
                "Accepted payment methods include cash, GCash, bank transfer, QR code payments, and selected trade-ins subject to evaluation. All photos posted are actual photos unless stated otherwise.",
        },
    ],
});

const handleImages = (event) => {
    const files = Array.from(event.target.files || []);

    form.images = files;
    imagePreviews.value = files.map((file) => URL.createObjectURL(file));
};

const submit = () => {
    form.post(route("admin.watches.store"), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Add Watch | Montre Nova" />

    <AuthenticatedLayout title="Add Watch">
        <form @submit.prevent="submit" class="space-y-6">
            <div
                class="flex flex-col justify-between gap-4 md:flex-row md:items-center"
            >
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-zinc-600">
                        Inventory
                    </p>
                    <h2
                        class="mt-2 text-3xl font-semibold tracking-tight text-white"
                    >
                        Add New Watch
                    </h2>
                    <p class="mt-2 text-sm text-zinc-400">
                        Encode watch details, pricing, status, and upload HD
                        photos.
                    </p>
                </div>

                <div class="flex gap-3">
                    <Link
                        :href="route('admin.watches.index')"
                        class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30"
                    >
                        Cancel
                    </Link>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-zinc-200 disabled:opacity-50"
                    >
                        {{ form.processing ? "Saving..." : "Save Watch" }}
                    </button>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1fr_420px]">
                <div class="space-y-6">
                    <section
                        class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-6"
                    >
                        <h3 class="text-lg font-semibold text-white">
                            Basic Information
                        </h3>

                        <div class="mt-6 grid gap-5 md:grid-cols-2">
                            <div>
                                <label
                                    class="mb-2 block text-xs uppercase tracking-[0.18em] text-zinc-500"
                                >
                                    Stock Code
                                </label>
                                <input
                                    v-model="form.stock_code"
                                    class="mn-input"
                                    placeholder="MN-0001"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.stock_code"
                                />
                            </div>

                            <div>
                                <label
                                    class="mb-2 block text-xs uppercase tracking-[0.18em] text-zinc-500"
                                >
                                    Brand
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
                                <label
                                    class="mb-2 block text-xs uppercase tracking-[0.18em] text-zinc-500"
                                >
                                    Model Name
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
                                <label
                                    class="mb-2 block text-xs uppercase tracking-[0.18em] text-zinc-500"
                                >
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
                                <label
                                    class="mb-2 block text-xs uppercase tracking-[0.18em] text-zinc-500"
                                >
                                    Condition
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
                                <label
                                    class="mb-2 block text-xs uppercase tracking-[0.18em] text-zinc-500"
                                >
                                    Category
                                </label>
                                <input
                                    v-model="form.category"
                                    class="mn-input"
                                    placeholder="Diver, GMT, Dress, Chronograph"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <label
                                class="mb-2 block text-xs uppercase tracking-[0.18em] text-zinc-500"
                            >
                                Description
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="5"
                                class="mn-input"
                                placeholder="Short product description..."
                            ></textarea>
                        </div>
                    </section>

                    <section
                        class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-6"
                    >
                        <h3 class="text-lg font-semibold text-white">
                            Watch Specifications
                        </h3>

                        <div class="mt-6 grid gap-5 md:grid-cols-2">
                            <input
                                v-model="form.movement"
                                class="mn-input"
                                placeholder="Movement"
                            />
                            <input
                                v-model="form.case_size"
                                class="mn-input"
                                placeholder="Case Size"
                            />
                            <input
                                v-model="form.case_material"
                                class="mn-input"
                                placeholder="Case Material"
                            />
                            <input
                                v-model="form.dial_color"
                                class="mn-input"
                                placeholder="Dial Color"
                            />
                            <input
                                v-model="form.crystal"
                                class="mn-input"
                                placeholder="Crystal"
                            />
                            <input
                                v-model="form.bracelet_or_strap"
                                class="mn-input"
                                placeholder="Bracelet / Strap"
                            />
                            <input
                                v-model="form.water_resistance"
                                class="mn-input"
                                placeholder="Water Resistance"
                            />
                            <input
                                v-model="form.box_papers"
                                class="mn-input"
                                placeholder="Box and Papers"
                            />
                            <input
                                v-model="form.warranty_type"
                                class="mn-input md:col-span-2"
                                placeholder="Warranty Type"
                            />
                        </div>
                    </section>

                    <section
                        class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-6"
                    >
                        <h3 class="text-lg font-semibold text-white">
                            Per-Watch Details
                        </h3>

                        <div class="mt-6 space-y-5">
                            <div
                                v-for="(section, index) in form.sections"
                                :key="index"
                                class="rounded-2xl border border-white/10 bg-white/[0.03] p-5"
                            >
                                <input
                                    v-model="section.title"
                                    class="mn-input"
                                    placeholder="Section Title"
                                />
                                <textarea
                                    v-model="section.content"
                                    rows="5"
                                    class="mn-input mt-3"
                                    placeholder="Section content..."
                                ></textarea>
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="space-y-6">
                    <section
                        class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-6"
                    >
                        <h3 class="text-lg font-semibold text-white">
                            Pricing & Status
                        </h3>

                        <div class="mt-6 space-y-5">
                            <input
                                v-model="form.capital_price"
                                type="number"
                                step="0.01"
                                class="mn-input"
                                placeholder="Capital Price"
                            />
                            <input
                                v-model="form.selling_price"
                                type="number"
                                step="0.01"
                                class="mn-input"
                                placeholder="Selling Price"
                            />
                            <input
                                v-model="form.discounted_price"
                                type="number"
                                step="0.01"
                                class="mn-input"
                                placeholder="Discounted Price"
                            />

                            <select v-model="form.status" class="mn-input">
                                <option value="draft">Draft</option>
                                <option value="available">Available</option>
                                <option value="reserved">Reserved</option>
                                <option value="sold">Sold</option>
                                <option value="hidden">Hidden</option>
                            </select>

                            <div
                                class="space-y-3 border-t border-white/10 pt-5"
                            >
                                <label
                                    class="flex items-center justify-between text-sm text-zinc-400"
                                >
                                    <span>Visible on website</span>
                                    <input
                                        v-model="form.is_visible"
                                        type="checkbox"
                                        class="rounded border-white/20 bg-black text-white"
                                    />
                                </label>

                                <label
                                    class="flex items-center justify-between text-sm text-zinc-400"
                                >
                                    <span>Featured watch</span>
                                    <input
                                        v-model="form.is_featured"
                                        type="checkbox"
                                        class="rounded border-white/20 bg-black text-white"
                                    />
                                </label>

                                <label
                                    class="flex items-center justify-between text-sm text-zinc-400"
                                >
                                    <span>Display price</span>
                                    <input
                                        v-model="form.display_price"
                                        type="checkbox"
                                        class="rounded border-white/20 bg-black text-white"
                                    />
                                </label>

                                <label
                                    class="flex items-center justify-between text-sm text-zinc-400"
                                >
                                    <span>Allow inquiry</span>
                                    <input
                                        v-model="form.allow_inquiry"
                                        type="checkbox"
                                        class="rounded border-white/20 bg-black text-white"
                                    />
                                </label>
                            </div>
                        </div>
                    </section>

                    <section
                        class="rounded-[1.7rem] border border-white/10 bg-[#0B0B0D] p-6"
                    >
                        <h3 class="text-lg font-semibold text-white">
                            HD Photos
                        </h3>

                        <label
                            class="mt-5 flex cursor-pointer flex-col items-center justify-center rounded-[1.4rem] border border-dashed border-white/20 bg-white/[0.03] px-5 py-10 text-center transition hover:border-white/40"
                        >
                            <span class="text-sm font-semibold text-white">
                                Upload HD Photos
                            </span>
                            <span class="mt-2 text-xs text-zinc-500">
                                JPG, PNG, WEBP up to 10MB each
                            </span>
                            <input
                                type="file"
                                multiple
                                accept="image/*"
                                class="hidden"
                                @change="handleImages"
                            />
                        </label>

                        <InputError
                            class="mt-2"
                            :message="form.errors.images"
                        />

                        <div
                            v-if="imagePreviews.length"
                            class="mt-5 grid grid-cols-2 gap-3"
                        >
                            <img
                                v-for="preview in imagePreviews"
                                :key="preview"
                                :src="preview"
                                class="aspect-square rounded-2xl border border-white/10 object-cover"
                            />
                        </div>
                    </section>
                </aside>
            </div>
        </form>
    </AuthenticatedLayout>
</template>

<style scoped>
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
</style>
