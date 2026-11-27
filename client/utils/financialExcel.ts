import type { Row, Worksheet } from "exceljs";
import type { SalesSummary } from "~/types/report";

export interface FinancialExcelLine {
    date: string;
    invoice: string;
    patient: string;
    description: string;
    amount: number;
    status: string;
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

interface BranchImage {
    base64: string;
    width: number;
    height: number;
}

interface EmbeddedImage extends BranchImage {
    id: number;
}

const currencyFormat = '"PHP "#,##0.00;[Red]-"PHP "#,##0.00';

async function branchImageData(
    source: FinancialExcelOptions["branchImage"],
): Promise<BranchImage | null> {
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
        const width = bitmap.width;
        const height = bitmap.height;
        const canvas = document.createElement("canvas");
        canvas.width = width;
        canvas.height = height;
        const context = canvas.getContext("2d");
        if (!context) {
            bitmap.close();
            return null;
        }

        context.drawImage(bitmap, 0, 0);
        bitmap.close();

        return { base64: canvas.toDataURL("image/png"), width, height };
    } catch {
        // Keep the export usable if the branch image cannot be fetched or decoded.
        return null;
    }
}

function styleTableHeader(row: Row) {
    row.height = 27;
    row.eachCell((cell) => {
        cell.font = { bold: true, size: 10 };
        cell.alignment = { vertical: "middle", wrapText: true };
        cell.border = { bottom: { style: "thin", color: { argb: "FF808080" } } };
    });
}

function styleBodyRow(row: Row) {
    row.height = 23;
    row.eachCell((cell) => {
        cell.font = { size: 10 };
        cell.alignment = { vertical: "middle", wrapText: true };
        cell.border = { bottom: { style: "hair", color: { argb: "FFD0D0D0" } } };
    });
}

function brandSheet(
    sheet: Worksheet,
    options: FinancialExcelOptions,
    image: EmbeddedImage | null,
) {
    sheet.properties.defaultRowHeight = 21;
    sheet.views = [{ state: "frozen", ySplit: 17, showGridLines: true }];
    sheet.pageSetup = { orientation: "landscape", fitToWidth: 1, fitToHeight: 0 };
    sheet.columns = [
        { width: 20 },
        { width: 22 },
        { width: 25 },
        { width: 44 },
        { width: 18 },
        { width: 20 },
    ];

    sheet.getRow(1).height = 32;
    sheet.getRow(2).height = 32;
    sheet.getRow(3).height = 22;
    sheet.getRow(4).height = 22;
    sheet.getRow(5).height = 22;
    sheet.mergeCells("B1:F2");
    sheet.mergeCells("B3:F3");
    sheet.mergeCells("B4:F4");
    sheet.mergeCells("B5:F5");

    const title = sheet.getCell("B1");
    title.value = options.branchName + "  |  Financial Report";
    title.font = { bold: true, size: 18 };
    title.alignment = { vertical: "middle", horizontal: "left" };

    const metadata = [
        [3, "Branch: " + options.branchName],
        [4, "Period: " + options.period + " | Status: " + options.statusFilter],
        [5, "Search: " + options.searchFilter],
    ] as const;
    for (const [rowNo, value] of metadata) {
        const cell = sheet.getCell(rowNo, 2);
        cell.value = value;
        cell.font = { bold: rowNo === 3, size: 10 };
        cell.alignment = { vertical: "middle", horizontal: "left" };
    }

    if (image) {
        sheet.addImage(image.id, {
            tl: { col: 0.08, row: 0.08 },
            ext: { width: image.width, height: image.height },
        });
    }
}

