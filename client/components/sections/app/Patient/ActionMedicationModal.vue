<script setup lang="ts">
import { computed, onMounted, onUnmounted, reactive, ref, watch } from "vue";
import { Check, Clock, LoaderCircle, Pill, Plus, X } from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import { medicationSchema } from "~/types/medication";
import { getLocalDateStr } from "~/utils/time";
import type { PatientRetrieve } from "~/types/patient";
import {
    type Medication,
    type MedicationForm,
    emptyForm,
    dosageUnitOptions,
    routeOptions,
} from "~/types/medication";
import { useSchemaValidation } from "~/composables/useSchemaValidation";

const props = defineProps<{
    open: boolean;
    submitLoading?: boolean;
    patient: PatientRetrieve;
    medication?: Medication | null;
}>();

const emit = defineEmits<{
    close: [];
    submit: [action: "create" | "update", payload: MedicationForm, id?: string];
}>();

const form = reactive<MedicationForm>(emptyForm());
const newTime = ref("12:00");

const { errors, validate, clearError, reset } = useSchemaValidation(
    medicationSchema,
    form,
);

function formatTime(value: string) {
    if (!value) return "";
    const [hourStr, minuteStr] = value.split(":");
    const hour = Number(hourStr);
    const period = hour >= 12 ? "PM" : "AM";
    const displayHour = hour % 12 === 0 ? 12 : hour % 12;
    return `${displayHour}:${minuteStr} ${period}`;
}

function addTime() {
    if (!newTime.value) return;
    if (!form.times.includes(newTime.value)) {
        form.times = [...form.times, newTime.value].sort();
    }
    clearError("times");
}

function removeTime(time: string) {
    form.times = form.times.filter((t) => t !== time);
}

async function onSubmit() {
    if (!validate()) return;

    const payload = { ...form, times: [...form.times] };

    if (props?.medication?.id) {
        emit("submit", "update", payload, props.medication.id);
    } else {
        emit("submit", "create", payload);
    }
}
function onKeydown(e: KeyboardEvent) {
    if (e.key === "Escape" && props.open) {
        close();
    }
}

onMounted(() => {
    document.addEventListener("keydown", onKeydown, true);
});
onUnmounted(() => {
    document.removeEventListener("keydown", onKeydown, true);
});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            Object.assign(form, emptyForm());
            reset();
            newTime.value = "12:00";
        }
    },
);

const durationOptions = [
    { label: "1 Days", value: "1" },
    { label: "2 Days", value: "2" },
    { label: "3 Days", value: "3" },
    { label: "7 Days", value: "7" },
    { label: "14 Days", value: "14" },
    { label: "30 Days", value: "30" },
    { label: "60 Days", value: "60" },
    { label: "90 Days", value: "90" },
];

const sortedTimes = computed(() => [...form.times].sort());

