export function subscriptionInvoiceLink(
    reference: string,
    branchUuid?: string | null,
) {
    const query = branchUuid ? `?branch=${encodeURIComponent(branchUuid)}` : "";

    return `/subscription/invoice/${encodeURIComponent(reference)}${query}`;
}

export function paymentAccount(payment: {
    masked_card_number?: string | null;
    payment_method?: string | null;
}) {
    if (payment.masked_card_number) return payment.masked_card_number;

    return payment.payment_method === "GCASH" ? "GCash" : "—";
}

export function paymentTypeLabel(type?: string | null) {
    if (!type) return "—";

    const text = type.replace(/_/g, " ");

    return text.charAt(0).toUpperCase() + text.slice(1);
}