export async function downloadFinancialExcel(options: FinancialExcelOptions) {
    const ExcelJS = await import("exceljs");
    const workbook = new ExcelJS.Workbook();
    workbook.creator = "Amuma";
    workbook.subject = "Financial report for " + options.branchName;
    workbook.title = options.branchName + " Financial Report";
    workbook.created = new Date();

    const sheet = workbook.addWorksheet("Financial Data Input");
    const sourceImage = await branchImageData(options.branchImage);
    let image: EmbeddedImage | null = null;
    if (sourceImage) {
        const imageScale = Math.min(86 / sourceImage.height, 136 / sourceImage.width);
        image = {
            id: workbook.addImage({ base64: sourceImage.base64, extension: "png" }),
            base64: sourceImage.base64,
            width: sourceImage.width * imageScale,
            height: sourceImage.height * imageScale,
        };
    }
    brandSheet(sheet, options, image);

    const metrics = [
        { name: "Gross Sales", value: Number(options.summary?.gross_sales ?? 0), format: "currency" },
        { name: "Accounts Receivable", value: Number(options.summary?.accounts_receivable ?? 0), format: "currency" },
        { name: "Refunded", value: Number(options.summary?.total_refunded ?? 0), format: "currency" },
        { name: "Bad Debt", value: Number(options.summary?.bad_debt ?? 0), format: "currency" },
        { name: "Invoices", value: Number(options.summary?.invoice_count ?? 0), format: "number" },
    ];

    const addMetric = (
        startColumn: number,
        endColumn: number,
        labelRow: number,
        valueRow: number,
        metric: (typeof metrics)[number],
    ) => {
        const firstColumn = sheet.getColumn(startColumn).letter;
        const lastColumn = sheet.getColumn(endColumn).letter;
        sheet.mergeCells(`${firstColumn}${labelRow}:${lastColumn}${labelRow}`);
        sheet.mergeCells(`${firstColumn}${valueRow}:${lastColumn}${valueRow + 1}`);

        const label = sheet.getCell(labelRow, startColumn);
        label.value = metric.name.toUpperCase();
        label.font = { bold: true, size: 10 };
        label.alignment = { vertical: "middle" };

        const value = sheet.getCell(valueRow, startColumn);
        value.value = metric.value;
        value.numFmt = metric.format === "number" ? "#,##0" : currencyFormat;
        value.font = { bold: true, size: 18 };
        value.alignment = { vertical: "middle" };

        for (let column = startColumn; column <= endColumn; column += 1) {
            for (let row = labelRow; row <= valueRow + 1; row += 1) {
                sheet.getCell(row, column).border = {
                    top: { style: "thin", color: { argb: "FFD0D0D0" } },
                    bottom: { style: "thin", color: { argb: "FFD0D0D0" } },
                    left: { style: "thin", color: { argb: "FFD0D0D0" } },
                    right: { style: "thin", color: { argb: "FFD0D0D0" } },
                };
            }
        }
    };

    [24, 30, 30, 24, 30, 30].forEach((height, index) => {
        sheet.getRow([7, 8, 9, 11, 12, 13][index]!).height = height;
    });
    addMetric(1, 2, 7, 8, metrics[0]!);
    addMetric(3, 4, 7, 8, metrics[1]!);
    addMetric(5, 6, 7, 8, metrics[2]!);
    addMetric(1, 3, 11, 12, metrics[3]!);
    addMetric(4, 6, 11, 12, metrics[4]!);

    sheet.mergeCells("A15:F15");
    const summaryNote = sheet.getCell("A15");
    summaryNote.value = "KPIs reflect the selected date period; the invoice table uses the selected status and search filters.";
    summaryNote.font = { italic: true, size: 10 };
    summaryNote.alignment = { wrapText: true, vertical: "middle" };
    sheet.getRow(15).height = 26;

    sheet.addRow([]);
    const header = sheet.addRow([
        "Date",
        "Invoice No.",
        "Patient",
        "Description",
        "Amount",
        "Status",
    ]);
    styleTableHeader(header);

    options.rows.forEach((item) => {
        const row = sheet.addRow([
            item.date,
            item.invoice,
            item.patient,
            item.description,
            Number(item.amount ?? 0),
            item.status,
        ]);
        styleBodyRow(row);
        row.getCell(5).numFmt = currencyFormat;
        row.getCell(5).alignment = { horizontal: "right", vertical: "middle" };
    });

    const totalRow = sheet.addRow(["TOTAL", "", "", "", options.total, ""]);
    totalRow.height = 27;
    totalRow.eachCell((cell) => {
        cell.font = { bold: true };
        cell.border = { top: { style: "thin", color: { argb: "FF808080" } } };
    });
    totalRow.getCell(5).numFmt = currencyFormat;
    totalRow.getCell(5).alignment = { horizontal: "right", vertical: "middle" };
    sheet.autoFilter = { from: "A17", to: "F" + Math.max(17, totalRow.number - 1) };

    const buffer = await workbook.xlsx.writeBuffer();
    const file = new Blob([buffer], {
        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    });
    const url = URL.createObjectURL(file);
    const link = document.createElement("a");
    link.href = url;
    link.download = options.fileName + ".xlsx";
    link.click();
    URL.revokeObjectURL(url);
}
