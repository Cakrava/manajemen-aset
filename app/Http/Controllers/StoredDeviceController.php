<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\StoredDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\CssSelector\Node\FunctionNode;

use Illuminate\Support\Facades\DB;
class StoredDeviceController extends Controller
{
 


    public Function index(){
        $storedDevices = StoredDevice::with('device')->get(); // Ambil data StoredDevice dengan eager load 'device'
        $devices = \App\Models\Device::all(); // Ambil data Device untuk dropdown di modal (asumsi dibutuhkan)
        $deviceTypeNames = [
            'router' => 'Router',
            'access_point' => 'Access Point',
            'repeater' => 'Repeater / Range Extender',
            'network_adapter' => 'Network Adapter (USB/PCIe)',
            'switch' => 'Switch',
            'hub' => 'Hub',
            'modem' => 'Modem',
            'firewall' => 'Firewall',
            'load_balancer' => 'Load Balancer',
            'vpn_gateway' => 'VPN Gateway',
            'wireless_controller' => 'Wireless Controller',
            'media_converter' => 'Media Converter',
            'print_server' => 'Print Server',
            'network_storage' => 'Network Attached Storage (NAS)',
            'ip_camera' => 'IP Camera',
            'voip_phone' => 'VoIP Phone',
            'powerline_adapter' => 'Powerline Adapter',
            'bluetooth_adapter' => 'Bluetooth Adapter',
            'zigbee_gateway' => 'Zigbee Gateway',
            'zwave_gateway' => 'Z-Wave Gateway',
            'lorawan_gateway' => 'LoRaWAN Gateway',
            'nb_iot_gateway' => 'NB-IoT Gateway',
            'ethernet_over_power' => 'Ethernet over Power (EoP) Adapter',
            'serial_device_server' => 'Serial Device Server',
            'console_server' => 'Console Server',
            'network_tap' => 'Network Tap',
            'poe_injector' => 'PoE Injector',
            'poe_splitter' => 'PoE Splitter',
            'sfp_module' => 'SFP/SFP+ Module',
            'gbic_module' => 'GBIC Module',
            'cable' => 'Network Cable (Ethernet, Fiber)',
            'connector' => 'Connector (RJ45, Fiber Connectors)',
            'patch_panel' => 'Patch Panel',
            'rack' => 'Network Rack/Cabinet',
            'ups' => 'Uninterruptible Power Supply (UPS)',
            'pdu' => 'Power Distribution Unit (PDU)',
            'cooling_fan' => 'Cooling Fan (for Rack/Devices)',
            'antena' => 'Antenna (WiFi, Cellular)',
            'surge_protector' => 'Surge Protector (Network/Power)',
            'network_analyzer' => 'Network Analyzer/Tester',
            'crimping_tool' => 'Crimping Tool (for Cables)',
            'cable_tester' => 'Cable Tester',
        ];
    
        return view('page.stored_device', compact('storedDevices', 'devices', 'deviceTypeNames')); // Kirim data ke view
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'device_id' => 'required|max:255',
            'stock' => 'required|integer|min:1',
            'input_mode' => 'nullable|string|in:unit,roll',
            'condition' => 'required',
        ]);

        $device = Device::findOrFail($request->device_id);

        $stockToAdd = (int) $request->stock;
        if ($request->input_mode === 'roll' && $device->roll_capacity && $device->roll_capacity > 0) {
            $stockToAdd = $stockToAdd * (int)$device->roll_capacity;
        }

        $existingStoredDevice = StoredDevice::where('device_id', $request->device_id)
            ->where('condition', $request->condition)
            ->first();

        if ($existingStoredDevice) {
            $existingStoredDevice->stock += $stockToAdd;
            $existingStoredDevice->save();

            Session::flash('success', 'Stok perangkat berhasil diperbarui. ' . $device->full_display_name . ' (' . $request->condition . '), Stok Ditambahkan: ' . number_format($stockToAdd) . ' ' . ($device->unit_type ?? 'pcs'));
        } else {
            $storedDevice = StoredDevice::create([
                'device_id' => $request->device_id,
                'stock' => $stockToAdd,
                'condition' => $request->condition,
            ]);
            Session::flash('success', 'Perangkat baru berhasil ditambahkan. ' . $device->full_display_name . ' (' . $request->condition . '), Stok: ' . number_format($stockToAdd) . ' ' . ($device->unit_type ?? 'pcs'));
        }

        Session::flash('warning-stored-device', 'Penyesuaian data secara manual melalui halaman ini hanya dianjurkan dalam kondisi yang benar-benar diperlukan');
        
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Stok berhasil diperbarui.']);
        }
    }

    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'stored_id' => 'required|integer|exists:stored_devices,id',
            'newstock'  => 'required|integer|min:1',
            'input_mode'=> 'nullable|string|in:unit,roll',
        ]);

        $storedId = $validatedData['stored_id'];
        $storedDevice = StoredDevice::with('device')->findOrFail($storedId);

        $stockToAdd = (int) $validatedData['newstock'];
        if ($request->input_mode === 'roll' && $storedDevice->device && $storedDevice->device->roll_capacity && $storedDevice->device->roll_capacity > 0) {
            $stockToAdd = $stockToAdd * (int)$storedDevice->device->roll_capacity;
        }

        Log::info("Menerima permintaan untuk MENAMBAH stok sebanyak {$stockToAdd} untuk stored ID: {$storedId}");

        DB::beginTransaction();
        try {
            $storedDevice = StoredDevice::lockForUpdate()->findOrFail($storedId);
            $oldStock = $storedDevice->stock;
            $storedDevice->increment('stock', $stockToAdd);
            $newTotalStock = $storedDevice->fresh()->stock;

            DB::commit();

            $unitLabel = ($storedDevice->device && $storedDevice->device->unit_type === 'meter') ? 'Meter' : 'Pcs';
            $successMessage = "Berhasil menambahkan " . number_format($stockToAdd) . " {$unitLabel}. Stok {$storedDevice->device->full_display_name} sekarang menjadi " . number_format($newTotalStock) . " {$unitLabel}.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage
                ]);
            }

            Session::flash('success', $successMessage);
            Session::flash('warning-stored-device', 'Penyesuaian data secara manual melalui halaman ini hanya dianjurkan dalam kondisi yang benar-benar diperlukan');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("GAGAL menambah stok untuk perangkat ID {$storedId}: " . $e->getMessage());
            $errorMessage = 'Terjadi kesalahan saat mencoba menambah stok.';

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $errorMessage], 500);
            }

            Session::flash('error', $errorMessage);
            return redirect()->back();
        }
    }
  
