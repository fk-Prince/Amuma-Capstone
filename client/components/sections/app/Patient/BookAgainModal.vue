<template>
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
            class="fixed inset-0 z-[60] flex items-center justify-center p-4"
        >
            <div
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
            />

            <div
                class="relative z-10 flex max-h-[90vh] w-full flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-secondary"
                :class="step === 'payment' ? 'max-w-3xl' : 'max-w-5xl'"
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-6 py-4 dark:border-white/10"
                >
                    <div class="min-w-0">
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500"
                        >
                            Book Again
                        </p>

                        <h2
                            class="mt-1 truncate text-lg font-semibold text-gray-900 dark:text-white"
                        >
                            {{ patientName }}
                        </h2>
                    </div>

                    <button
                        type="button"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-white/80 dark:text-gray-500"
                        @click="close"
                    >
                        <X class="h-4.5 w-4.5" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto">
                    <div v-if="loadingBranch" class="space-y-4 p-8">
                        <div
                            class="h-5 w-40 animate-pulse rounded bg-slate-200 dark:bg-white/10"
                        />
                        <div
                            class="h-28 animate-pulse rounded-2xl bg-slate-100 dark:bg-white/5"
                        />
                        <div
                            class="h-28 animate-pulse rounded-2xl bg-slate-100 dark:bg-white/5"
                        />
                    </div>

                    <div
                        v-else-if="loadError"
                        class="flex flex-col items-center gap-2 p-10 text-center"
                    >
                        <p
                            class="text-sm font-medium text-slate-700 dark:text-gray-300"
                        >
                            Couldn't load booking options
                        </p>
                        <p class="text-xs text-muted dark:text-gray-400">
                            {{ loadError }}
                        </p>
                    </div>

                    <HomecareBooking
                        v-else-if="step === 'details'"
                        :model="model"
                        :services="services"
                        :homecare="branch?.homecare"
                        :settings="branch?.settings"
                        :errors="errors"
                        show-downpayment
                        @update:model="(value) => Object.assign(model, value)"
                        @update:errors="(value) => (errors = value)"
                    />

                    <div
                        v-else
                        class="grid gap-4 p-5 md:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] md:items-start"
                    >
                        <div
                            class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3.5 dark:border-white/10 dark:bg-white/5"
                        >
                            <dl class="space-y-2 text-xs">
                                <div class="flex justify-between gap-3">
                                    <dt
                                        class="text-slate-400 dark:text-gray-500"
                                    >
                                        Service
                                    </dt>

                                    <dd
                                        class="text-right font-medium text-slate-800 dark:text-white"
                                    >
                                        {{ serviceLabel }}
                                    </dd>
                                </div>

                                <div class="flex justify-between gap-3">
                                    <dt
                                        class="text-slate-400 dark:text-gray-500"
                                    >
                                        Schedule
                                    </dt>

                                    <dd
                                        class="text-right font-medium text-slate-800 dark:text-white"
                                    >
                                        {{ scheduleLabel }}
                                    </dd>
                                </div>
                            </dl>

                            <div
                                class="mt-3 space-y-1.5 border-t border-slate-200 pt-3 dark:border-white/10"
                            >
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-gray-400"
                                    >
                                        Total
                                    </span>

                                    <span
                                        class="text-base font-bold tabular-nums text-slate-800 dark:text-white"
                                    >
                                        {{ formatCurrency(totalAmount) }}
                                    </span>
                                </div>

                                <div
                                    v-if="downpaymentPercent < 100"
                                    class="flex items-center justify-between"
                                >
                                    <span
                                        class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-gray-400"
                                    >
                                        Downpayment ({{ downpaymentPercent }}%)
                                    </span>

                                    <span
                                        class="text-base font-bold tabular-nums text-primary"
                                    >
                                        {{ formatCurrency(downpaymentAmount) }}
                                    </span>
                                </div>

                                <div
                                    v-if="downpaymentPercent < 100"
                                    class="flex items-center justify-between"
                                >
                                    <span
                                        class="text-xs text-slate-400 dark:text-gray-500"
                                    >
                                        Remaining balance
                                    </span>

                                    <span
                                        class="text-xs font-medium tabular-nums text-slate-500 dark:text-gray-400"
                                    >
                                        {{
                                            formatCurrency(
                                                totalAmount - downpaymentAmount,
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <PaymentForm
                            compact
                            :card="card"
                            :total-amount="downpaymentAmount"
                            :processing="paying"
                            title="Pay the downpayment"
                            description="Pay securely to send your booking request."
                            submit-label="Confirm & Pay"
                            processing-label="Confirming payment..."
                            gcash-label="Pay with GCash"
                            gcash-processing-label="Redirecting to GCash..."
                            terms-context="booking"
                            @update:card="(value) => Object.assign(card, value)"
                            @card-pay="handleCardPay"
                            @g-cash-pay="handleGCashPay"
                        />
                    </div>
                </div>

                <div
                    class="flex shrink-0 items-center justify-end gap-3 border-t border-gray-100 px-6 py-3 dark:border-white/10"
                >
                    <button
                        v-if="step === 'payment'"
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-60 dark:text-gray-400 dark:hover:bg-white/10"
                        :disabled="paying"
                        @click="step = 'details'"
                    >
                        Back
                    </button>

                    <template v-else>
                        <button
                            type="button"
                            class="rounded-xl px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-white/10"
                            @click="close"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            class="flex items-center gap-2 rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="loadingBranch || !!loadError"
                            @click="continueToPayment"
                        >
                            Continue to Payment
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import { X } from "lucide-vue-next";
import HomecareBooking from "~/components/sections/booking/provider/HomecareBooking.vue";
import PaymentForm from "~/components/forms/PaymentForm.vue";
import { useBranch } from "~/composables/useBranchProvider";
import { serviceService } from "~/api/service/ServiceService";
import { patientAccessService } from "~/api/patient-access/PatientAccessService";
import { cardPayment, gcashPayment } from "~/composables/usePayment";
import { useToast } from "~/composables/useToast";
import { useSchemaValidation } from "~/composables/useSchemaValidation";
import { createHomecareBookingSchema } from "~/schema/booking-schema";
import { formatCurrency } from "~/utils/currency";
import { formatDate, formatTime } from "~/utils/time";
import type { HomecareBooking as HomecareBookingModel } from "~/types/booking";
import type { CardDetails } from "~/types/payment";
import type { Service } from "~/types/service";

const props = defineProps<{
    open: boolean;
    patientId: number;
    patientName: string;
    branchUuid: string | null;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "booked"): void;
}>();

const { success, error: toastError } = useToast();

const { branch, fetchBranch } = useBranch();
const services = ref<Service[]>([]);
const loadingBranch = ref(false);
const loadError = ref<string | null>(null);

function emptyModel(): HomecareBookingModel {
    return {
        type: "ADL",
        date: "",
        prefered_time: "",
        time_span: "",
        address: "",
        latitude: null,
        longitude: null,
        services: [],
        note: "",
    };
}

const model = reactive<HomecareBookingModel>(emptyModel());

const step = ref<"details" | "payment">("details");

function emptyCard(): CardDetails {
    return {
        number: "",
        expMonth: "",
        expYear: "",
        cvc: "",
        firstName: "",
        lastName: "",
        email: "",
    };
}

const card = reactive<CardDetails>(emptyCard());

const homecareSchema = computed(() =>
    createHomecareBookingSchema(branch.value?.homecare?.adl_min_hour ?? 0),
);

const { validate, errors } = useSchemaValidation(homecareSchema, model);

async function loadBranchOptions(uuid: string) {
    loadingBranch.value = true;
    loadError.value = null;

    try {
        await fetchBranch(uuid);

        const res: any = await serviceService.getBranchService(uuid);
        services.value = res?.services ?? res?.data?.services ?? [];
    } catch (err: any) {
        loadError.value =
            err?.response?.data?.message ??
            err?.message ??
            "Something went wrong loading this branch's services.";
    } finally {
        loadingBranch.value = false;
    }
}

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;

        Object.assign(model, emptyModel());
        Object.assign(card, emptyCard());
        errors.value = {};
        step.value = "details";

        if (props.branchUuid) {
            loadBranchOptions(props.branchUuid);
        } else {
            loadError.value = "This loved one has no branch on file.";
        }
    },
    { immediate: true },
);

