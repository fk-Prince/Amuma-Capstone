<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 bg-primary-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
            @click.self="handleCancel"
        >
            <div
                class="bg-white rounded-2xl shadow-[0_0_40px_rgba(10,40,87,0.15)] ring-1 ring-primary-100/60 w-full max-w-sm p-6"
            >
                <h3 class="text-base font-semibold text-primary-900">
                    Admit Patient
                </h3>
                <p class="text-xs text-muted mt-1 mb-4">
                    Confirm the admission date and deposit amount.
                </p>

                <div class="space-y-4">
                    <BaseInput
                        label="Admission Date"
                        mode="date"
                        v-model="admitDate"
                        :min="minDate"
                    />
                    <BaseInput
                        label="Deposit"
                        mode="number"
                        v-model="admitDeposit"
                        placeholder="0.00"
                    />
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-700 transition"
                        @click="handleCancel"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        :disabled="!admitDate || loading"
                        class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed transition"
                        @click="handleConfirm"
                    >
                        {{ loading ? "Admitting..." : "Admit" }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import BaseInput from "~/components/ui/BaseInput.vue";

const props = defineProps<{
    open: boolean;
    loading?: boolean;
    minDate?: string;
}>();

const emit = defineEmits<{
    (e: "confirm", payload: { admittedAt: string; deposit: string }): void;
    (e: "cancel"): void;
}>();

const admitDate = ref("");
const admitDeposit = ref("");

// Reset the fields every time the modal is opened so stale values from a
// previous admit attempt don't leak into the next one.
watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            admitDate.value = props.minDate ?? "";
            admitDeposit.value = "";
        }
    },
);

function handleConfirm() {
    if (!admitDate.value) return;
    emit("confirm", {
        admittedAt: admitDate.value,
        deposit: admitDeposit.value,
    });
}

function handleCancel() {
    emit("cancel");
}
</script>
