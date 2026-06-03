<script setup>
import { router } from "@inertiajs/vue3";

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

const closeModal = () => {
    emit("close");
};

const deleteWatch = () => {
    if (!props.watch) return;

    router.delete(route("admin.watches.destroy", props.watch.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit("close");
        },
    });
};
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
                class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/80 px-4 py-6 backdrop-blur-sm"
            >
                <div class="absolute inset-0" @click="closeModal"></div>

                <div
                    class="relative w-full max-w-md rounded-[2rem] border border-white/10 bg-[#0B0B0D] p-6 shadow-2xl shadow-black"
                >
                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-2xl border border-red-500/20 bg-red-500/10 text-red-300"
                    >
                        <svg
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.7"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM10.29 3.86L1.82 18a1.5 1.5 0 001.29 2.25h17.78A1.5 1.5 0 0022.18 18L13.71 3.86a1.5 1.5 0 00-2.42 0z"
                            />
                        </svg>
                    </div>

                    <h2
                        class="mt-5 text-2xl font-semibold tracking-tight text-white"
                    >
                        Delete watch?
                    </h2>

                    <p class="mt-3 text-sm leading-6 text-zinc-400">
                        This will permanently delete
                        <span class="font-semibold text-white">
                            {{ watch.brand }} {{ watch.model_name }}
                        </span>
                        and its uploaded photos.
                    </p>

                    <div
                        class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"
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
                            class="rounded-2xl bg-red-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-red-400"
                            @click="deleteWatch"
                        >
                            Delete Watch
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