const paying = ref(false);

const totalAmount = computed(() => {
    if (model.type === "Medical") {
        return (model.services ?? []).reduce(
            (total, item) => total + Number(item.price || 0),
            0,
        );
    }

    const hours = Number(model.time_span) || 0;
    const rate = Number(branch.value?.homecare?.adl_hourly_rate ?? 0);

    return hours * rate;
});

const downpaymentPercent = computed(
    () => branch.value?.settings?.homecare_downpayment_percent ?? 50,
);

const downpaymentAmount = computed(
    () =>
        Math.round(totalAmount.value * (downpaymentPercent.value / 100) * 100) /
        100,
);

const serviceLabel = computed(() =>
    model.type === "ADL"
        ? "Activities of Daily Living (ADL)"
        : (model.services?.[0]?.service_name ?? "Medical service"),
);

const scheduleLabel = computed(() =>
    [formatDate(`${model.date}T00:00:00`), formatTime(model.prefered_time)]
        .filter(Boolean)
        .join(" · "),
);

function continueToPayment() {
    if (!validate()) return;

    step.value = "payment";
}

function bookingPayload() {
    return {
        action: "book_again",
        patient_id: props.patientId,
        category: "Homecare",
        type: model.type,
        date: model.date,
        prefered_time: model.prefered_time,
        time_span: model.time_span,
        address: model.address,
        latitude: model.latitude,
        longitude: model.longitude,
        services: model.services,
        note: model.note?.trim() || null,
        rate:
            model.type === "ADL"
                ? Number(branch.value?.homecare?.adl_hourly_rate ?? 0)
                : undefined,
    };
}

function errorMessage(err: any, fallback: string) {
    return err?.response?.data?.message ?? err?.message ?? fallback;
}

function onPaid(message?: string) {
    paying.value = false;

    success(
        message ??
            "Payment received. Your booking request has been sent to the branch.",
    );

    emit("booked");
    close();
}

async function handleCardPay() {
    if (paying.value) return;

    paying.value = true;

    try {
        await cardPayment({
            card,
            amount: downpaymentAmount.value,
            onClose: () => {
                paying.value = false;
            },
            createPayment: ({ token_id, authentication_id }) =>
                patientAccessService.executeAction({
                    ...bookingPayload(),
                    payment_method: "CREDIT-CARD",
                    token_id,
                    authentication_id,
                }),
            onSuccess: async (res) => onPaid(res?.message),
        });
    } catch (err: any) {
        toastError(errorMessage(err, "Payment failed."));
    } finally {
        paying.value = false;
    }
}

async function handleGCashPay() {
    if (paying.value) return;

    paying.value = true;

    try {
        await gcashPayment({
            createPayment: () =>
                patientAccessService.executeAction({
                    ...bookingPayload(),
                    payment_method: "GCASH",
                }),
            onSuccess: async () => onPaid(),
            onClose: () => {
                paying.value = false;
            },
        });
    } catch (err: any) {
        toastError(errorMessage(err, "GCash payment failed."));
    } finally {
        paying.value = false;
    }
}

function close() {
    if (paying.value) return;

    emit("close");
}
</script>
