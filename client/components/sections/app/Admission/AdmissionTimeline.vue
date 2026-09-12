<template>
    <div
        :class="
            flat
                ? ''
                : 'rounded-2xl bg-white border border-primary-100 shadow-[0_0_40px_rgba(10,40,87,0.06)] overflow-hidden dark:bg-secondary dark:border-primary-500/20'
        "
    >
        <div
            :class="
                flat
                    ? 'mb-4'
                    : 'border-b border-primary-100 px-5 py-4 dark:border-primary-500/20'
            "
        >
            <h3
                class="text-sm font-semibold text-primary-900 dark:text-primary-300"
            >
                Admission Timeline
            </h3>

            <p class="text-xs text-muted mt-1 dark:text-gray-400">
                Billing periods for this admission
            </p>
        </div>

        <div :class="flat ? '' : 'p-5'">
            <div
                v-if="!allAdmissions.length"
                class="py-8 text-center text-sm text-slate-400 dark:text-gray-500"
            >
                No admission records.
            </div>

            <div v-else>
                <div class="relative">
                    <span
                        v-if="visibleAdmissions.length"
                        class="absolute left-[6px] top-2.5 bottom-2.5 w-0.5 -translate-x-1/2 bg-primary-100 dark:bg-primary-500/15"
                    />

                    <div
                        v-for="(admission, idx) in visibleAdmissions"
                        :key="admission.patient_admission_id"
                        class="relative"
                        :class="
                            idx !== visibleAdmissions.length - 1 ? 'pb-6' : ''
                        "
                    >
                        <div class="flex items-start gap-3 mb-3">
                            <span
                                class="relative z-10 mt-1.5 h-3 w-3 shrink-0 rounded-full border-2 border-white"
                                :class="
                                    isCurrent(admission)
                                        ? 'bg-primary ring-4 ring-primary-100 dark:ring-primary-500/20'
                                        : 'bg-slate-300 dark:bg-white/20'
                                "
                            />

                            <div class="flex-1 min-w-0">
                                <div
                                    class="flex items-start justify-between gap-2"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="text-sm font-semibold text-primary-900 dark:text-primary-300"
                                        >
                                            Admission
                                        </p>

                                        <p
                                            class="text-[11px] text-muted mt-0.5 dark:text-gray-400"
                                        >
                                            {{
                                                formatDate(
                                                    admission.admitted_at,
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <span
                                        class="shrink-0 text-[12px] font-medium capitalize rounded-full px-2 py-1"
                                        :class="
                                            statusBadgeClass(admission.status)
                                        "
                                    >
                                        {{ admission.status }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="admission.invoices?.length"
                            class="ml-6 overflow-hidden rounded-xl"
                            :class="
                                flat
                                    ? 'border border-slate-200/70 dark:border-white/10'
                                    : 'border border-slate-200/80 bg-white shadow-sm dark:border-white/10 dark:bg-secondary'
                            "
                        >
                            <div
                                v-for="(invoice, i) in sortedInvoices(
                                    admission.invoices,
                                ).filter(isInvoiceVisible)"
                                :key="invoice.invoice_admission_id"
                                class="px-4 py-3.5 transition-colors"
                                :class="[
                                    i !== 0
                                        ? 'border-t border-slate-100 dark:border-white/10'
                                        : '',
                                    isInEffect(invoice, admission)
                                        ? 'bg-emerald-50/40 dark:bg-emerald-500/5'
                                        : 'hover:bg-slate-50/60 dark:hover:bg-white/5',
                                    isCancelled(invoice) ? 'opacity-60' : '',
                                ]"
                            >
                                <div
                                    class="flex flex-wrap items-center gap-x-2 gap-y-1.5"
                                >
                                    <span
                                        v-if="invoice.period_code"
                                        class="rounded-md bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-slate-600 dark:bg-white/10 dark:text-gray-300"
                                    >
                                        {{ invoice.period_code }}
                                    </span>

                                    <span
                                        class="text-[12px] font-semibold capitalize text-slate-800 dark:text-white"
                                    >
                                        {{
                                            billingCycleLabel(
                                                invoice,
                                                admission,
                                            )
                                        }}
                                    </span>

                                    <span
                                        v-if="invoice.accommodation_reason"
                                        class="rounded-full px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide"
                                        :class="
                                            reasonClass(
                                                invoice.accommodation_reason,
                                            )
                                        "
                                    >
                                        {{
                                            reasonLabel(
                                                invoice.accommodation_reason,
                                            )
                                        }}
                                    </span>

                                    <span
                                        class="ml-auto inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide"
                                        :class="
                                            coverageClass(invoice, admission)
                                        "
                                    >
                                        <span
                                            v-if="
                                                isInEffect(invoice, admission)
                                            "
                                            class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                                        ></span>
                                        {{ coverageLabel(invoice, admission) }}
                                    </span>
                                </div>

                                <div
                                    class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-slate-500 dark:text-gray-400"
                                >
                                    <span
                                        class="font-medium text-slate-700 dark:text-gray-300"
                                    >
                                        {{
                                            invoice.contract
                                                ?.accommodation_type ??
                                            admission.current_contract
                                                ?.accommodation_type ??
                                            "—"
                                        }}
                                    </span>

                                    <template v-if="invoice.period_start">
                                        <span
                                            class="text-slate-300 dark:text-gray-600"
                                        >
                                            ·
                                        </span>

                                        <span>
                                            {{ boundary(invoice.period_start) }}
                                        </span>

                                        <svg
                                            class="h-3 w-3 shrink-0 text-slate-300 dark:text-gray-600"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <path d="M5 12h14" />
                                            <path d="m12 5 7 7-7 7" />
                                        </svg>

                                        <span>
                                            {{ boundary(invoice.period_end) }}
                                        </span>

                                        <span
                                            v-if="
                                                periodLength(invoice) &&
                                                !isCancelled(invoice)
                                            "
                                            class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500 dark:bg-white/10 dark:text-gray-400"
                                        >
                                            {{ periodLength(invoice) }}
                                        </span>
                                    </template>

                                    <!-- <span
                                        v-if="movedLabel(invoice)"
                                        class="text-slate-400 dark:text-gray-500"
                                    >
                                        · moved {{ movedLabel(invoice) }}
                                    </span> -->
                                </div>

                                <div
                                    class="mt-1.5 flex flex-wrap items-baseline gap-x-2 gap-y-1 text-[11px]"
                                >
                                    <span
                                        class="text-[12px] font-semibold"
                                        :class="
                                            isCancelled(invoice)
                                                ? 'text-slate-400 line-through dark:text-gray-500'
                                                : 'text-slate-800 dark:text-white'
                                        "
                                    >
                                        {{ formatCurrency(invoice.price) }}
                                    </span>

                                    <span
                                        v-if="isCancelled(invoice)"
                                        class="text-rose-500 dark:text-rose-300"
                                    >
                                        not charged — invoice voided
                                    </span>

                                    <span
                                        v-else-if="priceNote(invoice)"
                                        class="text-slate-400 dark:text-gray-500"
                                    >
                                        {{ priceNote(invoice) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="ml-6 rounded-xl bg-slate-50 border border-slate-100 px-4 py-4 text-center text-xs text-slate-400 dark:bg-white/5 dark:border-white/10 dark:text-gray-500"
                        >
                            No invoices for this admission.
                        </div>
                    </div>
                </div>

                <div
                    v-if="canToggle"
                    class="mt-5 pt-4 border-t border-primary-100 flex justify-center dark:border-primary-500/20"
                >
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-primary-100 bg-white px-4 py-2 text-xs font-medium text-primary hover:bg-primary-50 transition dark:border-primary-500/20 dark:bg-secondary dark:hover:bg-primary-500/10"
                        @click="toggleExpanded"
                    >
                        {{ expanded ? "Show less" : "Show more" }}

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            class="w-3.5 h-3.5 transition-transform"
                            :class="expanded ? 'rotate-180' : ''"
                        >
                            <path d="m6 9 6 6 6-6" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { formatCurrency } from "~/utils/currency";
import { formatDate, stringToDateTime } from "~/utils/time";
import type { Admission, InvoiceAccommodation } from "~/types/patient";

const props = withDefaults(
    defineProps<{
        admissions?: Admission[] | null;
        // Drops the card chrome so the timeline can sit inside a parent card
        // without stacking a second border around it.
        flat?: boolean;
    }>(),
    { admissions: null, flat: false },
);

const allAdmissions = computed(() => props.admissions ?? []);

const sortedAdmissions = computed(() =>
    [...allAdmissions.value].sort(
        (a, b) =>
            new Date(b.admitted_at).getTime() -
            new Date(a.admitted_at).getTime(),
    ),
);

const DEFAULT_VISIBLE_COUNT = 5;

const expanded = ref(false);

const visibleAdmissions = computed(() => sortedAdmissions.value);

const orderedInvoiceIds = computed(() => {
    const ids: number[] = [];

    for (const admission of sortedAdmissions.value) {
        for (const invoice of sortedInvoices(admission.invoices ?? [])) {
            ids.push(invoice.invoice_admission_id);
        }
    }

    return ids;
});

const visibleInvoiceIdSet = computed(() => {
    if (expanded.value) {
        return null;
    }

    return new Set(orderedInvoiceIds.value.slice(0, DEFAULT_VISIBLE_COUNT));
});

function isInvoiceVisible(invoice: InvoiceAccommodation) {
    return (
        visibleInvoiceIdSet.value === null ||
        visibleInvoiceIdSet.value.has(invoice.invoice_admission_id)
    );
}

const canToggle = computed(
    () => orderedInvoiceIds.value.length > DEFAULT_VISIBLE_COUNT,
);

function toggleExpanded() {
    expanded.value = !expanded.value;
}

watch(
    () => props.admissions,
    () => {
        expanded.value = false;
    },
);

const currentAdmissionId = computed(() => {
    const active = sortedAdmissions.value.find(
        (a) => a.status === "admitted" || a.status === "waiting",
    );

    return active?.patient_admission_id ?? null;
});

function isCurrent(admission: Admission) {
    return admission.patient_admission_id === currentAdmissionId.value;
}

function sortedInvoices(invoices: InvoiceAccommodation[]) {
    const byId = [...invoices].sort(
        (a, b) => (a.admission_period_id ?? 0) - (b.admission_period_id ?? 0),
    );

    const ids = new Set(
        byId
            .map((invoice) => invoice.admission_period_id)
            .filter((id): id is number => !!id),
    );

    const children = new Map<number, InvoiceAccommodation[]>();

    for (const invoice of byId) {
        const parent = invoice.parent_admission_period_id;

        if (!parent || !ids.has(parent)) continue;

        children.set(parent, [...(children.get(parent) ?? []), invoice]);
    }

    const ordered: InvoiceAccommodation[] = [];
    const seen = new Set<InvoiceAccommodation>();

    function walk(invoice: InvoiceAccommodation) {
        if (seen.has(invoice)) return;

        seen.add(invoice);
        ordered.push(invoice);

        for (const child of children.get(invoice.admission_period_id ?? -1) ??
            []) {
            walk(child);
        }
    }

    for (const invoice of byId) {
        const parent = invoice.parent_admission_period_id;

        if (parent && ids.has(parent)) continue;

        walk(invoice);
    }

    byId.forEach(walk);

    return ordered;
}

function isInEffect(invoice: InvoiceAccommodation, admission: Admission) {
    const currentId = admission.current_period?.admission_period_id;

    return (
        !!currentId &&
        invoice.admission_period_id === currentId &&
        admission.status !== "discharged"
    );
}

function isCancelled(invoice: InvoiceAccommodation) {
    return invoice.accommodation_status === "cancelled";
}

function coverageLabel(invoice: InvoiceAccommodation, admission: Admission) {
    if (isCancelled(invoice)) return "Cancelled";

    if (isInEffect(invoice, admission)) return "In effect";

    if (invoice.accommodation_status === "inactive") return "Ended";

    if (invoice.accommodation_status === "pending") return "Awaiting payment";

    return "Upcoming";
}

function coverageClass(invoice: InvoiceAccommodation, admission: Admission) {
    if (isCancelled(invoice)) {
        return "bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300";
    }

    if (isInEffect(invoice, admission)) {
        return "bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300";
    }

    if (invoice.accommodation_status === "inactive") {
        return "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-400";
    }

    if (invoice.accommodation_status === "pending") {
        return "bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300";
    }

    return "bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300";
}

function boundary(value?: string | null) {
    if (!value) return "";

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return "";

    const midnight =
        date.getHours() === 0 &&
        date.getMinutes() === 0 &&
        date.getSeconds() === 0;

    return midnight ? formatDate(value) : stringToDateTime(value);
}

function movedLabel(invoice: InvoiceAccommodation) {
    if (!invoice.moved_at || !invoice.period_start) return "";

    const moved = new Date(invoice.moved_at);
    const start = new Date(invoice.period_start);

    if (Number.isNaN(moved.getTime()) || Number.isNaN(start.getTime())) {
        return "";
    }

    if (moved.toDateString() === start.toDateString()) return "";

    return stringToDateTime(invoice.moved_at);
}

function periodLength(invoice: InvoiceAccommodation) {
    if (!invoice.period_start || !invoice.period_end) return "";

    const start = new Date(invoice.period_start);
    const end = new Date(invoice.period_end);

    if (
        Number.isNaN(start.getTime()) ||
        Number.isNaN(end.getTime()) ||
        end <= start
    ) {
        return "";
    }

    const hours = (end.getTime() - start.getTime()) / 3_600_000;

    if (hours < 24) {
        const rounded = Math.max(1, Math.round(hours));
        return `${rounded} hr${rounded === 1 ? "" : "s"}`;
    }

    // Billing runs on calendar months, which are 28 to 31 days long. Measuring
    // in days made a 30-day month read as "30 days" while a 31-day one read as
    // "1 month", so whole months are counted on the calendar instead.
    const months =
        (end.getFullYear() - start.getFullYear()) * 12 +
        (end.getMonth() - start.getMonth());

    if (months > 0) {
        const anniversary = new Date(start);
        anniversary.setMonth(anniversary.getMonth() + months);

        if (anniversary.getTime() === end.getTime()) {
            if (months % 12 === 0) {
                const years = months / 12;
                return `${years} year${years === 1 ? "" : "s"}`;
            }

            return `${months} month${months === 1 ? "" : "s"}`;
        }
    }

    const days = Math.round(hours / 24);

    return `${days} day${days === 1 ? "" : "s"}`;
}

// A period charged less than its plan was cut short or started late, so the
// full price is worth showing next to what it actually came to.
function priceNote(invoice: InvoiceAccommodation) {
    const charged = Number(invoice.price ?? 0);
    const plan = Number(invoice.contract?.price ?? 0);

    if (!plan || !charged || Math.abs(plan - charged) < 0.01) {
        return "";
    }

    const length = periodLength(invoice);
    const basis = `of ${formatCurrency(plan)}`;

    return length ? `${basis} · ${length} charged` : basis;
}

function reasonLabel(reason: string) {
    return (
        {
            admitted: "Admitted",
            extended: "Extension",
            room_change: "Room change",
            accommodation_change: "Accommodation change",
        }[reason] ?? reason.replace(/_/g, " ")
    );
}

function reasonClass(reason: string) {
    return (
        {
            admitted:
                "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-400",
            extended:
                "bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300",
            room_change:
                "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-400",
            accommodation_change:
                "bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300",
        }[reason] ??
        "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-400"
    );
}

function billingCycleLabel(
    invoice: InvoiceAccommodation,
    admission: Admission,
) {
    const cycle =
        invoice.contract?.billing_cycle ??
        admission.current_contract?.billing_cycle;

    return cycle ? cycle.toLowerCase() : "—";
}

function statusBadgeClass(status?: string) {
    switch (status?.toLowerCase()) {
        case "admitted":
            return "bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300";

        case "waiting":
            return "bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300";

        case "discharged":
        case "completed":
            return "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-400";

        case "cancelled":
        case "rejected":
            return "bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300";

        default:
            return "bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300";
    }
}
</script>
