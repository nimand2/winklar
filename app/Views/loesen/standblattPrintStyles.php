    <style>
        @page {
            size: A5 landscape;
            margin: 7mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #e9eef4;
            color: #050505;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            line-height: 1.2;
        }

        .screen-actions {
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 12px;
        }

        .screen-actions a,
        .screen-actions button {
            border: 1px solid #6b7280;
            border-radius: 6px;
            background: #fff;
            color: #111827;
            cursor: pointer;
            font: inherit;
            padding: 7px 12px;
            text-decoration: none;
        }

        .sheet {
            width: 210mm;
            min-height: 148mm;
            margin: 0 auto 16px;
            padding: 7mm;
            background: #fff;
            border: 1px solid #222;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.18);
        }

        .header {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 58mm 42mm;
            gap: 6mm;
            border-bottom: 1px solid #111;
            padding-bottom: 2mm;
        }

        .club {
            font-size: 9pt;
            margin-bottom: 1mm;
        }

        .title {
            font-size: 19pt;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 2mm;
        }

        .person-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 26mm;
            gap: 1mm 5mm;
        }

        .header > div,
        .person-grid > div,
        .shot-group {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .meta {
            display: grid;
            gap: 1mm;
            text-align: right;
        }

        .barcode {
            align-self: start;
            padding-top: 1mm;
            text-align: center;
        }

        .barcode-svg {
            display: block;
            width: 58mm;
            height: 17mm;
        }

        .barcode-error {
            border: 1px solid #991b1b;
            color: #991b1b;
            font-size: 7.5pt;
            font-weight: 700;
            padding: 2mm;
            text-align: left;
        }

        .label {
            font-weight: 700;
        }

        .section-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6mm;
            min-height: 14mm;
            padding: 2mm 0;
        }

        .stich-box {
            display: grid;
            gap: 1mm;
        }

        .shoot-grid {
            display: grid;
            grid-template-columns: repeat(<?= htmlspecialchars((string) $gridGroups) ?>, minmax(0, 1fr));
            gap: 1mm;
            margin-top: 6mm;
        }

        .shot-group {
            display: grid;
            gap: 1mm;
            align-content: start;
        }

        .shot-group-title {
            display: grid;
            gap: 0.6mm;
            grid-column: 1 / -1;
            min-height: 13mm;
            font-size: 7.5pt;
        }

        .shot-group-title strong {
            font-size: 8pt;
        }

        .shot-cell,
        .total-cell {
            height: 7.8mm;
            border: 1px solid #222;
            padding: 0.6mm 0.8mm;
            font-size: 7.5pt;
        }

        .total-cell {
            height: 8.5mm;
            text-align: center;
            font-weight: 700;
        }

        .footer {
            border-top: 1px solid #111;
            margin-top: 5mm;
            padding-top: 2mm;
        }

        .awards {
            display: grid;
            grid-template-columns: repeat(<?= htmlspecialchars((string) $gabenColumns) ?>, minmax(0, 1fr));
            gap: 2mm;
            text-align: center;
            font-size: 7.5pt;
        }

        .print-note {
            display: flex;
            justify-content: space-between;
            margin-top: 2mm;
            font-size: 7.5pt;
        }

        @media print {
            html,
            body {
                width: auto;
                height: auto;
                min-height: 0;
                margin: 0;
                padding: 0;
                background: #fff;
            }

            .screen-actions {
                display: none;
            }

            .sheet {
                display: flow-root;
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
                border: 0;
                box-shadow: none;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .shoot-grid {
                display: flex;
                align-items: flex-start;
            }

            .shot-group {
                flex: 1 1 0;
            }

            .header,
            .shot-group,
            .footer {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
