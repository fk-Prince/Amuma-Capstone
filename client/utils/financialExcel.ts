import type { SalesSummary } from "~/types/report";
import type { Row, Worksheet } from "exceljs";

export interface FinancialExcelLine {
    date: string;
    invoice: string;
    patient: string;
    description: string;
    amount: number;
    status: string;
}

export interface FinancialExcelMetric {
    name: string;
    value: number;
    format?: "currency" | "number";
}

export interface FinancialExcelOptions {
    fileName: string;
    branchName: string;
    branchImage: File | string | null | undefined;
    period: string;
    statusFilter: string;
    searchFilter: string;
    summary: SalesSummary | null;
    rows: FinancialExcelLine[];
    total: number;
}

const colors = {
    navy: "FF0A2857",
    teal: "FF3182ED",
    tealDark: "FF1E68D1",
    sky: "FFEAF2FE",
    pale: "FFF5F9FC",
    mint: "FFD5E5FD",
    amber: "FFFFF4D6",
    rose: "FFFCE8E8",
    blue: "FFD5E5FD",
    gray: "FFF0F3F7",
    slate: "FF60758A",
    white: "FFFFFFFF",
    border: "FFD8E5ED",
};

const currencyFormat = '"PHP "#,##0.00;[Red]-"PHP "#,##0.00';

function applyPrimaryPalette() {
    const colorFromCss = (token: string, fallback: string) => {
        const channels = getComputedStyle(document.documentElement)
            .getPropertyValue(`--${token}`)
            .trim()
            .split(/\s+/)
            .map(Number);
        if (channels.length < 3 || channels.some((channel) => !Number.isFinite(channel))) {
            return fallback;
        }
        return `FF${channels
            .slice(0, 3)
            .map((channel) => Math.round(channel).toString(16).padStart(2, "0"))
            .join("")
            .toUpperCase()}`;
    };

    colors.navy = colorFromCss("primary-900", colors.navy);
    colors.teal = colorFromCss("primary-500", colors.teal);
    colors.tealDark = colorFromCss("primary-600", colors.tealDark);
    colors.sky = colorFromCss("primary-50", colors.sky);
    colors.mint = colorFromCss("primary-100", colors.mint);
    colors.blue = colors.mint;
}

async function branchImageData(source: FinancialExcelOptions["branchImage"]) {
    if (!source) return null;

    try {
        const blob =
            typeof source === "string"
                ? await fetch(source).then((response) => {
                    if (!response.ok) throw new Error("Branch image unavailable");
                    return response.blob();
                })
                : source;
        const bitmap = await createImageBitmap(blob);
        const canvas = document.createElement("canvas");
        canvas.width = bitmap.width;
        canvas.height = bitmap.height;
        const context = canvas.getContext("2d");
        if (!context) return null;
        context.drawImage(bitmap, 0, 0);
        bitmap.close();

        return canvas.toDataURL("image/png");
    } catch {
        // Keep the export usable if the branch image cannot be fetched or decoded.
        return null;
    }
}

function styleTableHeader(row: Row) {
    row.height = 27;
    row.eachCell((cell) => {
        cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: colors.tealDark } };
        cell.font = { bold: true, color: { argb: colors.white }, size: 10 };
        cell.alignment = { vertical: "middle", wrapText: true };
        cell.border = { bottom: { style: "medium", color: { argb: colors.tealDark } } };
    });
}

function styleBodyRow(row: Row, fill: string) {
    row.height = 23;
    row.eachCell((cell) => {
        cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: fill } };
        cell.font = { color: { argb: colors.navy }, size: 10 };
        cell.alignment = { vertical: "middle", wrapText: true };
        cell.border = { bottom: { style: "hair", color: { argb: colors.border } } };
    });
}

