export type ExcelCell =
    | string
    | number
    | null
    | undefined
    | { t: "n"; v: number; z: string };

export interface ExcelSheet {
    name: string;
    rows: ExcelCell[][];
}

export function moneyCell(value: unknown): ExcelCell {
    const amount = Number(value ?? 0);

    return {
        t: "n",
        v: Number.isFinite(amount) ? amount : 0,
        z: "#,##0.00",
    };
}

function sheetName(name: string, taken: Set<string>) {
    const base = name.replace(/[\\/?*[\]:]/g, " ").trim().slice(0, 31) || "Sheet";
    let candidate = base;
    let counter = 2;

    while (taken.has(candidate.toLowerCase())) {
        const suffix = ` ${counter++}`;
        candidate = base.slice(0, 31 - suffix.length) + suffix;
    }

    taken.add(candidate.toLowerCase());

    return candidate;
}

function columnWidths(rows: ExcelCell[][]) {
    const widths: number[] = [];

    for (const row of rows) {
        row.forEach((cell, index) => {
            const text =
                cell && typeof cell === "object"
                    ? cell.v.toLocaleString("en-US", { minimumFractionDigits: 2 })
                    : String(cell ?? "");

            widths[index] = Math.min(
                60,
                Math.max(widths[index] ?? 8, text.length + 2),
            );
        });
    }

    return widths.map((wch) => ({ wch }));
}

export function fileSafe(value: string) {
    return value.replace(/[^\w\-]+/g, "_").replace(/^_+|_+$/g, "") || "export";
}

export async function downloadExcel(fileName: string, sheets: ExcelSheet[]) {
    const XLSX = await import("xlsx");
    const book = XLSX.utils.book_new();
    const taken = new Set<string>();

    for (const sheet of sheets) {
        const rows = sheet.rows.map((row) => row.map((cell) => cell ?? ""));
        const worksheet = XLSX.utils.aoa_to_sheet(rows);

        worksheet["!cols"] = columnWidths(sheet.rows);

        XLSX.utils.book_append_sheet(
            book,
            worksheet,
            sheetName(sheet.name, taken),
        );
    }

    XLSX.writeFile(book, `${fileName}.xlsx`);
}
