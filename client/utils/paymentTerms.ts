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
    lastUpdated: "November 11, 2026",
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
                    text: "By selecting a plan, entering payment details, and clicking Start free trial or Confirm & Pay, you agree to be charged according to these Terms and to the pricing, billing cycle, and payment method you selected at checkout. If you are subscribing on behalf of an agency, you confirm you're authorized to bind that agency to these Terms.",
                },
            ],
        },
        {
            title: "Plans & pricing",
            blocks: [
                {
                    type: "paragraph",
                    text: "AMUMA offers three plans: Homecare Services, In-house Facility, and Hybrid, which includes both. Each plan comes in two sizes:",
                },
                {
                    type: "list",
                    items: [
                        "Small-Medium Enterprise, which covers one (1) branch.",
                        "Enterprise, which covers up to ten (10) branches.",
                    ],
                },
                {
                    type: "paragraph",
                    text: "The price of your plan, and of any additional branch, is shown to you before you confirm.",
                },
            ],
        },
        {
            title: "Free trial & billing cycle",
            blocks: [
                {
                    type: "paragraph",
                    text: "Every new subscription starts with a 2-week free trial. A card is required to start it, but nothing is charged during the trial. When the trial ends, your card is charged for your plan's first 12 months, unless you cancel the trial before then. If that charge fails, your branches become view-only until the plan is paid.",
                },
                {
                    type: "paragraph",
                    text: "All plans are billed yearly. Your paid subscription period runs for 12 months from the day it starts.",
                },
                {
                    type: "paragraph",
                    text: "You can renew your subscription from 7 days before it ends; a renewal adds 12 months to your current end date. If you save a card and turn on auto-renew, we charge that card for the renewal automatically. A subscription that is not renewed expires on its end date, and its branches become view-only until it is renewed.",
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
                    text: "You may cancel during the free trial. Anything you paid ahead during the trial is refunded in full to the original payment method.",
                },
                {
                    type: "paragraph",
                    text: "Each additional branch is paid for and reviewed on its own. If an additional branch is rejected, its payment is refunded to the original payment method. Once it is approved, that payment is non-refundable.",
                },
                {
                    type: "paragraph",
                    text: "Except where a refund is expressly provided for under these Terms, subscription payments are non-refundable.",
                },
            ],
        },
        {
            title: "Upgrades, branch plans & additional branches",
            blocks: [
                {
                    type: "paragraph",
                    text: "You manage your plan and branches through Manage Subscription in your dashboard.",
                },
                {
                    type: "paragraph",
                    text: "Plan upgrades. You may upgrade your plan (for example, from Homecare to Hybrid). An upgrade can start right away, charged for the months left in your current term, or start at your next renewal. A downgrade takes effect only at renewal.",
                },
                {
                    type: "paragraph",
                    text: "Branch plans. A branch in one of your included slots uses your subscription's plan. On a Hybrid subscription you may choose Homecare Services, In-house Facility, or Hybrid for each included branch and change it at any time at no charge. If you move from Hybrid to another plan, those branches follow the new plan.",
                },
                {
                    type: "paragraph",
                    text: "Additional branches. When your included slots are used, or you need a plan your subscription doesn't cover, you may add an additional branch on a paid subscription. You choose its plan when you buy it, and that plan can't be changed afterwards. It is charged at that plan's yearly branch price, prorated for the months left in your current term, and renews at its full yearly branch price together with your subscription.",
                },
                {
                    type: "paragraph",
                    text: "Deactivating a branch. You may deactivate a branch, which makes it view-only. Reactivating an additional branch may require a payment for the months left in your current term; the amount is shown before you pay.",
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
                    text: "These Terms are governed by the laws of the Republic of the Philippines. Any dispute arising from your subscription will be resolved in the courts of Davao City, without prejudice to any mandatory consumer-protection venue.",
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
                    text: "Questions about a charge or these Terms may be directed to amuma@gmail.com.",
                },
            ],
        },
    ],
};

