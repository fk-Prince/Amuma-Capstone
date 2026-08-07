<script setup lang="ts">
import { ref, computed, watch } from "vue";
import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";
import {
    Calendar,
    Phone,
    Globe2,
    Ruler,
    Weight,
    Pill,
    MapPin,
    Building2,
    DoorOpen,
    BedDouble,
} from "lucide-vue-next";

import { admissionService } from "~/api/admission/AdmissionService";
import type { PatientRetrieve, Admission } from "~/types/patient";
import { formatDate } from "~/utils/time";
import { useToast } from "~/composables/useToast";
import { useRoute } from "vue-router";
import BillingCycleModal from "./BillingCycleModal.vue";
import type { Contract } from "~/types/contract.js";

const route = useRoute();
const { success, error } = useToast();

const props = defineProps<{
    patient: PatientRetrieve;
    isEdit?: boolean;
}>();

const admissions = ref<Admission[]>([...(props.patient.admissions ?? [])]);

watch(
    () => props.patient.admissions,
    (val) => {
        admissions.value = [...(val ?? [])];
    },
);

const cancellingId = ref<number | null>(null);

function isAdmitted(status?: string) {
    return (status ?? "").toLowerCase() === "admitted";
}

function isWaiting(status?: string) {
    return (status ?? "").toLowerCase() === "waiting";
}

function statusClasses(status?: string) {
    const value = (status ?? "").toLowerCase();

    if (value === "admitted") {
        return "bg-primary text-white";
    }

    if (value.includes("complete")) {
        return "bg-[#E6F1FA] text-[#2563A6]";
    }

    if (value.includes("discharge")) {
        return "bg-[#FBE8E6] text-[#B3402F]";
    }

    if (value.includes("cancel")) {
        return "bg-[#F1F1F1] text-[#6B7280]";
    }

    return "bg-[#FDF3DE] text-[#966B1F]";
}

function cardClasses(status?: string) {
    return isAdmitted(status)
        ? "border-l-4 border-primary bg-primary-50"
        : "bg-[#F7FAF9]";
}

const dialogOpen = ref(false);
const dialogLoading = ref(false);

const selectedAdmission = ref<Admission | null>(null);

const dialogAction = ref<"admit" | "cancel" | "discharge" | "change-room">(
    "cancel",
);

const dialogConfig = computed(() => {
    switch (dialogAction.value) {
        case "admit":
            return {
                title: "Admit Patient",
                message: "Are you sure you want to admit this patient?",
                confirmLabel: "Admit",
                variant: "default" as const,
            };

        case "cancel":
            return {
                title: "Cancel Admission",
                message: "Are you sure you want to cancel this admission?",
                description: "This action cannot be undone.",
                confirmLabel: "Cancel Admission",
                variant: "danger" as const,
            };

        case "discharge":
            return {
                title: "Discharge Patient",
                message: "Are you sure you want to discharge this patient?",
                confirmLabel: "Discharge",
                variant: "danger" as const,
            };

        case "change-room":
            return {
                title: "Change Room",
                message: "Continue to change the patient's room?",
                confirmLabel: "Continue",
                variant: "default" as const,
            };

        //         case "extend":
        // return {
        //     title: "Extend Admission",
        //     message: "Do you want to extend this patient's admission?",
        //     confirmLabel: "Extend",
        //     variant: "default" as const,
        // };
    }
});

function openDialog(
    action: "admit" | "cancel" | "discharge" | "change-room",
    admission: Admission,
) {
    selectedAdmission.value = admission;
    dialogAction.value = action;
    dialogOpen.value = true;
}

function closeDialog() {
    if (dialogLoading.value) return;

    dialogOpen.value = false;
    selectedAdmission.value = null;
}

async function confirmDialog() {
    if (!selectedAdmission.value) return;

    dialogLoading.value = true;

    try {
        switch (dialogAction.value) {
            case "cancel":
                await handleAction(selectedAdmission.value, "cancel");
                break;

            case "admit":
                await handleAction(selectedAdmission.value, "admit");
                break;

            case "change-room":
                break;

            case "discharge":
                await handleAction(selectedAdmission.value, "discharge");
                break;
        }

        closeDialog();
    } finally {
        dialogLoading.value = false;
    }
}

async function handleAction(admission: Admission, action: string) {
    if (cancellingId.value) return;

    cancellingId.value = admission.patient_admission_id;

    try {
        const res = await admissionService.action({
            branch_uuid: route.params.uuid,
            admission_id: admission.patient_admission_id,
            p_uuid: route.params.p_uuid,
            action: action,
        });
        const updatedAdmission = res.data;
        const index = admissions.value.findIndex(
            (a) => a.patient_admission_id === admission.patient_admission_id,
        );
        if (index !== -1 && updatedAdmission) {
            admissions.value[index] = {
                ...admissions.value[index],
                ...updatedAdmission,
            };
        }
        success(res.message);
    } catch (err: any) {
        error(
            err?.data?.message ??
                "Something went wrong processing this admission.",
        );
    } finally {
        cancellingId.value = null;
    }
}
const billingModalOpen = ref(false);

