export const PAYMENT_RETURN_KEY = "payment_return_to";

export function rememberPaymentReturn(path: string) {
    try {
        sessionStorage.setItem(PAYMENT_RETURN_KEY, path);
    } catch {}
}
