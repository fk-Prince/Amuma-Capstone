<template>
    <div
        v-if="variant === 1"
        class="rounded-2xl border bg-white overflow-hidden cursor-pointer transition hover:shadow-md"
        @click="$emit('select', branch)"
    >
        <div class="h-32 bg-slate-100">
            <img
                v-if="branch?.image"
                :src="branch.image"
                class="w-full h-full object-cover"
                alt="branch image"
            />
            <img
                v-else
                :src="Logo"
                class="w-16 h-16 object-contain opacity-60"
                alt="default logo"
            />
        </div>

        <div class="p-4">
            <h3 class="font-semibold text-slate-900">
                {{ branch.name }}
            </h3>

            <p class="text-xs text-slate-500 mt-1 flex gap-1">
                <Location /> {{ branch.location.street }},
                {{ branch.location.city }}
            </p>

            <div class="mt-3 flex items-center justify-between">
                <span
                    class="text-xs px-2 py-1 rounded-full font-semibold"
                    :class="
                        branch.availability.is_open
                            ? 'bg-green-100 text-green-700'
                            : 'bg-red-100 text-red-600'
                    "
                >
                    {{ branch.availability.is_open ? "Open" : "Closed" }}
                </span>

                <span class="text-xs text-slate-400">
                    {{ branch.availability.opening_time }} -
                    {{ branch.availability.closing_time }}
                </span>
            </div>

            <div class="mt-3 text-xs text-slate-500">
                ⭐
                <span class="font-semibold text-slate-700">
                    {{ branch.averageRating ?? "No rating" }}
                </span>
                ({{ branch.reviewCount }})
            </div>
        </div>

        <div class="p-4 border-t bg-slate-50 flex justify-end items-center">
            <button
                class="text-xs font-semibold text-white bg-primary px-5 py-1.5 rounded-lg hover:bg-blue-700"
            >
                Book Now
            </button>
        </div>
    </div>

    <div
        v-else-if="variant === 2"
        class="flex border rounded-2xl bg-white overflow-hidden shadow-sm hover:shadow-md transition cursor-pointer"
        @click="$emit('select', branch)"
    >
        <div class="w-56 h-full bg-slate-100 relative">
            <img
                v-if="branch?.image"
                :src="branch.image"
                class="w-full h-full object-cover"
                alt="branch image"
            />
            <img
                v-else
                :src="Logo"
                class="w-full h-full object-contain opacity-50 p-6"
                alt="default logo"
            />

            <span
                v-if="branch.availability.is_open"
                class="absolute top-2 left-2 text-[10px] px-2 py-1 rounded bg-green-100 text-green-700 font-semibold"
            >
                Open now
            </span>
        </div>

        <div class="flex-1 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-semibold text-slate-900 text-lg">
                        {{ branch.name }}
                    </h3>

                    <p class="text-xs text-slate-500 mt-1 flex gap-1">
                        <Location />
                        {{ branch.location.street }}, {{ branch.location.city }}
                    </p>
                </div>

                <div class="text-right">
                    <p class="text-xs text-slate-400">Rating</p>
                    <p class="text-sm font-semibold text-slate-800">
                        ⭐ {{ branch.averageRating ?? "0.0" }}
                    </p>
                </div>
            </div>

            <div class="mt-3 text-xs text-slate-500">
                <div class="mt-3 flex flex-wrap gap-1">
                    <span
                        v-for="plan in branch.subscriptions"
                        :key="plan.plan_code ?? ''"
                        class="px-2 py-1 text-[12px] font-semibold rounded-full bg-slate-100 text-slate-700"
                    >
                        {{
                            plan.plan_name === "Hybrid"
                                ? "Homecare and Inhouse Facility"
                                : plan.plan_name
                        }}
                    </span>
                </div>
            </div>

            <div class="mt-4 flex gap-2 text-xs text-slate-600">
                <span class="px-2 py-1 bg-slate-100 rounded-full">
                    {{ branch.availability.opening_time }} -
                    {{ branch.availability.closing_time }}
                </span>
            </div>

            <div>
                <button
                    class="w-full mt-4 bg-primary text-white text-xs font-semibold py-2 rounded-lg hover:bg-blue-700"
                >
                    Visit
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { BranchRetrieve } from "~/types/branch";
import Location from "~/components/icons/location.vue";
import Logo from "~/assets/logo/logo.png";

const props = defineProps<{
    branch: BranchRetrieve;
    variant: 1 | 2;
}>();

defineEmits(["select"]);

const activePlan = computed(
    () =>
        props.branch.subscriptions?.find((s) => s.status === "active") ??
        props.branch.subscriptions?.[0] ??
        null,
);
</script>
