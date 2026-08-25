<template>
    <div class="patient-print-report">
        <section
            v-for="section in orderedSections"
            :key="section"
            class="print-page"
        >
            <header class="print-header">
                <div class="print-header-left">
                    <p class="print-brand">Amuma Care</p>
                    <p class="print-branch">
                        {{ report.patient.branch_name ?? "Medical Records" }}
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
                        <td>{{ report.patient.date_of_birth ?? "—" }}</td>
                    </tr>
                    <tr>
                        <th>Gender</th>
                        <td>{{ report.patient.gender ?? "—" }}</td>
                        <th>Contact</th>
                        <td>{{ report.patient.phone_number ?? "—" }}</td>
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
                            <td>{{ report.patient.height ?? "—" }}</td>
                            <th>Weight</th>
                            <td>{{ report.patient.weight ?? "—" }}</td>
                        </tr>
                        <tr>
                            <th>Allergies</th>
                            <td colspan="3">
                                {{ allergyList || "None recorded" }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h2 class="print-subhead">Initial Assessment</h2>

                <p v-if="!assessments.length" class="print-empty">
                    No initial assessment recorded.
                </p>

                <template v-else>
                    <table
                        v-for="(assessment, index) in assessments"
                        :key="index"
                        class="print-table print-assessment"
                    >
                        <caption v-if="assessments.length > 1">
                            Assessment {{ index + 1 }}
                        </caption>
                        <tbody>
                            <tr
                                v-for="field in assessment"
                                :key="field.label"
                            >
                                <th>{{ field.label }}</th>
                                <td>{{ field.value }}</td>
                            </tr>
                        </tbody>
                    </table>
                </template>
            </template>

            <template v-else-if="section === 'admission'">
                <p v-if="!report.admission?.length" class="print-empty">
                    No admission records.
                </p>

                <table v-else class="print-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Admitted</th>
                            <th>End Date</th>
                            <th>Room</th>
                            <th>Type</th>
                            <th>Bed</th>
                            <th>Floor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in report.admission" :key="i">
                            <td class="capitalize">{{ row.status ?? "—" }}</td>
                            <td>{{ row.admitted_at ?? "—" }}</td>
                            <td>{{ row.end_date ?? "—" }}</td>
                            <td>{{ row.room ?? "—" }}</td>
                            <td>{{ row.room_type ?? "—" }}</td>
                            <td>{{ row.bed ?? "—" }}</td>
                            <td>{{ row.floor ?? "—" }}</td>
                        </tr>
                    </tbody>
                </table>
            </template>

            <template v-else-if="section === 'billing'">
                <div class="print-summary">
                    <div>
                        <p class="print-summary-label">Total Paid</p>
                        <p class="print-summary-value">
                            {{ money(report.billing?.summary?.total_paid) }}
                        </p>
                    </div>
                    <div>
                        <p class="print-summary-label">Refunded</p>
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
                            <th class="right">Refunded</th>
                            <th class="right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(row, i) in report.billing.invoices"
                            :key="i"
                        >
                            <td>{{ row.invoice_code }}</td>
                            <td>{{ row.created_at ?? "—" }}</td>
                            <td class="capitalize">{{ row.status }}</td>
                            <td class="right">{{ money(row.total) }}</td>
                            <td class="right">{{ money(row.amount_paid) }}</td>
                            <td class="right">
                                {{ money(row.refunded_amount) }}
                            </td>
                            <td class="right">{{ money(row.balance_due) }}</td>
                        </tr>
                    </tbody>
                </table>
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
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in report.schedule" :key="i">
                            <td>{{ row.schedule_code ?? "—" }}</td>
                            <td>{{ row.scheduled_at ?? "—" }}</td>
                            <td class="capitalize">{{ row.status ?? "—" }}</td>
                            <td>{{ serviceSummary(row.services) }}</td>
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
                            <th>Frequency</th>
                            <th>Kind</th>
                            <th>Start</th>
                            <th>Instructions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in report.medication" :key="i">
                            <td>
                                {{ row.name
                                }}<template v-if="row.strength">
                                    ({{ row.strength }})</template
                                >
                            </td>
                            <td>{{ dosage(row) }}</td>
                            <td>{{ row.route ?? "—" }}</td>
                            <td>{{ row.frequency ?? "—" }}</td>
                            <td>{{ row.kind ?? "—" }}</td>
                            <td>{{ row.start_date ?? "—" }}</td>
                            <td>{{ row.instructions ?? "—" }}</td>
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
                            <th>BP</th>
                            <th>HR</th>
                            <th>RR</th>
                            <th>Temp</th>
                            <th>O₂</th>
                            <th>Glucose</th>
                            <th>Pain</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in report.vitals" :key="i">
                            <td>{{ row.recorded_date ?? "—" }}</td>
                            <td>{{ row.recorded_time ?? "—" }}</td>
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
                            <td>{{ row.occurred_at ?? "—" }}</td>
                            <td class="capitalize">{{ row.type ?? "—" }}</td>
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
        </section>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

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

const ASSESSMENT_LABELS: Record<string, string> = {
    mood: "Mood",
    speech: "Speech",
    mental_state: "Mental State",
    communication: "Communication",
    memory_issues: "Memory Issues",
    diagnosis: "Diagnosis",
    diagnosis_date: "Diagnosis Date",
    diagnosis_notes: "Diagnosis Notes",
    diagnosis_file_name: "Attached File",
    blood_pressure: "Blood Pressure",
    pulse_rate: "Pulse Rate",
    temperature: "Temperature",
    respiratory_rate: "Respiratory Rate",
    oxygen_saturation: "Oxygen Saturation",
};

function humanizeKey(key: string) {
    return (
        ASSESSMENT_LABELS[key] ??
        key
            .replace(/[_-]+/g, " ")
            .replace(/\b\w/g, (char) => char.toUpperCase())
    );
}

function humanizeValue(value: unknown): string {
    if (typeof value === "boolean") return value ? "Yes" : "No";
    if (Array.isArray(value)) return value.filter(Boolean).join(", ");
    if (value && typeof value === "object") {
        return Object.entries(value as Record<string, unknown>)
            .filter(([, v]) => v !== null && v !== "" && v !== undefined)
            .map(([k, v]) => `${humanizeKey(k)}: ${humanizeValue(v)}`)
            .join("; ");
    }
    const text = String(value).trim();
    return /^[a-z][a-z\s]*$/.test(text)
        ? text.replace(/\b\w/g, (char) => char.toUpperCase())
        : text;
}

const assessments = computed(() => {
    const raw = props.report?.profile?.initial_assessment;
    if (!raw) return [];

    const entries = Array.isArray(raw) ? raw : [raw];

    return entries
        .map((entry) => {
            if (!entry || typeof entry !== "object") return [];

            return Object.entries(entry as Record<string, unknown>)
                .filter(
                    ([, value]) =>
                        value !== null &&
                        value !== undefined &&
                        value !== "" &&
                        !(Array.isArray(value) && !value.length),
                )
                .map(([key, value]) => ({
                    label: humanizeKey(key),
                    value: humanizeValue(value),
                }));
        })
        .filter((fields) => fields.length);
});

const shortRef = computed(() => {
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
    return (
        orderedSections.value[orderedSections.value.length - 1] === key
    );
}

function money(value: unknown) {
    const amount = Number(value ?? 0);
    return `PHP ${(Number.isFinite(amount) ? amount : 0).toLocaleString("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
}

function dosage(row: any) {
    const parts = [row.dosage_amount, row.dosage_unit].filter(Boolean);
    return parts.length ? parts.join(" ") : "—";
}

function serviceSummary(services: any[]) {
    if (!services?.length) return "—";
    return services
        .map((service) => {
            const hours = service.hours_booked
                ? ` (${service.hours_booked}h)`
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
    .patient-print-report {
        display: block;
        font-family: ui-sans-serif, system-ui, sans-serif;
        color: #16302e;
    }

    .print-page {
        break-after: page;
        page-break-after: always;
        padding-bottom: 12mm;
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

    .print-assessment caption {
        caption-side: top;
        text-align: left;
        font-size: 8pt;
        font-weight: 600;
        color: #6b8a87;
        padding-bottom: 1.5mm;
    }

    .print-assessment th {
        width: 45mm;
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