function brandSheet(
    sheet: Worksheet,
    options: FinancialExcelOptions,
    imageId: number | null,
) {
    sheet.properties.defaultRowHeight = 21;
    sheet.views = [{ state: "frozen", ySplit: 7, showGridLines: false }];
    sheet.pageSetup = { orientation: "landscape", fitToWidth: 1, fitToHeight: 0 };
    sheet.columns = [
        { width: 20 },
        { width: 22 },
        { width: 25 },
        { width: 44 },
        { width: 18 },
        { width: 20 },
    ];
    sheet.getRow(1).height = 25;
    sheet.getRow(2).height = 25;
    sheet.getRow(3).height = 22;
    sheet.getRow(4).height = 22;
    sheet.getRow(5).height = 22;
    sheet.mergeCells("B1:F2");
    sheet.mergeCells("B3:F3");
    sheet.mergeCells("B4:F4");
    sheet.mergeCells("B5:F5");

    const title = sheet.getCell("B1");
    title.value = `${options.branchName}  |  Financial Report`;
    title.font = { bold: true, size: 19, color: { argb: colors.white } };
    title.alignment = { vertical: "middle", horizontal: "left" };
    title.fill = { type: "pattern", pattern: "solid", fgColor: { argb: colors.teal } };
    for (const rowNo of [1, 2]) {
        for (const colNo of [2, 3, 4, 5, 6]) {
            sheet.getCell(rowNo, colNo).fill = {
                type: "pattern",
                pattern: "solid",
                fgColor: { argb: colors.teal },
            };
        }
    }

    const metadata = [
        [3, `Branch: ${options.branchName}`],
        [4, `Period: ${options.period} | Status: ${options.statusFilter}`],
        [5, `Search: ${options.searchFilter}`],
    ] as const;
    for (const [rowNo, value] of metadata) {
        const cell = sheet.getCell(rowNo, 2);
        cell.value = value;
        cell.font = {
            color: { argb: rowNo === 3 ? colors.tealDark : colors.slate },
            bold: rowNo === 3,
            size: 10,
        };
        cell.alignment = { vertical: "middle", horizontal: "left" };
        cell.fill = {
            type: "pattern",
            pattern: "solid",
            fgColor: { argb: rowNo === 3 ? colors.mint : colors.sky },
        };
        for (const colNo of [3, 4, 5, 6]) {
            sheet.getCell(rowNo, colNo).fill = {
                type: "pattern",
                pattern: "solid",
                fgColor: { argb: rowNo === 3 ? colors.mint : colors.sky },
            };
        }
    }

    if (imageId !== null) {
        sheet.addImage(imageId, {
            tl: { col: 0.08, row: 0.08 },
            ext: { width: 84, height: 68 },
        });
    }
}

function addTableHeader(sheet: Worksheet, values: string[]) {
    sheet.addRow([]);
    const header = sheet.addRow(values);
    styleTableHeader(header);
}

