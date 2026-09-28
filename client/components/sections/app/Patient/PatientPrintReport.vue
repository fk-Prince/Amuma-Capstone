<template>
    <div class="patient-print-report">
        <section
            v-for="section in orderedSections"
            :key="section"
            class="print-page"
        >
            <header class="print-header">
                <div class="print-header-left">
                    <p class="print-brand">
                        {{ report.branch?.name ?? "Amuma Care" }}
                    </p>

                    <p v-if="report.branch?.agency_name" class="print-branch">
                        {{ report.branch.agency_name }}
                    </p>

                    <p v-if="report.branch?.address" class="print-branch-line">
                        {{ report.branch.address }}
                    </p>

                    <p v-if="branchContactLine" class="print-branch-line">
                        {{ branchContactLine }}
                    </p>

                    <p v-if="report.branch?.tin" class="print-branch-line">
                        TIN {{ report.branch.tin }}
                    </p>
                </div>

                <div class="print-header-right">
                    <p class="print-doc-type">Medical Record</p>
                    <p class="print-doc-ref">
                        Ref. {{ shortRef }} · Page {{ pageNumber(section) }} of
                        {{ orderedSections.length }}
                    </p>
                </div>
            </header>

            <h1 class="print-title">{{ sectionLabel(section) }}</h1>

            <table class="print-identity">
                <tbody>
                    <tr>
                        <th>Patient</th>
                        <td class="print-identity-name">
                            {{ report.patient.full_name }}
                        </td>
                        <th>Date of Birth</th>
                        <td>{{ birthLine }}</td>
                    </tr>
                    <tr>
                        <th>Gender</th>
                        <td>{{ report.patient.gender ?? "—" }}</td>
                        <th>Contact</th>
                        <td>
                            {{
                                formatPhone(report.patient.phone_number) || "—"
                            }}
                        </td>
                    </tr>
                    <tr>
                        <th>Address</th>
                        <td colspan="3">{{ report.patient.address ?? "—" }}</td>
                    </tr>
                </tbody>
            </table>

            <template v-if="section === 'profile'">
                <table class="print-table">
                    <tbody>
                        <tr>
                            <th>Blood Type</th>
                            <td>{{ report.patient.blood_type ?? "—" }}</td>
                            <th>Citizenship</th>
                            <td>{{ report.patient.citizenship ?? "—" }}</td>
                        </tr>
                        <tr>
                            <th>Height</th>
                            <td>{{ measure(report.patient.height, "cm") }}</td>
                            <th>Weight</th>
                            <td>{{ measure(report.patient.weight, "kg") }}</td>
                        </tr>
                        <tr>
                            <th>Allergies</th>
                            <td colspan="3">
                                {{ allergyList || "None recorded" }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h2 class="print-subhead">Diagnoses</h2>

                <p v-if="!diagnoses.length" class="print-empty">
                    No diagnosis recorded.
                </p>

                <table v-else class="print-table">
                    <thead>
                        <tr>
                            <th>Diagnosis</th>
                            <th class="print-col-date">Date</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in diagnoses" :key="i">
                            <td class="print-strong">
                                {{ row.diagnosis ?? "—" }}
                            </td>
                            <td>{{ formatDate(row.diagnosis_date) }}</td>
                            <td>{{ row.diagnosis_notes || "—" }}</td>
                        </tr>
                    </tbody>
                </table>

                <h2 class="print-subhead">Assessment</h2>

                <p v-if="!assessments.length" class="print-empty">
                    No assessment recorded.
                </p>

                <template v-else>
                    <div
                        v-for="(assessment, index) in assessments"
                        :key="index"
                        class="print-assessment"
                    >
                        <p
                            v-if="assessments.length > 1"
                            class="print-caption"
                        >
                            Assessment {{ index + 1 }}
                        </p>

                        <table class="print-table">
                            <tbody>
                                <tr
                                    v-for="pair in assessment.fields"
                                    :key="pair[0].label"
                                >
                                    <template v-for="field in pair" :key="field.label">
                                        <th>{{ field.label }}</th>
                                        <td>{{ field.value }}</td>
                                    </template>
                                </tr>
                            </tbody>
                        </table>

                        <table
                            v-if="assessment.lifeSystem.length"
                            class="print-table print-life-system"
                        >
                            <thead>
                                <tr>
                                    <th>Daily Activity</th>
                                    <th class="print-col-score">Score</th>
                                    <th>Level of Independence</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in assessment.lifeSystem"
                                    :key="row.activity"
                                >
                                    <td>{{ row.activity }}</td>
                                    <td class="print-col-score">
                                        {{ row.score }} / 5
                                    </td>
                                    <td>{{ row.meaning }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>

                <template v-if="guardians.length">
                    <h2 class="print-subhead">Family &amp; Guardians</h2>

                    <table class="print-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Relationship</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Portal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, i) in guardians" :key="i">
                                <td class="print-strong">
                                    {{ row.full_name || "—" }}
                                </td>
                                <td>{{ row.relationship || "—" }}</td>
                                <td>
                                    {{ formatPhone(row.phone_number) || "—" }}
                                </td>
                                <td>{{ row.email || "—" }}</td>
                                <td>
                                    {{
                                        row.has_portal_access
                                            ? "Has access"
                                            : "No access"
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </template>
            </template>

            <template v-else-if="section === 'admission'">
                <p v-if="!report.admission?.length" class="print-empty">
                    No admission records.
                </p>

                <template v-else>
                    <table class="print-table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Admitted</th>
                                <th>Covered Until</th>
                                <th>Accommodation</th>
                                <th>Room / Bed</th>
                                <th class="right">Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template
                                v-for="(row, i) in report.admission"
                                :key="i"
                            >
                                <tr>
                                    <td>{{ statusLabel(row.status) }}</td>
                                    <td>{{ formatDate(row.admitted_at) }}</td>
                                    <td>{{ formatDate(row.end_date) }}</td>
                                    <td>{{ accommodationLabel(row) }}</td>
                                    <td>{{ roomLabel(row) }}</td>
                                    <td class="right">
                                        {{ row.rate != null ? money(row.rate) : "—" }}
                                    </td>
                                </tr>
                                <tr v-if="row.note" class="print-note-row">
                                    <td colspan="6">
                                        <span class="print-subtle">Note:</span>
                                        {{ row.note }}
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </template>
            </template>

            <template v-else-if="section === 'billing'">
                <div class="print-summary">
                    <div>
                        <p class="print-summary-label">Total Paid</p>
                        <p class="print-summary-value">
                            {{ money(report.billing?.summary?.total_paid) }}
                        </p>
                    </div>
                    <div v-if="Number(report.billing?.summary?.refundable) > 0">
                        <p class="print-summary-label">Credit</p>
                        <p class="print-summary-value">
                            {{ money(report.billing?.summary?.refundable) }}
                        </p>
                    </div>
                    <div>
                        <p class="print-summary-label">Balance</p>
                        <p class="print-summary-value">
                            {{ money(report.billing?.summary?.balance_due) }}
                        </p>
                    </div>
                </div>

                <p v-if="!report.billing?.invoices?.length" class="print-empty">
                    No invoices on record.
                </p>

                <table v-else class="print-table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="right">Total</th>
                            <th class="right">Paid</th>
                            <th v-if="hasRefunds" class="right">Refunded</th>
                            <th class="right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(row, i) in report.billing.invoices"
                            :key="i"
                        >
                            <td class="print-strong">{{ row.invoice_code }}</td>
                            <td>{{ formatDate(row.created_at) }}</td>
                            <td>{{ statusLabel(row.status) }}</td>
                            <td class="right">{{ money(row.total) }}</td>
                            <td class="right">{{ money(row.amount_paid) }}</td>
                            <td v-if="hasRefunds" class="right">
                                {{ money(row.refunded_amount) }}
                            </td>
                            <td class="right">{{ money(row.balance_due) }}</td>
                        </tr>
                    </tbody>
                </table>

                <template v-if="billingPayments.length">
                    <h2 class="print-subtitle">Payments Received</h2>

                    <table class="print-table">
                        <thead>
                            <tr>
                                <th>Receipt No.</th>
                                <th>Invoice</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th class="right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, i) in billingPayments" :key="i">
                                <td>{{ row.payment_code ?? "—" }}</td>
                                <td>{{ row.invoice_code }}</td>
                                <td>{{ formatDateTime(row.paid_at) }}</td>
                                <td>{{ statusLabel(row.payment_method) }}</td>
                                <td>{{ row.reference_id ?? "—" }}</td>
                                <td class="right">{{ money(row.amount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </template>
            </template>

            <template v-else-if="section === 'schedule'">
                <p v-if="!report.schedule?.length" class="print-empty">
                    No schedules on record.
                </p>

                <table v-else class="print-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Scheduled</th>
                            <th>Status</th>
                            <th>Services</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in report.schedule" :key="i">
                            <td class="print-strong">
                                {{ row.schedule_code ?? "—" }}
                            </td>
                            <td>{{ formatDateTime(row.scheduled_at) }}</td>
                            <td>{{ statusLabel(row.status) }}</td>
                            <td>
                                <span v-if="row.category" class="print-subtle">
                                    {{ row.category }} ·
                                </span>
                                {{ serviceSummary(row.services) }}
                            </td>
                            <td>{{ row.address ?? "—" }}</td>
                        </tr>
                    </tbody>
                </table>
            </template>

            <template v-else-if="section === 'medication'">
                <p v-if="!report.medication?.length" class="print-empty">
                    No medications on record.
                </p>

                <table v-else class="print-table">
                    <thead>
                        <tr>
                            <th>Medication</th>
                            <th>Dosage</th>
                            <th>Route</th>
                            <th>Schedule</th>
                            <th>Start</th>
                            <th>Instructions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in report.medication" :key="i">
                            <td>
                                <span class="print-strong">{{ row.name }}</span>
                                <template v-if="row.strength">
                                    {{ row.strength }}</template
                                >
                                <span
                                    v-if="row.taken_for"
                                    class="print-subtle print-block"
                                >
                                    For {{ row.taken_for }}
                                </span>
                            </td>
                            <td>{{ dosage(row) }}</td>
                            <td>{{ ROUTE_LABELS[row.route] ?? row.route ?? "—" }}</td>
                            <td>{{ medicationSchedule(row) }}</td>
                            <td>
                                {{ formatDate(row.start_date) }}
                                <span
                                    v-if="row.duration"
                                    class="print-subtle print-block"
                                >
                                    {{ row.duration }} days
                                </span>
                            </td>
                            <td>{{ row.instructions || "—" }}</td>
                        </tr>
                    </tbody>
                </table>
            </template>

            <template v-else-if="section === 'vitals'">
                <p v-if="!report.vitals?.length" class="print-empty">
                    No vital signs on record.
                </p>

                <table v-else class="print-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>BP (mmHg)</th>
                            <th>HR (bpm)</th>
                            <th>RR (/min)</th>
                            <th>Temp (°C)</th>
                            <th>SpO₂ (%)</th>
                            <th>Glucose (mg/dL)</th>
                            <th>Pain (/10)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in report.vitals" :key="i">
                            <td>{{ formatDate(row.recorded_date) }}</td>
                            <td>{{ formatTime(row.recorded_time) || "—" }}</td>
                            <td>{{ row.blood_pressure ?? "—" }}</td>
                            <td>{{ row.heart_rate ?? "—" }}</td>
                            <td>{{ row.respiratory_rate ?? "—" }}</td>
                            <td>{{ row.temperature ?? "—" }}</td>
                            <td>{{ row.oxygen_saturation ?? "—" }}</td>
                            <td>{{ row.blood_glucose ?? "—" }}</td>
                            <td>{{ row.pain_level ?? "—" }}</td>
                        </tr>
                    </tbody>
                </table>
            </template>

            <template v-else-if="section === 'activity'">
                <p v-if="!report.activity?.length" class="print-empty">
                    No activities on record.
                </p>

                <table v-else class="print-table">
                    <thead>
                        <tr>
                            <th>Occurred</th>
                            <th>Type</th>
                            <th>Title</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in report.activity" :key="i">
                            <td>{{ formatDateTime(row.occurred_at) }}</td>
                            <td>{{ statusLabel(row.type) }}</td>
                            <td>
                                {{ row.title ?? "—" }}
                                <span v-if="row.subtitle" class="print-subtle">
                                    — {{ row.subtitle }}
                                </span>
                            </td>
                            <td>{{ row.description ?? "—" }}</td>
                        </tr>
                    </tbody>
                </table>
            </template>

            <div class="print-page-bottom">
                <div v-if="isLastSection(section)" class="print-signature">
                    <div class="print-sign-block">
                        <span class="print-sign-line" />
                        <p class="print-sign-label">Prepared by</p>
                    </div>
                    <div class="print-sign-block">
                        <span class="print-sign-line" />
                        <p class="print-sign-label">Reviewed by</p>
                    </div>
                    <div class="print-sign-block">
                        <span class="print-sign-line" />
                        <p class="print-sign-label">Date</p>
                    </div>
                </div>

                <footer class="print-footer">
                    <span>
                        {{ report.patient.full_name }} ·
                        {{ sectionLabel(section) }}
                    </span>
                    <span>
                        Generated {{ formatDateTime(report.generated_at) }} ·
                        Confidential
                    </span>
                </footer>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { formatAmount } from "~/utils/currency";
import { formatPhone } from "~/utils/phone";
import { formatDate, formatTime } from "~/utils/time";
import { ROUTE_LABELS, DOSAGE_UNIT_LABELS } from "~/utils/medication";
import {
    LIFE_SYSTEM_ACTIVITIES,
    LIFE_SYSTEM_SCALE,
    activityLabel,
    assessmentLabel,
} from "~/utils/assessment";

const props = defineProps<{ report: any }>();

const SECTION_ORDER = [
    "profile",
    "admission",
    "billing",
    "schedule",
    "medication",
    "vitals",
    "activity",
];

const SECTION_LABELS: Record<string, string> = {
    profile: "Patient Profile",
    admission: "Admission Records",
    billing: "Billing Statement",
    schedule: "Service Schedules",
    medication: "Medication Records",
    vitals: "Vital Signs",
    activity: "Activity Log",
};

const orderedSections = computed(() => {
    const active: string[] = props.report?.sections ?? [];
    return SECTION_ORDER.filter((s) => active.includes(s));
});

const allergyList = computed(() => {
    const allergies = props.report?.patient?.allergies;
    if (!allergies) return "";
    return Array.isArray(allergies) ? allergies.join(", ") : String(allergies);
});

const ASSESSMENT_FIELDS: [string, string][] = [
    ["condition", "Mobility"],
    ["mental_state", "Level of Consciousness"],
    ["affect", "Affect"],
    ["behavior", "Behavior"],
    ["communication", "Communication"],
    ["speech", "Speech"],
];

const FREQUENCY_LABELS: Record<string, string> = {
    everyday: "Everyday",
    every_2_days: "Every 2 days",
    every_3_days: "Every 3 days",
    every_week: "Every week",
};

const assessments = computed(() => {
    const raw = props.report?.profile?.assessment;
    if (!raw) return [];

    const entries: any[] = Array.isArray(raw) ? raw : [raw];

    return entries
        .filter((entry) => entry && typeof entry === "object")
        .map((entry) => {
            const fields = ASSESSMENT_FIELDS.filter(([key]) => entry[key])
                .map(([key, label]) => ({
                    label,
                    value: assessmentLabel(entry[key]),
                }));

            const pairs = [];
            for (let i = 0; i < fields.length; i += 2) {
                pairs.push(fields.slice(i, i + 2));
            }

            const profile = entry.life_system_profile ?? {};
            const lifeSystem = LIFE_SYSTEM_ACTIVITIES.filter(
                (activity) => profile[activity] !== undefined && profile[activity] !== null,
            ).map((activity) => ({
                activity: activityLabel(activity),
                score: profile[activity],
                meaning:
                    LIFE_SYSTEM_SCALE.find((step) => step.value === Number(profile[activity]))
                        ?.label ?? "—",
            }));

            return { fields: pairs, lifeSystem };
        })
        .filter((entry) => entry.fields.length || entry.lifeSystem.length);
});

const diagnoses = computed<any[]>(() => props.report?.profile?.diagnoses ?? []);

const guardians = computed<any[]>(() => props.report?.profile?.guardians ?? []);

const birthLine = computed(() => {
    const dob = props.report?.patient?.date_of_birth;
    if (!dob) return "—";

    const birth = new Date(dob);
    if (Number.isNaN(birth.getTime())) return dob;

    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const beforeBirthday =
        today.getMonth() < birth.getMonth() ||
        (today.getMonth() === birth.getMonth() && today.getDate() < birth.getDate());
    if (beforeBirthday) age -= 1;

    return `${formatDate(dob)} (${age} yrs)`;
});

const hasRefunds = computed(() =>
    (props.report?.billing?.invoices ?? []).some(
        (invoice: any) => Number(invoice.refunded_amount) > 0,
    ),
);

function measure(value: unknown, unit: string) {
    const amount = Number(value);
    if (value === null || value === undefined || value === "" || !Number.isFinite(amount)) {
        return "—";
    }
    return `${Number(amount.toFixed(1))} ${unit}`;
}

function statusLabel(value?: string | null) {
    if (!value) return "—";
    return value
        .replace(/[_-]+/g, " ")
        .toLowerCase()
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function accommodationLabel(row: any) {
    const plan = row.accommodation ?? row.room_type;
    if (!plan) return "—";
    const name = plan.toUpperCase() === "VIP" ? "VIP" : statusLabel(plan);
    return row.billing_cycle ? `${name} · ${statusLabel(row.billing_cycle)}` : name;
}

function roomLabel(row: any) {
    const parts = [
        row.room ? `Room ${row.room}` : null,
        row.bed ? `Bed ${row.bed}` : null,
        row.floor ? `${row.floor} floor` : null,
    ].filter(Boolean);
    return parts.length ? parts.join(" · ") : "—";
}

function medicationSchedule(row: any) {
    if (row.kind === "PRN") return "As needed (PRN)";
    const frequency = FREQUENCY_LABELS[row.frequency] ?? statusLabel(row.frequency);
    const times = (row.times ?? []).map((time: string) => formatTime(time)).filter(Boolean);
    return times.length ? `${frequency} · ${times.join(", ")}` : frequency;
}

const shortRef = computed(() => {
    const code = props.report?.patient?.patient_code;

    if (code) return code;

    const uuid = props.report?.patient?.patient_uuid ?? "";

    return uuid ? uuid.split("-")[0]!.toUpperCase() : "—";
});

function sectionLabel(key: string) {
    return SECTION_LABELS[key] ?? key;
}

function pageNumber(key: string) {
    return orderedSections.value.indexOf(key) + 1;
}

function isLastSection(key: string) {
    return orderedSections.value[orderedSections.value.length - 1] === key;
}

const branchContactLine = computed(() =>
    [props.report?.branch?.contact_number, props.report?.branch?.email]
        .filter(Boolean)
        .join(" · "),
);

const billingPayments = computed(() => {
    const invoices = props.report?.billing?.invoices ?? [];

    return invoices.flatMap((invoice: any) =>
        (invoice.payments ?? []).map((payment: any) => ({
            ...payment,
            invoice_code: invoice.invoice_code,
        })),
    );
});

function money(value: unknown) {
    const amount = Number(value ?? 0);
    return `PHP ${formatAmount(Number.isFinite(amount) ? amount : 0)}`;
}

function dosage(row: any) {
    const amount = Number(row.dosage_amount);
    if (!row.dosage_amount || !Number.isFinite(amount)) return "—";
    const unit = DOSAGE_UNIT_LABELS[row.dosage_unit] ?? row.dosage_unit ?? "";
    return `${Number(amount.toFixed(2))} ${unit}`.trim();
}

function serviceSummary(services: any[]) {
    if (!services?.length) return "—";
    return services
        .map((service) => {
            const hours = service.hours_booked
                ? ` (${Number(service.hours_booked)} hrs)`
                : "";
            return `${service.service_name ?? "Service"}${hours}`;
        })
        .join(", ");
}

function formatDateTime(value?: string) {
    if (!value) return "—";
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime())
        ? "—"
        : parsed.toLocaleString("en-US", {
              month: "short",
              day: "numeric",
              year: "numeric",
              hour: "numeric",
              minute: "2-digit",
          });
}
</script>

<style scoped>
.patient-print-report {
    display: none;
}

@media print {
    @page {
        size: A4 portrait;
        margin: 0;
    }

    .patient-print-report {
        display: block;
        font-family: ui-sans-serif, system-ui, sans-serif;
        color: #16302e;
    }

    /* Fills the printable area (A4 height less the @page margins) so the
       footer can be pushed to the bottom edge on short sections. */
    .print-page {
        display: flex;
        flex-direction: column;
        box-sizing: border-box;
        min-height: 297mm;
        padding: 15mm;
        break-after: page;
        page-break-after: always;
    }

    .print-page:last-child {
        break-after: auto;
        page-break-after: auto;
    }

    .print-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 12mm;
        border-bottom: 2pt solid #16302e;
        padding-bottom: 2.5mm;
    }

    .print-brand {
        font-size: 13pt;
        font-weight: 700;
        letter-spacing: 0.04em;
        color: #16302e;
        margin: 0;
    }

    .print-branch {
        font-size: 8pt;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #6b8a87;
        margin: 0.8mm 0 0;
    }

    .print-branch-line {
        font-size: 7.5pt;
        color: #6b8a87;
        margin: 0.6mm 0 0;
    }

    .print-header-right {
        text-align: right;
    }

    .print-doc-type {
        font-size: 8pt;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #6b8a87;
        margin: 0;
    }

    .print-doc-ref {
        font-size: 8pt;
        color: #4a5f5d;
        margin: 0.8mm 0 0;
    }

    .print-title {
        font-size: 14pt;
        font-weight: 700;
        letter-spacing: 0.01em;
        margin: 5mm 0 3mm;
        padding-bottom: 1.5mm;
        border-bottom: 0.5pt solid #dcebe9;
    }

    .print-subtitle {
        font-size: 10pt;
        font-weight: 700;
        margin: 4mm 0 2mm;
        page-break-after: avoid;
    }

    .print-identity {
        width: 100%;
        border-collapse: collapse;
        font-size: 8.5pt;
        margin-bottom: 5mm;
    }

    .print-identity th,
    .print-identity td {
        border: 0.5pt solid #dcebe9;
        padding: 1.6mm 2mm;
        text-align: left;
        vertical-align: top;
    }

    .print-identity th {
        background: #f6faf9;
        width: 22mm;
        font-weight: 600;
        font-size: 7.5pt;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6b8a87;
    }

    .print-identity-name {
        font-weight: 700;
        color: #16302e;
    }

    .print-subhead {
        font-size: 10pt;
        font-weight: 700;
        margin: 6mm 0 2.5mm;
        padding-bottom: 1mm;
        border-bottom: 0.5pt solid #dcebe9;
    }

    .print-assessment {
        margin-bottom: 4mm;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .print-caption {
        font-size: 8pt;
        font-weight: 600;
        color: #6b8a87;
        margin: 0 0 1.5mm;
    }

    .print-assessment tbody th {
        width: 34mm;
    }

    .print-life-system {
        margin-top: 3mm;
    }

    .print-col-date {
        width: 26mm;
    }

    .print-col-score {
        width: 18mm;
        text-align: center;
    }

    .print-note-row td {
        font-size: 8pt;
        background: #fbfdfc;
    }

    .print-strong {
        font-weight: 600;
        color: #16302e;
    }

    .print-block {
        display: block;
        font-size: 7.5pt;
        margin-top: 0.5mm;
    }

    .print-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8.5pt;
    }

    .print-table thead {
        display: table-header-group;
    }

    .print-table tr {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .print-table th,
    .print-table td {
        border: 0.5pt solid #dcebe9;
        padding: 1.8mm 2mm;
        text-align: left;
        vertical-align: top;
    }

    .print-table th {
        background: #f0f7f6;
        font-weight: 600;
        font-size: 8pt;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #4a5f5d;
    }

    .print-table .right {
        text-align: right;
    }

    .print-summary {
        display: flex;
        gap: 3mm;
        margin-bottom: 4mm;
    }

    .print-summary > div {
        flex: 1;
        border: 0.5pt solid #dcebe9;
        border-radius: 2mm;
        padding: 2.5mm 3mm;
    }

    .print-summary-label {
        font-size: 7.5pt;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #6b8a87;
        margin: 0;
    }

    .print-summary-value {
        font-size: 11pt;
        font-weight: 700;
        margin: 1mm 0 0;
    }

    .print-empty {
        font-size: 9pt;
        color: #6b8a87;
        font-style: italic;
        padding: 6mm 0;
        text-align: center;
        border: 0.5pt dashed #dcebe9;
        border-radius: 2mm;
    }

    .print-subtle {
        color: #6b8a87;
    }

    .print-signature {
        display: flex;
        gap: 8mm;
        margin-top: 12mm;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .print-sign-block {
        flex: 1;
    }

    .print-sign-line {
        display: block;
        border-bottom: 0.5pt solid #16302e;
        height: 10mm;
    }

    .print-sign-label {
        font-size: 7.5pt;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #6b8a87;
        margin: 1.5mm 0 0;
    }

    .print-page-bottom {
        margin-top: auto;
    }

    .print-footer {
        display: flex;
        justify-content: space-between;
        gap: 6mm;
        margin-top: 6mm;
        padding-top: 2mm;
        border-top: 0.5pt solid #dcebe9;
        font-size: 7.5pt;
        color: #8aa3a1;
    }

    .capitalize {
        text-transform: capitalize;
    }
}
</style>
