# StockFlow – Opening CSV Reports in Microsoft Excel

StockFlow exports two types of CSV reports:

- **Products:** product details, prices, stock levels, and inventory values.
- **Stock movements:** dates, products, movement types, quantities, and operators.

These reports are **CSV files, not native Excel `.xlsx` workbooks**. A CSV file contains data but does not store Excel formatting, column widths, or colors.

## CSV format used by StockFlow

| Setting | Value |
| --- | --- |
| File type | `.csv` |
| Character encoding | UTF-8 |
| Field delimiter | Semicolon (`;`) |
| Text qualifier | Double quotation mark (`"`) when required |

On some computers, double-clicking a CSV file opens all fields in a single Excel column, or special characters such as `ă`, `â`, `î`, `ș`, and `ț` appear incorrectly. This depends on Excel and Windows regional settings; it does **not** necessarily mean the export is damaged.

## Recommended method: import the CSV into Excel

1. Open **Microsoft Excel**.
2. Create a **Blank workbook**.
3. Open the **Data** tab.
4. Select **From Text/CSV** (or **Get Data → From File → From Text/CSV**, depending on your Excel version).
5. Select the CSV report downloaded from StockFlow.
6. In the import preview, choose:
   - **File Origin / Encoding:** `65001: Unicode (UTF-8)`
   - **Delimiter:** `Semicolon` (`;`)
7. Confirm that product names, quantities, prices, and other fields appear in separate columns, with correct special characters.
8. Click **Load**.

Repeat these steps for either the products report or the stock-movements report.

## Save a reusable Excel workbook

After importing the CSV:

1. Select **File → Save As**.
2. Choose **Excel Workbook (`*.xlsx`)** as the file type.
3. Save the workbook.

The resulting `.xlsx` file can be opened directly in Excel later, without repeating the CSV import.

## Troubleshooting

### All data appears in column A

Excel has not recognized the semicolon separator. Import the file through **Data → From Text/CSV** and explicitly select **Semicolon**.

### Romanian characters look incorrect

Use **65001: Unicode (UTF-8)** as the file origin/encoding when importing. Avoid repeatedly opening and saving the incorrectly decoded CSV file, as this can damage the displayed characters.

### Excel warns about possible data loss

This warning is normal when saving in CSV format, which cannot retain spreadsheet formatting. If you need formatting, save the imported data as **Excel Workbook (`.xlsx`)**.

### Prices or numbers are interpreted incorrectly

Excel may apply local decimal and thousands separators. Check numeric columns during import. If needed, keep the columns as text first, then convert them using the correct locale settings.

## Important distinction

**StockFlow downloads CSV reports.** Saving an imported CSV as `.xlsx` is an operation performed in Microsoft Excel. StockFlow does not currently promise automatic `.xlsx` generation.

Return to the [main StockFlow README](README.md).
