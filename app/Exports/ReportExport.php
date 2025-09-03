<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Carbon\Carbon;

class ReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithDrawings, WithCustomStartCell
{
    protected $reportData;
    protected $reportType;
    protected $filters;
    private $reportTitle;

    public function __construct(array $reportData, string $reportType, array $filters)
    {
        $this->reportData = $reportData;
        $this->reportType = $reportType;
        $this->filters = $filters;
        $this->setReportTitle($reportType);
    }

    private function setReportTitle($reportType)
    {
        // Diambil dari title di response JSON controller Anda
        $titles = [
            'inventory' => 'Daftar Inventaris',
            'instansi' => 'Daftar Instansi (Klien)',
            'other_profile' => 'Daftar Profil Sumber Lain',
            'flow_transaction' => 'Alur Transaksi Perangkat',
            'letter' => 'Daftar Surat',
            'deployed_device' => 'Perangkat Dideploy',
        ];
        $this->reportTitle = 'LAPORAN ' . strtoupper($titles[$reportType] ?? 'UMUM');

        // Pengecualian untuk Berita Acara
        if ($reportType === 'letter' && !empty($this->reportData['data'][0])) {
            $this->reportTitle = 'BERITA ACARA SERAH TERIMA BARANG';
        }
    }
    
    // ... Fungsi drawings(), startCell(), buildFilterString() dan styles() tidak perlu diubah, bisa menggunakan versi sebelumnya ...
    // Saya akan sertakan lagi untuk kelengkapan.

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Company Logo');
        $drawing->setPath(public_path('asset/image/icon_title.png'));
        $drawing->setHeight(75);
        $drawing->setCoordinates('A2');
        $drawing->setOffsetX(20); 

