<template>
    <div class="min-h-screen bg-slate-50">
        <div class="w-full mx-auto px-4 lg:px-8 py-8">
            <button
                type="button"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline mb-6"
                @click="router.back()"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="w-4 h-4"
                >
                    <polyline points="15 18 9 12 15 6" />
                </svg>
                Back
            </button>

            <div v-if="loading" class="space-y-6">
                <div
                    class="rounded-2xl border border-primary-100 bg-white p-6 animate-pulse"
                >
                    <div class="h-6 w-64 bg-slate-200 rounded"></div>
                    <div class="h-3 w-80 bg-slate-100 rounded mt-3"></div>
                </div>
                <div class="grid lg:grid-cols-3 gap-6">
                    <div
                        class="lg:col-span-2 rounded-2xl border border-primary-100 bg-white p-6 animate-pulse h-48"
                    ></div>
                    <div
                        class="rounded-2xl border border-primary-100 bg-white p-6 animate-pulse h-48"
                    ></div>
                </div>
            </div>

            <div
                v-else-if="!patient"
                class="rounded-2xl border border-dashed border-slate-300 p-12 text-center text-slate-400"
            >
                We couldn't find this patient's admission record.
            </div>

            <template v-else>
                <div
                    class="rounded-2xl bg-white border border-primary-100 shadow-[0_0_40px_rgba(10,40,87,0.06)] p-6 mb-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5"
                >
                    <div class="flex items-center gap-4 min-w-0">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary text-lg font-semibold"
                        >
                            {{ initials }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <h1
                                    class="text-lg font-semibold text-primary-900 truncate"
                                >
                                    {{ patient.full_name }}
                                </h1>
                                <span
                                    v-if="latestAdmission"
                                    class="shrink-0 text-xs font-medium capitalize rounded-full px-2.5 py-1"
                                    :class="
                                        statusBadgeClass(latestAdmission.status)
                                    "
                                >
                                    {{ latestAdmission.status }}
                                </span>
                            </div>

                            <div
                                class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1.5 text-xs text-muted"
                            >
                                <span>{{ patient.gender }}</span>
                                <span
                                    class="w-1 h-1 rounded-full bg-slate-300"
                                />
                                <span>{{ patient.age }} years old</span>
                                <template v-if="patient.blood_type">
                                    <span
                                        class="w-1 h-1 rounded-full bg-slate-300"
                                    />
                                    <span>{{ patient.blood_type }}</span>
                                </template>
                                <template v-if="patient.phone_number">
                                    <span
                                        class="w-1 h-1 rounded-full bg-slate-300"
                                    />
                                    <span>{{ patient.phone_number }}</span>
                                </template>
                                <template v-if="patient.location?.full_address">
                                    <span
                                        class="w-1 h-1 rounded-full bg-slate-300"
                                    />
                                    <span class="truncate">{{
                                        patient.location.full_address
                                    }}</span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 shrink-0">
                        <ActionButton
                            variant="primary"
                            :disabled="isWaiting || isAdmitted"
                            tooltip="You don't have permission to admit this patient."
                            @click="openNewAdmissionModal"
                        >
                            New Admission
                        </ActionButton>

                        <ActionButton
                            variant="primary"
                            :disabled="!isWaiting"
                            tooltip="You don't have permission to admit this patient."
                            @click="handleAdmitClick"
                        >
                            Admit
                        </ActionButton>

                        <ActionButton
                            variant="outline"
                            :disabled="!isAdmitted"
                            tooltip="Transfer history is only available for currently admitted patients."
                            @click="transferHistoryModalOpen = true"
                        >
                            Transfer History
                        </ActionButton>
                        <ActionButton
                            variant="outline"
                            :disabled="!isAdmitted"
                            tooltip="You cannot extend the stay because this patient is not currently admitted."
                            @click="handleExtendClick"
                        >
                            Extend Stay
                        </ActionButton>

                        <ActionButton
                            variant="outline"
                            :disabled="!isAdmitted"
                            tooltip="You cannot change the room because this patient is not currently admitted."
                            @click="openChangeRoomModal"
                        >
                            Change Room
                        </ActionButton>

                        <ActionButton
                            variant="danger"
                            :disabled="!isAdmitted"
                            tooltip="This patient cannot be discharged because they are not currently admitted.  "
                            @click="dischargeDialogOpen = true"
                        >
                            Discharge
                        </ActionButton>

                        <ActionButton
                            variant="danger"
                            :disabled="!isWaiting"
                            tooltip="You can only cancel an admission that is still waiting."
                            @click="cancelAdmissionDialogOpen = true"
                        >
                            Cancel
                        </ActionButton>
                    </div>
                </div>

                <div class="grid lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6">
                        <section>
                            <h2
                                class="text-[11px] uppercase tracking-wide text-muted font-semibold mb-2.5"
                            >
                                Current admission
                            </h2>

                            <button
                                v-if="
                                    latestAdmission &&
                                    ['admitted', 'waiting'].includes(
                                        latestAdmission?.status,
                                    )
                                "
                                type="button"
                                class="w-full text-left rounded-2xl bg-white border border-primary-100 shadow-[0_0_40px_rgba(10,40,87,0.06)] p-6 hover:bg-primary-50/40 transition"
                            >
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                                >
                                    <div>
                                        <p
                                            class="text-sm font-semibold text-primary-900"
                                        >
                                            {{
                                                latestAdmission.room?.room_no
                                                    ? `Room ${latestAdmission.room.room_no}`
                                                    : "No room assigned"
                                            }}
                                            <span
                                                v-if="
                                                    latestAdmission.bed?.bed_no
                                                "
                                                class="text-muted font-normal"
                                            >
                                                · Bed
                                                {{ latestAdmission.bed.bed_no }}
                                            </span>
                                        </p>
                                        <p class="text-xs text-muted mt-1">
                                            <span
                                                v-if="
                                                    latestAdmission.status ===
                                                    'waiting'
                                                "
                                            >
                                                Waiting to Admit
                                                {{
                                                    formatDate(
                                                        latestAdmission.admitted_at,
                                                    )
                                                }}
                                            </span>

                                            <span v-else>
                                                Admitted
                                                {{
                                                    formatDate(
                                                        latestAdmission.admitted_at,
                                                    )
                                                }}

                                                <span
                                                    v-if="
                                                        latestAdmission.end_date
                                                    "
                                                >
                                                    — Ends
                                                    {{
                                                        formatDate(
                                                            latestAdmission.end_date,
                                                        )
                                                    }}
                                                </span>
                                            </span>
                                        </p>
                                    </div>

                                    <div
                                        v-if="latestAdmission.current_contract"
                                        class="text-right shrink-0"
                                    >
                                        <p
                                            class="text-lg text-primary uppercase"
                                        >
                                            {{ latestAdmission.status }}
                                        </p>
                                        <p class="text-xs text-muted mt-0.5">
                                            {{
                                                latestAdmission.current_contract
                                                    .accommodation_type
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="totalStayDays !== null" class="mt-5">
                                    <div
                                        class="h-1.5 rounded-full bg-slate-100 overflow-hidden"
                                    >
                                        <div
                                            class="h-full rounded-full bg-primary transition-all duration-300"
                                            :style="{
                                                width: `${stayProgress ?? 0}%`,
                                            }"
                                        ></div>
                                    </div>
                                    <p class="text-[11px] text-muted mt-1.5">
                                        Day {{ dayOfStay ?? 0 }} of
                                        {{ totalStayDays }}
                                    </p>
                                </div>

                                <div
                                    v-if="latestInvoice"
                                    class="mt-4 pt-4 border-t border-primary-100 flex items-center justify-between"
                                >
                                    <p class="text-xs text-muted">
                                        Invoice
                                        <span
                                            class="text-primary-900 font-medium"
                                        >
                                            #{{ latestInvoice.invoice_code }}
                                        </span>

                                        :
                                        <span
                                            class="font-medium border rounded-md px-2 py-1 uppercase"
                                            :class="
                                                INVOICE_STATUS[
                                                    latestInvoice.status
                                                ]
                                            "
                                        >
                                            {{ latestInvoice.status }}
                                        </span>
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-primary-900"
                                    >
                                        {{
                                            formatCurrency(latestInvoice.price)
                                        }}
                                    </p>
                                </div>
                            </button>

                            <div
                                v-else
                                class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-400"
                            >
                                No active admission on record.
                            </div>
                        </section>

                        <section>
                            <h2
                                class="text-[11px] uppercase tracking-wide text-muted font-semibold mb-2.5"
                            >
                                Admission history
                                <span v-if="pastAdmissions.length">
                                    ({{ pastAdmissions.length }})
                                </span>
                            </h2>

                            <div
                                v-if="!pastAdmissions.length"
                                class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-400"
                            >
                                No previous admissions.
                            </div>

                            <div v-else class="space-y-4">
                                <div
                                    v-for="admission in pastAdmissions"
                                    :key="admission.patient_admission_id"
                                    class="rounded-2xl bg-white border border-primary-100 shadow-[0_0_40px_rgba(10,40,87,0.06)] p-6 hover:bg-primary-50/40 transition"
                                >
                                    <div
                                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                                    >
                                        <div>
                                            <p
                                                class="text-sm font-semibold text-primary-900"
                                            >
                                                {{
                                                    admission.room?.room_no
                                                        ? `Room ${admission.room.room_no}`
                                                        : "No room assigned"
                                                }}

                                                <span
                                                    v-if="admission.bed?.bed_no"
                                                    class="text-muted font-normal"
                                                >
                                                    · Bed
                                                    {{ admission.bed.bed_no }}
                                                </span>
                                            </p>

                                            <p class="text-xs text-muted mt-1">
                                                Admitted
                                                {{
                                                    formatDate(
                                                        admission.admitted_at,
                                                    )
                                                }}

                                                <span v-if="admission.end_date">
                                                    — Ended
                                                    {{
                                                        formatDate(
                                                            admission.end_date,
                                                        )
                                                    }}
                                                </span>
                                            </p>
                                        </div>

                                        <div
                                            class="flex items-center gap-3 shrink-0"
                                        >
                                            <div
                                                v-if="
                                                    admission.current_contract
                                                "
                                                class="text-right"
                                            >
                                                <p
                                                    class="text-xs text-muted mt-0.5"
                                                >
                                                    {{
                                                        admission
                                                            .current_contract
                                                            .accommodation_type
                                                    }}
                                                </p>
                                            </div>

                                            <span
                                                class="text-xs font-medium capitalize rounded-full px-2.5 py-1"
                                                :class="
                                                    statusBadgeClass(
                                                        admission.status,
                                                    )
                                                "
                                            >
                                                {{ admission.status }}
                                            </span>
                                        </div>
                                    </div>

                                    <div
                                        v-if="admission.latest_invoice"
                                        class="mt-4 pt-4 border-t border-primary-100 flex items-center justify-between"
                                    >
                                        <p class="text-xs text-muted">
                                            Latest invoice
                                            <span
                                                class="text-primary-900 font-medium"
                                            >
                                                #{{
                                                    admission.latest_invoice
                                                        .invoice_code
                                                }}
                                            </span>
                                        </p>

                                        <p
                                            class="text-sm font-semibold text-primary-900"
                                        >
                                            {{
                                                formatCurrency(
                                                    admission.latest_invoice
                                                        .price,
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <aside class="space-y-6">
                        <div
                            class="rounded-2xl bg-white border border-primary-100 shadow-[0_0_40px_rgba(10,40,87,0.06)] p-6"
                        >
                            <h3
                                class="text-[11px] uppercase tracking-wide text-muted font-semibold mb-4"
                            >
                                Quick facts
                            </h3>

                            <dl class="space-y-3.5">
                                <div class="flex items-center justify-between">
                                    <dt class="text-xs text-muted">
                                        Total admissions
                                    </dt>
                                    <dd
                                        class="text-sm font-semibold text-primary-900"
                                    >
                                        {{ patient.admissions?.length ?? 0 }}
                                    </dd>
                                </div>
                                <div
                                    v-if="dayOfStay !== null"
                                    class="flex items-center justify-between"
                                >
                                    <dt class="text-xs text-muted">
                                        Current stay
                                    </dt>
                                    <dd
                                        class="text-sm font-semibold text-primary-900"
                                    >
                                        {{ dayOfStay }} day{{
                                            dayOfStay === 1 ? "" : "s"
                                        }}
                                    </dd>
                                </div>
                                <div
                                    v-if="patient.height"
                                    class="flex items-center justify-between"
                                >
                                    <dt class="text-xs text-muted">Height</dt>
                                    <dd
                                        class="text-sm font-semibold text-primary-900"
                                    >
                                        {{ patient.height }} cm
                                    </dd>
                                </div>
                                <div
                                    v-if="patient.weight"
                                    class="flex items-center justify-between"
                                >
                                    <dt class="text-xs text-muted">Weight</dt>
                                    <dd
                                        class="text-sm font-semibold text-primary-900"
                                    >
                                        {{ patient.weight }} kg
                                    </dd>
                                </div>
                                <div
                                    v-if="patient.citizenship"
                                    class="flex items-center justify-between"
                                >
                                    <dt class="text-xs text-muted">
                                        Citizenship
                                    </dt>
                                    <dd
                                        class="text-sm font-semibold text-primary-900"
                                    >
                                        {{ patient.citizenship }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </aside>
                </div>
            </template>
        </div>

        <Teleport to="body">
            <div
                v-if="admitModalOpen"
                class="fixed inset-0 bg-primary-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
                @click.self="admitModalOpen = false"
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
                            :min="todayStr"
                        />
                        <!-- <BaseInput
                            label="Deposit"
                            mode="number"
                            v-model="admitDeposit"
                            placeholder="0.00"
                        /> -->
                    </div>

                    <div class="mt-5 flex justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-700 transition"
                            @click="admitModalOpen = false"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :disabled="!admitDate || actionLoading"
                            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed transition"
                            @click="confirmAdmit"
                        >
                            {{ actionLoading ? "Admitting..." : "Admit" }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <AdmissionDetail
                v-if="newAdmissionModalOpen"
                variant="modal"
                :loading="loadingContract"
                :roomContract="roomContract"
                :model="reserved"
                :errors="reservedErrors"
                :requireAdmissionDate="true"
                @update:model="reserved = $event"
                @close="closeNewAdmissionModal"
                @confirm="handleNewAdmissionConfirm"
            />
        </Teleport>

        <ConfirmDialog
            :open="dischargeDialogOpen"
            title="Discharge Patient"
            message="Are you sure you want to discharge this patient?"
            description="This will end the current admission and mark it as discharged and cannot be undone."
            confirm-label="Discharge"
            variant="danger"
            :loading="actionLoading"
            @confirm="confirmDischarge"
            @cancel="dischargeDialogOpen = false"
        />
        <ConfirmDialog
            :open="cancelAdmissionDialogOpen"
            title="Cancel Admission"
            message="Are you sure you want to cancel this admission?"
            description="This will cancel the pending admission and free up the reserved bed. This cannot be undone."
            confirm-label="Cancel Admission"
            variant="danger"
            :loading="actionLoading"
            @confirm="confirmCancelAdmission"
            @cancel="cancelAdmissionDialogOpen = false"
        />

        <ConfirmDialog
            :open="newAdmissionDialogOpen"
            title="Confirm New Admission"
            message="Are you sure you want to admit this patient?"
            description="This will create a new admission record for this patient."
            confirm-label="Admit"
            variant="default"
            :loading="actionLoading"
            @confirm="confirmNewAdmission"
            @cancel="cancelNewAdmissionConfirm"
        />

        <ConfirmDialog
            :open="unpaidAdmitDialogOpen"
            title="Unpaid Invoice"
            message="This patient hasn't paid yet."
            description="Are you sure you want to admit this patient without full payment?"
            confirm-label="Yes, Continue"
            variant="danger"
            :loading="actionLoading"
            @confirm="proceedToAdmitModal"
            @cancel="unpaidAdmitDialogOpen = false"
        />

        <ConfirmDialog
            :open="unpaidExtendDialogOpen"
            title="Unpaid Invoice"
            message="This patient hasn't paid yet."
            description="Are you sure you want to extend the stay without full payment?"
            confirm-label="Yes, Continue"
            variant="danger"
            :loading="actionLoading"
            @confirm="proceedToExtendModal"
            @cancel="unpaidExtendDialogOpen = false"
        />

        <BillingCycleModal
            :open="extendModalOpen"
            :admission="latestAdmission ?? null"
            @select="handleExtendSelect"
            @close="extendModalOpen = false"
        />

        <ChangeRoomModal
            :open="changeRoomModalOpen"
            :admission="latestAdmission ?? null"
            @select="handleChangeRoomSelect"
            @close="changeRoomModalOpen = false"
        />

        <TransferHistoryModal
            :open="transferHistoryModalOpen"
            :admission="latestAdmission ?? null"
            @close="transferHistoryModalOpen = false"
        />
    </div>
</template>
<script setup lang="ts">
import { computed, ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import type { PatientRetrieve, Admission } from "~/types/patient";
import type { RoomContract, Reserved } from "~/types/contract";
import { patientService } from "~/api/patient/PatientService";
import { admissionService } from "~/api/admission/AdmissionService";
import { useToast } from "~/composables/useToast";
import { toLocalDateString } from "~/utils/time";
import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import BillingCycleModal from "~/components/sections/app/Patient/BillingCycleModal.vue";
import type { Room } from "~/types/room";
import type { Bed } from "~/types/bed";
import ChangeRoomModal from "~/components/sections/app/Admission/ChangeRoomModal.vue";
import TransferHistoryModal from "~/components/sections/app/Admission/TransferHistoryModal.vue";
import ActionButton from "~/components/ui/ActionButton.vue";
import AdmissionDetail from "~/components/sections/app/Admission/AdmissionDetail.vue";
import { INVOICE_STATUS } from "~/types/invoice";

definePageMeta({
    layout: "dashboard",
    middleware: ["auth-client"],
});

useHead({ title: "Admission History" });

const route = useRoute();
const router = useRouter();
const { success, error } = useToast();

const id = computed(() => route.params.id as string);
const uuid = computed(() => route.params.uuid as string);

const loading = ref(true);
const patient = ref<PatientRetrieve | null>(null);

const latestAdmission = computed<Admission | undefined>(
    () => patient.value?.latest_admission,
);

const pastAdmissions = computed<Admission[]>(() => {
    const all = patient.value?.admissions ?? [];
    const latestId = latestAdmission.value?.patient_admission_id;
    const latestStatus = latestAdmission.value?.status;

    return all
        .filter((a) => {
            if (a.status !== "discharged") {
                return false;
            }

            if (a.patient_admission_id === latestId) {
                return latestStatus === "discharged";
            }

            return true;
        })
        .sort(
            (a, b) =>
                new Date(b.admitted_at).getTime() -
                new Date(a.admitted_at).getTime(),
        );
});

const latestInvoice = computed(() => {
    const invoices = latestAdmission.value?.invoices ?? [];
    return invoices.length ? invoices[invoices.length - 1] : null;
});

const initials = computed(() => {
    const parts = (patient.value?.full_name ?? "").trim().split(/\s+/);
    return (
        (parts[0]?.[0] ?? "") + (parts[parts.length - 1]?.[0] ?? "")
    ).toUpperCase();
});

const status = computed(() => latestAdmission.value?.status?.toLowerCase());
const isWaiting = computed(() => status.value === "waiting");
const isAdmitted = computed(() => status.value === "admitted");

const isInvoiceUnpaid = computed(() => {
    return (latestInvoice.value?.status ?? "").toLowerCase() !== "paid";
});

const admitModalOpen = ref(false);
const admitDate = ref("");
const admitDeposit = ref("");
const dischargeDialogOpen = ref(false);
const extendModalOpen = ref(false);
const actionLoading = ref(false);
const unpaidAdmitDialogOpen = ref(false);
const unpaidExtendDialogOpen = ref(false);
const cancelAdmissionDialogOpen = ref(false);
const todayStr = toLocalDateString(new Date());

const changeRoomModalOpen = ref(false);
const transferHistoryModalOpen = ref(false);

const newAdmissionModalOpen = ref(false);
const newAdmissionDialogOpen = ref(false);
const loadingContract = ref(false);
const roomContract = ref<RoomContract[]>([]);
const reserved = ref<Reserved | null>(null);
const reservedErrors = ref<Record<string, string>>({});
const pendingAdmission = ref<Reserved | null>(null);

function openChangeRoomModal() {
    changeRoomModalOpen.value = true;
}

function openAdmitModal() {
    admitDate.value = todayStr;
    admitDeposit.value = "";
    admitModalOpen.value = true;
}

function handleAdmitClick() {
    if (isInvoiceUnpaid.value) {
        unpaidAdmitDialogOpen.value = true;
        return;
    }
    openAdmitModal();
}

function proceedToAdmitModal() {
    unpaidAdmitDialogOpen.value = false;
    openAdmitModal();
}

function openExtendModal() {
    extendModalOpen.value = true;
}

function handleExtendClick() {
    if (isInvoiceUnpaid.value) {
        unpaidExtendDialogOpen.value = true;
        return;
    }
    openExtendModal();
}

function proceedToExtendModal() {
    unpaidExtendDialogOpen.value = false;
    openExtendModal();
}

async function openNewAdmissionModal() {
    reserved.value = null;
    reservedErrors.value = {};
    newAdmissionModalOpen.value = true;
    await fetchRoomContracts();
}

function closeNewAdmissionModal() {
    newAdmissionModalOpen.value = false;
    reserved.value = null;
    reservedErrors.value = {};
}

async function fetchRoomContracts() {
    loadingContract.value = true;
    try {
        const res = await admissionService.action({
            branch_uuid: route.params.uuid,
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

async function runAction(
    action: "admit" | "discharge" | "extend" | "change_room" | "cancel",
    extra: Record<string, unknown> = {},
) {
    if (!latestAdmission.value) return;

    actionLoading.value = true;

    try {
        const res = await admissionService.action({
            branch_uuid: uuid.value,
            p_uuid: id.value,
            admission_id: latestAdmission.value.patient_admission_id,
            action,
            ...extra,
        });
        patient.value = res.data;
        success(res?.message ?? "Updated successfully.");
    } catch (err: any) {
        error(err?.data?.message ?? "Something went wrong. Please try again.");
    } finally {
        actionLoading.value = false;
        admitModalOpen.value = false;
        dischargeDialogOpen.value = false;
        extendModalOpen.value = false;
    }
}

function confirmCancelAdmission() {
    runAction("cancel");
}

function confirmAdmit() {
    if (!admitDate.value) return;
    runAction("admit", {
        admitted_at: admitDate.value,
        deposit: admitDeposit.value,
    });
}

function confirmDischarge() {
    runAction("discharge");
}

async function handleExtendSelect(payload: {
    contract: RoomContract;
    end_date: string;
    room?: Room;
    bed?: Bed;
}) {
    await runAction("extend", {
        end_date: payload.end_date,
        contract_id: payload.contract.contract_id,
        ...(payload.room ? { room_id: payload.room.room_id } : {}),
        ...(payload.bed ? { bed_id: payload.bed.bed_id } : {}),
    });
    extendModalOpen.value = false;
}

async function handleChangeRoomSelect(payload: {
    room: Room;
    bed: Bed;
    reason: string;
}) {
    await runAction("change_room", {
        room_id: payload.room.room_id,
        bed_id: payload.bed.bed_id,
        reason: payload.reason,
    });
    changeRoomModalOpen.value = false;
}

function handleNewAdmissionConfirm(payload: Reserved) {
    pendingAdmission.value = payload;
    newAdmissionModalOpen.value = false;
    newAdmissionDialogOpen.value = true;
}

function cancelNewAdmissionConfirm() {
    newAdmissionDialogOpen.value = false;
    pendingAdmission.value = null;
}

async function confirmNewAdmission() {
    if (!pendingAdmission.value) return;

    actionLoading.value = true;
    reservedErrors.value = {};

    try {
        const payload = {
            contract_id: pendingAdmission.value.contract_id,
            bed_id: pendingAdmission.value.bed?.bed_id,
            room_id: pendingAdmission.value.room?.room_id,
            admitted_at: pendingAdmission.value.admitted_at,
        };
        const res = await admissionService.action({
            branch_uuid: uuid.value,
            p_uuid: id.value,
            action: "new_admission",
            ...payload,
        });
        patient.value = res.data;
        success(res?.message ?? "Patient admitted successfully.");
        newAdmissionDialogOpen.value = false;
        pendingAdmission.value = null;
        reserved.value = null;
    } catch (err: any) {
        reservedErrors.value = err?.data?.errors ?? {};
        error(err?.data?.message ?? "Something went wrong. Please try again.");
        newAdmissionDialogOpen.value = false;
        newAdmissionModalOpen.value = true;
    } finally {
        newAdmissionDialogOpen.value = false;
        actionLoading.value = false;
    }
}

const dayOfStay = computed(() => {
    if (!latestAdmission.value) return null;
    if (status.value !== "admitted" || !latestAdmission.value.admitted_at) {
        return 0;
    }
    const start = new Date(latestAdmission.value.admitted_at).getTime();
    const now = Date.now();
    if (Number.isNaN(start)) return 0;
    return Math.max(1, Math.ceil((now - start) / 86400000));
});

const totalStayDays = computed(() => {
    if (!latestAdmission.value?.end_date) return null;
    const startRaw = latestAdmission.value.admitted_at ?? todayStr;
    const start = new Date(startRaw).getTime();
    const end = new Date(latestAdmission.value.end_date).getTime();
    if (Number.isNaN(start) || Number.isNaN(end)) return null;
    return Math.max(1, Math.ceil((end - start) / 86400000));
});

const stayProgress = computed(() => {
    if (dayOfStay.value === null || !totalStayDays.value) return null;
    return Math.min(
        100,
        Math.round((dayOfStay.value / totalStayDays.value) * 100),
    );
});

async function fetchPatient() {
    loading.value = true;
    patient.value = null;
    try {
        const response = await patientService.show(
            {
                branch_uuid: uuid.value,
                p_uuid: id.value,
            },
            id.value,
        );
        const list = response.data ?? response;
        patient.value = Array.isArray(list) ? (list[0] ?? null) : list;
    } catch (err: any) {
        error(err?.data?.message ?? "Couldn't load admission details.");
    } finally {
        loading.value = false;
    }
}

function formatDate(value?: string | null) {
    if (!value) return "—";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString(undefined, {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

function formatCurrency(value?: string | number) {
    if (value === undefined || value === null || value === "") return "—";
    const num = Number(value);
    if (Number.isNaN(num)) return String(value);
    return num.toLocaleString(undefined, {
        style: "currency",
        currency: "PHP",
    });
}

function statusBadgeClass(status?: string) {
    switch (status?.toLowerCase()) {
        case "admitted":
            return "bg-emerald-100 text-emerald-700";
        case "waiting":
            return "bg-blue-100 text-blue-700";
        case "discharged":
        case "completed":
            return "bg-slate-100 text-slate-600";
        case "cancelled":
        case "rejected":
            return "bg-rose-100 text-rose-700";
        default:
            return "bg-primary-50 text-primary-600";
    }
}

onMounted(fetchPatient);
</script>
