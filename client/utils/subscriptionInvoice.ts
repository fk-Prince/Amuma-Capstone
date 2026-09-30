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
