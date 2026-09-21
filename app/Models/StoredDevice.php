<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoredDevice extends Model
{
    protected $table = 'stored_devices';
    protected $fillable = ['device_id', 'stock', 'condition' , 'previous_stock', 'status'];
    protected $appends = ['formatted_stock'];
    public function device()
    {
        return $this->belongsTo(Device::class, 'device_id', 'id');
    }

    public function getFormattedStockAttribute()
    {
        if (!$this->device) {
            return $this->stock . ' Pcs';
        }

        if ($this->device->unit_type === 'meter') {
            $capacity = $this->device->roll_capacity;
            if ($capacity && $capacity > 0) {
                $fullRolls = floor($this->stock / $capacity);
                $remainingMeters = $this->stock % $capacity;

                if ($fullRolls > 0 && $remainingMeters > 0) {
                    return number_format($this->stock) . " Meter ({$fullRolls} Roll Utuh + 1 Roll Sisa {$remainingMeters}m)";
                } elseif ($fullRolls > 0 && $remainingMeters == 0) {
                    return number_format($this->stock) . " Meter ({$fullRolls} Roll Utuh @{$capacity}m)";
                } else {
                    return number_format($this->stock) . " Meter (1 Roll Sisa {$this->stock}m)";
                }
            }
            return number_format($this->stock) . ' Meter';
        }

        return number_format($this->stock) . ' Pcs';
    }
}
