
export function Currency() {
    return [
        {
            label: "Philippine Peso",
            value: "PHP",
            icon: "https://flagcdn.com/ph.svg",
        },
        // {
        //     label: "US Dollar",
        //     value: "USD",
        //     icon: "https://flagcdn.com/us.svg",
        // },
        // {
        //     label: "Euro",
        //     value: "EUR",
        //     icon: "https://flagcdn.com/eu.svg",
        // },
        // {
        //     label: "Japanese Yen",
        //     value: "JPY",
        //     icon: "https://flagcdn.com/jp.svg",
        // },
    ];;
}


export interface FormatCurrencyOptions {
    minimumFractionDigits?: number;
    maximumFractionDigits?: number;
    withSymbol?: boolean;
    treatMissingAsZero?: boolean;
    fallback?: string;
}

export function formatCurrency(
    value?: number | string | null,
    options: FormatCurrencyOptions = {},
): string {
    const {
        minimumFractionDigits = 2,
        maximumFractionDigits = 2,
        withSymbol = true,
        treatMissingAsZero = false,
        fallback = "—",
    } = options;

    const isMissing = value === undefined || value === null || value === "";

    if (isMissing && !treatMissingAsZero) {
        return fallback;
    }

    const num = Number(isMissing ? 0 : value);

    if (isNaN(num)) {
        return fallback;
    }

    const formatted = num.toLocaleString("en-PH", {
        minimumFractionDigits,
        maximumFractionDigits,
    });

    return withSymbol ? `₱${formatted}` : formatted;
}

export function formatAmount(
    value?: number | string | null,
    options: Omit<FormatCurrencyOptions, "withSymbol"> = {},
): string {
    return formatCurrency(value, { ...options, withSymbol: false });
}

export function formatPercent(value: number) {
    return `${Number((value * 100).toFixed(2))}%`;
}