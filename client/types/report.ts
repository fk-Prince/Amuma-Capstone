export type ReportPeriodMode = "month" | "quarter" | "range";

export type SalesStatus = "" | "paid" | "partially_paid" | "unpaid";

export interface SalesVatRow {
    invoice_code: string;
    created_at: string | null;
    patient_name: string | null;
    description: string | null;
    gross_amount: number;
    vatable_sales: number;
    vat_exempt_sales: number;
    vat_amount: number;
    vat_rate: number;
    status: string;
}

export interface SalesVatSummary {
    gross_sales: number;
    vatable_sales: number;
    vat_exempt_sales: number;
    output_vat: number;
    invoice_count: number;
}

export const SALES_STATUS_OPTIONS: { label: string; value: SalesStatus }[] = [
    { label: "All statuses", value: "" },
    { label: "Paid", value: "paid" },
    { label: "Partially paid", value: "partially_paid" },
    { label: "Unpaid", value: "unpaid" },
];

export const SALES_STATUS_LABEL: Record<string, string> = {
    paid: "Paid",
    partially_paid: "Partially Paid",
    unpaid: "Unpaid",
    written_off: "Written Off",
};

export const SALES_STATUS_CLASS: Record<string, string> = {
    paid: "bg-green-50 text-green-700 border-green-200 dark:bg-green-500/10 dark:text-green-300 dark:border-green-500/20",
    partially_paid:
        "bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:border-blue-500/20",
    unpaid: "bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:border-amber-500/20",
    written_off:
        "bg-slate-100 text-slate-600 border-slate-200 dark:bg-white/10 dark:text-gray-300 dark:border-white/10",
};
