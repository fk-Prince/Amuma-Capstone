<template>
    <div class="patient-print-report">
        <section
            v-for="section in orderedSections"
            :key="section"
            class="print-page"
        >
            <header class="print-header">
                <div class="print-branch">
                    <img
                        v-if="report.branch?.image"
                        :src="report.branch.image"
                        :alt="report.branch?.name"
                        class="print-branch-logo"
                    />
                    <div v-else class="print-branch-logo print-branch-initials">
                        {{ initials(report.branch?.name ?? "Amuma Care") }}
                    </div>

                    <div class="print-branch-info">
                        <p class="print-brand">
                            {{ report.branch?.name ?? "Amuma Care" }}
                        </p>
                        <p
                            v-if="report.branch?.agency_name"
                            class="print-agency"
                        >
                            {{ report.branch.agency_name }}
                        </p>

                        <p
                            v-if="report.branch?.address"
                            class="print-branch-line"
                        >
                            <MapPin class="print-icon" />
                            <span>{{ report.branch.address }}</span>
                        </p>
                        <p
                            v-if="
                                report.branch?.contact_number ||
                                report.branch?.email
                            "
                            class="print-branch-line"
                        >
                            <template v-if="report.branch?.contact_number">
                                <Phone class="print-icon" />
                                <span>{{
                                    formatPhone(report.branch.contact_number)
                                }}</span>
                            </template>
                            <template v-if="report.branch?.email">
                                <Mail class="print-icon print-icon-gap" />
                                <span>{{ report.branch.email }}</span>
                            </template>
                        </p>
                        <p v-if="report.branch?.tin" class="print-branch-line">
                            <Hash class="print-icon" />
                            <span>TIN {{ report.branch.tin }}</span>
                        </p>
                    </div>
                </div>

                <div class="print-heading">
                    <div class="print-title-row">
                        <span class="print-title-icon">
                            <component
                                :is="sectionIcon(section)"
                                class="print-icon"
                            />
                        </span>
                        <h1 class="print-title">{{ sectionLabel(section) }}</h1>
                    </div>
                    <p class="print-doc-type">Medical Record</p>
                    <p class="print-doc-ref">
                        Ref. {{ shortRef }} · Page {{ pageNumber(section) }} of
                        {{ orderedSections.length }}
                    </p>
                </div>
            </header>

            <div class="print-patient">
                <img
                    v-if="report.patient.avatar"
                    :src="report.patient.avatar"
                    :alt="report.patient.full_name"
                    class="print-photo"
                />
                <!-- <div v-else class="print-photo print-photo-initials">
                    {{ initials(report.patient.full_name) }}
                </div> -->

                <dl class="print-lines">
                    <template
                        v-for="line in patientLines(section)"
                        :key="line.label"
                    >
                        <dt>{{ line.label }}</dt>
                        <dd :class="{ 'print-strong': line.strong }">
                            {{ line.value }}
                        </dd>
                    </template>
                </dl>
            </div>

            <template v-if="section === 'diagnosis'">
                <h2 class="print-subhead">
                    <Stethoscope class="print-icon" />
                    Diagnoses
                </h2>

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
            </template>

            <template v-if="section === 'assessment'">
                <h2 class="print-subhead">
                    <ClipboardCheck class="print-icon" />
                    Assessment
                </h2>

                <p v-if="!assessments.length" class="print-empty">
                    No assessment recorded.
                </p>

                <template v-else>
                    <div
                        v-for="(assessment, index) in assessments"
                        :key="index"
                        class="print-assessment"
                    >
                        <p v-if="assessments.length > 1" class="print-caption">
                            Assessment {{ index + 1 }}
                        </p>

                        <div class="print-pairs">
                            <template
                                v-for="pair in assessment.fields"
                                :key="pair[0]?.label"
                            >
                                <div
                                    v-for="field in pair"
                                    :key="field.label"
                                    class="print-pair"
                                >
                                    <p class="print-label">{{ field.label }}</p>
                                    <p class="print-value">{{ field.value }}</p>
                                </div>
                            </template>
                        </div>

                        <table
                            v-if="assessment.lifeSystem.length"
                            class="print-table print-life-system"
                        >
                            <thead>
                                <tr>
                                    <th>Daily activity</th>
                                    <th class="print-col-score">Score</th>
                                    <th>Level of independence</th>
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

            </template>

            <template v-if="section === 'profile'">
                <template v-if="guardians.length">
                    <h2 class="print-subhead">
                        <Users class="print-icon" />
                        Family &amp; Guardians
                    </h2>

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

                <table v-else class="print-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Admitted</th>
                            <th>Covered until</th>
                            <th>Accommodation</th>
                            <th>Room / Bed</th>
                            <th class="right">Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="(row, i) in report.admission" :key="i">
                            <tr>
                                <td>
                                    {{ statusLabel(row.status) }}
                                </td>
                                <td>{{ formatDate(row.admitted_at) }}</td>
                                <td>{{ formatDate(row.end_date) }}</td>
                                <td>{{ accommodationLabel(row) }}</td>
                                <td>{{ roomLabel(row) }}</td>
                                <td class="right">
                                    {{
                                        row.rate != null ? money(row.rate) : "—"
                                    }}
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

            <template v-else-if="section === 'billing'">
                <div class="print-stats">
                    <div class="print-stat">
                        <Wallet class="print-icon" />
                        <div>
                            <p class="print-label">Total paid</p>
                            <p class="print-value print-strong">
                                {{ money(report.billing?.summary?.total_paid) }}
                            </p>
                        </div>
                    </div>
                    <div
                        v-if="Number(report.billing?.summary?.refundable) > 0"
                        class="print-stat"
                    >
                        <PiggyBank class="print-icon" />
                        <div>
                            <p class="print-label">Credit</p>
                            <p class="print-value print-strong">
                                {{ money(report.billing?.summary?.refundable) }}
                            </p>
                        </div>
                    </div>
                    <div class="print-stat">
                        <Scale class="print-icon" />
                        <div>
                            <p class="print-label">Balance</p>
                            <p class="print-value print-strong">
                                {{
                                    money(report.billing?.summary?.balance_due)
                                }}
                            </p>
                        </div>
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
                            <td class="right print-strong">
                                {{ money(row.balance_due) }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <template v-if="billingPayments.length">
                    <h2 class="print-subhead">
                        <Receipt class="print-icon" />
                        Payments received
                    </h2>

                    <table class="print-table">
                        <thead>
                            <tr>
                                <th>Receipt no.</th>
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
                                <span v-if="row.category" class="print-subtle"
                                    >{{ row.category }} ·</span
                                >
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
                            <th>Recorded by</th>
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
                            <td>
                                {{
                                    ROUTE_LABELS[row.route] ?? row.route ?? "—"
                                }}
                            </td>
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
                            <th>Recorded by</th>
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
                            <td>{{ row.recorded_by || "—" }}</td>
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
                            <th>Recorded by</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in report.activity" :key="i">
                            <td>{{ formatDateTime(row.occurred_at) }}</td>
                            <td>{{ statusLabel(row.type) }}</td>
                            <td>
                                {{ row.title ?? "—" }}
                                <span v-if="row.subtitle" class="print-subtle"
                                    >— {{ row.subtitle }}</span
                                >
                            </td>
                            <td>{{ row.description ?? "—" }}</td>
                            <td>{{ row.recorded_by || "—" }}</td>
                        </tr>
                    </tbody>
                </table>
            </template>

            <div class="print-page-bottom">
                <div v-if="isLastSection(section)" class="print-signature">
                    <div class="print-sign-block">
                        <span class="print-sign-line" />
                        <p class="print-label">Prepared by</p>
                    </div>
                    <div class="print-sign-block">
                        <span class="print-sign-line" />
                        <p class="print-label">Reviewed by</p>
                    </div>
                    <div class="print-sign-block">
                        <span class="print-sign-line" />
                        <p class="print-label">Date</p>
                    </div>
                </div>

                <footer class="print-footer">
                    <span
                        >{{ report.patient.full_name }} ·
                        {{ sectionLabel(section) }}</span
                    >
                    <span class="print-footer-right">
                        <ShieldCheck class="print-icon" />
                        Generated {{ formatDateTime(report.generated_at) }} ·
                        Confidential
                    </span>
                </footer>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import { computed, type Component } from "vue";
import {
    Activity,
    BedDouble,
    CalendarClock,
    ClipboardCheck,
    Hash,
    HeartPulse,
    Mail,
    MapPin,
    Phone,
    PiggyBank,
    Pill,
    Receipt,
    Scale,
    ShieldCheck,
    Stethoscope,
    UserRound,
    Users,
    Wallet,
} from "lucide-vue-next";
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
    "diagnosis",
    "assessment",
    "admission",
    "billing",
    "schedule",
    "medication",
    "vitals",
    "activity",
];

