<script setup lang="ts">
import { ref, reactive, watch } from "vue";
import { LoaderCircle, Check, Pencil } from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import { bedForm, bedSchema, type BedForm } from "~/types/bed";
import { useSchemaValidation } from "~/composables/useSchemaValidation";
import { type PatientAdmission } from "~/types/admission";

const props = defineProps<{
    loading?: boolean;
    errors?: any;
    bedData?: BedForm;
    action?: "create" | "update";
    patient?: PatientAdmission | null;
}>();

const emit = defineEmits<{
    bedAction: [action: "create" | "update", bed: BedForm, done: () => void];
    cancel: [];
}>();

const editing = ref(props.action === "create");

const bed = reactive(bedForm());

watch(
    () => props.bedData,
    (value) => {
        if (value) {
            Object.assign(bed, value);
        }
    },
    { immediate: true },
);

const {
    errors: bedError,
    validate,
    clearError,
    reset,
} = useSchemaValidation(bedSchema, bed);

const editBed = () => {
    editing.value = true;
};

const cancelEdit: any = () => {
    if (props.bedData) {
        Object.assign(bed, props.bedData);
    } else {
        Object.assign(bed, bedForm());
    }

    reset();
    if (props.action === "create" || props.action === "update") {
        emit("cancel");
        return;
    }
    editing.value = false;
};

const saveBed = () => {
    if (!validate()) return;
    editing.value = true;
    emit("bedAction", props.action ?? "update", bed, () => {
        editing.value = false;
        reset();
    });
};
</script>

<template>
    <div
        class="rounded-2xl bg-white p-4"
        :class="{
            border: action !== 'update',
        }"
    >
        <div class="space-y-4">
            <BaseInput
                v-model="bed.bed_no"
                label="Bed Number"
                :error="bedError.bed_no || errors?.bed_no"
                @update:modelValue="clearError('bed_no')"
            />

            <template v-if="action === 'update'">
                <Combobox
                    v-model="bed.status"
                    label="Bed Status"
                    placeholder="Select status"
                    :error="bedError.status || errors?.status"
                    :disabled="!!patient?.patient"
                    @update:modelValue="clearError('status')"
                    :items="[
                        {
                            label: 'Available',
                            value: 'Available',
                        },
                        {
                            label: 'Occupied',
                            value: 'Occupied',
                        },
                        {
                            label: 'Maintenance',
                            value: 'Maintenance',
                        },
                    ]"
                />

                <p v-if="patient?.patient" class="text-xs text-gray-400 -mt-2">
                    This bed has a patient assigned. Discharge the patient
                    before changing its status.
                </p>
            </template>

            <div class="flex justify-end gap-2">
                <button
                    type="button"
                    @click="cancelEdit"
                    class="rounded-lg border px-4 py-2 text-sm text-slate-600"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    :disabled="loading || editing"
                    @click="saveBed"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm text-white disabled:opacity-50"
                >
                    <LoaderCircle
                        v-if="loading || editing"
                        class="h-4 w-4 animate-spin"
                    />

                    <Check v-else class="h-4 w-4" />

                    {{
                        loading || editing
                            ? action === "update"
                                ? "Updating..."
                                : "Adding..."
                            : action === "update"
                              ? "Update Bed"
                              : "Add Bed"
                    }}
                </button>
            </div>
        </div>
    </div>
</template>
