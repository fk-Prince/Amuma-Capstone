import type { Row, Worksheet } from "exceljs";

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
    sheet.views = [{ state: "frozen", ySplit: 7, showGridLines: true }];
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
    sheet.autoFilter = { from: "A7", to: "F" + Math.max(7, totalRow.number - 1) };

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