const SECTION_LABELS: Record<string, string> = {
    profile: "Patient Information",
    diagnosis: "Diagnosis Records",
    assessment: "Assessment",
    admission: "Admission Records",
    billing: "Billing Statement",
    schedule: "Service Schedules",
    medication: "Medication Records",
    vitals: "Vital Signs",
    activity: "Activity Log",
};

const SECTION_ICONS: Record<string, Component> = {
    profile: UserRound,
    diagnosis: Stethoscope,
    assessment: ClipboardCheck,
    admission: BedDouble,
    billing: Receipt,
    schedule: CalendarClock,
    medication: Pill,
    vitals: HeartPulse,
    activity: Activity,
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
    const raw = props.report?.assessment?.assessment;
    if (!raw) return [];

    const entries: any[] = Array.isArray(raw) ? raw : [raw];

    return entries
        .filter((entry) => entry && typeof entry === "object")
        .map((entry) => {
            const fields = ASSESSMENT_FIELDS.filter(([key]) => entry[key]).map(
                ([key, label]) => ({
                    label,
                    value: assessmentLabel(entry[key]),
                }),
            );

            const pairs = [];
            for (let i = 0; i < fields.length; i += 2) {
                pairs.push(fields.slice(i, i + 2));
            }

            const profile = entry.life_system_profile ?? {};
            const lifeSystem = LIFE_SYSTEM_ACTIVITIES.filter(
                (activity) =>
                    profile[activity] !== undefined &&
                    profile[activity] !== null,
            ).map((activity) => ({
                activity: activityLabel(activity),
                score: profile[activity],
                meaning:
                    LIFE_SYSTEM_SCALE.find(
                        (step) => step.value === Number(profile[activity]),
                    )?.label ?? "—",
            }));

            return { fields: pairs, lifeSystem };
        })
        .filter((entry) => entry.fields.length || entry.lifeSystem.length);
});

