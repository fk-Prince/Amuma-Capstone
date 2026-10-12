<script setup lang="ts">
import { computed } from "vue";
import {
    Bath,
    Clock,
    HandHeart,
    Home,
    Shirt,
    Soup,
    Stethoscope,
    Footprints,
} from "lucide-vue-next";
import type { BranchRetrieve } from "~/types/branch";
import { formatCurrency } from "~/utils/currency";

const props = defineProps<{
    branch: BranchRetrieve | null;
    hasHomecare: boolean;
}>();

const emit = defineEmits<{
    homecare: [];
}>();

const ADL_ACTIVITIES = [
    { label: "Bathing", icon: Bath },
    { label: "Dressing", icon: Shirt },
    { label: "Meals", icon: Soup },
    { label: "Mobility", icon: Footprints },
];

const adl = computed(() => {
    const homecare = props.branch?.homecare;
    const rate = Number(homecare?.adl_hourly_rate ?? 0);

    if (!props.hasHomecare || rate <= 0) return null;

    return {
        rate,
        minHours: homecare?.adl_min_hour ?? 8,
        description: homecare?.description,
    };
});

const medicalGroups = computed(() => {
    if (!props.hasHomecare) return [];

    const groups = new Map<string, any[]>();

    (props.branch?.services ?? []).forEach((service: any) => {
        const category =
            service.category?.category_name ??
            service.category_name ??
            "Other Services";

        if (!groups.has(category)) groups.set(category, []);
        groups.get(category)!.push(service);
    });

    return [...groups.entries()].map(([name, services]) => ({
        name,
        services,
    }));
});

const serviceCount = computed(() =>
    medicalGroups.value.reduce((sum, group) => sum + group.services.length, 0),
);

const peso = (value: number | string) =>
    formatCurrency(value, { minimumFractionDigits: 0 });
</script>

<template>
    <section
        class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-secondary"
    >
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-start gap-3">
                <span
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-400 to-teal-500 text-white shadow-md shadow-emerald-500/20"
                >
                    <Home class="h-5 w-5" />
                </span>
                <div>
                    <h2
                        class="text-xl font-bold text-secondary dark:text-white"
                    >
                        Homecare Services
                    </h2>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                        Care delivered at home by {{ branch?.name }}.
                    </p>
                </div>
            </div>

            <span
                v-if="serviceCount || adl"
                class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
            >
                {{ serviceCount + (adl ? 1 : 0) }} services
            </span>
        </div>

        <div
            v-if="!adl && !medicalGroups.length"
            class="mt-6 flex flex-col items-center rounded-2xl border border-dashed border-gray-200 px-6 py-12 text-center dark:border-white/10"
        >
            <span
                class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300"
            >
                <Home class="h-6 w-6" />
            </span>
            <p
                class="mt-3 text-sm font-semibold text-gray-700 dark:text-gray-200"
            >
                No homecare services offered yet
            </p>
        </div>

        <template v-else>
            <article
                v-if="adl"
                class="service-card relative mt-6 overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-600 p-6 text-white shadow-lg shadow-emerald-500/20"
            >
                <span
                    class="pointer-events-none absolute -right-10 -top-12 h-40 w-40 rounded-full bg-white/10"
                />
                <span
                    class="pointer-events-none absolute -bottom-16 right-24 h-32 w-32 rounded-full bg-white/10"
                />

                <div class="relative flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
                    <div class="min-w-0 md:max-w-[60%]">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-white/20 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide backdrop-blur"
                        >
                            <HandHeart class="h-3.5 w-3.5" />
                            Care Contract
                        </span>

                        <h3 class="mt-3 text-xl font-bold">
                            Activities of Daily Living (ADL)
                        </h3>
                        <p class="mt-1.5 line-clamp-3 text-sm text-white/85">
                            {{
                                adl.description ||
                                "A dedicated caregiver at home to help your loved one with everyday routines."
                            }}
                        </p>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <span
                                v-for="activity in ADL_ACTIVITIES"
                                :key="activity.label"
                                class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-medium"
                            >
                                <component
                                    :is="activity.icon"
                                    class="h-3.5 w-3.5"
                                />
                                {{ activity.label }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="flex shrink-0 flex-col gap-3 rounded-xl bg-white/15 p-4 backdrop-blur md:items-end md:text-right"
                    >
                        <div>
                            <p class="text-3xl font-extrabold leading-none">
                                {{ peso(adl.rate) }}
                                <span class="text-sm font-medium text-white/75"
                                    >/ hour</span
                                >
                            </p>
                            <p
                                class="mt-1.5 inline-flex items-center gap-1 text-xs text-white/80"
                            >
                                <Clock class="h-3.5 w-3.5" />
                                Minimum of {{ adl.minHours }} hours
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-lg bg-white px-5 py-2 text-sm font-semibold text-emerald-700 shadow-sm transition hover:bg-emerald-50"
                            @click="emit('homecare')"
                        >
                            Book Homecare
                        </button>
                    </div>
                </div>
            </article>

            <div v-if="medicalGroups.length" class="mt-8">
                <div class="flex items-center gap-2">
                    <Stethoscope class="h-4 w-4 text-primary" />
                    <h3
                        class="text-sm font-bold text-secondary dark:text-white"
                    >
                        Medical Services
                    </h3>
                </div>

                <div class="mt-3 flex flex-col gap-4">
                    <div
                        v-for="(group, groupIndex) in medicalGroups"
                        :key="group.name"
                        class="service-card overflow-hidden rounded-2xl border border-gray-100 dark:border-white/10"
                        :style="{ animationDelay: `${Math.min(groupIndex + 1, 8) * 60}ms` }"
                    >
                        <div
                            class="flex items-center justify-between gap-3 border-b border-gray-100 bg-gradient-to-r from-primary-50 to-transparent px-4 py-3 dark:border-white/10 dark:from-primary/10"
                        >
                            <p
                                class="text-sm font-semibold text-secondary dark:text-white"
                            >
                                {{ group.name }}
                            </p>
                            <span
                                class="rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold text-primary ring-1 ring-primary/15 dark:bg-white/10"
                            >
                                {{ group.services.length }}
                            </span>
                        </div>

                        <ul
                            class="divide-y divide-gray-100 dark:divide-white/10"
                        >
                            <li
                                v-for="service in group.services"
                                :key="service.service_uuid ?? service.service_id"
                                class="flex items-center justify-between gap-3 px-4 py-3 transition hover:bg-gray-50 dark:hover:bg-white/5"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <span
                                        class="h-2 w-2 shrink-0 rounded-full"
                                        :class="
                                            service.is_available === false
                                                ? 'bg-gray-300 dark:bg-white/20'
                                                : 'bg-emerald-400'
                                        "
                                    />
                                    <p
                                        class="truncate text-sm font-medium text-gray-800 dark:text-gray-100"
                                        :class="
                                            service.is_available === false
                                                ? 'opacity-50'
                                                : ''
                                        "
                                    >
                                        {{ service.service_name }}
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-3">
                                    <span
                                        v-if="service.is_available === false"
                                        class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-500 dark:bg-white/10 dark:text-gray-400"
                                    >
                                        Unavailable
                                    </span>
                                    <span
                                        class="text-sm font-bold text-secondary dark:text-white"
                                    >
                                        {{ peso(service.price) }}
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </template>
    </section>
</template>

<style scoped>
.service-card {
    animation: service-in 0.4s ease-out backwards;
}

@keyframes service-in {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
