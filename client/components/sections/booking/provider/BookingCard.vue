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
            <div class="w-full flex justify-between items-center">
                <p
                    class="text-xs font-semibold uppercase tracking-wide text-gray-400"
                >
                    Choose a service
                </p>

                <div class="flex items-center gap-2 text-sm">
                    <svg
                        class="h-4 w-4 shrink-0"
                        :class="
                            getBranchTimeDisplay(branch?.settings).is24Hours
                                ? 'text-emerald-600'
                                : 'text-slate-400'
                        "
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 6v6l4 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>

                    <span
                        v-if="getBranchTimeDisplay(branch?.settings).is24Hours"
                        class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-700"
                    >
                        Open 24 Hours
                    </span>

                    <span v-else class="font-medium text-slate-600">
                        {{ getBranchTimeDisplay(branch?.settings).label }}
                    </span>
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-3">
                <button
                    v-if="hasHomecare"
                    type="button"
                    :disabled="!canUseHomecare"
                    @click="selected = 'homecare'"
                    class="flex items-center gap-3 rounded-xl border p-4 text-left transition-all"
                    :class="[
                        selected === 'homecare'
                            ? 'border-primary bg-primary/5 ring-1 ring-primary/20'
                            : 'border-gray-200',
                        canUseHomecare
                            ? 'hover:border-primary cursor-pointer'
                            : 'cursor-not-allowed opacity-60',
                    ]"
                >
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary text-white"
                    >
                        <House class="h-5 w-5" />
                    </span>

                    <span class="flex-1">
                        <span class="block text-sm font-semibold text-gray-900">
                            Homecare Services
                        </span>

                        <span class="block text-sm text-gray-500">
                            Care delivered at your home
                        </span>

                        <span
                            v-if="!canUseHomecare"
                            class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-200"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-amber-500"
                            ></span>
                            Currently unavailable
                        </span>
                    </span>

                    <CheckCircle2
                        v-if="selected === 'homecare'"
                        class="h-5 w-5 shrink-0 text-primary"
                    />

                    <Circle v-else class="h-5 w-5 shrink-0 text-gray-300" />
                </button>

                <button
                    v-if="hasHomecare"
                    type="button"
                    :disabled="!canUseFacility"
                    @click="selected = 'facility'"
                    class="flex items-center gap-3 rounded-xl border p-4 text-left transition-all"
                    :class="[
                        selected === 'facility'
                            ? 'border-emerald-600 bg-emerald-50 ring-1 ring-emerald-600/20'
                            : 'border-gray-200',
                        canUseFacility
                            ? 'hover:border-emerald-300 cursor-pointer'
                            : 'cursor-not-allowed opacity-60',
                    ]"
                >
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white"
                    >
                        <Building2 class="h-5 w-5" />
                    </span>

                    <span class="flex-1">
                        <span class="block text-sm font-semibold text-gray-900">
                            Facility Admission
                        </span>

                        <span class="block text-sm text-gray-500">
                            In-house, facility-based care
                        </span>

                        <span
                            v-if="branch?.facility.length === 0"
                            class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-200"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-amber-500"
                            ></span>
                            Currently unavailable
                        </span>

                        <span
                            v-else-if="availableSlots > 0"
                            class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                            ></span>

                            {{ availableSlots }}
                            slot {{ availableSlots === 1 ? "" : "s" }}
                            available
                        </span>

                        <span
                            v-else
                            class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 ring-1 ring-gray-200"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-gray-400"
                            ></span>

                            No rooms availables
                        </span>
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
            class="lg:hidden fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white p-4"
        >
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="flex flex-1 items-center gap-3 rounded-xl border border-gray-200 px-4 py-2.5 text-left transition hover:border-gray-300"
                    @click="mobileSheetOpen = true"
                >
                    <span
                        v-if="selected"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-white"
                        :class="
                            selected === 'homecare'
                                ? 'bg-primary'
                                : 'bg-emerald-600'
                        "
                    >
                        <House v-if="selected === 'homecare'" class="h-4 w-4" />
                        <Building2 v-else class="h-4 w-4" />
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="block text-[11px] text-gray-400">
                            Service
                        </span>
                        <span
                            class="block truncate text-sm font-semibold text-gray-900"
                        >
                            {{ selectedLabel || "Choose a service" }}
                        </span>
                    </span>

                    <ChevronUp class="h-4 w-4 shrink-0 text-gray-400" />
                </button>

                <button
                    type="button"
                    :disabled="!selected"
                    @click="confirm"
                    class="shrink-0 rounded-lg bg-primary px-5 py-3 text-sm font-semibold text-white disabled:opacity-40"
                >
                    Continue
                </button>
            </div>
        </div>

        <Teleport to="body">
            <Transition name="sheet-fade">
                <div
                    v-if="mobileSheetOpen"
                    class="fixed inset-0 z-50 bg-gray-900/50 lg:hidden"
                    @click.self="mobileSheetOpen = false"
                >
                    <Transition name="sheet-slide" appear>
                        <div
                            class="fixed inset-x-0 bottom-0 max-h-[80vh] overflow-y-auto rounded-t-3xl bg-white p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))]"
                        >
                            <div
                                class="mx-auto mb-4 h-1.5 w-10 rounded-full bg-gray-200"
                            />

                            <div class="flex items-center justify-between">
                                <p class="text-base font-semibold text-gray-900">
                                    Choose a service
                                </p>

                                <button
                                    type="button"
                                    class="text-gray-400 hover:text-gray-600"
                                    aria-label="Close"
                                    @click="mobileSheetOpen = false"
                                >
                                    <X class="h-5 w-5" />
                                </button>
                            </div>

                            <div class="mt-4 flex flex-col gap-3">
                                <button
                                    v-if="hasHomecare"
                                    type="button"
                                    :disabled="!canUseHomecare"
                                    @click="selectAndClose('homecare')"
                                    class="flex items-center gap-3 rounded-xl border p-4 text-left transition-all"
                                    :class="[
                                        selected === 'homecare'
                                            ? 'border-primary bg-primary/5 ring-1 ring-primary/20'
                                            : 'border-gray-200',
                                        canUseHomecare
                                            ? 'cursor-pointer'
                                            : 'cursor-not-allowed opacity-60',
                                    ]"
                                >
                                    <span
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary text-white"
                                    >
                                        <House class="h-5 w-5" />
                                    </span>

                                    <span class="flex-1">
                                        <span
                                            class="block text-sm font-semibold text-gray-900"
                                        >
                                            Homecare Services
                                        </span>

                                        <span
                                            class="block text-sm text-gray-500"
                                        >
                                            Care delivered at your home
                                        </span>

                                        <span
                                            v-if="!canUseHomecare"
                                            class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-200"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full bg-amber-500"
                                            ></span>
                                            Currently unavailable
                                        </span>
                                    </span>

                                    <CheckCircle2
                                        v-if="selected === 'homecare'"
                                        class="h-5 w-5 shrink-0 text-primary"
                                    />

                                    <Circle
                                        v-else
                                        class="h-5 w-5 shrink-0 text-gray-300"
                                    />
                                </button>

                                <button
                                    v-if="hasHomecare"
                                    type="button"
                                    :disabled="!canUseFacility"
                                    @click="selectAndClose('facility')"
                                    class="flex items-center gap-3 rounded-xl border p-4 text-left transition-all"
                                    :class="[
                                        selected === 'facility'
                                            ? 'border-emerald-600 bg-emerald-50 ring-1 ring-emerald-600/20'
                                            : 'border-gray-200',
                                        canUseFacility
                                            ? 'cursor-pointer'
                                            : 'cursor-not-allowed opacity-60',
                                    ]"
                                >
                                    <span
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white"
                                    >
                                        <Building2 class="h-5 w-5" />
                                    </span>

                                    <span class="flex-1">
                                        <span
                                            class="block text-sm font-semibold text-gray-900"
                                        >
                                            Facility Admission
                                        </span>

                                        <span
                                            class="block text-sm text-gray-500"
                                        >
                                            In-house, facility-based care
                                        </span>

                                        <span
                                            v-if="branch?.facility.length === 0"
                                            class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-200"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full bg-amber-500"
                                            ></span>
                                            Currently unavailable
                                        </span>

                                        <span
                                            v-else-if="availableSlots > 0"
                                            class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                                            ></span>

                                            {{ availableSlots }}
                                            slot {{ availableSlots === 1 ? "" : "s" }}
                                            available
                                        </span>

                                        <span
                                            v-else
                                            class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 ring-1 ring-gray-200"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full bg-gray-400"
                                            ></span>

                                            No rooms availables
                                        </span>
                                    </span>

                                    <CheckCircle2
                                        v-if="selected === 'facility'"
                                        class="h-5 w-5 shrink-0 text-emerald-600"
                                    />

                                    <Circle
                                        v-else
                                        class="h-5 w-5 shrink-0 text-gray-300"
                                    />
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
<script setup lang="ts">
import { computed, ref, watchEffect } from "vue";
import {
    House,
    Building2,
    CheckCircle2,
    Circle,
    ChevronUp,
    X,
} from "lucide-vue-next";
import { getBranchTimeDisplay } from "~/utils/time";
import { useBranch } from "~/composables/useBranchProvider";

