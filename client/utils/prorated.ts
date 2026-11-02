import { formatAmount } from "~/utils/currency";

export const proratedHelp = (yearlyPrice: number, monthsLeft: number) =>
    `Prorated means you only pay for the time left on your subscription: ₱${formatAmount(yearlyPrice)} ÷ 12 × ${monthsLeft} ${monthsLeft === 1 ? "month" : "months"} left. After that, it renews with the subscription at the full yearly price.`;
