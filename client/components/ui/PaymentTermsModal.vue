<script setup lang="ts">
import { onBeforeUnmount, onMounted } from "vue";
import { X } from "lucide-vue-next";
import BaseButton from "./BaseButton.vue";
import type { PaymentTerms } from "~/utils/paymentTerms";

const props = withDefaults(
    defineProps<{
        terms: PaymentTerms;
        showAccept?: boolean;
    }>(),
    { showAccept: false },
);

const emit = defineEmits<{
    close: [];
    accept: [];
}>();

function onKeydown(event: KeyboardEvent) {
    if (event.key === "Escape") emit("close");
}

onMounted(() => {
    document.addEventListener("keydown", onKeydown);
    document.body.style.overflow = "hidden";
});

onBeforeUnmount(() => {
    document.removeEventListener("keydown", onKeydown);
    document.body.style.overflow = "";
});
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-[60] flex items-center justify-center bg-secondary/50 px-4 py-6 backdrop-blur-sm dark:bg-white/10"
            @click.self="emit('close')"
        >
            <div
                class="flex max-h-[88vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary dark:ring-white/10"
                role="dialog"
                aria-modal="true"
                aria-labelledby="payment-terms-title"
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-6 py-5 dark:border-white/10"
                >
                    <div class="min-w-0">
                        <h2
                            id="payment-terms-title"
                            class="text-lg font-bold text-slate-900 dark:text-white"
                        >
                            {{ terms.heading }}
                        </h2>
                        <p
                            class="mt-0.5 text-xs text-slate-500 dark:text-gray-400"
                        >
                            Last updated {{ terms.lastUpdated }}
                        </p>
                    </div>

                    <button
                        type="button"
                        aria-label="Close terms and conditions"
                        class="shrink-0 rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-200"
                        @click="emit('close')"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div
                    class="min-h-0 flex-1 space-y-6 overflow-y-auto px-6 py-5 text-sm leading-6 text-slate-600 dark:text-gray-300"
                >
                    <section
                        v-for="(section, index) in terms.sections"
                        :key="section.title"
                    >
                        <h3
                            class="mb-1.5 text-sm font-bold text-slate-900 dark:text-white"
                        >
                            {{ index + 1 }}. {{ section.title }}
                        </h3>

                        <template
                            v-for="(block, blockIndex) in section.blocks"
                            :key="blockIndex"
                        >
                            <p v-if="block.type === 'paragraph'" class="mt-2 first:mt-0">
                                {{ block.text }}
                            </p>

                            <ul
                                v-else
                                class="mt-2 list-disc space-y-1 pl-5"
                            >
                                <li v-for="item in block.items" :key="item">
                                    {{ item }}
                                </li>
                            </ul>
                        </template>
                    </section>
                </div>

                <div
                    class="flex shrink-0 justify-end gap-2 border-t border-slate-100 px-6 py-4 dark:border-white/10"
                >
                    <BaseButton
                        v-if="showAccept"
                        variant="secondary"
                        size="md"
                        @click="emit('close')"
                    >
                        Close
                    </BaseButton>

                    <BaseButton
                        variant="primary"
                        size="md"
                        @click="showAccept ? emit('accept') : emit('close')"
                    >
                        {{ showAccept ? "I Agree" : "Close" }}
                    </BaseButton>
                </div>
            </div>
        </div>
    </Teleport>
</template>
