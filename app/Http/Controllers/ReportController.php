<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Profile;
use App\Models\OtherSourceProfile;
use App\Models\Transaction;
use App\Models\Letters;
use App\Models\StoredDevice;
use App\Models\DeploymentDevice;
use App\Models\Device;

use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Session;
class ReportController extends Controller
{
    public function index()
    {
        return view('page.report');
    }
    
    public function generateReport(Request $request)
    {
        $reportTypes = $request->input('report_types', []);
        $filters = $request->input('filters', []);

        // Pastikan filter tanggal di-parse dengan benar jika ada
        $startDate = !empty($filters['startDate']) ? Carbon::parse($filters['startDate'])->startOfDay() : null;
        $endDate = !empty($filters['endDate']) ? Carbon::parse($filters['endDate'])->endOfDay() : null;

        $reportsData = [];

        foreach ($reportTypes as $type) {
            switch ($type) {
                case 'inventory':
                    $query = StoredDevice::with('device');
                    if ($startDate && $endDate) {
                        $query->whereBetween('created_at', [$startDate, $endDate]);
                    }
                    // DIUBAH: Menggunakan whereIn untuk mendukung multi-pilihan kondisi
                    if (!empty($filters['inventoryCondition'])) {
                        $query->whereIn('condition', $filters['inventoryCondition']);
                    }
                    // DIUBAH: Menggunakan whereIn di dalam relasi untuk multi-pilihan tipe perangkat
                    if (!empty($filters['inventoryDeviceType'])) {
                        $query->whereHas('device', function($q) use ($filters) {
                            $q->whereIn('type', $filters['inventoryDeviceType']);
                        });
                    }
                    $reportsData['inventory'] = ['title' => 'Daftar Inventaris', 'data' => $query->get()->toArray()];
                    break;

                case 'instansi':
                    $query = Profile::with('user')->whereHas('user', function($q) { $q->where('role', 'user'); });
                    if ($startDate && $endDate) {
                        $query->whereBetween('created_at', [$startDate, $endDate]);
                    }
                    // DIUBAH: Menggunakan whereIn untuk mendukung multi-pilihan tipe instansi
                    if (!empty($filters['instansiType'])) {
                        $query->whereIn('institution_type', $filters['instansiType']);
                    }
                    $reportsData['instansi'] = ['title' => 'Daftar Instansi (Klien)', 'data' => $query->get()->toArray()];
                    break;

                case 'other_profile':
                    $query = OtherSourceProfile::query();
                    if ($startDate && $endDate) {
                        $query->whereBetween('created_at', [$startDate, $endDate]);
                    }
                    $reportsData['other_profile'] = ['title' => 'Daftar Profil Sumber Lain', 'data' => $query->get()->toArray()];
                    break;

                case 'flow_transaction':
                    $query = Transaction::with(['client.profile', 'otherSourceProfile', 'details.storedDevice.device']);
                    if ($startDate && $endDate) {
                        $query->whereBetween('created_at', [$startDate, $endDate]);
                    }
                    // DIUBAH: Menggunakan whereIn untuk multi-pilihan tipe transaksi
                    if (!empty($filters['transactionType'])) {
                        $query->whereIn('transaction_type', $filters['transactionType']);
                    }
                    // DIUBAH: Menggunakan whereIn untuk multi-pilihan status transaksi
                    if (!empty($filters['transactionStatus'])) {
                        $query->whereIn('instalation_status', $filters['transactionStatus']);
                    }
                    $reportsData['flow_transaction'] = ['title' => 'Alur Transaksi Perangkat', 'data' => $query->get()->toArray()];
                    break;

                case 'letter':
                    $query = Letters::with('client.profile');
                    if ($startDate && $endDate) {
                        $query->whereBetween('created_at', [$startDate, $endDate]);
                    }
                    $reportsData['letter'] = ['title' => 'Daftar Surat', 'data' => $query->get()->toArray()];
                    break;

                case 'deployed_device':
                    $query = DeploymentDevice::with(['client.profile', 'details.storedDevice.device']);
                    if ($startDate && $endDate) {
                        $query->whereBetween('created_at', [$startDate, $endDate]);
                    }
                    // DIUBAH: Menggunakan whereIn di dalam relasi untuk multi-pilihan kondisi
                    if (!empty($filters['inventoryCondition'])) {
                        $query->whereHas('details.storedDevice', function($q) use ($filters) {
                            $q->whereIn('condition', $filters['inventoryCondition']);
                        });
                    }
                    // DIUBAH: Menggunakan whereIn di dalam relasi bertingkat untuk multi-pilihan tipe perangkat
                    if (!empty($filters['inventoryDeviceType'])) {
                        $query->whereHas('details.storedDevice.device', function($q) use ($filters) {
                            $q->whereIn('type', $filters['inventoryDeviceType']);
                        });
                    }
                    $reportsData['deployed_device'] = ['title' => 'Perangkat Dideploy', 'data' => $query->get()->toArray()];
                    break;
            }
        }
        return response()->json($reportsData);
    }

    private function _mapQueryFilters(Request $request): array
    {
        return [
            'startDate' => $request->query('start_date'),
            'endDate' => $request->query('end_date'),
            'transactionType' => $request->query('transaction_type', []), // Default ke array kosong
            'transactionStatus' => $request->query('transaction_status', []),
            'instansiType' => $request->query('instansi_type', []),
            'inventoryCondition' => $request->query('inventory_condition', []),
            'inventoryDeviceType' => $request->query('inventory_device_type', []),
            'documentSize' => $request->query('document_size', 'a4'), // Menambahkan documentSize jika ada
        ];
    }

