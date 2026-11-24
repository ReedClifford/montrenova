<script setup>
import InputError from "@/Components/InputError.vue";
import axios from "axios";
import { onBeforeUnmount, ref } from "vue";

const props = defineProps({
    form: { type: Object, required: true },
    duplicate: { type: Boolean, default: false },
});

const fields = [
    "brand", "model_name", "movement", "case_size", "case_material",
    "dial_color", "crystal", "bracelet_or_strap", "water_resistance",
];

const busy = ref(false);
const message = ref("");
const source = ref(null);
let request;
let attemptedReference = "";
let applied = {};

const normalize = (value) => value.replace(/[. -]/g, "").toUpperCase();

function updateReference(event) {
    const value = event.target.value;
    const changed = normalize(value) !== normalize(props.form.reference_number);
    props.form.reference_number = value;

    if (changed) {
        request?.abort();
        request = undefined;
        busy.value = false;
        attemptedReference = "";
        message.value = "";
        source.value = null;

        for (const [field, { previous, value: filledValue }] of Object.entries(applied)) {
            if (props.form[field] === filledValue) {
                props.form[field] = previous;
            }
        }
        applied = {};
    }

    if (event.inputType === "insertFromPaste") {
        lookup();
    }
}

async function lookup(retry = false) {
    const reference = props.form.reference_number.trim();
    const normalized = normalize(reference);
    if (!reference || busy.value || (!retry && normalized === attemptedReference)) {
        return;
    }

    if (!/^[A-Za-z0-9][A-Za-z0-9 ./_-]*$/.test(reference) || reference.length > 255) {
        message.value = "Enter a valid watch reference to look up specs.";
        return;
    }

    attemptedReference = normalized;
    busy.value = true;
    source.value = null;
    message.value = "Looking up watch details…";
    const controller = new AbortController();
    request = controller;
    const before = Object.fromEntries(fields.map((field) => [field, props.form[field]]));
    const defaultBrand = !props.duplicate && before.brand === "Seiko" && !before.model_name?.trim();

    try {
        const { data } = await axios.get(route("admin.watches.reference-lookup"), {
            params: { reference },
            signal: controller.signal,
            headers: { Accept: "application/json" },
        });
        if (request !== controller) return;

        let filled = false;
        for (const field of fields) {
            const value = data.details[field];
            const canFill = !before[field]?.trim() || (field === "brand" && defaultBrand);
            if (value && canFill && props.form[field] === before[field]) {
                props.form[field] = value;
                applied[field] = { previous: before[field], value };
                filled = true;
            }
        }

        source.value = {
            label: data.source,
            url: data.source_url,
            verificationStatus: data.verification_status,
        };
        message.value = filled
            ? "Watch details filled. Review them before saving."
            : "Reference found. Your existing details were kept.";
    } catch (error) {
        if (request !== controller) return;

        const status = error.response?.status;
        message.value = status === 404
            ? error.response.data.message
            : "Couldn’t look up this reference. Try again or enter the specs manually.";
    } finally {
        if (request === controller) {
            request = undefined;
            busy.value = false;
        }
    }
}

onBeforeUnmount(() => {
    request?.abort();
    request = undefined;
});
</script>

<template>
    <div class="min-w-0">
        <label
            for="create-watch-reference"
            class="mb-2 block text-[0.7rem] uppercase tracking-[0.16em] text-zinc-400"
        >
            Reference Number / SKU
        </label>
        <div
            class="reference-control"
            :class="{ 'reference-control-invalid': form.errors.reference_number }"
        >
            <input
                id="create-watch-reference"
                :value="form.reference_number"
                class="reference-input"
                placeholder="SSC813"
                autocomplete="off"
                autocapitalize="characters"
                spellcheck="false"
                :aria-invalid="Boolean(form.errors.reference_number)"
                :aria-describedby="[
                    message && 'create-watch-reference-status',
                    form.errors.reference_number && 'create-watch-reference-error',
                ].filter(Boolean).join(' ') || undefined"
                @input="updateReference"
                @blur="lookup()"
                @keydown.enter.prevent="lookup(true)"
            />
            <button
                type="button"
                class="reference-action"
                :disabled="busy || !form.reference_number.trim() || Boolean(source)"
                @click="lookup(true)"
            >
                <span v-if="busy" class="reference-spinner" aria-hidden="true"></span>
                {{ busy ? "Looking up" : "Look up" }}
            </button>
        </div>
        <p
            v-if="message"
            id="create-watch-reference-status"
            class="mt-2 text-xs leading-relaxed"
            :class="source ? 'text-emerald-300' : busy ? 'text-zinc-400' : 'text-amber-200'"
            role="status"
        >
            {{ message }}
            <span v-if="source" class="ml-1 text-zinc-400">{{ source.label }}.</span>
            <span v-if="source?.verificationStatus" class="mt-1 block text-amber-200">
                {{ source.verificationStatus }} specs before saving.
            </span>
            <a
                v-if="source?.url"
                :href="source.url"
                target="_blank"
                rel="noopener noreferrer"
                class="ml-1 font-medium underline underline-offset-2"
            >Check SKU on Tissot</a>
        </p>
        <InputError
            id="create-watch-reference-error"
            class="mt-2"
            :message="form.errors.reference_number"
        />
    </div>
</template>

<style scoped>
.reference-control {
    display: flex;
    align-items: center;
    min-height: 47px;
    padding: 3px;
    border: 1px solid rgb(255 255 255 / 0.12);
    border-radius: 1rem;
    background: #050505;
    transition: border-color 150ms ease, box-shadow 150ms ease;
}

.reference-control:focus-within {
    border-color: rgb(255 255 255 / 0.38);
    box-shadow: 0 0 0 2px rgb(255 255 255 / 0.08);
}

.reference-control-invalid {
    border-color: rgb(248 113 113 / 0.65);
}

.reference-input {
    flex: 1;
    min-width: 0;
    border: 0;
    border-radius: inherit;
    padding: 0.6rem 0.75rem;
    background: transparent;
    color: white;
    font-size: 0.875rem;
}

.reference-input::placeholder {
    color: #71717a;
}

.reference-input:focus {
    outline: none;
    box-shadow: none;
}

.reference-action {
    display: inline-flex;
    flex: none;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    min-height: 39px;
    padding: 0 0.75rem;
    border: 1px solid rgb(253 230 138 / 0.18);
    border-radius: 0.75rem;
    background: #28221a;
    color: #fde5b1;
    font-size: 0.75rem;
    font-weight: 600;
    transition: background-color 150ms ease, color 150ms ease;
}

.reference-action:hover:not(:disabled) {
    background: #3a3023;
}

.reference-action:focus-visible {
    outline: 2px solid #fde5b1;
    outline-offset: 1px;
}

.reference-action:disabled {
    border-color: rgb(255 255 255 / 0.08);
    background: #18181b;
    color: #a1a1aa;
}

.reference-spinner {
    width: 0.75rem;
    height: 0.75rem;
    border: 1px solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
}

@media (prefers-reduced-motion: no-preference) {
    .reference-spinner {
        animation: reference-spin 650ms linear infinite;
    }
}

@keyframes reference-spin {
    to { transform: rotate(360deg); }
}

@media (max-width: 640px) {
    .reference-input {
        font-size: 16px;
    }
}
</style>