function close() {
    emit("close");
}
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
                v-if="open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
            >
                <Transition
                    appear
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-if="open"
                        role="dialog"
                        aria-modal="true"
                        aria-label="Add Medication"
                        class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
                    >
                        <div
                            class="sticky top-0 z-10 flex items-center justify-between gap-4 rounded-t-2xl border-b border-gray-100 bg-white/95 px-6 py-5 backdrop-blur"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/20 text-primary"
                                >
                                    <Pill class="h-5 w-5" />
                                </div>

                                <div>
                                    <h2
                                        class="text-lg font-semibold leading-tight text-gray-900"
                                    >
                                        Add Medication
                                    </h2>
                                    <p class="mt-0.5 text-xs text-gray-400">
                                        Record a new medication and its
                                        administration schedule for this
                                        patient.
                                    </p>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="close"
                                aria-label="Close dialog"
                                class="shrink-0 rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/40"
                            >
                                <X class="h-5 w-5" />
                            </button>
                        </div>

                        <form @submit.prevent="onSubmit" class="p-6">
                            <div class="space-y-6">
                                <BaseInput
                                    v-model="form.name"
                                    label="Medication Name"
                                    placeholder="e.g. Hydrocortisone Oxydation"
                                    :error="errors.name"
                                    @update:modelValue="clearError('name')"
                                />

                                <BaseInput
                                    v-model="form.strength"
                                    label="Strength"
                                    placeholder="e.g. 5mg"
                                    :error="errors.strength"
                                    @update:modelValue="clearError('strength')"
                                />

                                <div
                                    class="grid grid-cols-1 gap-4 sm:grid-cols-3"
                                >
                                    <BaseInput
                                        v-model="form.dosageAmount"
                                        type="number"
                                        min="0"
                                        step="0.5"
                                        label="Dosage Amount"
                                        placeholder="e.g. 1"
                                        :error="errors.dosageAmount"
                                        @update:modelValue="
                                            clearError('dosageAmount')
                                        "
                                    />

                                    <Combobox
                                        v-model="form.dosageUnit"
                                        label="Unit"
                                        placeholder="Select unit"
                                        :items="dosageUnitOptions"
                                        :error="errors.dosageUnit"
                                        @update:modelValue="
                                            clearError('dosageUnit')
                                        "
                                    />

                                    <Combobox
                                        v-model="form.route"
                                        label="Route"
                                        placeholder="Select route"
                                        :items="routeOptions"
                                        :error="errors.route"
                                        @update:modelValue="clearError('route')"
                                    />
                                </div>

                                <BaseInput
                                    v-model="form.instructions"
                                    label="Instructions"
                                    placeholder="e.g. Take 1 Tab by Mouth Twice Daily"
                                    :error="errors.instructions"
                                    @update:modelValue="
                                        clearError('instructions')
                                    "
                                />

                                <BaseInput
                                    v-model="form.takenFor"
                                    label="Taken For"
                                    placeholder="e.g. High blood pressure"
                                />

                                <div
                                    class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                                >
                                    <BaseInput
                                        v-model="form.startDate"
                                        mode="date"
                                        :min="getLocalDateStr(new Date())"
                                        label="Start Date"
                                        :error="errors.startDate"
                                        @update:modelValue="
                                            clearError('startDate')
                                        "
                                    />

                                    <Combobox
                                        v-model="form.duration"
                                        label="Duration"
                                        placeholder="Select duration"
                                        :items="durationOptions"
                                    />
                                </div>

                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-semibold text-gray-700"
                                    >
                                        Type
                                    </label>

                                    <div
                                        class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-0.5"
                                    >
                                        <button
                                            type="button"
                                            @click="form.kind = 'Scheduled'"
                                            class="rounded-md px-4 py-1.5 text-sm font-medium transition"
                                            :class="
                                                form.kind === 'Scheduled'
                                                    ? 'bg-white text-emerald-600 shadow-sm'
                                                    : 'text-slate-500 hover:text-slate-700'
                                            "
                                        >
                                            Scheduled
                                        </button>

                                        <button
                                            type="button"
                                            @click="form.kind = 'PRN'"
                                            class="rounded-md px-4 py-1.5 text-sm font-medium transition"
                                            :class="
                                                form.kind === 'PRN'
                                                    ? 'bg-white text-emerald-600 shadow-sm'
                                                    : 'text-slate-500 hover:text-slate-700'
                                            "
                                        >
                                            PRN
                                        </button>
                                    </div>

                                    <p class="mt-1.5 text-xs text-gray-400">
                                        {{
                                            form.kind === "Scheduled"
                                                ? "Given at fixed times every day."
                                                : "Given as needed — no fixed schedule."
                                        }}
                                    </p>
                                </div>

                                <div v-if="form.kind === 'Scheduled'">
                                    <label
                                        class="mb-1.5 block text-sm font-semibold text-gray-700"
                                    >
                                        Times
                                    </label>

                                    <div
                                        class="flex flex-wrap items-center gap-2 rounded-xl border p-3"
                                        :class="
                                            errors.times
                                                ? 'border-red-300'
                                                : 'border-gray-200'
                                        "
                                    >
                                        <span
                                            v-for="time in sortedTimes"
                                            :key="time"
                                            class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700"
                                        >
                                            <Clock class="h-3 w-3" />
                                            {{ formatTime(time) }}
                                            <button
                                                type="button"
                                                @click="removeTime(time)"
                                                :aria-label="`Remove ${formatTime(time)}`"
                                                class="rounded-full p-0.5 text-emerald-500 transition hover:bg-emerald-100 hover:text-emerald-700"
                                            >
                                                <X class="h-3 w-3" />
                                            </button>
                                        </span>

                                        <span
                                            v-if="!sortedTimes.length"
                                            class="text-xs text-gray-400"
                                        >
                                            No times added yet.
                                        </span>

                                        <div
                                            class="ml-auto flex items-center gap-2"
                                        >
                                            <input
                                                v-model="newTime"
                                                type="time"
                                                class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs text-gray-700 focus:border-emerald-400 focus:outline-none focus:ring-1 focus:ring-emerald-300"
                                            />
                                            <button
                                                type="button"
                                                @click="addTime"
                                                class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100"
                                            >
                                                <Plus class="h-3.5 w-3.5" />
                                                Add
                                            </button>
                                        </div>
                                    </div>

                                    <p
                                        v-if="errors.times"
                                        class="mt-1.5 text-xs text-red-500"
                                    >
                                        {{ errors.times }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="mt-8 flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end"
                            >
                                <button
                                    type="button"
                                    @click="close"
                                    class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-300"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="submit"
                                    :disabled="submitLoading"
                                    class="inline-flex min-w-[170px] items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 focus:outline-none focus-visible:ring-2 disabled:cursor-not-allowed disabled:opacity-70"
                                >
                                    <LoaderCircle
                                        v-if="submitLoading"
                                        class="h-4 w-4 animate-spin"
                                    />
                                    <Check v-else class="h-4 w-4" />

                                    {{
                                        submitLoading
                                            ? "Saving Medication..."
                                            : "Add Medication"
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
