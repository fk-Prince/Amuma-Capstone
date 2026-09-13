<template>
    <div
        class="min-h-screen-header grid grid-cols-1 lg:grid-cols-[1fr_320px] bg-slate-50 dark:bg-surface"
    >
        <main class="px-5 sm:px-10 py-8 w-full mx-auto lg:mx-0">
            <div class="lg:hidden mb-6">
                <div class="flex items-center gap-2">
                    <div
                        class="h-1.5 flex-1 rounded-full bg-gray-100 overflow-hidden dark:bg-white/10"
                    >
                        <div
                            class="h-full rounded-full bg-primary transition-all duration-300"
                            :style="{ width: `${progress}%` }"
                        ></div>
                    </div>
                    <span
                        class="text-xs font-medium text-gray-400 shrink-0 dark:text-gray-500"
                    >
                        {{ Math.round(progress) }}%
                    </span>
                </div>
            </div>

            <div
                :class="
                    showPayment
                        ? 'grid grid-cols-1 xl:grid-cols-[1fr_450px] gap-6 items-start'
                        : ''
                "
            >
                <div>
                    <div
                        class="mt-6 rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
                    >
                        <AdmissionReview
                            :reserved="bookingStore.reserved"
                            :roomContract="bookingStore.contract"
                            :patient="bookingStore.patient"
                            :guardian="bookingStore.guardian"
                            :assessment="bookingStore.assessment"
                            :diagnoses="bookingStore.diagnoses"
                            :payment="bookingStore.payment"
                            @edit-step="goEditStep"
                        />
                    </div>

                    <div
                        v-if="requiresReservation && !hasReservation"
                        class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 flex items-start gap-3 dark:border-amber-500/20 dark:bg-amber-500/10"
                    >
                        <span
                            class="h-8 w-8 shrink-0 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 text-sm font-semibold dark:bg-amber-500/15 dark:text-amber-300"
                        >
                            !
                        </span>
                        <div>
                            <p
                                class="text-sm font-medium text-amber-800 dark:text-amber-300"
                            >
                                No room or bed reserved
                            </p>
                            <p
                                class="text-xs text-amber-700 mt-0.5 dark:text-amber-300"
                            >
                                You need to select an accommodation and bed
                                before you can proceed to payment.
                            </p>
                            <button
                                type="button"
                                class="mt-2 text-xs font-medium text-amber-800 underline hover:no-underline dark:text-amber-300"
                                @click="goEditStep('step2')"
                            >
                                Go back and select a room
                            </button>
                        </div>
                    </div>

                    <div
                        class="mt-6 rounded-2xl border border-gray-100 bg-white shadow-sm p-6 dark:border-white/10 dark:bg-secondary"
                    >
                        <div class="relative group w-full">
                            <BaseButton
                                variant="primary"
                                class="w-full rounded-xl py-3"
                                :disabled="submitDisabled"
                                @click="onAdmitClick"
                            >
                                {{ submitting ? "Submitting..." : actionLabel }}
                            </BaseButton>

                            <div
                                v-if="submitDisabled && admitBlockedReason"
                                class="pointer-events-none absolute right-0 top-full z-50 mt-2 hidden w-max max-w-xs rounded-md bg-gray-900 px-3 py-2 text-[12px] text-white shadow-lg group-hover:block"
                            >
                                {{ admitBlockedReason }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <aside
            class="hidden lg:flex flex-col bg-white border-r sticky top-0 h-screen dark:bg-secondary"
        >
            <div class="px-6 py-6 border-b">
                <p
                    class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500"
                >
                    Admission Progress
                </p>
                <div class="mt-3 flex items-center gap-2">
                    <div
                        class="h-1.5 flex-1 rounded-full bg-gray-100 overflow-hidden dark:bg-white/10"
                    >
                        <div
                            class="h-full rounded-full bg-primary transition-all duration-300"
                            :style="{ width: `${progress}%` }"
                        ></div>
                    </div>
                    <span
                        class="text-xs font-medium text-gray-400 shrink-0 dark:text-gray-500"
                    >
                        {{ Math.round(progress) }}%
                    </span>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-3 py-4">
                <BookingSteps
                    active="step6"
                    :completed="completedSteps"
                    @go="goEditStep"
                />
            </div>
        </aside>

        <AdmissionSlipModal :slip="admissionSlip" @close="closeSlip" />

        <ConfirmDialog
            :open="showPaymentWarning"
            title="Admission payment is incomplete"
            :message="`This booking still has an outstanding balance of ₱${formatMoney(balanceDue)}.`"
            description="You can proceed with the admission and collect the remaining balance later."
            confirm-label="Proceed anyway"
            cancel-label="Cancel"
            variant="danger"
            @confirm="confirmBypassPayment"
            @cancel="showPaymentWarning = false"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "~/composables/useToast";
import BookingSteps from "~/components/sections/booking/provider/BookingSteps.vue";
import BaseButton from "~/components/ui/BaseButton.vue";
import { useBookingStore } from "~/stores/booking";
import type { CardDetails } from "~/types/payment";
import AdmissionReview from "~/components/sections/app/Admission/AdmissionReview.vue";
import AdmissionSlipModal from "~/components/sections/app/Admission/AdmissionSlipModal.vue";
import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";
import { admissionService } from "~/api/admission/AdmissionService";
import { invoiceService } from "~/api/invoice/InvoiceService";
import { formatAmount } from "~/utils/currency";
import { useBranchStore } from "~/stores/branch";
import type { AdmissionSlip } from "~/types/admission-slip";
useHead({ title: "Review Admission" });

definePageMeta({
    navVariant: 1,
    layout: "dashboard",
    // middleware: ["auth-client", "booking-review-guard"],
    middleware: ["auth-client"],
});

const actionLabel = computed(() => {
    // if (
    //     route.query.reference_id &&
    //     bookingStore.payment?.payment_status === "paid"
    // ) {
    //     return "Admit Patient";
    // }
    return "Confirm & Submit Admission";
});

const route = useRoute();
const router = useRouter();
const bookingStore = useBookingStore();
const toast = useToast();
const submitting = ref(false);
const uuid = route.params.uuid as string;

const card = reactive<CardDetails>({
    number: "4000000000002503",
    expMonth: "04",
    expYear: "29",
    cvc: "123",
    firstName: "prince",
    lastName: "sestoso",
    email: "prince.sestoso@gmail.com",
});

const showPayment = computed(
    () =>
        bookingStore.category === "facility" &&
        bookingStore.facility?.type === "Complete",
);

const requiresReservation = computed(
    () =>
        bookingStore.category === "facility" &&
        bookingStore.facility?.type === "Complete",
);

const hasReservation = computed(
    () => !!bookingStore.reserved?.room && !!bookingStore.reserved?.bed,
);

const branchStore = useBranchStore();
const referenceId = computed(() => (route.query.reference_id as string) ?? "");
const balanceDue = ref<number | null>(null);
const showPaymentWarning = ref(false);

const requiresFullPaymentOnAdmit = computed(
    () => branchStore.activeBranch?.settings?.requires_full_payment_on_admit ?? true,
);

const paymentIncomplete = computed(
    () => showPayment.value && (balanceDue.value ?? 0) > 0,
);

const submitDisabled = computed(
    () =>
        submitting.value ||
        (requiresReservation.value && !hasReservation.value) ||
        (requiresFullPaymentOnAdmit.value && paymentIncomplete.value),
);

const admitBlockedReason = computed(() => {
    if (requiresFullPaymentOnAdmit.value && paymentIncomplete.value) {
        return `Full payment is required before this patient can be admitted. Outstanding balance: ₱${formatMoney(balanceDue.value)}.`;
    }

    return "";
});

function formatMoney(amount: number | string | null | undefined) {
    return formatAmount(amount, { treatMissingAsZero: true });
}

async function fetchBalance() {
    if (!referenceId.value) return;

    try {
        const res = await invoiceService.show(
            { reference_id: referenceId.value, branch_uuid: uuid },
            referenceId.value,
        );
        const data = res.data ?? res;
        balanceDue.value = Number(data?.balance_due) || 0;
    } catch (err) {
        console.error(err);
    }
}

onMounted(fetchBalance);

const completedSteps = computed(() => [
    "step1",
    "step2",
    "step3",
    "step4",
    "step5",
]);
const progress = computed(() => 100);

function goEditStep(step: string) {
    router.push({
        path: `/app/branches/${uuid}/admissions`,
        query: {
            ...route.query,
            step,
        },
    });
}

const bookingData = computed(() => ({
    homecare: bookingStore.homecare,
    facility: bookingStore.facility,
    patient: bookingStore.patient,
    guardian: bookingStore.guardian,
    assessment: bookingStore.assessment,
    diagnoses: bookingStore.diagnoses,
    reserved: bookingStore.reserved,
    payment: bookingStore.payment,
}));

const admissionSlip = ref<AdmissionSlip | null>(null);

function closeSlip() {
    admissionSlip.value = null;
    router.push(`/app/branches/${uuid}/admissions/`);
}

function onAdmitClick() {
    if (submitDisabled.value) return;

    if (paymentIncomplete.value) {
        showPaymentWarning.value = true;
        return;
    }

    handleSubmit();
}

function confirmBypassPayment() {
    showPaymentWarning.value = false;
    handleSubmit();
}

async function handleSubmit() {
    if (submitting.value) return;

    if (requiresReservation.value && !hasReservation.value) {
        toast.error(
            "Please select a room and bed before submitting your admission.",
        );
        return;
    }

    submitting.value = true;

    try {
        const res = await admissionService.create({
            reference_id: route.query.reference_id ?? "",
            branch_uuid: uuid,
            ...bookingData.value,
        });
        toast.success(
            res.message ?? "Your admission request was submitted successfully!",
        );

        const slip = res.data?.portal ? (res.data as AdmissionSlip) : null;

        bookingStore.$reset();

        if (slip) {
            admissionSlip.value = slip;

            return;
        }

        router.push(`/app/branches/${uuid}/admissions/`);
    } catch (err: any) {
        console.error(err);
        toast.error(
            err?.data?.message ??
                err?.response?.data?.message ??
                err?.message ??
                "Something went wrong while submitting your admission request.",
        );
    } finally {
        submitting.value = false;
    }
}
</script>
