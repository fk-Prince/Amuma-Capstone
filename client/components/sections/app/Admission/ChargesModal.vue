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
                @click.self="$emit('close')"
            >
                <div
                    class="flex w-full max-w-2xl max-h-[88dvh] flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary"
                >
                    <div
                        class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5 dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <p
                                class="text-xs font-semibold text-slate-400 dark:text-gray-500"
                            >
                                Charges
                            </p>

                            <h3
                                class="mt-0.5 truncate text-lg font-semibold tracking-tight"
                            >
                                {{ patientName || "Patient" }}
                            </h3>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <button
                                v-if="addable"
                                type="button"
                                :disabled="!canAdd"
                                :title="canAdd ? undefined : addBlockedReason"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                                @click="addOpen = true"
                            >
                                <Plus class="h-3.5 w-3.5" />
                                Add charge
                            </button>

                            <button
                                type="button"
                                class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10"
                                aria-label="Close"
                                @click="$emit('close')"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <ul
                        v-if="loading"
                        class="min-h-[400px] flex-1 divide-y divide-slate-100 overflow-hidden dark:divide-white/10"
                    >
                        <li
                            v-for="i in 5"
                            :key="i"
                            class="flex animate-pulse items-start justify-between gap-4 px-6 py-4"
                        >
                            <div class="min-w-0 flex-1 space-y-2">
                                <div
                                    class="h-4 w-20 rounded-full bg-slate-200 dark:bg-white/10"
                                />
                                <div
                                    class="h-3.5 w-48 rounded bg-slate-100 dark:bg-white/5"
                                />
                                <div
                                    class="h-2.5 w-36 rounded bg-slate-100 dark:bg-white/5"
                                />
                            </div>
                            <div
                                class="h-4 w-20 shrink-0 rounded bg-slate-200 dark:bg-white/10"
                            />
                        </li>
                    </ul>

                    <div
                        v-else-if="!charges.length"
                        class="flex min-h-[400px] flex-1 items-center justify-center px-6 text-sm text-slate-400 dark:text-gray-500"
                    >
                        No charges recorded for this patient.
                    </div>

                    <ul
                        v-else
                        class="min-h-[400px] flex-1 divide-y divide-slate-100 overflow-y-auto dark:divide-white/10"
                    >
                        <li
                            v-for="charge in charges"
                            :key="charge.additional_charge_id"
                            class="flex items-start justify-between gap-4 px-6 py-4"
                        >
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span
                                        class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                                        :class="chargeTypeClass(charge.type)"
                                    >
                                        {{ charge.type_label }}
                                    </span>

                                    <span
                                        v-if="
                                            charge.patient_admission_id ===
                                            currentAdmissionId
                                        "
                                        class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
                                    >
                                        Current stay
                                    </span>
                                </div>

                                <p
                                    class="mt-1.5 text-sm font-medium text-slate-800 break-words dark:text-white"
                                >
                                    {{ charge.description }}
                                </p>

                                <p
                                    v-if="charge.diagnosis"
                                    class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400"
                                >
                                    Diagnosis: {{ charge.diagnosis }}
                                    <template v-if="charge.diagnosis_case">
                                        · Case: {{ charge.diagnosis_case }}
                                    </template>
                                </p>

                                <p
                                    class="mt-0.5 text-[11px] text-slate-400 dark:text-gray-500"
                                >
                                    {{ formatDate(charge.created_at) }}
                                    <template v-if="charge.admitted_at">
                                        · Admission of
                                        {{ formatDate(charge.admitted_at) }}
                                    </template>
                                    <template v-if="charge.invoice_code">
                                        · {{ charge.invoice_code }}
                                    </template>
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                <p
                                    class="text-sm font-semibold text-slate-800 dark:text-white"
                                >
                                    {{ formatCurrency(charge.amount) }}
                                </p>

                                <button
                                    v-if="charge.invoice_code"
                                    type="button"
                                    class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-primary dark:text-gray-500 dark:hover:bg-white/10"
                                    title="Print charge slip"
                                    aria-label="Print charge slip"
                                    @click="openSlip(charge.invoice_code, charges)"
                                >
                                    <Printer class="h-4 w-4" />
                                </button>
                            </div>
                        </li>
                    </ul>

                    <div
                        v-if="meta.total > meta.per_page"
                        class="border-t border-slate-100 px-6 pt-3 dark:border-white/10"
                    >
                        <Pagination
                            :current-page="meta.current_page"
                            :total-pages="meta.last_page"
                            :total-items="meta.total"
                            :items-per-page="meta.per_page"
                            class="pb-3"
                            @change-page="fetchCharges"
                        />
                    </div>
                </div>
            </div>
        </Transition>

        <ChargeSlipModal :slip="slip" @close="slip = null" />

        <AdditionalChargeModal
            v-if="addable"
            :open="addOpen"
            :branch-uuid="branchUuid"
            :patient-uuid="patientUuid"
            :patient-name="patientName"
            @close="addOpen = false"
            @created="onCreated"
        />
    </Teleport>