function openBillingModal(admission: Admission) {
    selectedAdmission.value = admission;
    billingModalOpen.value = true;
}
async function handleExtend(contract: Contract) {
    if (!selectedAdmission.value) return;

    try {
        const res = await admissionService.action({
            branch_uuid: route.params.uuid,
            p_uuid: route.params.p_uuid,
            admission_id: selectedAdmission.value.patient_admission_id,
            action: "extend",
            contract: contract,
        });

        const updatedAdmission = res.data;

        const index = admissions.value.findIndex(
            (a) =>
                a.patient_admission_id ===
                selectedAdmission.value?.patient_admission_id,
        );

        if (index !== -1) {
            admissions.value[index] = {
                ...admissions.value[index],
                ...updatedAdmission,
            };
        }

        success(res.message);
    } catch (err: any) {
        error(
            err?.data?.message ??
                "Something went wrong extending this admission.",
        );
    } finally {
        selectedAdmission.value = null;
        billingModalOpen.value = false;
    }
}
</script>

<template>
    <div class="space-y-6">
        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div
                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-primary text-xl font-semibold text-white"
                >
                    {{ patient.first_name.charAt(0) }}
                </div>

                <div>
                    <p
                        class="text-xs font-medium uppercase tracking-wide text-primary"
                    >
                        Patient Overview
                    </p>

                    <h2 class="mt-1 text-xl font-semibold text-[#16302E]">
                        {{ patient.full_name }}
                    </h2>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <span
                            class="rounded-full bg-primary-50 px-3 py-1 text-xs font-medium text-primary"
                        >
                            {{ patient.gender }}
                        </span>

                        <span
                            class="rounded-full bg-[#F7FAF9] px-3 py-1 text-xs font-medium text-[#16302E]"
                        >
                            {{ patient.age }} years old
                        </span>

                        <span
                            class="rounded-full bg-[#F7FAF9] px-3 py-1 text-xs font-medium text-[#16302E]"
                        >
                            {{ patient.blood_type || "No blood type on file" }}
                        </span>
                    </div>
                </div>
            </div>

            <div
                class="mt-6 grid gap-6 border-t border-[#F0F4F3] pt-6 sm:grid-cols-3"
            >
                <div class="flex items-center gap-3">
                    <Calendar class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted">Birthday</p>
                        <p class="mt-0.5 text-sm font-medium text-[#16302E]">
                            {{ formatDate(patient.date_of_birth) }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Phone class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted">Contact</p>
                        <p class="mt-0.5 text-sm font-medium text-[#16302E]">
                            {{ patient.phone_number || "—" }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Globe2 class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted">Citizenship</p>
                        <p class="mt-0.5 text-sm font-medium text-[#16302E]">
                            {{ patient.citizenship || "—" }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="mt-6 grid gap-6 border-t border-[#F0F4F3] pt-6 sm:grid-cols-3"
            >
                <div class="flex items-center gap-3">
                    <Ruler class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted">Height</p>
                        <p class="mt-0.5 text-sm font-medium text-[#16302E]">
                            {{ patient.height || "—" }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Weight class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted">Weight</p>
                        <p class="mt-0.5 text-sm font-medium text-[#16302E]">
                            {{ patient.weight || "—" }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Pill class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted">Medication</p>
                        <p class="mt-0.5 text-sm font-medium text-[#16302E]">
                            {{ patient.medication?.length || 0 }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="mt-6 flex items-start gap-3 border-t border-[#F0F4F3] pt-6"
            >
                <MapPin class="h-4 w-4 shrink-0 text-primary" />
                <div>
                    <p class="text-xs text-muted">Location</p>
                    <p class="mt-0.5 text-sm font-medium text-[#16302E]">
                        {{
                            patient.location?.full_address ||
                            "No address recorded."
                        }}
                    </p>
                </div>
            </div>
        </section>

        <section
            v-if="admissions.length"
            class="rounded-2xl bg-white p-6 shadow-sm"
        >
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Building2 class="h-4 w-4 text-primary" />
                    <h3 class="font-semibold text-[#16302E]">
                        Admission{{ admissions.length === 1 ? "" : "s" }}
                    </h3>
                </div>

                <span
                    class="rounded-full bg-primary-50 px-2.5 py-1 text-xs font-medium text-primary"
                >
                    {{ admissions.length }} Record{{
                        admissions.length === 1 ? "" : "s"
                    }}
                </span>
            </div>

            <div class="mt-5 space-y-5">
                <div
                    v-for="admission in admissions"
                    :key="admission.patient_admission_id"
                    class="rounded-xl p-4 transition hover:bg-primary-50/60"
                    :class="cardClasses(admission.status)"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <p
                                    class="text-sm font-semibold capitalize text-[#16302E]"
                                >
                                    {{ admission.status }}
                                </p>

                                <p
                                    v-if="
                                        admission.status
                                            ?.toLowerCase()
                                            .includes('discharge') &&
                                        admission.end_date
                                    "
                                    class="mt-0.5 text-xs text-muted"
                                >
                                    at {{ formatDate(admission.end_date) }}
                                </p>
                            </div>

                            <div class="flex items-center gap-1">
                                <p class="mt-0.5 text-xs text-muted">
                                    {{
                                        isWaiting(admission.status)
                                            ? `Waiting for admission at ${formatDate(admission.admitted_at)}`
                                            : `Admitted at ${formatDate(admission.admitted_at)}`
                                    }}
                                </p>

                                <p
                                    v-if="
                                        isAdmitted(admission.status) &&
                                        admission.end_date
                                    "
                                    class="mt-0.5 text-xs text-muted"
                                >
                                    till {{ formatDate(admission.end_date) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <span
                                class="rounded-full px-3 py-1 text-xs font-medium capitalize"
                                :class="statusClasses(admission.status)"
                            >
                                {{ admission.status }}
                            </span>

                            <div class="flex items-center gap-2">
                                <template v-if="isWaiting(admission.status)">
                                    <button
                                        type="button"
                                        class="rounded-full bg-primary px-3 py-1 text-xs font-medium text-white hover:opacity-90"
                                        @click="openDialog('admit', admission)"
                                    >
                                        Admit
                                    </button>

                                    <button
                                        type="button"
                                        :disabled="
                                            cancellingId ===
                                            admission.patient_admission_id
                                        "
                                        class="rounded-full border border-[#F0C4BC] bg-white px-3 py-1 text-xs font-medium text-[#B3402F] hover:bg-[#FBE8E6] disabled:opacity-60"
                                        @click="openDialog('cancel', admission)"
                                    >
                                        {{
                                            cancellingId ===
                                            admission.patient_admission_id
                                                ? "Cancelling..."
                                                : "Cancel"
                                        }}
                                    </button>
                                </template>

                                <template v-if="isAdmitted(admission.status)">
                                    <button
                                        type="button"
                                        class="rounded-full bg-primary px-3 py-1 text-xs font-medium text-white hover:opacity-90"
                                        @click="
                                            openDialog('change-room', admission)
                                        "
                                    >
                                        Change Room
                                    </button>

                                    <button
                                        type="button"
                                        class="rounded-full bg-amber-500 px-3 py-1 text-xs font-medium text-white hover:bg-amber-600"
                                        @click="openBillingModal(admission)"
                                    >
                                        Extend
                                    </button>

                                    <button
                                        type="button"
                                        class="rounded-full border border-[#F0C4BC] bg-white px-3 py-1 text-xs font-medium text-[#B3402F] hover:bg-[#FBE8E6]"
                                        @click="
                                            openDialog('discharge', admission)
                                        "
                                    >
                                        Discharge
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-3">
                        <div class="flex items-center gap-2">
                            <Building2 class="h-3.5 w-3.5 text-primary" />
                            <div>
                                <p class="text-[11px] text-muted">Floor</p>
                                <p class="text-sm font-medium text-[#16302E]">
                                    {{ admission.room?.floor || "—" }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <DoorOpen class="h-3.5 w-3.5 text-primary" />
                            <div>
                                <p class="text-[11px] text-muted">Room</p>
                                <p class="text-sm font-medium text-[#16302E]">
                                    {{ admission.room?.room_no || "—" }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <BedDouble class="h-3.5 w-3.5 text-primary" />
                            <div>
                                <p class="text-[11px] text-muted">Bed</p>
                                <p class="text-sm font-medium text-[#16302E]">
                                    {{ admission.bed?.bed_no || "—" }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="admission.current_contract"
                        class="mt-4 border-t border-[#E4EFED] pt-4"
                    >
                        <p class="mb-2 text-xs font-semibold text-[#16302E]">
                            Current Contract
                        </p>

                        <div class="space-y-2">
                            <div
                                class="rounded-lg bg-white px-3 py-2 border border-[#E4EFED]"
                            >
                                <div class="flex justify-between">
                                    <span class="text-xs text-muted">
                                        {{
                                            admission.current_contract
                                                ?.category || "—"
                                        }}
                                    </span>

                                    <span
                                        class="text-xs font-semibold text-primary"
                                    >
                                        ₱{{ admission.current_contract?.price }}
                                    </span>
                                </div>

                                <div class="mt-1 text-xs text-[#16302E]">
                                    {{
                                        admission.current_contract
                                            ?.accommodation_type || "—"
                                    }}
                                    ·
                                    {{
                                        admission.current_contract
                                            ?.billing_cycle || "—"
                                    }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <ConfirmDialog
        :open="dialogOpen"
        :title="dialogConfig.title"
        :message="dialogConfig.message"
        :description="dialogConfig.description"
        :confirm-label="dialogConfig.confirmLabel"
        :variant="dialogConfig.variant"
        :loading="dialogLoading"
        @confirm="confirmDialog"
        @cancel="closeDialog"
    />

    <BillingCycleModal
        :open="billingModalOpen"
        :admission="selectedAdmission"
        @close="billingModalOpen = false"
        @select="handleExtend"
    />
</template>