const diagnoses = computed<any[]>(() => props.report?.diagnosis?.diagnoses ?? []);

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
        (today.getMonth() === birth.getMonth() &&
            today.getDate() < birth.getDate());
    if (beforeBirthday) age -= 1;

    return `${formatDate(dob)} (${age} yrs)`;
});

const hasRefunds = computed(() =>
    (props.report?.billing?.invoices ?? []).some(
        (invoice: any) => Number(invoice.refunded_amount) > 0,
    ),
);

function patientLines(section: string) {
    const patient = props.report?.patient ?? {};

    const lines: { label: string; value: string; strong?: boolean }[] = [
        { label: "Name", value: patient.full_name || "—", strong: true },
        { label: "Patient ID", value: shortRef.value },
        { label: "Date of birth", value: birthLine.value },
        { label: "Gender", value: statusLabel(patient.gender) },
        { label: "Contact", value: formatPhone(patient.phone_number) || "—" },
        { label: "Address", value: patient.address ?? "—" },
    ];

    if (section === "profile") {
        lines.push(
            { label: "Blood type", value: patient.blood_type ?? "—" },
            { label: "Citizenship", value: patient.citizenship ?? "—" },
            { label: "Occupation", value: patient.occupation || "—" },
            {
                label: "Marital status",
                value: statusLabel(patient.marital_status),
            },
            { label: "Height", value: measure(patient.height, "cm") },
            { label: "Weight", value: measure(patient.weight, "kg") },
            { label: "Allergies", value: allergyList.value || "None recorded" },
        );
    }

    return lines;
}

function initials(name?: string | null) {
    return (name ?? "")
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]!.toUpperCase())
        .join("");
}

function sectionIcon(key: string) {
    return SECTION_ICONS[key] ?? ClipboardCheck;
}