public function getStoredDeviceData($id)
{
    $storedDevice = StoredDevice::with('device')->findOrFail($id); // Eager load relation device
    return response()->json($storedDevice);
}



public function destroy($id)
{
    try {
        $storedDevice = StoredDevice::findOrFail($id);

        // 1. ATURAN BISNIS: Periksa apakah stok lebih dari 0.
        if ($storedDevice->stock > 0) {
            // 2. Jika ya, kembalikan error 400 dengan pesan yang jelas.
            return response()->json([
                'message' => 'Gagal: Perangkat tidak dapat dihapus karena stok masih tersedia (Stok saat ini: ' . $storedDevice->stock . ').'
            ], 400);
        }

        // 3. Jika stok 0, ubah status menjadi 'deleted' (Soft Delete).
        $storedDevice->update(['status' => 'deleted']);

        // 4. Kembalikan respons sukses dalam format JSON.
        return response()->json([
            'message' => 'Perangkat berhasil ditandai sebagai dihapus.'
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['message' => 'Gagal: Data perangkat tidak ditemukan.'], 404);
    } catch (\Exception $e) {
        // Menangkap error tak terduga lainnya.
        return response()->json(['message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()], 500);
    }
}
public function bulkDestroy(Request $request)
{
    $storedDeviceIds = $request->input('ids');

    if (empty($storedDeviceIds) || !is_array($storedDeviceIds)) {
        return response()->json(['message' => 'Tidak ada data perangkat yang dipilih!'], 400);
    }

    // 1. ATURAN BISNIS: Cari semua perangkat yang dipilih yang stoknya MASIH LEBIH DARI 0.
    $devicesWithStock = StoredDevice::whereIn('id', $storedDeviceIds)
                                    ->where('stock', '>', 0)
                                    ->get();

    // 2. Jika ditemukan perangkat yang masih memiliki stok...
    if ($devicesWithStock->isNotEmpty()) {
        // Buat pesan error yang informatif.
        $problematicItems = $devicesWithStock->pluck('name', 'stock'); // Mengambil nama dan stok
        $errorList = $problematicItems->map(function ($name, $stock) {
            return "$name (Stok: $stock)";
        })->implode(', ');

        // 3. Kembalikan error 400 dan batalkan seluruh operasi.
        return response()->json([
            'message' => 'Gagal: Operasi dibatalkan karena beberapa perangkat masih memiliki stok: ' . $errorList
        ], 400);
    }

    // 4. Jika semua perangkat yang dipilih stoknya 0, lanjutkan dengan Soft Delete.
    try {
        $jumlahDitandai = StoredDevice::whereIn('id', $storedDeviceIds)
                                      ->update(['status' => 'deleted']);

        // 5. Kembalikan respons sukses dalam format JSON.
        return response()->json([
            'message' => $jumlahDitandai . ' data perangkat berhasil ditandai sebagai dihapus.'
        ]);
        
    } catch (\Exception $e) {
        return response()->json(['message' => 'Terjadi kesalahan saat proses penghapusan massal: ' . $e->getMessage()], 500);
    }
}


}
