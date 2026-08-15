<script setup lang="ts">
import { computed } from "vue";
import {
    Stethoscope,
    CheckCircle2,
    XCircle,
    Layers,
    Plus,
} from "lucide-vue-next";
import { Modules } from "~/types/module";
import { usePermissions } from "~/composables/usePermission";
import type { Service } from "~/types/service";

const { canCreate } = usePermissions();

const props = defineProps<{
    services: Service[];
    loading: boolean;
}>();

const emit = defineEmits<{
    addService: [];
}>();

const total = computed(() => props.services.length);

const available = computed(
    () => props.services.filter((s) => s.is_available).length,
);

const unavailable = computed(() => total.value - available.value);

const categoryCount = computed(
    () =>
        new Set(
            props.services
                .map((s) => s.category_name)
                .filter((name): name is string => !!name),
        ).size,
);

const averagePrice = computed(() => {
    if (!props.services.length) return 0;
    const sum = props.services.reduce(
        (acc, s) => acc + (Number(s.price) || 0),
        0,
    );
    return sum / props.services.length;
});

const formatPrice = (price: number) => `₱${price.toFixed(2)}`;

const stats = computed(() => [
    {
        label: "Total Services",
        value: total.value,
        icon: Stethoscope,
        accent: "bg-primary/10 text-primary",
    },
    {
        label: "Available",
        value: available.value,
        icon: CheckCircle2,
        accent: "bg-emerald-50 text-emerald-600",
    },
    {
        label: "Unavailable",
        value: unavailable.value,
        icon: XCircle,
        accent: "bg-rose-50 text-rose-500",
    },
    {
        label: "Categories",
        value: categoryCount.value,
        icon: Layers,
        accent: "bg-violet-50 text-violet-600",
        extra: `Avg ${formatPrice(averagePrice.value)}`,
    },
]);
</script>

<template>
    <div
        class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm"
    >
        <div
            class="flex flex-col gap-4 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h2 class="text-lg font-semibold text-slate-900">
                    Service Overview
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    A quick snapshot of your medical services.
                </p>
            </div>

            <button
                v-if="canCreate(Modules.Services)"
                type="button"
                @click="emit('addService')"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-primary px-5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md active:scale-[0.98]"
            >
                <Plus class="h-4 w-4" />
                Add Service
            </button>
        </div>

        <div
            class="grid grid-cols-1 divide-y divide-slate-100 sm:grid-cols-2 sm:divide-x sm:divide-y-0 lg:grid-cols-4"
        >
            <template v-if="loading">
                <div
                    v-for="n in 4"
                    :key="n"
                    class="p-5 animate-pulse space-y-3"
                >
                    <div class="h-9 w-9 rounded-xl bg-slate-100" />
                    <div class="h-6 w-16 rounded bg-slate-100" />
                    <div class="h-3 w-20 rounded bg-slate-100" />
                </div>
            </template>

            <template v-else>
                <div v-for="stat in stats" :key="stat.label" class="p-5">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl"
                        :class="stat.accent"
                    >
                        <component :is="stat.icon" class="h-5 w-5" />
                    </div>

                    <p class="mt-3 text-2xl font-bold text-slate-900">
                        {{ stat.value }}
                    </p>

                    <div
                        class="mt-1 flex items-center justify-between text-xs text-slate-500"
                    >
                        <span>{{ stat.label }}</span>
                        <span
                            v-if="stat.extra"
                            class="font-medium text-slate-400"
                        >
                            {{ stat.extra }}
                        </span>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
