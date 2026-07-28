<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\EquipmentDisposal;
use App\Models\EquipmentReceivingRegister;
use App\Models\WorkshopEquipmentRegister;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\EquipmentController;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportExportController extends Controller
{
    // =========================================================================
    //  WORKSHOP
    // =========================================================================

    public function workshop(Request $request)
    {
        $data    = $this->buildWorkshopQuery($request)->get();
        $format  = $request->input('format', 'csv');
        $file    = 'workshop-register-' . now()->format('Y-m-d');
        $title   = 'ICT Workshop Equipment Register';
        $filters = $request->only(['status', 'priority', 'date_from', 'date_to', 'depot', 'technician', 'search']);

        return match ($format) {
            'csv'   => $this->csvDownload($data, $this->workshopHeaders(), fn($r) => $this->workshopRow($r), $file),
            'excel' => $this->excelDownload($data, $this->workshopHeaders(), fn($r) => $this->workshopRow($r), $title, $file),
            'word'  => $this->wordDownload($data, $this->workshopHeaders(), fn($r) => $this->workshopRow($r), $title, $file),
            'pdf'   => $this->pdfDownload('exports.workshop-pdf', compact('data', 'title', 'filters'), $file),
            default => abort(400, 'Unknown format'),
        };
    }

    private function buildWorkshopQuery(Request $request)
    {
        $user  = auth()->user();
        $query = WorkshopEquipmentRegister::with('technician', 'creator');

        if ($user->isTechnician()) {
            $query->where('technician_assigned', $user->id);
        }
        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('priority')) $query->where('priority_level', $request->priority);
        if ($request->filled('date_from')) $query->whereDate('date_time_received', '>=', $request->date_from);
        if ($request->filled('date_to'))   $query->whereDate('date_time_received', '<=', $request->date_to);
        if ($request->filled('depot'))     $query->where('depot_name', $request->depot);
        if ($request->filled('technician')) $query->where('technician_assigned', $request->technician);
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($sq) => $sq
                ->where('entry_job_number', 'like', "%{$q}%")
                ->orWhere('equipment_type', 'like', "%{$q}%")
                ->orWhere('serial_number_asset_tag', 'like', "%{$q}%")
                ->orWhere('depot_name', 'like', "%{$q}%")
                ->orWhere('department', 'like', "%{$q}%")
                ->orWhere('contact_person', 'like', "%{$q}%"));
        }
        return $query->oldest();
    }

    private function workshopHeaders(): array
    {
        return [
            'Job No.', 'Date Received', 'Equipment Type', 'Brand / Model',
            'Serial / Asset Tag', 'Department', 'Extension Number', 'Phone',
            'Nature of Fault', 'Priority', 'Technician', 'Status',
            'Due Date', 'Date Repair Completed', 'Date Collected', 'Repair Action Taken',
        ];
    }

    private function workshopRow($r): array
    {
        return [
            $r->entry_job_number,
            $r->date_time_received?->format('d/m/Y H:i') ?? '',
            $r->equipment_type,
            $r->brand_make_model ?? '',
            $r->serial_number_asset_tag ?? '',
            $r->department ?? '',
            $r->contact_person ?? '',
            $r->phone_number ?? '',
            $r->nature_of_fault,
            $r->priority_level,
            trim(($r->technician?->firstname ?? '') . ' ' . ($r->technician?->lastname ?? '')),
            $r->status,
            $r->due_date?->format('d/m/Y') ?? '',
            $r->date_repair_completed?->format('d/m/Y') ?? '',
            $r->date_collected?->format('d/m/Y') ?? '',
            $r->repair_action_taken ?? '',
        ];
    }

    // =========================================================================
    //  ALL EQUIPMENT (Workshop + Registry, combined)
    // =========================================================================

    public function equipment(Request $request)
    {
        $data    = EquipmentController::buildRows($request);
        $format  = $request->input('format', 'csv');
        $file    = 'equipment-' . now()->format('Y-m-d');
        $title   = 'ICT Equipment — Workshop & Registry';
        $filters = $request->only(['type', 'search']);

        return match ($format) {
            'csv'   => $this->csvDownload($data, $this->equipmentHeaders(), fn($r) => $this->equipmentRow($r), $file),
            'excel' => $this->excelDownload($data, $this->equipmentHeaders(), fn($r) => $this->equipmentRow($r), $title, $file),
            'word'  => $this->wordDownload($data, $this->equipmentHeaders(), fn($r) => $this->equipmentRow($r), $title, $file),
            'pdf'   => $this->pdfDownload('exports.equipment-pdf', compact('data', 'title', 'filters'), $file),
            default => abort(400, 'Unknown format'),
        };
    }

    private function equipmentHeaders(): array
    {
        return ['Source', 'Ref No.', 'Item / Equipment', 'Brand / Model', 'Serial / Asset Tag', 'Status', 'Available', 'Technician / Supplier', 'Date'];
    }

    private function equipmentRow($r): array
    {
        return [
            $r->source,
            $r->ref,
            $r->item,
            $r->brand ?? '',
            $r->serial ?? '',
            $r->status,
            $r->available ?? '',
            $r->handler ?? '',
            $r->date?->format('d/m/Y') ?? '',
        ];
    }

    // =========================================================================
    //  EQUIPMENT RECEIVING
    // =========================================================================

    public function equipmentReceiving(Request $request)
    {
        $query = EquipmentReceivingRegister::with('creator')
            ->withSum('inspections', 'qty_inspected')
            ->withSum('redistributions', 'qty_redistributed');

        if ($request->filled('status')) {
            // Filter by computed stock status using a WHERE-clause subquery (not HAVING —
            // comparing a plain column like qty_received inside HAVING without GROUP BY
            // fails under MySQL's ONLY_FULL_GROUP_BY mode).
            $inspectedSub = '(select coalesce(sum(qty_inspected), 0) from equipment_inspections '
                . 'where equipment_inspections.equipment_receiving_register_id = equipment_receiving_registers.id)';
            match ($request->status) {
                'pending'   => $query->whereRaw("{$inspectedSub} = 0"),
                'partial'   => $query->whereRaw("{$inspectedSub} > 0 AND {$inspectedSub} < equipment_receiving_registers.qty_received"),
                'inspected' => $query->whereRaw("{$inspectedSub} >= equipment_receiving_registers.qty_received"),
                default     => null,
            };
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($sq) => $sq
                ->where('cross_ref_no', 'like', "%{$q}%")
                ->orWhere('supplier', 'like', "%{$q}%")
                ->orWhere('item_description', 'like', "%{$q}%")
                ->orWhere('serial_number', 'like', "%{$q}%"));
        }

        $data    = $query->latest()->get();
        $format  = $request->input('format', 'csv');
        $file    = 'equipment-receiving-' . now()->format('Y-m-d');
        $title   = 'ICT Equipment Receiving, Inspection & Transfer Register';
        $filters = $request->only(['status', 'search']);

        return match ($format) {
            'csv'   => $this->csvDownload($data, $this->receivingHeaders(), fn($r) => $this->receivingRow($r), $file),
            'excel' => $this->excelDownload($data, $this->receivingHeaders(), fn($r) => $this->receivingRow($r), $title, $file),
            'word'  => $this->wordDownload($data, $this->receivingHeaders(), fn($r) => $this->receivingRow($r), $title, $file),
            'pdf'   => $this->pdfDownload('exports.receiving-pdf', compact('data', 'title', 'filters'), $file),
            default => abort(400, 'Unknown format'),
        };
    }

    private function receivingHeaders(): array
    {
        return [
            'Ref No.', 'Date Received', 'Supplier', 'Delivery Note', 'PO No.',
            'Item Description', 'Brand / Model', 'Qty Received',
            'Qty Inspected', 'Qty Redistributed', 'Available Stock', 'Stock Status',
            'Serial Numbers',
        ];
    }

    private function receivingRow($r): array
    {
        $inspected     = (int) ($r->inspections_sum_qty_inspected ?? 0);
        $redistributed = (int) ($r->redistributions_sum_qty_redistributed ?? 0);
        $available     = max(0, $r->qty_received - $redistributed);

        $statusMap = [
            $inspected === 0              => 'Pending Inspection',
            $inspected < $r->qty_received => 'Partially Inspected',
            true                          => 'Fully Inspected',
        ];

        return [
            $r->cross_ref_no,
            $r->date_received?->format('d/m/Y') ?? '',
            $r->supplier,
            $r->delivery_note_no ?? '',
            $r->po_no ?? '',
            $r->item_description,
            $r->brand_model ?? '',
            $r->qty_received,
            $inspected,
            $redistributed,
            $available,
            $statusMap[true] ?? 'Pending Inspection',
            implode(', ', $r->serial_numbers ?? []),
        ];
    }

    // =========================================================================
    //  DISPOSAL
    // =========================================================================

    public function disposal(Request $request)
    {
        $user  = auth()->user();
        $query = EquipmentDisposal::with('requester', 'approver');

        if ($user->isTechnician()) {
            $query->where('requested_by', $user->id);
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($sq) => $sq
                ->where('disposal_ref_no', 'like', "%{$q}%")
                ->orWhere('asset_description', 'like', "%{$q}%")
                ->orWhere('asset_tag_serial_no', 'like', "%{$q}%"));
        }

        $data    = $query->latest()->get();
        $format  = $request->input('format', 'csv');
        $file    = 'disposal-register-' . now()->format('Y-m-d');
        $title   = 'ICT Equipment Disposal Register';
        $filters = $request->only(['status', 'search']);

        return match ($format) {
            'csv'   => $this->csvDownload($data, $this->disposalHeaders(), fn($r) => $this->disposalRow($r), $file),
            'excel' => $this->excelDownload($data, $this->disposalHeaders(), fn($r) => $this->disposalRow($r), $title, $file),
            'word'  => $this->wordDownload($data, $this->disposalHeaders(), fn($r) => $this->disposalRow($r), $title, $file),
            'pdf'   => $this->pdfDownload('exports.disposal-pdf', compact('data', 'title', 'filters'), $file),
            default => abort(400, 'Unknown format'),
        };
    }

    private function disposalHeaders(): array
    {
        return [
            'Disposal Ref.', 'Asset Description', 'Model / Brand',
            'Asset Tag / Serial', 'Department / User', 'Date Acquired',
            'Condition at Disposal', 'Reason for Disposal', 'Disposal Method',
            'Disposal Value (USD)', 'Data Wiped', 'Status',
            'Requested By', 'Approved By', 'Date Disposed', 'Remarks',
        ];
    }

    private function disposalRow($r): array
    {
        return [
            $r->disposal_ref_no ?? '',
            $r->asset_description,
            $r->model_brand ?? '',
            $r->asset_tag_serial_no ?? '',
            $r->department_user ?? '',
            $r->date_acquired?->format('d/m/Y') ?? '',
            $r->condition_at_disposal ?? '',
            $r->reason_for_disposal,
            $r->disposal_method ?? '',
            $r->disposal_value ?? '',
            $r->data_wiped_destroyed ? 'Yes' : 'No',
            $r->status,
            trim(($r->requester?->firstname ?? '') . ' ' . ($r->requester?->lastname ?? '')),
            trim(($r->approver?->firstname ?? '') . ' ' . ($r->approver?->lastname ?? '')),
            $r->date_disposed?->format('d/m/Y') ?? '',
            $r->remarks ?? '',
        ];
    }

    // =========================================================================
    //  AUDIT TRAIL
    // =========================================================================

    public function auditTrail(Request $request)
    {
        $query = AuditTrail::query();

        if ($request->filled('module'))       $query->where('module', $request->module);
        if ($request->filled('badge_status')) $query->where('badge_status', $request->badge_status);
        if ($request->filled('username'))     $query->where('username', 'like', '%' . $request->username . '%');
        if ($request->filled('date_from'))    $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))      $query->whereDate('created_at', '<=', $request->date_to);

        $data    = $query->latest('created_at')->get();
        $format  = $request->input('format', 'csv');
        $file    = 'audit-trail-' . now()->format('Y-m-d');
        $title   = 'System Audit Trail';
        $filters = $request->only(['username', 'module', 'badge_status', 'date_from', 'date_to']);

        return match ($format) {
            'csv'   => $this->csvDownload($data, $this->auditHeaders(), fn($r) => $this->auditRow($r), $file),
            'excel' => $this->excelDownload($data, $this->auditHeaders(), fn($r) => $this->auditRow($r), $title, $file),
            'word'  => $this->wordDownload($data, $this->auditHeaders(), fn($r) => $this->auditRow($r), $title, $file),
            'pdf'   => $this->pdfDownload('exports.audit-trail-pdf', compact('data', 'title', 'filters'), $file),
            default => abort(400, 'Unknown format'),
        };
    }

    private function auditHeaders(): array
    {
        return [
            'Date / Time', 'Username', 'Action', 'Module',
            'Description', 'IP Address', 'MAC Address', 'Computer Name', 'Status',
        ];
    }

    private function auditRow($r): array
    {
        return [
            $r->created_at?->format('d/m/Y H:i:s') ?? '',
            $r->username,
            $r->action,
            $r->module,
            $r->description,
            $r->ip_address ?? '',
            $r->mac_address ?? '',
            $r->computer_name ?? '',
            ucfirst($r->badge_status),
        ];
    }

    // =========================================================================
    //  Format drivers
    // =========================================================================

    private function csvDownload($data, array $headers, callable $rowFn, string $filename)
    {
        return response()->streamDownload(function () use ($data, $headers, $rowFn) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM for Excel
            fputcsv($f, $headers);
            foreach ($data as $row) {
                fputcsv($f, $rowFn($row));
            }
            fclose($f);
        }, $filename . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function excelDownload($data, array $headers, callable $rowFn, string $title, string $filename)
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($title, 0, 31));

        $colCount = count($headers);
        $lastCol  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

        // Title row
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FF1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        // Generated date row
        $sheet->setCellValue('A2', 'Generated: ' . now()->format('d M Y H:i'));
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FF64748B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Header row (row 3)
        $sheet->fromArray($headers, null, 'A3');
        $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(18);

        // Data rows starting at row 4
        $rowNum = 4;
        foreach ($data as $record) {
            $rowData = array_values($rowFn($record));
            $sheet->fromArray($rowData, null, 'A' . $rowNum);
            if ($rowNum % 2 === 0) {
                $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']],
                ]);
            }
            $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray([
                'font'      => ['size' => 9],
                'alignment' => ['wrapText' => false],
            ]);
            $rowNum++;
        }

        // Auto-size columns
        for ($i = 1; $i <= $colCount; $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        // Border on table
        if ($rowNum > 4) {
            $sheet->getStyle("A3:{$lastCol}" . ($rowNum - 1))->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]],
            ]);
        }

        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename . '.xlsx', [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment',
        ]);
    }

    private function wordDownload($data, array $headers, callable $rowFn, string $title, string $filename)
    {
        $headerRow = '<tr>' . implode('', array_map(
            fn($h) => '<th>' . htmlspecialchars($h) . '</th>',
            $headers
        )) . '</tr>';

        $bodyRows = '';
        $i = 0;
        foreach ($data as $record) {
            $bg = (++$i % 2 === 0) ? '#f8fafc' : '#ffffff';
            $cells = implode('', array_map(
                fn($v) => '<td style="background:' . $bg . '">' . htmlspecialchars((string) $v) . '</td>',
                $rowFn($record)
            ));
            $bodyRows .= '<tr>' . $cells . '</tr>';
        }

        $html = '
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:w="urn:schemas-microsoft-com:office:word"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta charset="UTF-8">
<!--[if gte mso 9]>
<xml><w:WordDocument><w:View>Print</w:View><w:Zoom>90</w:Zoom>
<w:Orientation>Landscape</w:Orientation></w:WordDocument></xml>
<![endif]-->
<style>
  body { font-family: Calibri, Arial, sans-serif; font-size: 9pt; }
  h1   { font-size: 13pt; color: #1e293b; margin-bottom: 2px; }
  p.sub { font-size: 8pt; color: #64748b; font-style: italic; margin-bottom: 10px; }
  table { border-collapse: collapse; width: 100%; font-size: 8pt; }
  th { background: #1e3a5f; color: #ffffff; font-weight: bold; padding: 5px 7px;
       text-align: center; border: 1px solid #c8d3e0; }
  td { padding: 4px 7px; border: 1px solid #e2e8f0; vertical-align: top; }
</style>
</head>
<body>
<h1>' . htmlspecialchars($title) . '</h1>
<p class="sub">Generated: ' . now()->format('d M Y H:i') . '</p>
<table>
<thead>' . $headerRow . '</thead>
<tbody>' . $bodyRows . '</tbody>
</table>
</body>
</html>';

        return response($html, 200, [
            'Content-Type'        => 'application/vnd.ms-word',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.doc"',
            'Content-Length'      => strlen($html),
        ]);
    }

    private function pdfDownload(string $view, array $viewData, string $filename)
    {
        $pdf = Pdf::loadView($view, $viewData)->setPaper('a4', 'landscape');
        return $pdf->download($filename . '.pdf');
    }
}