export async function downloadFinancialExcel(options: FinancialExcelOptions) {
    applyPrimaryPalette();
    const ExcelJS = await import("exceljs");
    const workbook = new ExcelJS.Workbook();
    workbook.creator = "Amuma";
    workbook.subject = `Financial report for ${options.branchName}`;
    workbook.title = `${options.branchName} Financial Report`;
    workbook.created = new Date();

    let imageId: number | null = null;
    const image = await branchImageData(options.branchImage);
    if (image) {
        imageId = workbook.addImage({ base64: image, extension: "png" });
    }

    const summarySheet = workbook.addWorksheet("Financial Report", {
        properties: { tabColor: { argb: colors.teal } },
    });
    brandSheet(summarySheet, options, imageId);
    const metrics: FinancialExcelMetric[] = [
        { name: "Gross Sales", value: Number(options.summary?.gross_sales ?? 0), format: "currency" },
        { name: "Accounts Receivable", value: Number(options.summary?.accounts_receivable ?? 0), format: "currency" },
        { name: "Refunded", value: Number(options.summary?.total_refunded ?? 0), format: "currency" },
        { name: "Bad Debt", value: Number(options.summary?.bad_debt ?? 0), format: "currency" },
        { name: "Invoices", value: Number(options.summary?.invoice_count ?? 0), format: "number" },
    ];
    summarySheet.views = [{ state: "frozen", ySplit: 5, showGridLines: false }];
    const addMetricCard = (
        startColumn: number,
        endColumn: number,
        labelRow: number,
        valueRow: number,
        metric: FinancialExcelMetric,
    ) => {
        const first = summarySheet.getColumn(startColumn).letter;
        const last = summarySheet.getColumn(endColumn).letter;
        summarySheet.mergeCells(`${first}${labelRow}:${last}${labelRow}`);
        summarySheet.mergeCells(`${first}${valueRow}:${last}${valueRow + 1}`);

        const label = summarySheet.getCell(labelRow, startColumn);
        label.value = metric.name.toUpperCase();
        label.fill = { type: "pattern", pattern: "solid", fgColor: { argb: colors.tealDark } };
        label.font = { bold: true, size: 10, color: { argb: colors.white } };
        label.alignment = { vertical: "middle", horizontal: "left" };

        const value = summarySheet.getCell(valueRow, startColumn);
        value.value = metric.value;
        value.numFmt = metric.format === "number" ? "#,##0" : currencyFormat;
        value.fill = { type: "pattern", pattern: "solid", fgColor: { argb: colors.sky } };
        value.font = { bold: true, size: 19, color: { argb: colors.navy } };
        value.alignment = { vertical: "middle", horizontal: "left" };
        for (let col = startColumn; col <= endColumn; col += 1) {
            for (let row = labelRow; row <= valueRow + 1; row += 1) {
                summarySheet.getCell(row, col).border = {
                    top: { style: "thin", color: { argb: colors.white } },
                    bottom: { style: "thin", color: { argb: colors.white } },
                    left: { style: "thin", color: { argb: colors.white } },
                    right: { style: "thin", color: { argb: colors.white } },
                };
            }
        }
    };

    summarySheet.getRow(7).height = 24;
    summarySheet.getRow(8).height = 28;
    summarySheet.getRow(9).height = 28;
    summarySheet.getRow(11).height = 24;
    summarySheet.getRow(12).height = 28;
    summarySheet.getRow(13).height = 28;
    addMetricCard(1, 2, 7, 8, metrics[0]!);
    addMetricCard(3, 4, 7, 8, metrics[1]!);
    addMetricCard(5, 6, 7, 8, metrics[2]!);
    addMetricCard(1, 3, 11, 12, metrics[3]!);
    addMetricCard(4, 6, 11, 12, metrics[4]!);

    const note = summarySheet.getRow(15);
    summarySheet.mergeCells("A15:F15");
    note.getCell(1).value =
        "Summary values follow the selected period. Status and search filters apply to the detail sheet.";
    note.getCell(1).font = { italic: true, color: { argb: colors.slate }, size: 10 };
    note.getCell(1).alignment = { wrapText: true, vertical: "middle" };
    note.height = 30;

    const dataSheet = workbook.addWorksheet("Financial Data Input", {
        properties: { tabColor: { argb: "FF31A879" } },
    });
    brandSheet(dataSheet, options, imageId);
    addTableHeader(dataSheet, ["Date", "Invoice No.", "Patient", "Description", "Amount", "Status"]);
    options.rows.forEach((item, index) => {
        const row = dataSheet.addRow([
            item.date,
            item.invoice,
            item.patient,
            item.description,
            Number(item.amount ?? 0),
            item.status,
        ]);
        styleBodyRow(row, index % 2 ? colors.pale : colors.sky);
        row.getCell(5).numFmt = currencyFormat;
        row.getCell(5).alignment = { horizontal: "right", vertical: "middle" };
        const statusCell = row.getCell(6);
        const statusKey = item.status.toLowerCase();
        const statusColor =
            statusKey === "paid"
                ? "FF17845D"
                : statusKey.includes("partial")
                    ? "FF2765B0"
                    : statusKey === "void"
                        ? "FFB4233A"
                        : statusKey.includes("written")
                            ? colors.slate
                            : "FFA86100";
        statusCell.font = { bold: true, color: { argb: statusColor }, size: 10 };
        const statusFill =
            statusKey === "paid"
                ? colors.mint
                : statusKey.includes("partial")
                    ? colors.blue
                    : statusKey === "void"
                        ? colors.rose
                        : statusKey.includes("written")
                            ? colors.gray
                            : colors.amber;
        statusCell.fill = {
            type: "pattern",
            pattern: "solid",
            fgColor: { argb: statusFill },
        };
    });
    const totalRow = dataSheet.addRow(["TOTAL", "", "", "", options.total, ""]);
    totalRow.height = 27;
    totalRow.eachCell((cell) => {
        cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: colors.tealDark } };
        cell.font = { bold: true, color: { argb: colors.white } };
        cell.border = { top: { style: "medium", color: { argb: colors.teal } } };
    });
    totalRow.getCell(5).numFmt = currencyFormat;
    totalRow.getCell(5).alignment = { horizontal: "right", vertical: "middle" };
    dataSheet.autoFilter = { from: "A7", to: `F${Math.max(7, totalRow.number - 1)}` };

    const settingsSheet = workbook.addWorksheet("Key Metric Settings", {
        properties: { tabColor: { argb: "FFE7A83E" } },
    });
    brandSheet(settingsSheet, options, imageId);
    settingsSheet.views = [{ state: "frozen", ySplit: 7, showGridLines: false }];
    addTableHeader(settingsSheet, ["Metric Name", "Definition"]);
    settingsSheet.getColumn(2).width = 76;
    const definitions = [
        ["Gross Sales", "Sales amount of non-void invoices issued in the selected period."],
        ["Accounts Receivable", "Outstanding balance for invoices issued in the selected period."],
        ["Refunded", "Completed refunds issued in the selected period."],
        ["Bad Debt", "Outstanding balance written off in the selected period."],
        ["Invoices", "Count of non-void invoices issued in the selected period."],
        ["Status and search filters", "These filters apply to the Financial Data Input sheet; summary cards are based on the selected date period."],
    ];
    definitions.forEach((definition, index) => {
        const row = settingsSheet.addRow(definition);
        styleBodyRow(row, index % 2 ? colors.pale : colors.sky);
        row.getCell(1).font = { bold: true, color: { argb: colors.tealDark }, size: 10 };
        row.getCell(2).alignment = { vertical: "middle", wrapText: true };
        row.height = 31;
    });

    const buffer = await workbook.xlsx.writeBuffer();
    const file = new Blob([buffer], {
        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    });
    const url = URL.createObjectURL(file);
    const link = document.createElement("a");
    link.href = url;
    link.download = `${options.fileName}.xlsx`;
    link.click();
    URL.revokeObjectURL(url);
}