const props = defineProps<{
    hasHomecare: boolean;
    hasFacility: boolean;
}>();
const emit = defineEmits<{
    (e: "homecare"): void;
    (e: "facility"): void;
}>();

const { branch, loading, has, canUseHomecare, canUseFacility, availableSlots } =
    useBranch();

const selected = ref<"homecare" | "facility" | null>(null);
const mobileSheetOpen = ref(false);

const serviceOptions = computed(() => {
    const options: {
        label: string;
        value: "homecare" | "facility";
    }[] = [];

    if (canUseHomecare.value) {
        options.push({
            label: "Homecare Services",
            value: "homecare",
        });
    }

    if (canUseFacility.value) {
        options.push({
            label: "Facility Admission",
            value: "facility",
        });
    }

    return options;
});

watchEffect(() => {
    if (canUseHomecare.value && !canUseFacility.value) {
        selected.value = "homecare";
    }

    if (!canUseHomecare.value && canUseFacility.value) {
        selected.value = "facility";
    }
});

const selectedLabel = computed(
    () => serviceOptions.value.find((o) => o.value === selected.value)?.label ?? "",
);

function selectAndClose(value: "homecare" | "facility") {
    selected.value = value;
    mobileSheetOpen.value = false;
}

const confirm = () => {
    if (selected.value === "homecare") {
        emit("homecare");
    }

    if (selected.value === "facility") {
        emit("facility");
    }
};
</script>

<style scoped>
.sheet-fade-enter-active,
.sheet-fade-leave-active {
    transition: opacity 0.2s ease;
}

.sheet-fade-enter-from,
.sheet-fade-leave-to {
    opacity: 0;
}

.sheet-slide-enter-active,
.sheet-slide-leave-active {
    transition: transform 0.25s ease-out;
}

.sheet-slide-enter-from,
.sheet-slide-leave-to {
    transform: translateY(100%);
}
</style>
