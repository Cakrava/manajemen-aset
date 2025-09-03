<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use App\Models\DeploymentDevice;
use App\Models\DeploymentDeviceDetail;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Letters;
use App\Models\Device;
use App\Models\StoredDevice;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Log;
use App\Models\OtherSourceProfile;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
class TransactionController extends Controller
{
    public function index()
    {
        // Transaksi aktif (bukan revoked)
        $transactions = Transaction::with('client.profile', 'otherSourceProfile', 'details.storedDevice.device','letter')
            ->where('instalation_status', '!=', 'Revoked')
            ->latest()
            ->get();
    
        // Transaksi yang berstatus revoked (kalau mau ditampilkan terpisah)
        $revokedTransactions = Transaction::with('client.profile', 'otherSourceProfile', 'details.storedDevice.device','letter')
            ->where('instalation_status', 'Revoked')
            ->latest()
            ->get();
    
        $letters = Letters::with('client.profile', 'details.storedDevice.device')
                          ->where('status', 'Needed')
                          ->get();
    
        $devices = Device::all();
    
        $storedDevices = StoredDevice::with('device')
            ->where('stock', '>', 0)
            ->where('condition', '!=', 'Rusak')
            ->get();
    
        $users = User::with('profile')
            ->whereHas('profile')
            ->where('role', 'user')
            ->where('status','active')
            ->get();
    
        // Baca file token link dari storage/app/temporary_url.json
        $tokenLinks = [];
        $filePath = storage_path('app/temporary_url.json');
    
        if (file_exists($filePath)) {
            $json = file_get_contents($filePath);
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $tokenLinks = $decoded;
            }
        } if (!empty($tokenLinks)) {
            $transactions->each(function ($transaction) use ($tokenLinks) {
                // Cek apakah ID transaksi ini ada sebagai kunci di dalam $tokenLinks
                if (isset($tokenLinks[$transaction->id])) {
                    // Jika ada, tambahkan properti 'access_url' ke objek transaksi
                    $transaction->access_url = $tokenLinks[$transaction->id]['url'];
                } else {
                    // Jika tidak, set properti ke null agar konsisten
                    $transaction->access_url = null;
                }
            });
        }
    
    
        return view('page.admin.asset-flow', compact(
            'transactions',
            'devices',
            'letters',
            'storedDevices',
            'users',
            'tokenLinks',
            'revokedTransactions'
        ));
    }
    

    public function searchOtherSource(Request $request)
{
    $query = $request->get('query');

    if (strlen($query) < 2) {
        return response()->json([]);
    }

    // Pecah query menjadi kata-kata
    $keywords = explode(' ', strtolower($query));

    // Mulai query
    $profilesQuery = \App\Models\OtherSourceProfile::query();

    $profilesQuery->where(function ($q) use ($keywords) {
        foreach ($keywords as $word) {
            $q->orWhere('name', 'LIKE', "%{$word}%")
              ->orWhere('institution', 'LIKE', "%{$word}%");
        }
    });

    $profiles = $profilesQuery->limit(10)->get();

    return response()->json($profiles);
}
    public function getPreviousDeployment($user_id)
    {
        // Cari data deployment untuk user_id yang diberikan, dan eager load relasi yang diperlukan
        $deployment = DeploymentDevice::with('details.storedDevice.device')
                                      ->where('client_id', $user_id) // Menggunakan client_id sesuai model
                                      ->first();
    
        if (!$deployment) {
            // Jika tidak ada data, kembalikan array kosong
            return response()->json(['devices' => []]);
        }
    
        // Format data agar mudah digunakan di JavaScript
        $formattedDevices = $deployment->details->map(function ($detail) {
            // Hanya sertakan data jika relasi lengkap
            if ($detail->storedDevice && $detail->storedDevice->device) {
                // Return additional necessary info like original stored_device_id and actual stock
                return [
                    'stored_device_id' => $detail->storedDevice->id,
                    'name'             => $detail->storedDevice->device->brand . ' ' . $detail->storedDevice->device->model,
                    'condition'        => $detail->storedDevice->condition,
                    'quantity'         => $detail->quantity, // This is the deployed quantity
                    'stock'            => $detail->storedDevice->stock, // Actual stock in StoredDevice
                ];
            }
            return null;
        })->filter(); // Hapus item null dari koleksi
    
        return response()->json(['devices' => $formattedDevices]);
    }

  
    protected function getInstallationStatus(string $transactionType): string
    {
        return $transactionType === 'in' ? 'Intake' : 'Pending';
    }