</template>

<script setup lang="ts">
import { reactive, ref, watch } from "vue";
import { Plus, Printer } from "lucide-vue-next";
import Pagination from "~/components/ui/Pagination.vue";
import AdditionalChargeModal from "~/components/sections/app/Admission/AdditionalChargeModal.vue";
import ChargeSlipModal from "~/components/sections/app/Admission/ChargeSlipModal.vue";
import { useAuthUser } from "~/composables/useAuthUser";
import { useBranchStore } from "~/stores/branch";
import type { ChargeSlip } from "~/types/charge-slip";
import { additionalChargeService } from "~/api/additional-charge/AdditionalChargeService";
import { useToast } from "~/composables/useToast";
import { formatCurrency } from "~/utils/currency";
import type {
    AdditionalCharge,
    AdditionalChargeType,
} from "~/types/additional-charge";

const PER_PAGE = 10;

const props = withDefaults(
    defineProps<{
        open: boolean;
        branchUuid: string;
        patientUuid: string;
        patientName?: string | null;
        currentAdmissionId?: number | null;
        addable?: boolean;
        canAdd?: boolean;
        addBlockedReason?: string;
    }>(),
    {
        patientName: null,
        currentAdmissionId: null,
        addable: true,
        canAdd: false,
        addBlockedReason: "You can't add charges right now.",
    },
);

defineEmits<{ (e: "close"): void }>();

const { success, error } = useToast();

const charges = ref<AdditionalCharge[]>([]);
const loading = ref(false);
const addOpen = ref(false);
const slip = ref<ChargeSlip | null>(null);
const authUser = useAuthUser();
const branchStore = useBranchStore();

function openSlip(invoiceCode: string | null, source: AdditionalCharge[]) {
    const lines = source.filter((charge) => charge.invoice_code === invoiceCode);

    if (!lines.length) return;

    const user = authUser.value;

    slip.value = {
        branch_name: branchStore.activeBranch?.name ?? null,
        patient_name: props.patientName ?? null,
        prepared_by: user
            ? [user.first_name, user.last_name].filter(Boolean).join(" ")
            : null,
        invoice_code: invoiceCode,
        total:
            lines[0]?.invoice_total ??
            lines.reduce((sum, charge) => sum + charge.amount, 0),
        charges: [...lines].reverse().map((charge) => ({
            id: charge.additional_charge_id,
            type_label: charge.type_label,
            description: charge.description,
            amount: charge.amount,
            diagnosis: charge.diagnosis,
            diagnosis_case: charge.diagnosis_case,
        })),
    };
}
const meta = reactive({
    current_page: 1,
    last_page: 1,
    per_page: PER_PAGE,
    total: 0,
});

async function fetchCharges(page = 1) {
    loading.value = true;

    try {
        const res = await additionalChargeService.list({
            branch_uuid: props.branchUuid,
            patient_uuid: props.patientUuid,
            page,
            per_page: PER_PAGE,
        });

        charges.value = res.data ?? [];
        Object.assign(meta, res.meta ?? {});
    } catch (err: any) {
        charges.value = [];
        error(err?.message ?? "Couldn't load charges.");
    } finally {
        loading.value = false;
    }
}

function onCreated(created: AdditionalCharge[]) {
    addOpen.value = false;

    if (!created.length) return;

    const code = created[0]?.invoice_code ?? "a new invoice";
    success(
        created.length === 1
            ? `Charge added to ${code}.`
            : `${created.length} charges added to ${code}.`,
    );

    openSlip(created[0]?.invoice_code ?? null, created);

    meta.total += created.length;
    meta.last_page = Math.max(1, Math.ceil(meta.total / meta.per_page));

    if (meta.current_page === 1) {
        charges.value = [...[...created].reverse(), ...charges.value].slice(
            0,
            meta.per_page,
        );
    }
}

function chargeTypeClass(type: AdditionalChargeType) {
    switch (type) {
        case "medication":
            return "bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300";
        case "supplies":
            return "bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300";
        case "diagnosis_case":
            return "bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300";
        default:
            return "bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300";
    }
}

function formatDate(value?: string | null) {
    if (!value) return "—";

    return new Date(value).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

watch(
    () => props.open,
    (open) => {
        if (open) fetchCharges(1);
    },
);
</script>
