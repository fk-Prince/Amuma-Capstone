
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


export function formatCurrency(value?: number | string | null): string {
    if (value === undefined || value === null || isNaN(Number(value))) {
        return "—";
    }
    return `₱${Number(value).toLocaleString("en-PH", { minimumFractionDigits: 2 })}`;
}