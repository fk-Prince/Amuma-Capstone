export type PaymentTermsBlock =
    | { type: "paragraph"; text: string }
    | { type: "list"; items: string[] };

export interface PaymentTermsSection {
    title: string;
    blocks: PaymentTermsBlock[];
}

export interface PaymentTerms {
    heading: string;
    lastUpdated: string;
    sections: PaymentTermsSection[];
}

const SUBSCRIPTION_TERMS: PaymentTerms = {
    heading: "Subscription & Billing Terms",
    lastUpdated: "September 2, 2026",
    sections: [
        {
            title: "Scope & acceptance",
            blocks: [
                {
                    type: "paragraph",
                    text: 'These Subscription & Billing Terms ("Terms") apply between [Legal Entity Name], operator of AMUMA ("AMUMA", "we", "us"), and the care agency or facility operator that subscribes to a paid plan ("you", "Subscriber"). They govern every subscription purchased through the AMUMA dashboard, including new subscriptions, renewals, upgrades, and additional branch capacity.',
                },
                {
                    type: "paragraph",
                    text: "By selecting a plan, entering payment details, and clicking Confirm & Pay, you agree to be charged according to these Terms and to the pricing, billing cycle, and payment method you selected at checkout. If you are subscribing on behalf of an agency, you confirm you're authorized to bind that agency to these Terms.",
                },
            ],
        },
        {
            title: "Plans & pricing",
            blocks: [
                {
                    type: "paragraph",
                    text: "AMUMA offers three subscription modules. Each unlocks a distinct part of the platform, and each is billed as a single subscription covering up to 5 branches.",
                },
            ],
        },
        {
            title: "Billing cycle",
            blocks: [
                {
                    type: "paragraph",
                    text: "You choose Monthly or Yearly billing when you subscribe. Your subscription period starts on the date payment is confirmed and runs for one full cycle from that date:",
                },
                {
                    type: "list",
                    items: [
                        "Monthly plans are billed every 30 days from your subscription date.",
                        "Yearly plans are billed once every 12 months from your subscription date, at the discounted yearly rate.",
                    ],
                },
                // {
                //     type: "paragraph",
                //     text: "Switching billing cycle mid-subscription takes effect at the start of your next cycle, not immediately, unless stated otherwise when you make the change.",
                // },
            ],
        },
        {
            title: "Payment methods",
            blocks: [
                {
                    type: "paragraph",
                    text: "Subscription payments are processed securely through our payment partner, Xendit, and may be made using the payment methods made available through Xendit, including:",
                },
                {
                    type: "list",
                    items: ["Credit or debit card", "GCash"],
                },
                {
                    type: "paragraph",
                    text: "By subscribing, you authorize us and our payment partner, Xendit, to process and charge your selected payment method for all subscription payments due under these Terms.",
                },
                {
                    type: "paragraph",
                    text: "If your payment method expires, is declined, or otherwise becomes unavailable, you are responsible for updating your payment information or providing a valid payment method before your next billing date. We are not responsible for failed payments resulting from an expired, declined, or otherwise invalid payment method.",
                },
            ],
        },
        {
            title: "Refunds and subscription review",
            blocks: [
                {
                    type: "paragraph",
                    text: "All subscription applications are subject to review after the subscription has been submitted.",
                },
                {
                    type: "paragraph",
                    text: "If a subscription is rejected following the review, the subscription payment will be refunded to the original payment method, subject to the processing time required by our payment partner.",
                },
                {
                    type: "paragraph",
                    text: "If the subscription is approved, the subscription payment is final and non-refundable.",
                },
                {
                    type: "paragraph",
                    text: "Where additional branches are included as part of the subscription, they are subject to the same subscription review and refund terms. No separate payment or refund applies specifically to additional branches.",
                },
                {
                    type: "paragraph",
                    text: "Except where a refund is expressly provided for under these Terms, subscription payments are non-refundable.",
                },
            ],
        },
        {
            title: "Upgrades & additional branches",
            blocks: [
                {
                    type: "paragraph",
                    text: "You may change your subscription plan or add branch capacity at any time through your subscription dashboard.",
                },
                {
                    type: "paragraph",
                    text: "Plan Upgrades. You may upgrade your subscription plan (for example, from Homecare to Hybrid). An upgrade may take effect immediately upon confirmation, or you may choose to have the upgraded plan take effect when your current subscription term ends, as made available through the subscription dashboard.",
                },
                {
                    type: "paragraph",
                    text: "Additional Branches. Each plan includes up to five (5) branches. If you add branches beyond the five (5) included branches, the additional branches will be billed as additional subscription capacity at the rate displayed to you before you confirm the purchase. Additional branch capacity will remain subject to the terms of your subscription and any applicable renewal charges.",
                },
            ],
        },
        {
            title: "Price changes",
            blocks: [
                {
                    type: "paragraph",
                    text: "We may change plan pricing going forward. A price change never affects a billing cycle you've already paid for.",
                },
                {
                    type: "paragraph",
                    text: "For your next renewal, we'll give you reasonable advance notice of a price increase beforehand.",
                },
                {
                    type: "paragraph",
                    text: "A price change will not result in any additional charge for an existing subscription or change the amount already paid for it.",
                },
            ],
        },
        {
            title: "Disclaimers & limitation of liability",
            blocks: [
                {
                    type: "paragraph",
                    text: "AMUMA is billing and operations software for care agencies; it does not itself provide medical or caregiving services, and a subscription doesn't make us a party to the care your agency delivers. To the extent permitted by law, our liability arising from your subscription is limited to the subscription fees you paid us in the 12 months before the claim arose.",
                },
            ],
        },
        {
            title: "Governing law",
            blocks: [
                {
                    type: "paragraph",
                    text: "These Terms are governed by the laws of the Republic of the Philippines. Any dispute arising from your subscription will be resolved in the courts of [City — e.g. Davao City], without prejudice to any mandatory consumer-protection venue.",
                },
            ],
        },
        {
            title: "Changes to these Terms & contact",
            blocks: [
                {
                    type: "paragraph",
                    text: "We may update these Terms as the product changes. For a material change — one that affects price, billing cycle, or cancellation rights — we'll notify subscribed agencies before it takes effect on your next renewal. Continuing your subscription after that point means you accept the update.",
                },
                {
                    type: "paragraph",
                    text: "Questions about a charge or these Terms may be directed to [amuma@gmail.com].",
                },
            ],
        },
    ],
};

