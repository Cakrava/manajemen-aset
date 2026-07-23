<?php

namespace App\Http\Controllers;

use App\Models\LetterDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Letters;             
use App\Models\StoredDevice;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
use App\Events\LetterStatusUpdated;
use App\Events\RealtimeBadgeUpdated;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class LettersController extends Controller
{
    public function index()
    {
        $letters = Letters::with('client.profile')->where('status', '!=' ,'Deleted')->latest()->get();
        $deletedLetters = Letters::with('client.profile')->where('status', '=' ,'Deleted')->latest()->get();

        $clients = User::with('profile')
            ->where('role', 'user')
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        $inventories = StoredDevice::with('device')
            ->where('stock', '>', 0) 
            ->get();

        return view('page.admin.letter', [ 
            'letters' => $letters,
            'clients' => $clients,
            'inventories' => $inventories,
            'deletedLetters' => $deletedLetters,
        ]);
    }

    public function viewArchivedPdf(Letters $letter)
    {
        if (!$letter->pdf_path || !Storage::disk('public')->exists($letter->pdf_path)) {
            abort(404, 'File arsip surat tidak ditemukan.');
        }

        $fileContents = Storage::disk('public')->get($letter->pdf_path);

        return Response::make($fileContents, 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function viewSignedArchive(Letters $letter)
    {
        if (!$letter->sign_pdf_path || !Storage::disk('public')->exists($letter->sign_pdf_path)) {
            abort(404, 'File arsip surat tidak ditemukan.');
        }

        $fileContents = Storage::disk('public')->get($letter->sign_pdf_path);

        return Response::make($fileContents, 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function downloadArchivedPdf(Letters $letter)
    {
        if (!$letter->pdf_path || !Storage::disk('public')->exists($letter->pdf_path)) {
            abort(404, 'File arsip surat tidak ditemukan.');
        }
        return Storage::disk('public')->download($letter->pdf_path);
    }public function storeWithDevices(Request $request)
{
    // 1. Validasi Input
    $validatedData = $request->validate([
        'client_id' => 'required|integer|exists:users,id',
        'equipments' => 'nullable|array',
        'equipments.*.id' => 'required|integer|exists:stored_devices,id',
        'equipments.*.quantity' => 'required|integer|min:1',
        'withdrawals' => 'nullable|array',
        'withdrawals.*.stored_device_id' => 'required|integer|exists:stored_devices,id',
        'withdrawals.*.quantity' => 'required|integer|min:1',
        'withdrawals.*.condition' => 'required|string',
    ]);

    if (empty($validatedData['equipments']) && empty($validatedData['withdrawals'])) {
        Log::warning('storeWithDevices: Request ditolak karena tidak ada item perangkat maupun penarikan.');
        return response()->json([
            'message' => 'Pilih setidaknya 1 perangkat untuk diserahkan atau ditarik.',
            'type' => 'warning'
        ], 422);
    }

    // Cek surat open
    $existingOpenLetter = Letters::where('client_id', $validatedData['client_id'])
        ->whereIn('status', ['Open', 'Needed'])
        ->first();

    if ($existingOpenLetter) {
        Log::warning("storeWithDevices: Klien ID {$validatedData['client_id']} sudah punya surat aktif (ID Surat: {$existingOpenLetter->id}).");
        return response()->json([
            'message' => 'Klien ini sudah memiliki surat berstatus "Open/Needed". Mohon selesaikan surat sebelumnya atau buat yang baru setelah statusnya "Closed/Deleted".',
            'type' => 'warning'
        ], 409); 
    }

    try {
        Log::info('--- MEMULAI TRANSAKSI STORE WITH DEVICES ---', ['client_id' => $validatedData['client_id']]);

        // A. SIMPAN SURAT & DETAIL KE DATABASE
        $letter = DB::transaction(function () use ($validatedData) {
            $now = Carbon::now();
            $year = $now->year;
            $month = $now->month;
            $romanMonth = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
            
            $lastLetter = Letters::whereYear('created_at', $year)
                ->orderByDesc('created_at')
                ->pluck('letter_number')
                ->first();

            $lastNumber = 0;
            if ($lastLetter) {
                preg_match('/^(\d+)/', $lastLetter, $matches);
                if (isset($matches[1])) {
                    $lastNumber = (int) $matches[1];
                }
            }

            $nextLetterNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
            $letterNumber = sprintf('%s/SST/DISKOMINFO/%s/%s', $nextLetterNumber, $romanMonth[$month - 1], $year);

            $newLetter = Letters::create([
                'letter_number' => $letterNumber,
                'status' => 'Needed',
                'client_id' => $validatedData['client_id'],
                'ticket_id' => null,
            ]);

            Log::info("DB: Surat baru berhasil dibuat.", ['letter_id' => $newLetter->id, 'letter_number' => $letterNumber]);

            // Item Penyerahan
            if (!empty($validatedData['equipments'])) {
                foreach ($validatedData['equipments'] as $item) {
                    LetterDetail::create([
                        'letter_id' => $newLetter->id,
                        'stored_device_id' => $item['id'],
                        'quantity' => $item['quantity'],
                        'status' => 0, 
                        'withdrawcondition' => 0, 
                    ]);
                }
                Log::info("DB: " . count($validatedData['equipments']) . " item penyerahan berhasil disimpan.");
            }

            // Item Penarikan
            if (!empty($validatedData['withdrawals'])) {
                foreach ($validatedData['withdrawals'] as $item) {
                    $withdrawConditionVal = (strtolower($item['condition']) === 'rusak') ? 1 : 0;

                    LetterDetail::create([
                        'letter_id' => $newLetter->id,
                        'stored_device_id' => $item['stored_device_id'],
                        'quantity' => $item['quantity'],
                        'status' => 1, 
                        'withdrawcondition' => $withdrawConditionVal, 
                    ]);
                }
                Log::info("DB: " . count($validatedData['withdrawals']) . " item penarikan berhasil disimpan.");
            }

            return $newLetter;
        });

        // B. PROSES GENERATE & SIMPAN PDF (Terapkan Try-Catch Khusus PDF)
        try {
            Log::info("PDF: Memuat relasi data untuk cetak PDF...", ['letter_id' => $letter->id]);
            $letter->load('client.profile', 'details.storedDevice.device');

            $cleanLetterNumber = str_replace(['/', '\\'], '-', $letter->letter_number);
            $pdfFolder = 'arsip-surat';
            $pdfFileName = $pdfFolder . '/' . $cleanLetterNumber . '.pdf';

            // Pastikan direktori arsip-surat ada di disk public
            if (!Storage::disk('public')->exists($pdfFolder)) {
                Storage::disk('public')->makeDirectory($pdfFolder);
                Log::info("PDF: Folder '{$pdfFolder}' dibuat otomatis.");
            }

            Log::info("PDF: Mengkompilasi View 'page.admin.letter-print-preview'...");
            $pdf = PDF::loadView('page.admin.letter-print-preview', ['letter' => $letter]);

            Log::info("PDF: Menyimpan file PDF ke disk public...", ['path' => $pdfFileName]);
            Storage::disk('public')->put($pdfFileName, $pdf->output());

            $letter->pdf_path = $pdfFileName;
            $letter->save();
            Log::info("PDF: Berhasil disimpan & path diupdate ke DB.", ['pdf_path' => $pdfFileName]);

        } catch (\Exception $pdfEx) {
            // Log Error Spesifik Pembuatan PDF
            Log::error('!!! GAGAL MEMBUAT / MENYIMPAN PDF SURAT !!!', [
                'letter_id' => $letter->id,
                'error_msg' => $pdfEx->getMessage(),
                'file' => $pdfEx->getFile(),
                'line' => $pdfEx->getLine(),
            ]);
            
            // Catatan: DB surat tetap tersimpan, response memberitahu PDF gagal
            return response()->json([
                'message' => 'Surat berhasil disimpan ke database, tetapi gagal membuat arsip PDF.',
                'error' => $pdfEx->getMessage(),
                'type' => 'warning'
            ], 200);
        }

        // Dispatch event real-time agar menu & badge surat muncul seketika di layar Admin / User tanpa refresh
        event(new LetterStatusUpdated($letter));
        $neededCount = Letters::where('status', 'Needed')->count();
        event(new RealtimeBadgeUpdated((int)$letter->client_id, 'needed_letters', $neededCount));

        $assetFlowUrl = route('admin.asset-flow.view');
        session()->flash('success', 'Surat dengan nomor ' . $letter->letter_number . ' berhasil dibuat dan diarsipkan. Silahkan lanjutkan proses di <a href="' . $assetFlowUrl . '" class="underline font-bold hover:text-blue-200">Asset Flow</a>.');
        
        Log::info("--- PROSES STORE WITH DEVICES SELESAI SUKSES ---");
        return response()->json(['message' => 'Surat berhasil dibuat dan diarsipkan.', 'type' => 'success'], 200);

    } catch (\Exception $e) {
        // Log Error Utama (Gagal DB Transaction / General Server Error)
        Log::error('!!! GAGAL UTAMA PADA CONTROLLER (storeWithDevices) !!!', [
            'error_message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'message' => 'Terjadi kesalahan pada server saat membuat surat.',
            'error' => $e->getMessage(),
            'type' => 'error'
        ], 500);
    }
}

 
    public function generateSst(Request $request)
    {
        Log::info('--- Proses Generate SST Dimulai (generateSst) ---');
        Log::info('Data mentah dari request:', $request->all());

        try {
            $validatedData = $request->validate([
                'ticket_id' => 'required|integer|exists:tickets,id',
                'equipments' => 'nullable|array',
                'equipments.*.id' => 'required|integer|exists:stored_devices,id',
                'equipments.*.quantity' => 'required|integer|min:1',
                'withdrawals' => 'nullable|array',
                'withdrawals.*.stored_device_id' => 'required|integer|exists:stored_devices,id',
                'withdrawals.*.quantity' => 'required|integer|min:1',
                'withdrawals.*.condition' => 'required|string',
            ]);

            if (empty($validatedData['equipments']) && empty($validatedData['withdrawals'])) {
                Log::warning('generateSst: Request ditolak karena tidak ada item perangkat maupun penarikan.');
                return response()->json([
                    'message' => 'Pilih setidaknya 1 perangkat untuk diserahkan atau ditarik.',
                    'type' => 'warning'
                ], 422);
            }
            Log::info('Validasi berhasil.', $validatedData);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validasi Gagal:', $e->errors());
            throw $e;
        }

        try {
            Log::info('Memulai DB Transaction (generateSst).');
            $letter = DB::transaction(function () use ($validatedData) {

                Log::info('Mencari tiket dengan ID: ' . $validatedData['ticket_id']);
                $ticket = Ticket::findOrFail($validatedData['ticket_id']);
                Log::info('Tiket ditemukan. User ID: ' . $ticket->user_id);

                Log::info('Memulai pembuatan nomor surat.');
                $now = Carbon::now();
                $year = $now->year;
                $month = $now->month;
                $romanMonth = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

                $lastLetter = Letters::whereYear('created_at', $year)
                    ->orderByDesc('created_at')
                    ->pluck('letter_number')
                    ->first();

                $lastNumber = 0;

                if ($lastLetter) {
                    preg_match('/^(\d+)/', $lastLetter, $matches);
                    if (isset($matches[1])) {
                        $lastNumber = (int) $matches[1];
                    }
                }

                $nextLetterNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
                $letterNumber = sprintf('%s/SST/DISKOMINFO/%s/%s', $nextLetterNumber, $romanMonth[$month - 1], $year);

                $newLetter = Letters::create([
                    'letter_number' => $letterNumber,
                    'status' => 'Needed',
                    'client_id' => $ticket->user_id,
                    'ticket_id' => $ticket->id,
                ]);
                Log::info('Data berhasil disimpan ke tabel letters dengan ID: ' . $newLetter->id);
        
                // Item Penyerahan
                if (!empty($validatedData['equipments'])) {
                    foreach ($validatedData['equipments'] as $index => $item) {
                        $detailData = [
                            'letter_id' => $newLetter->id,
                            'stored_device_id' => $item['id'],
                            'quantity' => $item['quantity'],
                            'status' => 0, 
                            'withdrawcondition' => 0,
                        ];
                        Log::info("Loop Penyerahan " . ($index + 1) . ": Menyimpan data detail:", $detailData);
                        LetterDetail::create($detailData);
                    }
                }

                // Item Penarikan
                if (!empty($validatedData['withdrawals'])) {
                    foreach ($validatedData['withdrawals'] as $index => $item) {
                        $withdrawConditionVal = (strtolower($item['condition']) === 'rusak') ? 1 : 0;
                        $detailData = [
                            'letter_id' => $newLetter->id,
                            'stored_device_id' => $item['stored_device_id'],
                            'quantity' => $item['quantity'],
                            'status' => 1, 
                            'withdrawcondition' => $withdrawConditionVal,
                        ];
                        Log::info("Loop Penarikan " . ($index + 1) . ": Menyimpan data detail:", $detailData);
                        LetterDetail::create($detailData);
                    }
                }
                Log::info('Penyimpanan detail surat selesai.');

                Log::info('Mengupdate status tiket ID ' . $ticket->id . ' menjadi "completed".');
                $ticket->update(['status' => 'completed']);
                Log::info('Status tiket berhasil diupdate.');

                Log::info('DB Transaction akan di-commit.');
                return $newLetter; 
            });

            Log::info("Memulai pembuatan arsip PDF untuk surat dari generateSst: {$letter->letter_number}");

            $letter->load('client.profile', 'details.storedDevice.device');

            $pdfFileName = 'arsip-surat/' . str_replace('/', '-', $letter->letter_number) . '.pdf';

            $pdf = PDF::loadView('page.admin.letter-print-preview', ['letter' => $letter]);

            Storage::disk('public')->put($pdfFileName, $pdf->output());
            Log::info("Arsip PDF berhasil disimpan di: {$pdfFileName}");

            $letter->pdf_path = $pdfFileName;
            $letter->save();
            Log::info("PDF path berhasil disimpan untuk surat ID: {$letter->id}");

            // Dispatch event real-time agar menu & badge surat muncul seketika di layar Admin / User tanpa refresh
            event(new LetterStatusUpdated($letter));
            $neededCount = Letters::where('status', 'Needed')->count();
            event(new RealtimeBadgeUpdated((int)$letter->client_id, 'needed_letters', $neededCount));

            session()->flash('success', 'Surat Serah Terima berhasil dibuat dengan nomor ' . $letter->letter_number . ' dan tiket telah selesai.');

            Log::info('--- Proses Generate SST Selesai Sukses ---');
            return response()->json(['message' => 'Surat berhasil dibuat.', 'type' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('!!! TERJADI ERROR KRITIS SAAT PROSES GENERATE SST (generateSst) !!!');
            Log::error('Error Message: ' . $e->getMessage());
            Log::error('File: ' . $e->getFile() . ' pada baris ' . $e->getLine());
            Log::error('Stack Trace: ' . $e->getTraceAsString());

            return response()->json([
                'message' => 'Terjadi kesalahan internal pada server. Silakan periksa log.',
                'error' => $e->getMessage(),
                'type' => 'error'
            ], 500);
        }
    }

    public function softDelete(Letters $letter)
    {
        if (!in_array($letter->status, ['Open', 'Needed'])) {
            return response()->json([
                'message' => 'Hanya surat dengan status "Open" atau "Needed" yang bisa dihapus.',
                'type'    => 'warning'
            ], 403);
        }
    
        DB::beginTransaction();
    
        try {
            $letter->status = 'Deleted';
            $letter->save();
    
            Transaction::where('letter_id', $letter->id)
                ->update(['instalation_status' => 'Revoked']);
    
            $transaction = Transaction::where('letter_id', $letter->id)->first();
    
            if ($transaction) {
                $this->removeLink($transaction->id);
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Surat berhasil dihapus dan transaksi terkait telah dibatalkan (Revoked).',
                'type'    => 'success'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
    
            return response()->json([
                'message' => 'Terjadi kesalahan saat mencoba menghapus surat.',
                'type'    => 'error'
            ], 500);
        }
    }
    
    private function removeLink($transactionId)
    {
        $jsonFilePath = storage_path('app/temporary_url.json');
    
        if (!File::exists($jsonFilePath)) {
            return;
        }
    
        $data = json_decode(File::get($jsonFilePath), true);
    
        if (is_array($data) && isset($data[$transactionId])) {
            unset($data[$transactionId]);
            File::put($jsonFilePath, json_encode($data, JSON_PRETTY_PRINT));
        }
    }
}