/**
 * Memproses item di dalam keranjang transaksi, menangani logika stok
 * untuk sumber 'manual' dan 'deployed' dengan mempertimbangkan perubahan kondisi.
 *
 * @param array $cartItems Item dari keranjang.
 * @param string $transactionId ID dari transaksi utama.
 * @param string $transactionType Tipe transaksi ('in' atau 'out').
 * @param int|null $clientId ID Klien, wajib diisi untuk item 'deployed'.
 * @throws \Exception
 */
protected function processCartItems(array $cartItems, string $transactionId, string $transactionType, ?int $clientId = null)
{
    foreach ($cartItems as $item) {
        $storedDeviceId = null;
        $quantity = $item['quantity'];
        $itemCondition = $item['condition'];

        if ($item['source'] === 'manual') { 
            $deviceId = $item['id'];
            $storedDevice = StoredDevice::where('device_id', $deviceId)
                                        ->where('condition', $itemCondition)
                                        ->first();

            if ($transactionType === 'in') {
                if ($storedDevice) {
                    $storedDevice->increment('stock', $quantity);
                } else {
                    $storedDevice = StoredDevice::create([
                        'device_id' => $deviceId,
                        'condition' => $itemCondition,
                        'stock'     => $quantity,
                    ]);
                }
            } else { // transactionType === 'out'
                if (!$storedDevice) {
                    throw new \Exception("Stok untuk perangkat '{$item['name']}' dengan kondisi '{$itemCondition}' tidak ditemukan.");
                }
            }
            
            $storedDeviceId = $storedDevice->id;

        } elseif ($item['source'] === 'deployed') { 
            //====================================================================
            // BLOK LOGIKA UNTUK PENGEMBALIAN BARANG (DENGAN PERUBAHAN KONDISI)
            //====================================================================

            if ($transactionType !== 'in') {
                throw new \Exception("Operasi tidak valid: Item 'deployed' hanya bisa diproses untuk transaksi 'in' (pengembalian aset).");
            }
            if (is_null($clientId)) {
                throw new \Exception("Client ID wajib diisi untuk memproses pengembalian item 'deployed'.");
            }

            // Langkah 1: Kurangi kuantitas dari catatan DeploymentDeviceDetail (stok di klien).
            $deploymentHeader = DeploymentDevice::where('client_id', $clientId)->firstOrFail();
            $deployedDetail = DeploymentDeviceDetail::where('deployment_id', $deploymentHeader->id)
                                                    ->where('stored_device_id', $item['id'])
                                                    ->lockForUpdate()
                                                    ->first();

            if (!$deployedDetail || $deployedDetail->quantity < $quantity) {
                $jumlahTerpasang = $deployedDetail && isset($deployedDetail->quantity) ? $deployedDetail->quantity : 0;
                throw new \Exception("Gagal memproses pengembalian '{$item['name']}'. Jumlah yang dikembalikan ({$quantity}) melebihi jumlah yang terpasang ({$jumlahTerpasang}).");
            }
            $deployedDetail->decrement('quantity', $quantity);
            if ($deployedDetail->quantity <= 0) {
                $deployedDetail->delete();
            }

            // Langkah 2: Dapatkan ID perangkat asli (device_id) dari catatan StoredDevice lama.
            $originalStoredDevice = StoredDevice::find($item['id']);
            if (!$originalStoredDevice) {
                throw new \Exception("Inkonsistensi data: StoredDevice asli dengan ID {$item['id']} tidak ditemukan.");
            }
            $deviceId = $originalStoredDevice->device_id;
            
            // Langkah 3: Tambah stok ke gudang, DENGAN MEMPERTIMBANGKAN KONDISI BARU.
            // Cari apakah sudah ada baris di gudang dengan ID perangkat & kondisi yang dikembalikan.
            $targetStoredDevice = StoredDevice::where('device_id', $deviceId)
                                             ->where('condition', $itemCondition) // Menggunakan kondisi dari cart
                                             ->lockForUpdate()
                                             ->first();
            
            if ($targetStoredDevice) {
                // Jika sudah ada (misal, sebelumnya sudah ada barang bekas lainnya), tambah stoknya.
                $targetStoredDevice->increment('stock', $quantity);
                $storedDeviceId = $targetStoredDevice->id; // Gunakan ID ini untuk detail transaksi.
            } else {
                // Jika tidak ada (misal, ini pertama kalinya barang rusak dikembalikan), buat baris baru.
                $newStoredDevice = StoredDevice::create([
                    'device_id' => $deviceId,
                    'condition' => $itemCondition,
                    'stock'     => $quantity,
                ]);
                $storedDeviceId = $newStoredDevice->id; // Gunakan ID BARU ini untuk detail transaksi.
            }

        } else { // 'letter' source
            $storedDeviceId = $item['id'];
        }

        // Buat TransactionDetail dengan stored_device_id yang benar setelah diproses.
        \App\Models\TransactionDetail::create([
            'transaction_id'   => $transactionId,
            'stored_device_id' => $storedDeviceId,
            'quantity'         => $quantity,
        ]);
    }
}




    public function processTransactionFromLetter(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:users,id',
            'letter_id' => 'required|exists:letters,id',
            'transaction_cart' => 'required|array|min:1',
            'transaction_cart.*.id' => 'required|integer',
            'transaction_cart.*.quantity' => 'required|integer|min:1',
            'transaction_cart.*.source' => 'required|string|in:letter',
            'transaction_cart.*.condition' => 'required|string',
        ]);
    
        DB::beginTransaction();
        try {
            $transactionType = 'out';
            $instalationStatus = $this->getInstallationStatus($transactionType);
    
            // Buat record Transaksi utama
            $transaction = Transaction::create([
                'transaction_number'     => $request->input('transaction_id'),
             
                'instalation_status' => $instalationStatus,
                'transaction_type'   => $transactionType,
                'client_id'          => $request->input('client_id'),
                'other_source_id'    => null,
                'letter_id'          => $request->input('letter_id'),
            ]);
    
            // Proses item di keranjang
            foreach ($request->input('transaction_cart') as $item) {
                \App\Models\TransactionDetail::create([
                    'transaction_id'        => $transaction->id,
                    'stored_device_id' => $item['id'],
                    'quantity'         => $item['quantity'],
                ]);
            }
    
            // Inisialisasi variabel untuk URL akses, defaultnya null.
            $accessUrl = null;
    
            // Buat URL akses unik HANYA JIKA status instalasi adalah 'Pending'.
            if ($transaction->instalation_status === 'Pending') {
                $accessUrl = $this->generateLink($transaction);
            }
    
            // ==========================================================
            // LOGIKA BARU: Ubah status surat menjadi 'Open'
            // ==========================================================
            // Cari surat berdasarkan letter_id yang diberikan dalam request.
            $letter = Letters::find($request->input('letter_id'));
    
            // Jika surat ditemukan (seharusnya selalu ditemukan karena ada validasi),
            // ubah statusnya dan simpan.
            if ($letter) {
                $letter->status = 'Open';
                $letter->save();
            }
            // ==========================================================
    
            DB::commit();
            
            // Kembalikan respons JSON dengan pesan sukses dan URL (jika ada).
            return response()->json([
                'message' => 'Transaksi dari surat berhasil diproses!',
                'transaction_id' => $transaction->transaction_id,
                'access_url' => $accessUrl
            ], 200);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memproses transaksi dari surat: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 2. Fungsi untuk memproses transaksi manual dengan "Sumber Lain" diaktifkan.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processTransactionManualOtherSource(Request $request)
    {
        $request->validate([
            'other_source_profile.name' => 'required|string|max:255',
            'other_source_profile.phone' => 'nullable|string|max:255',
            'other_source_profile.institution' => 'nullable|string|max:255',
            'other_source_profile.institution_type' => 'nullable|string|max:255',
            'flow_type' => 'required|in:in,out',
            'transaction_cart' => 'required|array|min:1',
            'transaction_cart.*.id' => 'required|integer',
            'transaction_cart.*.name' => 'required|string',
            'transaction_cart.*.condition' => 'required|string',
            'transaction_cart.*.quantity' => 'required|integer|min:1',
            'transaction_cart.*.source' => 'required|string|in:manual,deployed',
        ]);

        DB::beginTransaction();
        try {
            // Buat profil untuk sumber lain
            $otherSourceProfile = OtherSourceProfile::create($request->input('other_source_profile'));

         
            $transactionType = $request->input('flow_type');
            $instalationStatus = $this->getInstallationStatus($transactionType);

            // Buat record Transaksi utama
            $transaction = Transaction::create([
                'transaction_number'     => $request->input('transaction_id'),
           
                'instalation_status' => $instalationStatus,
                'transaction_type'   => $transactionType,
                'client_id'          => null,
                'other_source_id'    => $otherSourceProfile->id,
            ]);
            $transactionId = $transaction->id;
            // Proses item di keranjang
            $this->processCartItems($request->input('transaction_cart'), $transactionId, $transactionType);

            // Inisialisasi variabel untuk URL akses, defaultnya null.
            $accessUrl = null;

            // Buat URL akses unik HANYA JIKA status instalasi adalah 'Pending'.
            if ($transaction->instalation_status === 'Pending') {
                $accessUrl = $this->generateLink($transaction);
            }

            DB::commit();

            // Kembalikan respons JSON dengan pesan sukses dan URL (jika ada).
            return response()->json([
                'message' => 'Transaksi manual (Sumber Lain) berhasil diproses!',
                'transaction_id' => $transaction->transaction_id,
                'access_url' => $accessUrl
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memproses transaksi manual (Sumber Lain): ' . $e->getMessage()], 500);
        }
    }

    /**
     * 3. Fungsi untuk memproses transaksi manual dengan klien terpilih dan tanpa item "deployed".
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processTransactionManualSelectedClient(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:users,id',
            'flow_type' => 'required|in:in,out',
            'transaction_cart' => 'required|array|min:1',
            'transaction_cart.*.id' => 'required|integer',
            'transaction_cart.*.name' => 'required|string',
            'transaction_cart.*.condition' => 'required|string',
            'transaction_cart.*.quantity' => 'required|integer|min:1',
            'transaction_cart.*.source' => 'required|string|in:manual',
        ]);

        // Pastikan tidak ada item 'deployed' di keranjang untuk route ini
        foreach ($request->input('transaction_cart') as $item) {
            if ($item['source'] === 'deployed') {
                return response()->json(['message' => 'Keranjang berisi item deployed, gunakan endpoint yang benar.'], 400);
            }
        }

        DB::beginTransaction();
        try {
          
            $transactionType = $request->input('flow_type');
            $instalationStatus = $this->getInstallationStatus($transactionType);

            // Buat record Transaksi utama
            $transaction = Transaction::create([
                'transaction_number'     => $request->input('transaction_id'),
               
                'instalation_status' => $instalationStatus,
                'transaction_type'   => $transactionType,
                'client_id'          => $request->input('client_id'),
                'other_source_id'    => null,
            ]);
            $transactionId = $transaction->id;
            // Proses item di keranjang
            $this->processCartItems($request->input('transaction_cart'), $transactionId, $transactionType, $request->input('client_id'));

            // Inisialisasi variabel untuk URL akses, defaultnya null.
            $accessUrl = null;

            // Buat URL akses unik HANYA JIKA status instalasi adalah 'Pending'.
            if ($transaction->instalation_status === 'Pending') {
                $accessUrl = $this->generateLink($transaction);
            }

            DB::commit();

            // Kembalikan respons JSON dengan pesan sukses dan URL (jika ada).
            return response()->json([
                'message' => 'Transaksi manual (Klien Terpilih) berhasil diproses!',
                'transaction_id' => $transaction->transaction_id,
                'access_url' => $accessUrl
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memproses transaksi manual (Klien Terpilih): ' . $e->getMessage()], 500);
        }
    }

    /**
     * 4. Fungsi untuk memproses transaksi manual dengan klien terpilih dan mengandung item "deployed".
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processTransactionManualDeployed(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:users,id',
            'flow_type' => 'required|in:in,out',
            'transaction_cart' => 'required|array|min:1',
            'transaction_cart.*.id' => 'required|integer',
            'transaction_cart.*.name' => 'required|string',
            'transaction_cart.*.condition' => 'required|string',
            'transaction_cart.*.quantity' => 'required|integer|min:1',
            'transaction_cart.*.source' => 'required|string|in:manual,deployed',
        ]);

        // Pastikan setidaknya ada satu item 'deployed' di keranjang untuk route ini
        $hasDeployedItems = collect($request->input('transaction_cart'))->contains('source', 'deployed');
        if (!$hasDeployedItems) {
            return response()->json(['message' => 'Keranjang tidak berisi item deployed, gunakan endpoint yang benar.'], 400);
        }

        DB::beginTransaction();
        try {
            $transactionType = $request->input('flow_type');
            $instalationStatus = $this->getInstallationStatus($transactionType);

            // Buat record Transaksi utama
            $transaction = Transaction::create([
                'transaction_number'     => $request->input('transaction_id'),
            
                'instalation_status' => $instalationStatus,
                'transaction_type'   => $transactionType,
                'client_id'          => $request->input('client_id'),
                'other_source_id'    => null,
            ]);
            $transactionId = $transaction->id;
            // Proses item di keranjang
            $this->processCartItems($request->input('transaction_cart'), $transactionId, $transactionType, $request->input('client_id'));

            // Inisialisasi variabel untuk URL akses, defaultnya null.
            $accessUrl = null;

            // Buat URL akses unik HANYA JIKA status instalasi adalah 'Pending'.
            if ($transaction->instalation_status === 'Pending') {
                $accessUrl = $this->generateLink($transaction);
            }

            DB::commit();
            
            // Kembalikan respons JSON dengan pesan sukses dan URL (jika ada).
            return response()->json([
                'message' => 'Transaksi manual (dengan item deployed) berhasil diproses!',
                'transaction_id' => $transaction->transaction_id,
                'access_url' => $accessUrl
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memproses transaksi manual (dengan item deployed): ' . $e->getMessage()], 500);
        }
    }





    








private function generateLink(Transaction $transaction)
{
    $jsonFilePath = storage_path('app/temporary_url.json');
    
    // Log yang benar, menggunakan $transaction->id
    Log::debug('Mulai generateLink untuk transaction ID (primary key): ' . $transaction->id);

    // Pastikan file JSON ada, jika tidak, buat file kosong
    if (!File::exists($jsonFilePath)) {
        File::put($jsonFilePath, json_encode([]));
    }

    // Baca data yang ada dari file
    $data = json_decode(File::get($jsonFilePath), true);
    if (!is_array($data)) {
        // Jika file rusak atau isinya bukan JSON, mulai dari array kosong
        $data = [];
    }

    // Buat token dan URL
    $token = Str::random(40);
    $url = url('/access-transaction/' . $token);

    // Tambahkan data baru menggunakan Primary Key 'id' sebagai kunci
    $data[$transaction->id] = [
        'token' => $token,
        'url' => $url,
    ];

    // Tulis kembali semua data ke file
    File::put($jsonFilePath, json_encode($data, JSON_PRETTY_PRINT));
    Log::debug("Data berhasil disimpan ke file. URL baru: " . $url);

    return $url;
}

/*
|--------------------------------------------------------------------------
| FUNGSI UNTUK MENGAKSES LINK
|--------------------------------------------------------------------------
| Kode ini sudah disesuaikan untuk membaca data yang disimpan oleh generateLink.
*/
public function accessTransaction($token)
{
    Log::info("Mencoba mengakses transaksi dengan token: {$token}");
    $jsonFilePath = storage_path('app/temporary_url.json');

    // Kondisi 1: File JSON tidak ada
    if (!File::exists($jsonFilePath)) {
        Log::warning('Akses gagal: File temporary_url.json tidak ditemukan.');
        abort(404, 'Halaman tidak ditemukan (File Hilang).');
    }

    $linkData = json_decode(File::get($jsonFilePath), true);

    // Kondisi 2: File JSON rusak atau kosong
    if (empty($linkData) || !is_array($linkData)) {
        Log::warning('Akses gagal: File temporary_url.json kosong atau rusak.');
        abort(404, 'Halaman tidak ditemukan (File Rusak).');
    }

    $foundTransactionId = null;
    $linkData = json_decode(File::get(storage_path('app/temporary_url.json')), true);

    foreach ($linkData as $transaction_id => $details) {
        if (isset($details['token']) && $details['token'] === $token) {
            $foundTransactionId = $transaction_id;
            break;
        }
    }
    
    if (is_null($foundTransactionId)) {
        abort(404, 'Token tidak ditemukan atau tidak valid.');
    }

    $transaction = Transaction::where('id', $foundTransactionId)->first();

    if (!$transaction || $transaction->instalation_status !== 'Pending') {
        abort(404, 'Halaman tidak ditemukan atau link sudah tidak berlaku.');
    }

    // PERBAIKAN: Kirim variabel 'transaction_number' ke view.
    // Ini lebih jelas daripada 'transaction_id' yang ambigu.
    return view('submit_transaction', [
        'transaction_number' => $transaction->transaction_number
    ]);
}

// Pastikan Anda sudah memiliki "use Illuminate\Support\Facades\Log;" di bagian atas file controller.
public function processSubmission(Request $request)
{
    // Menggunakan ID unik untuk log sesi ini agar mudah dicari di file log
    $logSessionId = Str::uuid()->toString();
    Log::info("Memulai proses submission.", ['session_id' => $logSessionId, 'request_data' => $request->all()]);

    // Langkah 1: Validasi input dari form berdasarkan kolom yang benar
    $request->validate([
        'transaction_id' => 'required|string|exists:transactions,transaction_number',
        'nomor_surat' => 'required|string|exists:letters,letter_number',
        'lampiran_surat' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
    ], [
        'transaction_id.exists' => 'ID Transaksi tidak valid atau tidak ditemukan.',
        'nomor_surat.exists' => 'Nomor Surat tidak ditemukan dalam sistem.',
    ]);
    Log::info("Langkah 1: Validasi input berhasil.", ['session_id' => $logSessionId]);

    // Memulai transaksi database untuk memastikan integritas data.
    DB::beginTransaction();
    Log::info("Transaksi database dimulai.", ['session_id' => $logSessionId]);
    
    try {
        // Langkah 2: Ambil data dan cari record di DB berdasarkan kolom yang benar
        $transactionNumber = $request->input('transaction_id');
        $nomorSurat = $request->input('nomor_surat');

        $transaction = Transaction::where('transaction_number', $transactionNumber)->firstOrFail();
        $letter = Letters::where('letter_number', $nomorSurat)->firstOrFail();
        Log::info("Langkah 2: Data Transaksi dan Surat berhasil ditemukan.", ['transaction_id' => $transaction->id, 'letter_number' => $nomorSurat]);

        // Langkah 3: Lakukan validasi bisnis
        if ($transaction->instalation_status !== 'Pending') {
            DB::rollBack();
            return redirect()->back()->withErrors(['submit' => 'Transaksi ini sudah pernah diselesaikan atau link tidak valid lagi.']);
        }
        if ($transaction->client_id !== $letter->client_id) {
            DB::rollBack();
            return redirect()->back()->withErrors(['submit' => 'Nomor Surat tidak cocok untuk transaksi ini.']);
        }
        Log::info("Langkah 3: Validasi bisnis berhasil.", ['session_id' => $logSessionId]);

        // Langkah 4: Proses upload file dan update status Surat
        $publicPdfPath = $this->handleFileUpload($request->file('lampiran_surat'), $letter);
        $letter->status = 'Closed';
        $letter->sign_pdf_path = $publicPdfPath;
        $letter->save();
        Log::info("Langkah 4: File lampiran berhasil diunggah.", ['session_id' => $logSessionId]);

        // ========================================================================
        // LANGKAH 5: PROSES UTAMA MANAJEMEN INVENTARIS
        // ========================================================================
        Log::info("Langkah 5: Memulai proses manajemen inventaris.", ['session_id' => $logSessionId]);

        // Langkah 5a: Dapatkan semua item dari detail transaksi
        $transactionDetails = TransactionDetail::where('transaction_id', $transaction->id)->get();
        Log::info("Langkah 5a: Ditemukan " . $transactionDetails->count() . " item detail transaksi.", ['session_id' => $logSessionId]);

        // Langkah 5b: Cari atau buat header perangkat terpasang untuk klien
        $deployedDeviceHeader = DeploymentDevice::firstOrCreate(['client_id' => $transaction->client_id]);
        Log::info("Langkah 5b: Header perangkat terpasang (DeployedDevice) siap.", ['deployment_id' => $deployedDeviceHeader->id]);

        // Langkah 5c: Loop setiap item untuk memindahkan kuantitas dari gudang ke klien
        foreach ($transactionDetails as $detail) {
            Log::info("Langkah 5c: Memproses item detail...", ['stored_device_id' => $detail->stored_device_id, 'quantity' => $detail->quantity]);
            
            // Bagian 1: Kurangi stok dari gudang (StoredDevice)
            $storedDevice = StoredDevice::lockForUpdate()->findOrFail($detail->stored_device_id);
            if ($storedDevice->stock < $detail->quantity) {
                DB::rollBack();
                $deviceName = $storedDevice->device->brand . ' ' . $storedDevice->device->model;
                return redirect()->back()->withInput()->withErrors(['submit' => "Proses gagal: Stok untuk {$deviceName} tidak mencukupi."]);
            }
            $storedDevice->decrement('stock', $detail->quantity);
            Log::info("--> Stok StoredDevice ID: {$storedDevice->id} berhasil dikurangi.");

            // ========================================================================
            // INILAH KODE YANG BENAR YANG HARUS ANDA PASTIKAN ADA DI SERVER ANDA
            // ========================================================================
            // Bagian 2: Cari detail penyebaran yang cocok, atau siapkan instance baru jika tidak ada.
            $deployedDetail = DeploymentDeviceDetail::firstOrNew([
                'deployment_id'    => $deployedDeviceHeader->id, // Menggunakan ID yang benar
                'stored_device_id' => $detail->stored_device_id,
            ]);

            // Tambahkan kuantitas ke kuantitas yang ada (atau 0 jika ini baris baru)
            $deployedDetail->quantity = ($deployedDetail->quantity ?? 0) + $detail->quantity;
            $deployedDetail->save();
            Log::info("--> Detail perangkat terpasang (DeployedDeviceDetail) berhasil dibuat/di-update.");
        }
        
        Log::info("Langkah 5 selesai: Semua item inventaris berhasil diproses.", ['session_id' => $logSessionId]);

        // Langkah 6: Update status Transaksi menjadi 'Deployed'
        $transaction->instalation_status = 'Deployed';
        $transaction->save();
        Log::info("Langkah 6: Status Transaksi berhasil diubah.", ['session_id' => $logSessionId]);
        
        // Langkah 7: Hapus link sementara menggunakan primary key yang benar
        $this->removeLink($transaction->id);
        Log::info("Langkah 7: Link sementara telah dihapus.", ['session_id' => $logSessionId]);

        DB::commit();
        Log::info("Transaksi database berhasil di-commit.", ['session_id' => $logSessionId]);

        return redirect()->route('front.index')
            ->with('success', 'Konfirmasi berhasil! Stok gudang telah diperbarui dan perangkat terpasang telah dicatat.');

    } catch (\Exception $e) {
        DB::rollBack();
        Log::info("Transaksi database di-rollback karena terjadi error.", ['session_id' => $logSessionId]);
        Log::error('Gagal memproses submission.', [
            'session_id' => $logSessionId,
            'error_message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);

        return redirect()->route('front.index')
            ->withErrors(['submit' => 'Terjadi kesalahan internal pada sistem. Tim kami telah diberitahu.']);
    }
}
    
private function handleFileUpload($file, $letter)
{
    $extension = strtolower($file->getClientOriginalExtension());
    $safeFileNameBase = Str::slug($letter->letter_number, '-') . '-TERTANDATANGANI';
    $finalPdfFileName = $safeFileNameBase . '.pdf';
    $storageDirectory = 'arsip-surat-tertanda';
    $publicPdfPath = "{$storageDirectory}/{$finalPdfFileName}";

    if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
        $imageData = base64_encode(file_get_contents($file->getRealPath()));
        $imageSrc = 'data:' . $file->getMimeType() . ';base64,' . $imageData;
        // Pastikan view 'component.image-to-pdf-wrapper' ada
        $pdf = Pdf::loadView('component.image-to-pdf-wrapper', ['imageSrc' => $imageSrc]);
        Storage::disk('public')->put($publicPdfPath, $pdf->output());
    } else {
        $file->storeAs($storageDirectory, $finalPdfFileName, 'public');
    }
    return $publicPdfPath;
}


private function removeLink($transactionId)
{
    $jsonFilePath = storage_path('app/temporary_url.json');

    if (!File::exists($jsonFilePath)) {
        return; // Tidak ada yang perlu dilakukan jika file tidak ada
    }

    $data = json_decode(File::get($jsonFilePath), true);

    // Pastikan data valid dan kunci transaksi ada sebelum menghapus
    if (is_array($data) && isset($data[$transactionId])) {
        unset($data[$transactionId]);
        // Tulis kembali data yang sudah diperbarui ke file
        File::put($jsonFilePath, json_encode($data, JSON_PRETTY_PRINT));
    }
   
}
}