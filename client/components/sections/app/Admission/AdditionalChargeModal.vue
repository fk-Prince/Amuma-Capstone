<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[70] flex items-center justify-center bg-black/40 p-5"
            >
                <div
                    class="flex w-full max-w-2xl max-h-[88dvh] flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary"
                >
                    <div
                        class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5 dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold tracking-tight">
                                Add Charges
                            </h3>

                            <p
                                class="mt-1 text-sm text-slate-500 dark:text-gray-400"
                            >
                                Every charge added here is billed to
                                {{ patientName || "the patient" }} on one new
                                invoice.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10"
                            aria-label="Close"
                            :disabled="submitting"
                            @click="$emit('close')"
                        >
                            ✕
                        </button>
                    </div>

                    <form
                        ref="formEl"
                        class="flex-1 overflow-y-auto px-6 py-5"
                        @submit.prevent="submit"
                    >
                        <div
                            v-if="lines.length > 1"
                            class="mb-6 flex flex-wrap items-center gap-2"
                        >
                            <button
                                v-for="(line, index) in lines"
                                :key="line.key"
                                type="button"
                                class="group flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition-colors"
                                :class="
                                    index === activeIndex
                                        ? 'bg-primary text-white'
                                        : 'bg-slate-50 text-slate-500 hover:bg-slate-100 dark:bg-secondary dark:text-gray-400 dark:border dark:border-white/10 dark:hover:bg-white/5'
                                "
                                @click="activeIndex = index"
                            >
                                <span
                                    class="flex h-5 w-5 items-center justify-center rounded-full text-[11px] font-semibold"
                                    :class="
                                        lineHasError(index)
                                            ? 'bg-red-500 text-white'
                                            : index === activeIndex
                                              ? 'bg-white/20 text-white'
                                              : 'bg-white text-slate-400 dark:bg-white/10 dark:text-gray-500'
                                    "
                                >
                                    {{ index + 1 }}
                                </span>

                                Charge {{ index + 1 }}

                                <XIcon
                                    class="h-3.5 w-3.5 opacity-0 transition-opacity group-hover:opacity-100"
                                    :class="
                                        index === activeIndex
                                            ? 'text-white/80 hover:text-white'
                                            : 'text-slate-400 hover:text-red-500 dark:text-gray-500'
                                    "
                                    @click.stop="removeLine(index)"
                                />
                            </button>

                            <button
                                type="button"
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-50 text-slate-400 transition hover:bg-primary/10 hover:text-primary disabled:opacity-50 dark:bg-secondary dark:text-gray-500"
                                :disabled="submitting || lines.length >= MAX_LINES"
                                @click="addLine"
                            >
                                <Plus class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="space-y-8">
                            <div class="space-y-5">
                                <div
                                    v-if="lines.length > 1"
                                    class="flex items-center justify-between"
                                >
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="flex h-7 w-7 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary"
                                        >
                                            {{ activeIndex + 1 }}
                                        </span>

                                        <h3
                                            class="text-sm font-semibold text-slate-700 dark:text-gray-300"
                                        >
                                            Charge {{ activeIndex + 1 }}
                                        </h3>
                                    </div>

                                    <button
                                        type="button"
                                        class="text-sm font-medium text-slate-400 transition hover:text-red-500 dark:text-gray-500"
                                        :disabled="submitting"
                                        @click="removeLine(activeIndex)"
                                    >
                                        Remove
                                    </button>
                                </div>

                                <div class="flex flex-col gap-1.5">
                                    <label
                                        class="text-sm font-semibold text-slate-700 dark:text-gray-300"
                                    >
                                        Type <span class="text-red-500">*</span>
                                    </label>

                                    <div
                                        class="grid grid-cols-3 gap-1 rounded-xl border border-slate-200 p-1 dark:border-white/10"
                                    >
                                        <button
                                            v-for="option in ADDITIONAL_CHARGE_TYPES"
                                            :key="option.value"
                                            type="button"
                                            class="rounded-lg px-2 py-2 text-xs font-medium transition sm:text-sm"
                                            :class="
                                                activeLine.type === option.value
                                                    ? 'bg-primary text-white shadow-sm'
                                                    : 'text-slate-500 hover:bg-slate-50 dark:text-gray-400 dark:hover:bg-white/5'
                                            "
                                            @click="
                                                activeLine.type = option.value;
                                                clearError(activeIndex, 'type');
                                            "
                                        >
                                            {{ option.label }}
                                        </button>
                                    </div>

                                    <p
                                        v-if="errorFor(activeIndex, 'type')"
                                        class="text-xs text-red-500"
                                    >
                                        {{ errorFor(activeIndex, "type") }}
                                    </p>
                                </div>

                                <BaseInput
                                    :key="`description-${activeLine.key}`"
                                    v-model="activeLine.description"
                                    label="Description"
                                    mode="textarea"
                                    :textMax="255"
                                    placeholder="e.g. Paracetamol 500mg × 10 tablets"
                                    :error="errorFor(activeIndex, 'description')"
                                    required
                                    @update:modelValue="
                                        clearError(activeIndex, 'description')
                                    "
                                />

                                <BaseInput
                                    :key="`amount-${activeLine.key}`"
                                    v-model="activeLine.amount"
                                    label="Amount"
                                    mode="number"
                                    placeholder="0.00"
                                    :error="errorFor(activeIndex, 'amount')"
                                    required
                                    @update:modelValue="
                                        clearError(activeIndex, 'amount')
                                    "
                                />
                            </div>

                            <button
                                type="button"
                                class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-200 py-4 text-sm font-semibold text-primary transition hover:border-primary/40 hover:bg-primary/5 disabled:opacity-50 dark:border-white/10"
                                :disabled="submitting || lines.length >= MAX_LINES"
                                @click="addLine"
                            >
                                <span class="text-xl leading-none">+</span>
                                Add Another Charge
                            </button>
                        </div>

                        <p
                            v-if="submitError"
                            class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-600 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300"
                        >
                            {{ submitError }}
                        </p>
                    </form>

                    <div
                        class="border-t border-slate-100 px-6 py-4 dark:border-white/10"
                    >
                        <div
                            class="mb-3 flex items-center justify-between text-sm"
                        >
                            <span class="text-slate-500 dark:text-gray-400">
                                Total ({{ lines.length }}
                                {{ lines.length === 1 ? "charge" : "charges" }})
                            </span>
                            <span class="text-lg font-bold tracking-tight">
                                {{ formatCurrency(total) }}
                            </span>
                        </div>

                        <div class="flex gap-3">
                            <button
                                type="button"
                                class="flex-1 rounded-xl border py-2.5 text-sm font-medium transition hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5"
                                :disabled="submitting"
                                @click="$emit('close')"
                            >
                                Cancel
                            </button>

                            <button
                                type="button"
                                class="flex flex-[2] items-center justify-center gap-2 rounded-xl bg-primary py-2.5 text-sm font-medium text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="submitting"
                                @click="submit"
                            >
                                <LoaderCircle
                                    v-if="submitting"
                                    class="h-4 w-4 animate-spin"
                                />
                                {{
                                    submitting
                                        ? "Adding..."
                                        : lines.length === 1
                                          ? "Add Charge"
                                          : `Add ${lines.length} Charges`
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, nextTick, ref, watch } from "vue";
import { LoaderCircle, Plus, X as XIcon } from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import { additionalChargeService } from "~/api/additional-charge/AdditionalChargeService";
import { formatCurrency } from "~/utils/currency";
import {
    ADDITIONAL_CHARGE_TYPES,
    type AdditionalCharge,
    type AdditionalChargeType,
} from "~/types/additional-charge";

const MAX_LINES = 50;

interface ChargeLine {
    key: number;
    type: AdditionalChargeType | "";
    description: string;
    amount: string | number;
}

const props = defineProps<{
    open: boolean;
    branchUuid: string;
    patientUuid: string;
    patientName?: string | null;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "created", charges: AdditionalCharge[]): void;
}>();

