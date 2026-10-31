export const VAT_RATE = 12;

const round2 = (value: number) => Math.round((value + Number.EPSILON) * 100) / 100;

export function withVat(subtotal: number, rate = VAT_RATE) {
    const base = round2(Number(subtotal) || 0);
    const vat = base > 0 && rate > 0 ? round2((base * rate) / 100) : 0;

    return { subtotal: base, rate, vat, total: round2(base + vat) };
}
