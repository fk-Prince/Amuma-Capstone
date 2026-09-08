import type { PortalTransactionType } from "~/types/portal-billing";

export function formatBillingDateTime(dateStr?: string): string {
    if (!dateStr) return "";

    const parsed = new Date(dateStr);

    if (Number.isNaN(parsed.getTime())) return dateStr;

    return parsed.toLocaleString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

export function formatBillingStatus(status?: string) {
    return (status || "Unknown")
        .replace(/_/g, " ")
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

export function invoiceStatusClasses(status?: string) {
    switch ((status || "").toLowerCase()) {
        case "paid":
        case "completed":
            return "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300";
        case "partial":
        case "partially_paid":
            return "bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300";
        case "cancelled":
        case "rejected":
            return "bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300";
        default:
            return "bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-400";
    }
}

export function transactionStatusClasses(status?: string) {
    switch ((status || "").toLowerCase()) {
        case "paid":
        case "completed":
        case "released":
            return "bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20";
        case "partial":
        case "processing":
        case "pending":
            return "bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-100 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20";
        case "refunded":
        case "partially refunded":
            return "bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-100 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20";
        case "cancelled":
        case "rejected":
            return "bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-100 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/20";
        default:
            return "bg-gray-100 text-gray-600 ring-1 ring-inset ring-gray-200 dark:bg-white/10 dark:text-gray-400 dark:ring-white/10";
    }
}

export function transactionIcon(type: PortalTransactionType) {
    switch (type) {
        case "payment":
            return "banknote";
        case "refund":
            return "arrow-down-circle";
        case "adjustment":
            return "sliders-horizontal";
        default:
            return "receipt";
    }
}

// A refund only moves money once the branch releases it. While it is still a
// request, and forever if it is declined, nothing has come back.
export function transactionMovedAmount(
    type: PortalTransactionType,
    amount: number,
    status?: string,
) {
    if (type !== "refund") return amount;

    return (status || "").toLowerCase() === "completed" ? amount : 0;
}

export function transactionSign(type: PortalTransactionType, amount?: number) {
    if (amount === 0) return "";

    switch (type) {
        case "payment":
            return "-";
        case "refund":
            return "+";
        default:
            return "";
    }
}

// Read from the family's side: a payment is money leaving them, a refund is
// money coming back.
export function transactionAmountColor(type: PortalTransactionType) {
    switch (type) {
        case "payment":
            return "text-rose-600 dark:text-rose-300";
        case "refund":
            return "text-emerald-600 dark:text-emerald-300";
        case "adjustment":
            return "text-amber-600 dark:text-amber-300";
        default:
            return "text-gray-600 dark:text-gray-300";
    }
}

export function transactionIconClasses(type: PortalTransactionType) {
    switch (type) {
        case "payment":
            return "bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300";
        case "refund":
            return "bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300";
        case "adjustment":
            return "bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300";
        default:
            return "bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400";
    }
}

export function transactionRole(type: PortalTransactionType, status?: string) {
    switch (type) {
        case "invoice":
            return "Billed";
        case "payment":
            return "Paid";
        default:
            break;
    }

    switch ((status || "").toLowerCase()) {
        case "completed":
            return "Returned";
        case "declined":
            return "Not returned";
        default:
            return "Not yet settled";
    }
}

export function getSourceTypeLabel(sourceType?: string | null): string {
    if (!sourceType) return "Unknown";

    if (sourceType.toLowerCase() === "adl") {
        return "Activities of Daily Living (ADL)";
    }

    if (sourceType.toLowerCase() === "medical") {
        return "Medical Services";
    }

    return sourceType;
}