const BOOKING_TERMS: PaymentTerms = {
    heading: "Booking & Payment Terms",
    lastUpdated: "October 14, 2026",
    sections: [
        {
            title: "Scope & acceptance",
            blocks: [
                {
                    type: "paragraph",
                    text: "These Booking & Payment Terms apply to any booking submitted through AMUMA for facility (Complete Admission or Pre-Admission) or homecare services. By submitting a booking and, where applicable, completing payment, you confirm the information provided is accurate and agree to these Terms and to the reservation fee or downpayment, balance, and schedule shown to you at checkout.",
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
                        "Facility Pre-Admission bookings do not require payment at the time of booking. These are submitted free of charge; payment is arranged later as part of the admission process.",
                        "Facility Complete Admission bookings require payment of a reservation fee before the booking is submitted. This fee is a percentage of the total contract amount, set by the branch, and is not the full cost of admission. The remaining balance is due separately as part of completing the admission.",
                        "Homecare bookings (Medical/ADL) require payment of a downpayment before the booking is submitted. The downpayment is a percentage of the total service amount, set by the branch, and is not the full cost of the service. The remaining balance is due separately.",
                    ],
                },
            ],
        },
        {
            title: "Payment methods",
            blocks: [
                {
                    type: "paragraph",
                    text: "Reservation fee and downpayment payments are processed securely through our payment partner, Xendit, using Credit/Debit Card or GCash. Cash is not accepted as a booking payment method; cash payments, where applicable, are handled in person by the branch as part of the admission process.",
                },
            ],
        },
        {
            title: "Booking expiry",
            blocks: [
                {
                    type: "paragraph",
                    text: "Every booking has a review deadline, shown to you as the booking's valid-until date. If the branch does not approve or reject the booking before that deadline, the booking automatically expires. If a reservation fee or downpayment was paid on an expired booking, it is automatically refunded to your original payment method.",
                },
            ],
        },
        {
            title: "Rejected bookings",
            blocks: [
                {
                    type: "paragraph",
                    text: "If the branch rejects your booking, any reservation fee or downpayment paid is automatically refunded to your original payment method, subject to the processing time required by our payment partner.",
                },
            ],
        },
        {
            title: "Approved bookings",
            blocks: [
                {
                    type: "paragraph",
                    text: "If your booking is approved, the reservation fee or downpayment already paid is credited toward your total amount, and the remaining balance becomes payable as part of completing the admission or service. The reservation fee or downpayment itself is not separately refundable once the booking is approved, except as otherwise required by law.",
                },
            ],
        },
        {
            title: "Accuracy of information",
            blocks: [
                {
                    type: "paragraph",
                    text: "The total amount, reservation fee or downpayment, and balance shown to you before payment are calculated from the plan, service, and dates you selected. Changing these details after payment may change the amount due and will be communicated to you before any additional charge.",
                },
            ],
        },
        {
            title: "Disclaimers & limitation of liability",
            blocks: [
                {
                    type: "paragraph",
                    text: "AMUMA facilitates booking and payment between you and the care branch; it is not itself the care provider. To the extent permitted by law, our liability arising from a booking is limited to the reservation fee or downpayment you paid for that booking.",
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
                    text: "Questions about a booking charge may be directed to amuma@gmail.com.",
                },
            ],
        },
    ],
};

const BALANCE_TERMS: PaymentTerms = {
    heading: "Online Payment Terms",
    lastUpdated: "September 20, 2026",
    sections: [
        {
            title: "Scope",
            blocks: [
                {
                    type: "paragraph",
                    text: "These terms apply to any payment you make through the AMUMA family portal on behalf of a resident under your account, including homecare and facility invoices.",
                },
            ],
        },
        {
            title: "Accepted payment methods",
            blocks: [
                {
                    type: "paragraph",
                    text: "Payments made through the portal may be completed via GCash or Credit/Debit Card. Cash payments are accepted only in person at the branch and are recorded by branch staff — they are not processed through the portal.",
                },
            ],
        },
        {
            title: "Payment authorization",
            blocks: [
                {
                    type: "paragraph",
                    text: "By submitting a payment, you confirm that you are authorized to pay on behalf of the resident and that the payment method used belongs to you or that you have permission to use it.",
                },
            ],
        },
        {
            title: "Invoice accuracy",
            blocks: [
                {
                    type: "paragraph",
                    text: "Please review the invoice details (services, dates, and amount due) before paying. If you believe an invoice contains an error, contact the branch to request a review before submitting payment — adjustments cannot be applied to an invoice after it has been paid in full.",
                },
            ],
        },
        {
            title: "Processing time",
            blocks: [
                {
                    type: "paragraph",
                    text: "Payments are typically reflected on your account immediately. In rare cases (e.g., payment gateway delays), it may take up to 24 hours for a payment to be confirmed. If a payment does not reflect within this window, contact support before attempting to pay again.",
                },
            ],
        },
        {
            title: "Invoice adjustments",
            blocks: [
                {
                    type: "paragraph",
                    text: "Charges may be adjusted by the branch to correct billing errors, apply discounts, or account for schedule changes (e.g., an accommodation change, a shortened service, or a late/incomplete visit). Adjustments are applied to the invoice before payment is due; they do not affect invoices you have already paid in full.",
                },
            ],
        },
        {
            title: "Refunds and credits",
            blocks: [
                {
                    type: "list",
                    items: [
                        "A refund reflects credit created on your account, most commonly from an invoice adjustment made after a payment.",
                        "Credit on your account can be applied automatically to a future invoice or withdrawn as a payout upon request.",
                        "A withdrawal request is reviewed by branch/cashier staff and may be approved or rejected; approved withdrawals are paid out via the method selected at request time.",
                        "Refunds and withdrawals are not guaranteed to be instant and follow the branch's review process.",
                    ],
                },
            ],
        },
        {
            title: "Non-refundable charges",
            blocks: [
                {
                    type: "paragraph",
                    text: "Services already rendered (completed homecare visits, facility accommodation already provided) are not eligible for refund except where an invoice adjustment has reduced the billed amount for that period.",
                },
            ],
        },
        {
            title: "Data and security",
            blocks: [
                {
                    type: "paragraph",
                    text: "Payment details are processed through our payment gateway partner and are not stored on AMUMA's servers. By paying through the portal, you consent to your payment information being shared with the gateway solely to process the transaction.",
                },
            ],
        },
        {
            title: "Changes to these terms",
            blocks: [
                {
                    type: "paragraph",
                    text: "These terms may be updated from time to time. Continued use of the payment feature after changes take effect constitutes acceptance of the updated terms.",
                },
            ],
        },
    ],
};

const PAYMENT_TERMS: Partial<Record<string, PaymentTerms>> = {
    subscription: SUBSCRIPTION_TERMS,
    booking: BOOKING_TERMS,
    balance: BALANCE_TERMS,
};

export function getPaymentTerms(
    context?: string | null,
): PaymentTerms | null {
    if (!context) return null;

    return PAYMENT_TERMS[context] ?? null;
}
