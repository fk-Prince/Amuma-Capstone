export type ReportPeriodMode = "month" | "quarter" | "range" | "all";

export type SalesStatus =
    | ""
    | "paid"
    | "partially_paid"
    | "unpaid"
    | "written_off"
    | "void";

export interface SalesRow {
    invoice_code: string;
    created_at: string | null;
    patient_name: string | null;
    description: string | null;
    adl_duration?: number | null;
    adl_months_billed?: number | null;
    gross_amount: number;
    status: string;
}

export interface SalesSummary {
    gross_sales: number;
    accounts_receivable: number;
    bad_debt: number;
    total_refunded: number;
    invoice_count: number;
}

export const SALES_STATUS_OPTIONS: { label: string; value: SalesStatus }[] = [
    { label: "All statuses", value: "" },
    { label: "Paid", value: "paid" },
    { label: "Partially paid", value: "partially_paid" },
    { label: "Unpaid", value: "unpaid" },
    { label: "Written off", value: "written_off" },
    { label: "Void", value: "void" },
];

export const SALES_STATUS_LABEL: Record<string, string> = {
    paid: "Paid",
    partially_paid: "Partially Paid",
    unpaid: "Unpaid",
    written_off: "Written Off",
    void: "Void",
};

export const SALES_STATUS_CLASS: Record<string, string> = {
    paid: "bg-green-50 text-green-700 border-green-200 dark:bg-green-500/10 dark:text-green-300 dark:border-green-500/20",
    partially_paid:
        "bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:border-blue-500/20",
    unpaid: "bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:border-amber-500/20",
    written_off:
        "bg-slate-100 text-slate-600 border-slate-200 dark:bg-white/10 dark:text-gray-300 dark:border-white/10",
    void: "bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:border-rose-500/20",
};
