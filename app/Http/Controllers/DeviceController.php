<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\StoredDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;

class DeviceController extends Controller
{


    public function index()
    {
        // 1. Mengambil semua perangkat yang tidak dihapus, diurutkan berdasarkan yang terbaru.
        $devices = Device::where('status', '!=', 'deleted')
            ->orderBy('created_at', 'desc')
            ->get();

        // Unique Brands & Models for datalist auto-suggestions
        $existingBrands = Device::where('status', '!=', 'deleted')->whereNotNull('brand')->pluck('brand')->unique()->values();
        $existingModels = Device::where('status', '!=', 'deleted')->whereNotNull('model')->pluck('model')->unique()->values();

        // 2. Daftar nama jenis perangkat untuk ditampilkan di form atau filter.
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
            'cable_pcs' => 'Kabel Set / Patch Cord (Pcs)',
            'cable_roll' => 'Kabel Roll / Bulk (Meter)',
            'cable' => 'Network Cable (Ethernet, Fiber)',
            'connector' => 'Connector (RJ45, LC, SC, Fiber Connectors)',
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

        return view('page.device', compact('devices', 'deviceTypeNames', 'existingBrands', 'existingModels'));
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'brand' => 'required|max:255',
            'model' => 'required|max:255',
            'type' => 'required|string',
            'unit_type' => 'nullable|string|in:pcs,meter',
            'length_value' => 'nullable|numeric|min:0',
            'length_unit' => 'nullable|string',
            'roll_capacity' => 'nullable|integer|min:1',
        ]);

        if ($validatedData['type'] === 'cable_roll') {
            $validatedData['unit_type'] = 'meter';
        } else {
            $validatedData['unit_type'] = $validatedData['unit_type'] ?? 'pcs';
        }

        $query = Device::where('brand', $validatedData['brand'])
                        ->where('model', $validatedData['model'])
                        ->where('type', $validatedData['type']);

        if (!empty($validatedData['length_value'])) {
            $query->where('length_value', $validatedData['length_value']);
        } else {
            $query->whereNull('length_value');
        }

        $existingDevice = $query->first();

        if ($existingDevice) {
            if ($existingDevice->status === 'deleted') {
                $existingDevice->status = 'active';
                $existingDevice->unit_type = $validatedData['unit_type'];
                $existingDevice->length_value = $validatedData['length_value'] ?? null;
                $existingDevice->length_unit = $validatedData['length_unit'] ?? 'meter';
                $existingDevice->roll_capacity = $validatedData['roll_capacity'] ?? null;
                $existingDevice->save();

                return response()->json(['message' => 'Perangkat yang sama pernah dihapus dan kini berhasil diaktifkan kembali.']);
            } else {
                return response()->json(['message' => 'Perangkat dengan spesifikasi ini sudah ada.'], 400);
            }
        }

        Device::create($validatedData);

        return response()->json(['message' => 'Perangkat berhasil ditambahkan.']);
    }

 
    public function update(Request $request)
    {
        $deviceId = $request->input('device_id');
        $device = Device::findOrFail($deviceId);

        $validatedData = $request->validate([
            'brand' => 'required|max:255',
            'model' => 'required|max:255',
            'type' => 'required|string',
            'unit_type' => 'nullable|string|in:pcs,meter',
            'length_value' => 'nullable|numeric|min:0',
            'length_unit' => 'nullable|string',
            'roll_capacity' => 'nullable|integer|min:1',
        ]);

        if ($validatedData['type'] === 'cable_roll') {
            $validatedData['unit_type'] = 'meter';
        } else {
            $validatedData['unit_type'] = $validatedData['unit_type'] ?? 'pcs';
        }

        $query = Device::where('id', '!=', $device->id)
                        ->whereRaw('LOWER(brand) = ?', [strtolower($validatedData['brand'])])
                        ->whereRaw('LOWER(model) = ?', [strtolower($validatedData['model'])])
                        ->whereRaw('LOWER(type) = ?', [strtolower($validatedData['type'])]);

        if (!empty($validatedData['length_value'])) {
            $query->where('length_value', $validatedData['length_value']);
        } else {
            $query->whereNull('length_value');
        }

        if ($query->exists()) {
            return response()->json(['message' => 'Update gagal. Perangkat lain dengan spesifikasi ini sudah ada.'], 400);
        }

        $device->update($validatedData);

        return response()->json(['message' => 'Perangkat berhasil diupdate.']);
    }

  
    public function bulkDestroy(Request $request)
    {
        $deviceIds = $request->input('ids');
    
        if (!is_array($deviceIds) || empty($deviceIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada perangkat terpilih.'
            ], 400);
        }
    
        // Cek apakah ada device yang masih dipakai di stored_devices
        $usedDevice = StoredDevice::whereIn('device_id', $deviceIds)->pluck('device_id')->toArray();
    
        if (!empty($usedDevice)) {
            return response()->json([
                'success' => false,
                'message' => 'Beberapa perangkat masih digunakan dan tidak dapat dihapus.',
                'used_ids' => $usedDevice
            ], 400);
        }
    
        $jumlahDihapus = Device::whereIn('id', $deviceIds)->update(['status' => 'deleted']);
    
        return response()->json([
            'success' => true,
            'message' => "$jumlahDihapus perangkat berhasil disembunyikan."
        ]);
    }
    
    public function destroy($id)
    {
        $device = Device::findOrFail($id);
    
        // Cek apakah device ini masih digunakan di stored_devices
        $isUsed = StoredDevice::where('device_id', $device->id)->exists();
    
        if ($isUsed) {
            // Kalau masih dipakai, kirim respon error JSON
            return response()->json([
                'success' => false,
                'message' => 'Perangkat ini masih digunakan dan tidak bisa dihapus.'
            ], 400);
        }
    
        $device->status = 'deleted';
        $device->save();
    
        return response()->json([
            'success' => true,
            'message' => 'Perangkat berhasil disembunyikan.'
        ]);
    }
    


    public function getDeviceData($id)
    {
        $device = Device::findOrFail($id);
        return response()->json($device);
    }
}

