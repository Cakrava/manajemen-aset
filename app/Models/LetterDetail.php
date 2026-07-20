<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LetterDetail extends Model

{

    
    protected $fillable = [
        'letter_id',
        'stored_device_id', // Ini adalah ID dari stored_devices
        'quantity',
        'status',
        'withdrawcondition'
    ];

    /**
     * Mendapatkan data StoredDevice yang terkait dengan detail transaksi ini.
     * INI PERUBAHAN KUNCI: Relasi kini menunjuk ke StoredDevice.
     */
    public function storedDevice()
    {
        return $this->belongsTo(StoredDevice::class, 'stored_device_id');
    }
    
    public function letter()
    {
        return $this->belongsTo(Letters::class, 'id', 'letter_id');
    }

}