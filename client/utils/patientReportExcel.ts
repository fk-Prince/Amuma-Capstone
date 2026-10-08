import { moneyCell, type ExcelCell, type ExcelSheet } from "~/utils/excel";
import { formatDate, formatTime } from "~/utils/time";
import { formatPhone } from "~/utils/phone";
import { calculateAge } from "~/utils/user";
import { ROUTE_LABELS, DOSAGE_UNIT_LABELS } from "~/utils/medication";
import {
    LIFE_SYSTEM_ACTIVITIES,
    LIFE_SYSTEM_SCALE,
    activityLabel,
    assessmentLabel,
} from "~/utils/assessment";

const SECTION_ORDER = [
    "profile",
    "diagnosis",
    "assessment",
    "admission",
    "billing",
    "transactions",
    "schedule",
    "medication",
    "vitals",
    "activity",
];

const SECTION_LABELS: Record<string, string> = {
    profile: "Patient Info",
    diagnosis: "Diagnosis",
    assessment: "Assessment",
    admission: "Admissions",
    billing: "Billing",
    transactions: "Transactions",
    schedule: "Schedules",
    medication: "Medications",
    vitals: "Vital Signs",
    activity: "Activities",
};

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

function label(value?: string | null) {
    if (!value) return "";

    return value
        .replace(/[_-]+/g, " ")
        .toLowerCase()
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function dateTime(value?: string | null) {
    if (!value) return "";

    const parsed = new Date(value);

    return Number.isNaN(parsed.getTime())
        ? ""
        : parsed.toLocaleString("en-US", {
              month: "short",
              day: "numeric",
              year: "numeric",
              hour: "numeric",
              minute: "2-digit",
          });
}

function date(value?: string | null) {
    return value ? formatDate(value) : "";
}

function measure(value: unknown, unit: string) {
    const amount = Number(value);

    if (value === null || value === undefined || value === "" || !Number.isFinite(amount)) {
        return "";
    }

    return `${Number(amount.toFixed(1))} ${unit}`;
}

function patientRef(report: any) {
    const patient = report?.patient ?? {};

    if (patient.patient_code) return patient.patient_code;

    const uuid = patient.patient_uuid ?? "";

    return uuid ? String(uuid).split("-")[0]!.toUpperCase() : "";
}

function titleRows(report: any, title: string): ExcelCell[][] {
    return [
        [report.branch?.name ?? "", ""],
        [title],
        ["Patient", report.patient?.full_name ?? ""],
        ["Patient ID", patientRef(report)],
        ["Generated", dateTime(report.generated_at)],
        [],
    ];
}

function table(headers: string[], rows: ExcelCell[][], empty: string): ExcelCell[][] {
    if (!rows.length) return [[empty]];

    return [headers, ...rows];
}

function profileSheet(report: any): ExcelCell[][] {
    const patient = report.patient ?? {};
    const allergies = Array.isArray(patient.allergies)
        ? patient.allergies.join(", ")
        : (patient.allergies ?? "");

    const rows: ExcelCell[][] = [
        ["Name", patient.full_name ?? ""],
        ["Patient ID", patientRef(report)],
        ["Date of birth", patient.date_of_birth ? calculateAge(patient.date_of_birth) : ""],
        ["Gender", label(patient.gender)],
        ["Contact", formatPhone(patient.phone_number) || ""],
        ["Address", patient.address ?? ""],
        ["Blood type", patient.blood_type ?? ""],
        ["Citizenship", patient.citizenship ?? ""],
        ["Occupation", patient.occupation ?? ""],
        ["Marital status", label(patient.marital_status)],
        ["Height", measure(patient.height, "cm")],
        ["Weight", measure(patient.weight, "kg")],
        ["Allergies", allergies || "None recorded"],
    ];

    const guardians: any[] = report.profile?.guardians ?? [];

    if (guardians.length) {
        rows.push(
            [],
            ["Family & Guardians"],
            ["Name", "Relationship", "Contact", "Email", "Portal"],
            ...guardians.map((row): ExcelCell[] => [
                row.full_name ?? "",
                row.relationship ?? "",
                formatPhone(row.phone_number) || "",
                row.email ?? "",
                row.has_portal_access ? "Has access" : "No access",
            ]),
        );
    }

    return [...titleRows(report, "Patient Information"), ...rows];
}

function diagnosisSheet(report: any): ExcelCell[][] {
    const diagnoses: any[] = report.diagnosis?.diagnoses ?? [];

    return [
        ...titleRows(report, "Diagnosis Records"),
        ...table(
            ["Diagnosis", "Date", "Notes"],
            diagnoses.map((row): ExcelCell[] => [
                row.diagnosis ?? "",
                date(row.diagnosis_date),
                row.diagnosis_notes ?? "",
            ]),
            "No diagnosis recorded.",
        ),
    ];
}

function assessmentSheet(report: any): ExcelCell[][] {
    const raw = report.assessment?.assessment;
    const entries: any[] = raw ? (Array.isArray(raw) ? raw : [raw]) : [];
    const rows: ExcelCell[][] = [];

    entries
        .filter((entry) => entry && typeof entry === "object")
        .forEach((entry, index) => {
            if (rows.length) rows.push([]);

            if (entries.length > 1) rows.push([`Assessment ${index + 1}`]);

            ASSESSMENT_FIELDS.filter(([key]) => entry[key]).forEach(
                ([key, text]) => rows.push([text, assessmentLabel(entry[key])]),
            );

            const profile = entry.life_system_profile ?? {};
            const scored = LIFE_SYSTEM_ACTIVITIES.filter(
                (activity: string) =>
                    profile[activity] !== undefined && profile[activity] !== null,
            );

            if (scored.length) {
                rows.push([], ["Daily activity", "Score", "Level of independence"]);

                scored.forEach((activity: string) =>
                    rows.push([
                        activityLabel(activity),
                        `${profile[activity]} / 5`,
                        LIFE_SYSTEM_SCALE.find(
                            (step: any) => step.value === Number(profile[activity]),
                        )?.label ?? "",
                    ]),
                );
            }
        });

    return [
        ...titleRows(report, "Assessment"),
        ...(rows.length ? rows : [["No assessment recorded."]]),
    ];
}

function admissionSheet(report: any): ExcelCell[][] {
    const admissions: any[] = report.admission ?? [];

    return [
        ...titleRows(report, "Admission Records"),
        ...table(
            ["Status", "Admitted", "Covered until", "Accommodation", "Room / Bed", "Rate", "Note"],
            admissions.map((row): ExcelCell[] => {
                const plan = row.accommodation ?? row.room_type;
                const name = plan
                    ? plan.toUpperCase() === "VIP"
                        ? "VIP"
                        : label(plan)
                    : "";

                return [
                    label(row.status),
                    date(row.admitted_at),
                    date(row.end_date),
                    row.billing_cycle ? `${name} · ${label(row.billing_cycle)}` : name,
                    [
                        row.room ? `Room ${row.room}` : null,
                        row.bed ? `Bed ${row.bed}` : null,
                        row.floor ? `${row.floor} floor` : null,
                    ]
                        .filter(Boolean)
                        .join(" · "),
                    row.rate != null ? moneyCell(row.rate) : "",
                    row.note ?? "",
                ];
            }),
            "No admission records.",
        ),
    ];
}

function billingSheets(report: any): { billing: ExcelCell[][]; payments: ExcelCell[][] | null } {
    const summary = report.billing?.summary ?? {};
    const invoices: any[] = report.billing?.invoices ?? [];
    const hasRefunds = invoices.some((invoice) => Number(invoice.refunded_amount) > 0);

    const summaryRows: ExcelCell[][] = [
        ["Total paid", moneyCell(summary.total_paid)],
        ...(Number(summary.refundable) > 0
            ? ([["Credit", moneyCell(summary.refundable)]] as ExcelCell[][])
            : []),
        ["Balance", moneyCell(summary.balance_due)],
        [],
    ];

    const headers = [
        "Invoice",
        "Date",
        "Status",
        "Total",
        "Paid",
        ...(hasRefunds ? ["Refunded"] : []),
        "Balance",
    ];

    const billing = [
        ...titleRows(report, "Billing Statement"),
        ...summaryRows,
        ...table(
            headers,
            invoices.map((row): ExcelCell[] => [
                row.invoice_code ?? "",
                date(row.created_at),
                label(row.status),
                moneyCell(row.total),
                moneyCell(row.amount_paid),
                ...(hasRefunds ? [moneyCell(row.refunded_amount)] : []),
                moneyCell(row.balance_due),
            ]),
            "No invoices on record.",
        ),
    ];

    const paymentRows = invoices.flatMap((invoice) =>
        (invoice.payments ?? []).map((payment: any): ExcelCell[] => [
            payment.payment_code ?? "",
            invoice.invoice_code ?? "",
            dateTime(payment.paid_at),
            label(payment.payment_method),
            payment.reference_id ?? "",
            moneyCell(payment.amount),
        ]),
    );

    return {
        billing,
        payments: paymentRows.length
            ? [
                  ...titleRows(report, "Payments Received"),
                  ["Receipt no.", "Invoice", "Date", "Method", "Reference", "Amount"],
                  ...paymentRows,
              ]
            : null,
    };
}

export function transactionsSheet(report: any): ExcelCell[][] {
    const transactions: any[] = report.transactions ?? [];

    return [
        ...titleRows(report, "Transaction History"),
        ...table(
            [
                "Date",
                "Transaction",
                "Reference",
                "Type",
                "Direction",
                "Status",
                "Method",
                "Party",
                "Description",
                "Amount",
            ],
            transactions.map((row): ExcelCell[] => [
                dateTime(row.created_at),
                row.transaction_code ?? "",
                row.reference_id ?? "",
                label(row.type),
                label(row.direction),
                label(row.status),
                label(row.method),
                row.party_name ?? "",
                row.description ?? "",
                moneyCell(row.amount),
            ]),
            "No transactions on record.",
        ),
    ];
}

function scheduleSheet(report: any): ExcelCell[][] {
    const schedules: any[] = report.schedule ?? [];

    return [
        ...titleRows(report, "Service Schedules"),
        ...table(
            ["Code", "Scheduled", "Status", "Category", "Services", "Location"],
            schedules.map((row): ExcelCell[] => [
                row.schedule_code ?? "",
                dateTime(row.scheduled_at),
                label(row.status),
                row.category ?? "",
                (row.services ?? [])
                    .map(
                        (service: any) =>
                            `${service.service_name ?? "Service"}${
                                service.hours_booked
                                    ? ` (${Number(service.hours_booked)} hrs)`
                                    : ""
                            }`,
                    )
                    .join(", "),
                row.address ?? "",
            ]),
            "No schedules on record.",
        ),
    ];
}

function medicationSheet(report: any): ExcelCell[][] {
    const medications: any[] = report.medication ?? [];

    return [
        ...titleRows(report, "Medication Records"),
        ...table(
            [
                "Medication",
                "Strength",
                "Taken for",
                "Dosage",
                "Route",
                "Schedule",
                "Start",
                "Duration (days)",
                "Instructions",
            ],
            medications.map((row): ExcelCell[] => {
                const amount = Number(row.dosage_amount);
                const unit = DOSAGE_UNIT_LABELS[row.dosage_unit] ?? row.dosage_unit ?? "";
                const times = (row.times ?? [])
                    .map((time: string) => formatTime(time))
                    .filter(Boolean);
                const frequency = FREQUENCY_LABELS[row.frequency] ?? label(row.frequency);

                return [
                    row.name ?? "",
                    row.strength ?? "",
                    row.taken_for ?? "",
                    row.dosage_amount && Number.isFinite(amount)
                        ? `${Number(amount.toFixed(2))} ${unit}`.trim()
                        : "",
                    ROUTE_LABELS[row.route] ?? row.route ?? "",
                    row.kind === "PRN"
                        ? "As needed (PRN)"
                        : times.length
                          ? `${frequency} · ${times.join(", ")}`
                          : frequency,
                    date(row.start_date),
                    row.duration ?? "",
                    row.instructions ?? "",
                ];
            }),
            "No medications on record.",
        ),
    ];
}

function vitalsSheet(report: any): ExcelCell[][] {
    const vitals: any[] = report.vitals ?? [];

    return [
        ...titleRows(report, "Vital Signs"),
        ...table(
            [
                "Date",
                "Time",
                "BP (mmHg)",
                "HR (bpm)",
                "RR (/min)",
                "Temp (°C)",
                "SpO₂ (%)",
                "Glucose (mg/dL)",
                "Pain (/10)",
                "Recorded by",
            ],
            vitals.map((row): ExcelCell[] => [
                date(row.recorded_date),
                formatTime(row.recorded_time) || "",
                row.blood_pressure ?? "",
                row.heart_rate ?? "",
                row.respiratory_rate ?? "",
                row.temperature ?? "",
                row.oxygen_saturation ?? "",
                row.blood_glucose ?? "",
                row.pain_level ?? "",
                row.recorded_by ?? "",
            ]),
            "No vital signs on record.",
        ),
    ];
}

function activitySheet(report: any): ExcelCell[][] {
    const activities: any[] = report.activity ?? [];

    return [
        ...titleRows(report, "Activity Log"),
        ...table(
            ["Occurred", "Type", "Title", "Description", "Recorded by"],
            activities.map((row): ExcelCell[] => [
                dateTime(row.occurred_at),
                label(row.type),
                [row.title, row.subtitle].filter(Boolean).join(" — "),
                row.description ?? "",
                row.recorded_by ?? "",
            ]),
            "No activities on record.",
        ),
    ];
}

export function patientReportSheets(report: any): ExcelSheet[] {
    const active: string[] = report?.sections ?? [];
    const sheets: ExcelSheet[] = [];

    for (const key of SECTION_ORDER.filter((section) => active.includes(section))) {
        const name = SECTION_LABELS[key] ?? key;

        switch (key) {
            case "profile":
                sheets.push({ name, rows: profileSheet(report) });
                break;
            case "diagnosis":
                sheets.push({ name, rows: diagnosisSheet(report) });
                break;
            case "assessment":
                sheets.push({ name, rows: assessmentSheet(report) });
                break;
            case "admission":
                sheets.push({ name, rows: admissionSheet(report) });
                break;
            case "billing": {
                const { billing, payments } = billingSheets(report);

                sheets.push({ name, rows: billing });

                if (payments) sheets.push({ name: "Payments", rows: payments });

                break;
            }
            case "transactions":
                sheets.push({ name, rows: transactionsSheet(report) });
                break;
            case "schedule":
                sheets.push({ name, rows: scheduleSheet(report) });
                break;
            case "medication":
                sheets.push({ name, rows: medicationSheet(report) });
                break;
            case "vitals":
                sheets.push({ name, rows: vitalsSheet(report) });
                break;
            case "activity":
                sheets.push({ name, rows: activitySheet(report) });
                break;
        }
    }

    return sheets;
}