let nextKey = 0;

const newLine = (): ChargeLine => ({
    key: nextKey++,
    type: "",
    description: "",
    amount: "",
});

const lines = ref<ChargeLine[]>([newLine()]);
const activeIndex = ref(0);
const errors = ref<Record<string, string>>({});
const submitError = ref("");
const submitting = ref(false);
const formEl = ref<HTMLFormElement | null>(null);

const activeLine = computed(
    () => lines.value[activeIndex.value] ?? lines.value[0]!,
);

function lineHasError(index: number) {
    return Object.keys(errors.value).some((key) =>
        key.startsWith(`charges.${index}.`),
    );
}

function scrollToTop() {
    nextTick(() => formEl.value?.scrollTo({ top: 0, behavior: "smooth" }));
}

const total = computed(() =>
    lines.value.reduce((sum, line) => {
        const amount = Number(line.amount);
        return sum + (Number.isFinite(amount) && amount > 0 ? amount : 0);
    }, 0),
);

function errorFor(index: number, field: string) {
    return errors.value[`charges.${index}.${field}`];
}

function clearError(index: number, field: string) {
    delete errors.value[`charges.${index}.${field}`];
}

function addLine() {
    if (lines.value.length >= MAX_LINES) return;
    lines.value.push(newLine());
    activeIndex.value = lines.value.length - 1;
    scrollToTop();
}

