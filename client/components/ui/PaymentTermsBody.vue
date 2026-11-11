<script setup lang="ts">
import type { PaymentTerms } from "~/utils/paymentTerms";

defineProps<{
    terms: PaymentTerms;
}>();
</script>

<template>
    <div class="space-y-6 text-sm leading-6 text-slate-600 dark:text-gray-300">
        <section
            v-for="(section, index) in terms.sections"
            :key="`${index}-${section.title}`"
        >
            <h3
                class="mb-1.5 flex flex-wrap items-center gap-2 text-sm font-bold text-slate-900 dark:text-white"
            >
                {{ index + 1 }}. {{ section.title }}
                <span
                    v-if="section.branch"
                    class="rounded-full bg-primary-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-primary-700 dark:bg-primary-500/10 dark:text-primary-300"
                >
                    Branch policy
                </span>
            </h3>

            <template v-for="(block, blockIndex) in section.blocks" :key="blockIndex">
                <p v-if="block.type === 'paragraph'" class="mt-2 first:mt-0">
                    {{ block.text }}
                </p>

                <ul v-else class="mt-2 list-disc space-y-1 pl-5">
                    <li v-for="item in block.items" :key="item">
                        {{ item }}
                    </li>
                </ul>
            </template>
        </section>

        <p
            v-if="terms.footnote"
            class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500 dark:bg-white/5 dark:text-gray-400"
        >
            {{ terms.footnote }}
        </p>
    </div>
</template>
