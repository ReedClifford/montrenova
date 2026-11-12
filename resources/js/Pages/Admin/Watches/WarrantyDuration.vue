<script setup>
import { useForm } from "@inertiajs/vue3";
import { watch } from "vue";

const props = defineProps({ item: { type: Object, required: true } });
const form = useForm({ warranty_years: props.item.warranty_years });

watch(() => props.item.warranty_years, (years) => {
    form.warranty_years = years;
});

function save() {
    form.patch(route("admin.watches.warranty.update", props.item.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <form class="mt-3 flex flex-wrap items-end gap-2" @submit.prevent="save">
        <label class="flex flex-col gap-1 text-xs text-zinc-400">
            Warranty length
            <select v-model="form.warranty_years" class="rounded-md border border-white/15 bg-zinc-900 px-2 py-2 text-sm text-white" :disabled="form.processing">
                <option :value="1">1 year</option>
                <option :value="2">2 years</option>
            </select>
        </label>
        <button
            type="submit"
            class="rounded-md border border-white/15 px-3 py-2 text-sm text-white disabled:opacity-40"
            :disabled="form.processing || form.warranty_years === item.warranty_years"
        >
            Save
        </button>
        <p v-if="form.errors.warranty_years" role="alert" class="w-full text-xs text-red-300">{{ form.errors.warranty_years }}</p>
    </form>
</template>
