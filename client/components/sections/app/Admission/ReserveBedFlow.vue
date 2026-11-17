<template>
    <Teleport to="body">
        <AdmissionDetail
            v-if="detailOpen"
            variant="new"
            :patient-uuid="patientUuid"
            :loading="loadingContract"
            :roomContract="roomContract"
            :model="reserved"
            :errors="errors"
            :requireAdmissionDate="true"
            @update:model="reserved = $event"
            @close="onDetailClose"
            @confirm="openConfirm"
        />
    </Teleport>

    <ConfirmDialog
        :open="confirmOpen"
        title="Reserve this bed?"
        :message="confirmMessage"
        :description="confirmDescription"
        confirm-label="Reserve bed"
        cancel-label="Back"
        loading-label="Reserving..."
        :loading="saving"
        @confirm="submit"
        @cancel="backToDetail"
    >
        <div
            v-if="upcomingHomecare.length"
            class="mt-4 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-3.5 dark:border-amber-500/20 dark:bg-amber-500/10"
        >
            <TriangleAlert
                class="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-300"
            />
            <div class="min-w-0">
                <p
                    class="text-sm font-medium text-amber-800 dark:text-amber-300"
                >
                    This patient has {{ upcomingHomecare.length }} upcoming
                    homecare
                    {{
                        upcomingHomecare.length === 1 ? "schedule" : "schedules"
                    }}
                </p>
                <ul
                    class="mt-1.5 space-y-0.5 text-xs text-amber-700 dark:text-amber-300"
                >
                    <li
                        v-for="schedule in upcomingHomecare"
                        :key="schedule.schedule_code"
                    >
                        {{ stringToDateTime(schedule.scheduled_at) }} ·
                        {{ schedule.schedule_code }}
                    </li>
                </ul>
            </div>
        </div>
    </ConfirmDialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { TriangleAlert } from "lucide-vue-next";
import type { RoomContract, Reserved } from "~/types/contract";
import { admissionService } from "~/api/admission/AdmissionService";
import { useToast } from "~/composables/useToast";
import { formatDate, stringToDateTime } from "~/utils/time";
import { formatCurrency } from "~/utils/currency";
import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";
import AdmissionDetail from "~/components/sections/app/Admission/AdmissionDetail.vue";

type UpcomingSchedule = { schedule_code: string; scheduled_at: string };

const props = defineProps<{
    open: boolean;
    patientUuid: string;
    branchUuid: string;
}>();

const emit = defineEmits<{
    close: [];
    reserved: [patient: any];
}>();

const { success, error } = useToast();

const detailOpen = ref(false);
const confirmOpen = ref(false);
const saving = ref(false);
const loadingContract = ref(false);
const roomContract = ref<RoomContract[]>([]);
const reserved = ref<Reserved | null>(null);
const pending = ref<Reserved | null>(null);
const errors = ref<Record<string, string>>({});
const upcomingHomecare = ref<UpcomingSchedule[]>([]);

const confirmMessage = computed(() => {
    const p = pending.value;
    if (!p) return "";
    return `Room ${p.room?.room_no} · Bed ${p.bed?.bed_no} will be held for this patient from ${formatDate(p.admitted_at)}.`;
});

const confirmDescription = computed(() => {
    const price = pending.value?.price;
    const invoice = price
        ? `A pending invoice of ${formatCurrency(Number(price))} is created now. `
        : "";
    return `${invoice}The patient is admitted only when you press Admit on the admission page.`;
});

watch(
    () => props.open,
    (open) => {
        if (open) start();
        else reset();
    },
    { immediate: true },
);

function reset() {
    detailOpen.value = false;
    confirmOpen.value = false;
    reserved.value = null;
    pending.value = null;
    errors.value = {};
    upcomingHomecare.value = [];
}

async function start() {
    reset();
    detailOpen.value = true;
    await Promise.all([fetchRoomContracts(), fetchUpcomingHomecare()]);
}

function close() {
    reset();
    emit("close");
}

function onDetailClose() {
    if (confirmOpen.value) return;
    close();
}

async function fetchRoomContracts() {
    loadingContract.value = true;
    try {
        const res = await admissionService.action({
            branch_uuid: props.branchUuid,
            action: "branch_contract",
        });
        roomContract.value = res.data?.data ?? res.data ?? res ?? [];
    } catch (err) {
        console.error("Failed loading contracts", err);
        roomContract.value = [];
    } finally {
        loadingContract.value = false;
    }
}

async function fetchUpcomingHomecare() {
    try {
        const res = await admissionService.action({
            branch_uuid: props.branchUuid,
            p_uuid: props.patientUuid,
            action: "upcoming_homecare",
        });
        upcomingHomecare.value = res.data ?? [];
    } catch (err) {
        console.error("Failed loading homecare schedules", err);
        upcomingHomecare.value = [];
    }
}

function openConfirm(payload: Reserved) {
    pending.value = payload;
    detailOpen.value = false;
    confirmOpen.value = true;
}

function backToDetail() {
    confirmOpen.value = false;
    detailOpen.value = true;
}

async function submit() {
    if (!pending.value) return;

    saving.value = true;
    errors.value = {};

    try {
        const res = await admissionService.action({
            branch_uuid: props.branchUuid,
            p_uuid: props.patientUuid,
            action: "new_admission",
            contract_id: pending.value.contract_id,
            bed_id: pending.value.bed?.bed_id,
            room_id: pending.value.room?.room_id,
            admitted_at: pending.value.admitted_at,
        });
        success(
            res?.message ??
                "Bed reserved. Press Admit when the patient arrives.",
        );
        emit("reserved", res.data);
        close();
    } catch (err: any) {
        errors.value = err?.data?.errors ?? {};
        error(err?.data?.message ?? "Something went wrong. Please try again.");
        backToDetail();
    } finally {
        saving.value = false;
    }
}
</script>
