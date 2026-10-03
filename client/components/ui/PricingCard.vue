<template>
    <div
        :class="[
            'group relative rounded-2xl flex flex-col transition-all duration-300 ease-out',
            'hover:-translate-y-2 hover:scale-[1.015]',
            featured
                ? 'bg-primary text-white shadow-2xl pt-10 pb-8 px-8 border border-primary hover:shadow-[0_20px_50px_-12px_rgba(0,0,0,0.45)]'
                : 'bg-white text-secondary shadow-sm border border-muted-light p-8 hover:border-primary hover:shadow-[0_20px_40px_-15px_rgba(0,0,0,0.15)] dark:bg-secondary dark:text-white dark:border-white/10',
        ]"
    >
        <div
            v-if="featured"
            class="absolute -top-4 left-1/2 -translate-x-1/2 whitespace-nowrap"
        >
            <span
                class="bg-white text-primary text-xs font-bold px-5 py-1.5 rounded-full shadow-md tracking-wide border border-primary/10 dark:border-white/20"
            >
                Most Popular
            </span>
        </div>

        <div
            class="pointer-events-none absolute inset-0 overflow-hidden rounded-2xl"
            aria-hidden="true"
        >
            <div
                :class="[
                    'absolute right-[-62px] top-[46px] w-[240px] rotate-45 py-1.5 text-center text-[10px] font-extrabold uppercase leading-4 tracking-wider shadow-lg',
                    featured
                        ? 'bg-white text-emerald-600 shadow-black/15'
                        : 'bg-gradient-to-r from-emerald-500 to-emerald-400 text-white shadow-emerald-500/30',
                ]"
            >
                1 Month Free Testing
            </div>
        </div>

        <div class="mb-4">
            <span
                :class="[
                    'text-xs font-semibold px-3 py-1 rounded-full transition-colors duration-300',
                    featured
                        ? 'bg-white/20 text-white'
                        : 'bg-light text-primary group-hover:bg-primary group-hover:text-white dark:bg-primary-500/10',
                ]"
            >
                {{ planLabel }}
            </span>
        </div>

        <div class="mb-6 pr-14">
            <h2
                :class="[
                    'font-display font-bold text-2xl leading-tight mb-1',
                    featured ? 'text-white' : 'text-secondary dark:text-white',
                ]"
            >
                {{ title }}
            </h2>
            <p
                :class="[
                    'text-sm',
                    featured ? 'text-white/70' : 'text-muted dark:text-gray-400',
                ]"
            >
                {{ description }}
            </p>
        </div>

        <div class="flex items-baseline gap-1 mb-2">
            <span
                :class="[
                    'font-display font-extrabold text-4xl transition-transform duration-300 group-hover:scale-105 origin-left inline-block',
                    featured ? 'text-white' : 'text-secondary dark:text-white',
                ]"
            >
                {{ formatCurrency(price) }}
            </span>
            <span
                :class="[
                    'text-sm font-medium',
                    featured ? 'text-white/60' : 'text-muted dark:text-gray-400',
                ]"
            >
                / year
            </span>
        </div>

        <p
            :class="[
                'mb-5 text-sm font-medium',
                featured ? 'text-white/80' : 'text-primary dark:text-primary-300',
            ]"
        >
            {{ branchNote }}
        </p>

        <button
            type="button"
            @click="$emit('select', $props)"
            :class="[
                'w-full py-3 rounded-xl font-semibold text-sm flex items-center justify-center gap-2 transition-all duration-200 mb-6',
                featured
                    ? 'bg-white text-primary hover:bg-light border border-white'
                    : 'bg-white text-secondary border border-secondary hover:bg-secondary hover:text-white dark:bg-white/5 dark:text-white dark:border-white/20 dark:hover:bg-white/10',
            ]"
        >
            {{ ctaText }}
            <svg
                width="16"
                height="16"
                viewBox="0 0 16 16"
                fill="none"
                class="transition-transform duration-300 group-hover:translate-x-1"
            >
                <path
                    d="M3 8h10M9 4l4 4-4 4"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
        </button>

        <div
            :class="[
                'w-full h-px mb-5 transition-colors duration-300',
                featured
                    ? 'bg-white/10'
                    : 'bg-muted-light group-hover:bg-primary/20 dark:bg-white/10',
            ]"
        />

        <ul class="flex flex-col gap-3 flex-1">
            <li
                v-for="(feature, i) in features"
                :key="feature"
                class="flex items-center gap-2.5 text-sm transition-transform duration-300"
                :style="{ transitionDelay: `${i * 30}ms` }"
            >
                <svg
                    class="w-4 h-4 flex-shrink-0 transition-transform duration-300 group-hover:scale-110"
                    :class="
                        featured ? 'text-white' : 'text-accent dark:text-accent-300'
                    "
                    viewBox="0 0 16 16"
                    fill="none"
                >
                    <path
                        d="M3 8l4 4 6-6"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>

                <span
                    :class="
                        featured ? 'text-white/85' : 'text-muted-dark dark:text-gray-300'
                    "
                >
                    {{ feature }}
                </span>
            </li>
        </ul>
    </div>
</template>

<script setup lang="ts">
import { formatCurrency } from "~/utils/currency";

defineProps<{
    planLabel: string;
    title: string;
    description: string;
    price: number | undefined | string;
    branchNote: string;
    ctaText: string;
    features: string[];
    featured?: boolean;
}>();

defineEmits<{
    (e: "select", plan: any): void;
}>();
</script>
