<template>
    <div>
        <div
            v-if="loading"
            class="hidden lg:block sticky top-24 rounded-2xl border border-gray-200 p-5 animate-pulse"
        >
            <div class="h-4 w-32 rounded bg-gray-200"></div>
            <div class="mt-4 flex flex-col gap-3">
                <div class="h-16 rounded-xl bg-gray-200"></div>
                <div class="h-16 rounded-xl bg-gray-200"></div>
            </div>
            <div class="mt-4 h-11 rounded-xl bg-gray-200"></div>
        </div>

        <div
            v-else
            class="hidden lg:block sticky top-24 rounded-2xl border border-gray-200 bg-white p-5 shadow-xl shadow-gray-100"
        >
            <p
                class="text-xs font-semibold uppercase tracking-wide text-gray-400"
            >
                Choose a service
            </p>

            <div class="mt-4 flex flex-col gap-3">
                <button
                    v-if="hasHomecare"
                    type="button"
                    @click="selected = 'homecare'"
                    class="flex items-center gap-3 rounded-xl border p-4 text-left transition-colors"
                    :class="
                        selected === 'homecare'
                            ? 'border-primary bg-primary/5 ring-1 ring-primary/20'
                            : 'border-gray-200 hover:border-gray-300'
                    "
                >
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary text-white"
                    >
                        <House class="h-5 w-5" />
                    </span>
                    <span class="flex-1">
                        <span class="block text-sm font-semibold text-gray-900"
                            >Homecare Services</span
                        >
                        <span class="block text-sm text-gray-500"
                            >Care delivered at your home</span
                        >
                    </span>
                    <CheckCircle2
                        v-if="selected === 'homecare'"
                        class="h-5 w-5 shrink-0 text-primary"
                    />
                    <Circle v-else class="h-5 w-5 shrink-0 text-gray-300" />
                </button>

                <button
                    v-if="hasFacility"
                    type="button"
                    @click="selected = 'facility'"
                    class="flex items-center gap-3 rounded-xl border p-4 text-left transition-colors"
                    :class="
                        selected === 'facility'
                            ? 'border-emerald-600 bg-emerald-50 ring-1 ring-emerald-600/20'
                            : 'border-gray-200 hover:border-gray-300'
                    "
                >
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white"
                    >
                        <Building2 class="h-5 w-5" />
                    </span>
                    <span class="flex-1">
                        <span class="block text-sm font-semibold text-gray-900"
                            >Facility Admission</span
                        >
                        <span class="block text-sm text-gray-500"
                            >In-house, facility-based care</span
                        >
                    </span>
                    <CheckCircle2
                        v-if="selected === 'facility'"
                        class="h-5 w-5 shrink-0 text-emerald-600"
                    />
                    <Circle v-else class="h-5 w-5 shrink-0 text-gray-300" />
                </button>

                <p
                    v-if="!hasHomecare && !hasFacility"
                    class="rounded-xl border border-dashed border-gray-200 p-4 text-sm text-gray-400"
                >
                    No services are currently listed for this branch.
                </p>
            </div>

            <button
                v-if="hasHomecare || hasFacility"
                type="button"
                :disabled="!selected"
                @click="confirm"
                class="mt-4 w-full rounded-xl bg-primary py-3 text-sm font-semibold text-white transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40"
            >
                Continue
            </button>
        </div>

        <div
            v-if="!loading && serviceOptions.length"
            class="lg:hidden fixed inset-x-0 bottom-0 z-40 flex items-center justify-between gap-3 border-t border-gray-200 bg-white p-4"
        >
            <div class="flex-1">
                <Combobox
                    v-model="selected"
                    :items="serviceOptions"
                    placeholder="Choose a service"
                    position="top"
                />
            </div>
            <button
                type="button"
                :disabled="!selected"
                @click="confirm"
                class="shrink-0 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-40"
            >
                Continue
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watchEffect } from "vue";
import Combobox from "~/components/ui/Combobox.vue";
import { House, Building2, CheckCircle2, Circle } from "lucide-vue-next";

const props = defineProps<{
    hasHomecare: boolean;
    hasFacility: boolean;
    loading?: boolean;
}>();

const emit = defineEmits<{
    (e: "homecare"): void;
    (e: "facility"): void;
}>();

const serviceOptions = computed(() => {
    const options: { label: string; value: "homecare" | "facility" }[] = [];
    if (props.hasHomecare)
        options.push({ label: "Homecare Services", value: "homecare" });
    if (props.hasFacility)
        options.push({ label: "Facility Admission", value: "facility" });
    return options;
});

const selected = ref<"homecare" | "facility" | null>(null);

watchEffect(() => {
    if (props.hasHomecare && !props.hasFacility) selected.value = "homecare";
    else if (props.hasFacility && !props.hasHomecare)
        selected.value = "facility";
});

const confirm = () => {
    if (selected.value === "homecare") {
        emit("homecare");
    } else if (selected.value === "facility") {
        emit("facility");
    }
};
</script>
