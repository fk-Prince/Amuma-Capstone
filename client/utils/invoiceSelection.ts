import type { PatientInvoiceItem } from "~/types/invoice";

export function invoiceBalance(
    invoices: PatientInvoiceItem[],
    code: string,
): number {
    const invoice = invoices.find((item) => item.invoice_code === code);

    return Number(invoice?.balance_due ?? 0);
}

export function amountFor(
    invoices: PatientInvoiceItem[],
    amounts: Record<string, number>,
    code: string,
): number {
    const entered = amounts[code];
    const balance = invoiceBalance(invoices, code);

    if (entered === undefined || entered === null || Number.isNaN(entered)) {
        return balance;
    }

    return Math.min(Math.max(Number(entered), 0), balance);
}