        return $drawing;
    }

    public function startCell(): string
    {
        return 'A9';
    }
    
    private function buildFilterString(): string
    {
        $activeFilters = [];
        $filterLabels = [
            'transactionType'   => 'Tipe Transaksi',
            'transactionStatus' => 'Status Transaksi',
            'instansiType'      => 'Tipe Instansi',
            'inventoryCondition'=> 'Kondisi',
            'inventoryDeviceType' => 'Tipe Perangkat'
        ];
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;
        if ($startDate || $endDate) {
            $activeFilters[] = 'Periode: ' . ($startDate ?: 'Awal') . ' s/d ' . ($endDate ?: 'Akhir');
        }
        foreach ($filterLabels as $key => $label) {
            if (!empty($this->filters[$key]) && is_array($this->filters[$key])) {
                $values = array_map(function($value) {
                    return ucwords(str_replace('_', ' ', $value));
                }, $this->filters[$key]);
                $activeFilters[] = $label . ': ' . implode(', ', $values);
            }
        }
        return empty($activeFilters) ? '' : 'Filter Aktif: ' . implode(' | ', $activeFilters);
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($this->headings()));
        $sheet->mergeCells('B2:' . $lastColumn . '2');
        $sheet->mergeCells('B3:' . $lastColumn . '4');
        $sheet->mergeCells('A6:' . $lastColumn . '6');
        $sheet->mergeCells('A7:' . $lastColumn . '7');
        $sheet->setCellValue('B2', 'DINAS KOMUNIKASI DAN INFORMASI KOTA PARIAMAN');
        $sheet->setCellValue('B3', "Jl. Jend. Sudirman 25-31, Pd. II, Kec. Pariaman Tengah,\nKota Pariaman, Sumatera Barat 25513");
        $sheet->setCellValue('A6', $this->reportTitle);
        // Subjudul khusus untuk Berita Acara
        if ($this->reportType === 'letter' && !empty($this->reportData['data'][0])) {
            $sheet->setCellValue('A7', 'Tanggal Ekspor: ' . Carbon::now()->isoFormat('D MMMM YYYY'));
        } else {
            $sheet->setCellValue('A7', 'Tanggal Ekspor: ' . Carbon::now()->isoFormat('D MMMM YYYY'));
        }
        $kopRange = 'B2:' . $lastColumn . '4';
        $sheet->getStyle($kopRange)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($kopRange)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle('B2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('B3')->getAlignment()->setWrapText(true);
        $judulRange = 'A6:' . $lastColumn . '7';
        $sheet->getStyle($judulRange)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A6')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A5:' . $lastColumn . '5')->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
        $filterString = $this->buildFilterString();
        if (!empty($filterString)) {
            $sheet->mergeCells('A8:' . $lastColumn . '8');
            $sheet->setCellValue('A8', $filterString);
            $sheet->getStyle('A8')->getFont()->setItalic(true)->setSize(9);
            $sheet->getStyle('A8')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        }
        $tableStartRow = 9;
        $lastDataRow = $tableStartRow + count($this->collection()) -1; // -1 karena collection sudah punya header
        $fullTableRange = 'A' . $tableStartRow . ':' . $lastColumn . $lastDataRow;
        $tableHeaderRange = 'A' . $tableStartRow . ':' . $lastColumn . $tableStartRow;
        $sheet->getStyle($fullTableRange)->applyFromArray(['font' => ['name' => 'Times New Roman'],'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]]);
        $sheet->getStyle($tableHeaderRange)->applyFromArray(['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF808080']], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
        if ($lastDataRow > $tableStartRow) {
            $dataRange = 'A' . ($tableStartRow + 1) . ':' . $lastColumn . $lastDataRow;
            $sheet->getStyle($dataRange)->getAlignment()->setWrapText(true);
            $sheet->getStyle($dataRange)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        }
    }


    /**
     * PERUBAHAN BESAR: Menyesuaikan data yang di-map agar identik dengan renderPaginatedReport
     */
    public function collection()
    {
        // Fungsi helper untuk meniru formatTypeName di JS
        $formatTypeName = function ($typeString) {
            if (!$typeString) return '-';
            return ucwords(str_replace('_', ' ', $typeString));
        };

        // Fungsi helper untuk meniru formatStatus di JS
        $formatStatus = function ($status) {
            if (!$status) return '-';
            $normalizedStatus = strtolower($status);
            switch ($normalizedStatus) {
                case 'in': case 'masuk': return 'Masuk';
                case 'out': case 'keluar': return 'Keluar';
                case 'intake': return 'Intake';
                case 'deployed': return 'Deployed';
                case 'pending': return 'Pending';
                case 'revoked': return 'Revoked';
                default: return ucwords($status);
            }
        };

        $index = 0;
        return collect($this->reportData['data'] ?? [])->map(function ($item) use (&$index, $formatTypeName, $formatStatus) {
            $index++;
            $item = (array)$item;
            switch ($this->reportType) {
                case 'inventory':
                    return [
                        'no' => $index,
                        'perangkat' => $item['device']['brand'] ?? '-',
                        'tipe' => $formatTypeName($item['device']['type'] ?? null),
                        'model' => $item['device']['model'] ?? '-',
                        'stok' => $item['stock'] ?? 0,
                        'kondisi' => $item['condition'] ?? '-',
                    ];
                case 'instansi':
                    return [
                        'no' => $index,
                        'nama_instansi' => $item['institution'] ?? '-',
                        'tipe' => $formatTypeName($item['institution_type'] ?? null),
                        'kontak' => $item['phone'] ?? '-',
                        'alamat' => $item['address'] ?? '-',
                    ];
                case 'other_profile':
                     return [
                        'no' => $index,
                        'nama' => $item['name'] ?? '-',
                        'instansi' => $item['institution'] ?? '-',
                        'tipe' => $formatTypeName($item['institution_type'] ?? null),
                        'kontak' => $item['phone'] ?? '-',
                    ];
                case 'flow_transaction':
                    $clientName = $item['client']['profile']['name'] ?? $item['other_source_profile']['name'] ?? '-';
                    $devicesList = collect($item['details'])->map(fn($d) => $d['stored_device']['device']['brand'] ?? '-')->implode("\n");
                    return [
                        'no' => $index,
                        'id_transaksi' => $item['transaction_number'] ?? '-',
                        'flow' => $formatStatus($item['transaction_type'] ?? null),
                        'klien_sumber' => $clientName,
                        'perangkat' => $devicesList ?: '-',
                        'status' => $formatStatus($item['instalation_status'] ?? $item['status'] ?? null),
                        'tanggal' => Carbon::parse($item['created_at'])->isoFormat('D MMM YYYY'),
                    ];
                case 'deployed_device':
                    $clientName = $item['client']['profile']['name'] ?? $item['other_source_profile']['name'] ?? '-';
                    $devicesList = collect($item['details'])->map(fn($d) => $d['stored_device']['device']['brand'] ?? '-')->implode("\n");
                    return [
                        'no' => $index,
                        'penerima' => $clientName,
                        'instansi' => $item['client']['profile']['institution'] ?? '-',
                        'tipe' => $formatTypeName($item['client']['profile']['institution_type'] ?? null),
                        'perangkat' => $devicesList ?: '-',
                        'tanggal_deploy' => Carbon::parse($item['created_at'])->isoFormat('D MMM YYYY'),
                        'status' => $formatStatus($item['instalation_status'] ?? $item['status'] ?? null),
                    ];
                case 'letter':
                    return [
                        'no' => $index,
                        'nomor_surat' => $item['letter_number'] ?? '-',
                        'perihal' => $item['subject'] ?? '-',
                        'klien' => $item['client']['profile']['name'] ?? '-',
                        'tanggal' => Carbon::parse($item['created_at'])->isoFormat('D MMM YYYY'),
                    ];
                default:
                    return ['no' => $index, 'data' => json_encode($item)];
            }
        });
    }

    /**
     * PERUBAHAN BESAR: Menyesuaikan judul kolom agar identik dengan renderPaginatedReport
     */
    public function headings(): array
    {
        switch ($this->reportType) {
            case 'inventory': return ['No', 'Perangkat', 'Tipe', 'Model', 'Stok', 'Kondisi'];
            case 'instansi': return ['No', 'Nama Instansi', 'Tipe', 'Kontak', 'Alamat'];
            case 'other_profile': return ['No', 'Nama', 'Instansi', 'Tipe', 'Kontak'];
            case 'flow_transaction': return ['No', 'ID Transaksi', 'Flow', 'Klien/Sumber', 'Perangkat', 'Status', 'Tanggal'];
            case 'letter': return ['No', 'No. Surat', 'Perihal', 'Klien', 'Tanggal'];
            case 'deployed_device': return ['No', 'Penerima', 'Instansi', 'Tipe', 'Perangkat', 'Tanggal Deploy', 'Status'];
            default: return ['No', 'Data'];
        }
    }
}