function removeLine(index: number) {
    if (lines.value.length <= 1) return;

    lines.value.splice(index, 1);
    errors.value = {};

    if (activeIndex.value >= lines.value.length) {
        activeIndex.value = lines.value.length - 1;
    } else if (index < activeIndex.value) {
        activeIndex.value -= 1;
    }
}

function focusFirstError() {
    const first = Object.keys(errors.value)
        .map((key) => Number(key.split(".")[1]))
        .filter((index) => Number.isInteger(index))
        .sort((a, b) => a - b)[0];

    if (first !== undefined) activeIndex.value = first;
}

function validate() {
    const next: Record<string, string> = {};

    lines.value.forEach((line, index) => {
        const amount = Number(line.amount);

        if (!line.type) next[`charges.${index}.type`] = "Choose a charge type.";
        if (!line.description.trim()) {
            next[`charges.${index}.description`] = "Enter a description.";
        }
        if (!line.amount || Number.isNaN(amount) || amount <= 0) {
            next[`charges.${index}.amount`] = "Enter an amount greater than zero.";
        }
    });

    errors.value = next;

    return Object.keys(next).length === 0;
}

async function submit() {
    submitError.value = "";

    if (submitting.value) return;

    if (!validate()) {
        focusFirstError();
        return;
    }

    submitting.value = true;

    try {
        const res = await additionalChargeService.create({
            branch_uuid: props.branchUuid,
            patient_uuid: props.patientUuid,
            charges: lines.value.map((line) => ({
                type: line.type,
                description: line.description.trim(),
                amount: Number(line.amount),
            })),
        });

        emit("created", res.data ?? []);
    } catch (err: any) {
        const raw = err?.errors ?? {};

        errors.value = Object.fromEntries(
            Object.entries(raw).map(([key, value]: any) => [
                key,
                Array.isArray(value) ? value[0] : value,
            ]),
        );

        submitError.value = Object.keys(raw).length
            ? ""
            : (err?.message ?? "Couldn't add the charges.");

        focusFirstError();
    } finally {
        submitting.value = false;
    }
}

watch(
    () => props.open,
    (open) => {
        if (!open) return;

        lines.value = [newLine()];
        activeIndex.value = 0;
        errors.value = {};
        submitError.value = "";
    },
);
</script>