const BOOKING_TERMS: PaymentTerms = {
    heading: "Booking & Payment Terms",
    lastUpdated: "September 19, 2026",
    sections: [
        {
            title: "Scope & acceptance",
            blocks: [
                {
                    type: "paragraph",
                    text: "These Booking & Payment Terms apply to any booking submitted through AMUMA for facility (Complete Admission or Pre-Admission) or homecare services. By submitting a booking and, where applicable, completing payment, you confirm the information provided is accurate and agree to these Terms and to the reservation fee, balance, and schedule shown to you at checkout.",
                },
            ],
        },
        {
            title: "Booking is a request, not a confirmed reservation",
            blocks: [
                {
                    type: "paragraph",
                    text: "Submitting a booking does not guarantee placement. Every booking, paid or unpaid, is reviewed by the branch's care team and may be approved or rejected. Your booking remains pending until the branch acts on it, and no bed, room, or service slot is guaranteed until the booking is approved.",
                },
            ],
        },
        {
            title: "When payment is required",
            blocks: [
                {
                    type: "list",
                    items: [
                        "Homecare bookings (Medical/ADL) and Facility Pre-Admission bookings do not require payment at the time of booking. These are submitted free of charge; payment is arranged later as part of the admission or service process.",
                        "Facility Complete Admission bookings require payment of a reservation fee before the booking is submitted. This fee is a percentage of the total contract amount, set by the branch, and is not the full cost of admission. The remaining balance is due separately as part of completing the admission.",
                    ],
                },
            ],
        },
        {
            title: "Payment methods",
            blocks: [
                {
                    type: "paragraph",
                    text: "Reservation fee payments are processed securely through our payment partner, Xendit, using Credit/Debit Card or GCash. Cash is not accepted as a booking payment method; cash payments, where applicable, are handled in person by the branch as part of the admission process.",
                },
            ],
        },
        {
            title: "Booking expiry",
            blocks: [
                {
                    type: "paragraph",
                    text: "Every booking has a review deadline, shown to you as the booking's valid-until date. If the branch does not approve or reject the booking before that deadline, the booking automatically expires. If a reservation fee was paid on an expired booking, it is automatically refunded to your original payment method.",
                },
            ],
        },
        {
            title: "Rejected bookings",
            blocks: [
                {
                    type: "paragraph",
                    text: "If the branch rejects your booking, any reservation fee paid is automatically refunded to your original payment method, subject to the processing time required by our payment partner.",
                },
            ],
        },
        {
            title: "Approved bookings",
            blocks: [
                {
                    type: "paragraph",
                    text: "If your booking is approved, the reservation fee already paid is credited toward your total contract amount, and the remaining balance becomes payable as part of completing the admission. The reservation fee itself is not separately refundable once the booking is approved, except as otherwise required by law.",
                },
            ],
        },
        {
            title: "Accuracy of information",
            blocks: [
                {
                    type: "paragraph",
                    text: "The total contract amount, reservation fee, and balance shown to you before payment are calculated from the plan, service, and dates you selected. Changing these details after payment may change the amount due and will be communicated to you before any additional charge.",
                },
            ],
        },
        {
            title: "Disclaimers & limitation of liability",
            blocks: [
                {
                    type: "paragraph",
                    text: "AMUMA facilitates booking and payment between you and the care branch; it is not itself the care provider. To the extent permitted by law, our liability arising from a booking is limited to the reservation fee you paid for that booking.",
                },
            ],
        },
        {
            title: "Governing law",
            blocks: [
                {
                    type: "paragraph",
                    text: "These Terms are governed by the laws of the Republic of the Philippines.",
                },
            ],
        },
        {
            title: "Changes to these Terms & contact",
            blocks: [
                {
                    type: "paragraph",
                    text: "Questions about a booking charge may be directed to [amuma@gmail.com].",
                },
            ],
        },
    ],
};

const PAYMENT_TERMS: Partial<Record<string, PaymentTerms>> = {
    subscription: SUBSCRIPTION_TERMS,
    booking: BOOKING_TERMS,
};

export function getPaymentTerms(
    context?: string | null,
): PaymentTerms | null {
    if (!context) return null;

    return PAYMENT_TERMS[context] ?? null;
}