function measure(value: unknown, unit: string) {
    const amount = Number(value);
    if (
        value === null ||
        value === undefined ||
        value === "" ||
        !Number.isFinite(amount)
    ) {
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
    return row.billing_cycle
        ? `${name} · ${statusLabel(row.billing_cycle)}`
        : name;
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
    const frequency =
        FREQUENCY_LABELS[row.frequency] ?? statusLabel(row.frequency);
    const times = (row.times ?? [])
        .map((time: string) => formatTime(time))
        .filter(Boolean);
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
    return `₱${formatAmount(Number.isFinite(amount) ? amount : 0)}`;
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
        color: #000000;
        background: #ffffff;
    }

    .patient-print-report * {
        color: #000000 !important;
        background: transparent !important;
    }

    .print-page {
        display: flex;
        flex-direction: column;
        box-sizing: border-box;
        min-height: 297mm;
        padding: 13mm 14mm;
        break-after: page;
        page-break-after: always;
    }

    .print-page:last-child {
        break-after: auto;
        page-break-after: auto;
    }

    .print-icon {
        width: 3mm;
        height: 3mm;
        flex-shrink: 0;
    }

    .print-icon-gap {
        margin-left: 2.5mm;
    }

    .print-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10mm;
        padding-bottom: 4mm;
        border-bottom: 1pt solid #000000;
    }

    .print-branch {
        display: flex;
        align-items: flex-start;
        gap: 3.5mm;
        min-width: 0;
    }

    .print-branch-logo {
        width: 12mm;
        height: 12mm;
        flex-shrink: 0;
        object-fit: cover;
    }

    .print-branch-initials {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 9pt;
        font-weight: 700;
    }

    .print-branch-info {
        min-width: 0;
    }

    .print-brand {
        font-size: 11pt;
        font-weight: 700;
        margin: 0;
    }

    .print-agency {
        font-size: 6.5pt;
        font-weight: 600;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        margin: 0.4mm 0 1mm;
    }

    .print-branch-line {
        display: flex;
        align-items: center;
        gap: 1.2mm;
        font-size: 7pt;
        margin: 0.7mm 0 0;
    }

    .print-heading {
        flex-shrink: 0;
        text-align: right;
    }

    .print-title-row {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 1.8mm;
    }

    .print-title-icon {
        display: inline-flex;
    }

    .print-title-icon .print-icon {
        width: 3.8mm;
        height: 3.8mm;
    }

    .print-title {
        font-size: 12pt;
        font-weight: 700;
        margin: 0;
    }

    .print-doc-type {
        font-size: 6.5pt;
        font-weight: 600;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        margin: 1.5mm 0 0;
    }

    .print-doc-ref {
        font-size: 7pt;
        margin: 0.5mm 0 0;
    }

    .print-patient {
        margin: 5mm 0 4mm;
    }

    .print-photo {
        display: block;
        width: 40px;
        height: 40px;
        object-fit: cover;
    }

    .print-photo-initials {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18pt;
        font-weight: 700;
    }

    .print-lines {
        display: grid;
        grid-template-columns: 26mm minmax(0, 1fr) 26mm minmax(0, 1fr);
        column-gap: 3mm;
        row-gap: 1.8mm;
        margin: 4mm 0 0;
        font-size: 8pt;
    }

    .print-lines dt {
        font-weight: 600;
    }

    .print-lines dd {
        margin: 0;
    }

    .print-label {
        font-size: 6pt;
        font-weight: 600;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        margin: 0;
    }

    .print-value {
        font-size: 7.5pt;
        margin: 0.4mm 0 0;
    }

    .print-stats {
        display: flex;
        gap: 8mm;
        margin-bottom: 4mm;
    }

    .print-stat {
        display: flex;
        align-items: flex-start;
        gap: 1.8mm;
    }

    .print-stat .print-icon {
        margin-top: 0.4mm;
    }

    .print-subhead {
        display: flex;
        align-items: center;
        gap: 1.8mm;
        font-size: 8.5pt;
        font-weight: 700;
        margin: 5mm 0 2mm;
        page-break-after: avoid;
    }

    .print-assessment {
        margin-bottom: 3mm;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .print-caption {
        font-size: 7pt;
        font-weight: 600;
        margin: 0 0 1.5mm;
    }

    .print-pairs {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 2.5mm 4mm;
    }

    .print-life-system {
        margin-top: 3mm;
    }

    .print-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 7.5pt;
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
        padding: 1.6mm 2mm 1.6mm 0;
        text-align: left;
        vertical-align: top;
    }

    .print-table th {
        font-size: 6.5pt;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        border-bottom: 0.75pt solid #000000;
    }

    .print-table .right {
        padding-right: 0;
        text-align: right;
    }

    .print-col-date {
        width: 24mm;
    }

    .print-col-score {
        width: 16mm;
        text-align: center;
    }

    .print-note-row td {
        font-size: 7pt;
        font-style: italic;
    }

    .print-strong {
        font-weight: 600;
    }

    .print-block {
        display: block;
        font-size: 6.5pt;
        margin-top: 0.5mm;
    }

    .print-empty {
        font-size: 7.5pt;
        font-style: italic;
        padding: 4mm 0;
        margin: 0;
    }

    .print-page-bottom {
        margin-top: auto;
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
        height: 9mm;
        margin-bottom: 1.2mm;
        border-bottom: 0.5pt solid #000000;
    }

    .print-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 6mm;
        margin-top: 6mm;
        padding-top: 2mm;
        border-top: 0.5pt solid #000000;
        font-size: 6.5pt;
    }

    .print-footer-right {
        display: inline-flex;
        align-items: center;
        gap: 1mm;
    }

    .print-footer-right .print-icon {
        width: 2.6mm;
        height: 2.6mm;
    }
}
</style>