    /**
     * MODIFIKASI: Mengunduh laporan dalam format PDF.
     * Menggunakan fungsi bantu _mapQueryFilters untuk membersihkan kode.
     */
    public function downloadPdf(Request $request)
    {
        $reportTypeToExport = $request->query('report_type');
        if (!$reportTypeToExport) {
            return back()->with('error', 'Pilih jenis laporan untuk diunduh sebagai PDF.');
        }

        // Gunakan fungsi bantu untuk mendapatkan filter yang terstruktur
        $filters = $this->_mapQueryFilters($request);

        // Generate data laporan dengan meneruskan filter yang sudah terstruktur
        $data = $this->generateReport(new Request(['report_types' => [$reportTypeToExport], 'filters' => $filters]))->getData(true);
        $reportData = $data[$reportTypeToExport] ?? null;

        if (!$reportData) {
            return back()->with('error', 'Data laporan tidak ditemukan untuk filter yang dipilih.');
        }

        $documentSize = $filters['documentSize'];

        // Kirim semua filter ke view, bukan hanya rawFilters
        $pdf = Pdf::loadView('component.pdf_template', [
            'reportData' => $reportData,
            'filters' => $filters, // Mengirimkan semua filter yang terstruktur
            'reportTypeToExport' => $reportTypeToExport
        ]);

        if ($documentSize === 'f4') {
            $pdf->setPaper([0, 0, 595.28, 935.43], 'portrait');
        }

        return $pdf->download('laporan_' . $reportTypeToExport . '_' . Carbon::now()->format('Ymd_His') . '.pdf');
    }

    /**
     * MODIFIKASI: Mengekspor laporan dalam format Excel.
     * Menggunakan fungsi bantu _mapQueryFilters untuk konsistensi.
     */
    public function exportExcel(Request $request)
    {
        $reportTypeToExport = $request->query('report_type');
        if (!$reportTypeToExport) {
            return back()->with('error', 'Pilih jenis laporan untuk diekspor.');
        }

        // Gunakan fungsi bantu untuk mendapatkan filter yang terstruktur
        $filters = $this->_mapQueryFilters($request);
        
        // Generate data laporan
        $data = $this->generateReport(new Request(['report_types' => [$reportTypeToExport], 'filters' => $filters]))->getData(true);
        $reportData = $data[$reportTypeToExport] ?? [];
        
        // Teruskan filter ke class Export untuk kemungkinan penggunaan di sana (misal, di heading sheet)
        return Excel::download(new ReportExport($reportData, $reportTypeToExport, $filters), 'laporan_' . $reportTypeToExport . '_' . Carbon::now()->format('Ymd_His') . '.xlsx');
    }

    /**
     * TIDAK ADA PERUBAHAN: Fungsi ini sudah menangani struktur filter dari AJAX dengan benar.
     * Logikanya untuk menyimpan "resep" laporan ke session sudah tepat.
     */
    public function printPdf(Request $request)
    {
        try {
            $request->validate(['report_type' => 'required|string']);

            // Ambil semua data yang diperlukan untuk membuat laporan.
            // Strukturnya sudah benar: ['report_type' => ..., 'filters' => [...]]
            $reportParams = [
                'report_type' => $request->input('report_type'),
                'filters' => $request->input('filters', [])
            ];
            
            // Simpan "resep" laporan ke session
            Session::put('printable_report_params', $reportParams);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menyiapkan data cetak: ' . $e->getMessage()], 500);
        }
    }

    /**
     * MODIFIKASI: Fungsi yang menyajikan PDF untuk dialog cetak browser.
     * Disesuaikan agar meneruskan data ke view dengan cara yang sama seperti downloadPdf.
     */
    public function viewPrintablePdf()
    {
        $params = Session::get('printable_report_params');

        if (!$params) {
            abort(404, 'Sesi laporan tidak ditemukan atau sudah kedaluwarsa.');
        }

        // Hapus resep dari session agar tidak bisa di-refresh atau digunakan lagi
        Session::forget('printable_report_params');
        
        // Filter sudah dalam format yang benar dari fungsi printPdf, tidak perlu mapping ulang
        $filters = $params['filters'];
        $reportType = $params['report_type'];

        // Generate data laporan menggunakan resep dari session
        $requestForGeneration = new Request(['report_types' => [$reportType], 'filters' => $filters]);
        $data = $this->generateReport($requestForGeneration)->getData(true);
        $reportData = $data[$reportType] ?? null;

        if (!$reportData) {
            abort(404, 'Gagal menghasilkan data untuk laporan.');
        }

        // Buat PDF secara on-the-fly
        // PERUBAHAN UTAMA: Kirim variabel $filters, bukan membuat array baru.
        $pdf = Pdf::loadView('component.pdf_template', [
            'reportData' => $reportData,
            'filters' => $filters, // Mengirimkan objek filter LENGKAP ke view
            'reportTypeToExport' => $reportType // Menggunakan nama variabel yang konsisten
        ]);
        
        $documentSize = $filters['documentSize'] ?? 'a4';
        if ($documentSize === 'f4') {
            $pdf->setPaper([0, 0, 595.28, 935.43], 'portrait');
        }

        // Sajikan PDF langsung ke browser untuk dicetak
        return $pdf->stream('laporan.pdf');
    }
}