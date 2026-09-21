<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $table = 'devices';
    protected $fillable = [
        'brand',
        'model',
        'type',
        'unit_type',
        'length_value',
        'length_unit',
        'roll_capacity',
        'status',
    ];

    protected $appends = ['full_display_name'];

    public function getFullDisplayNameAttribute()
    {
        $name = $this->brand . ' ' . $this->model;
        if ($this->unit_type === 'meter') {
            if ($this->roll_capacity && $this->type === 'cable_roll') {
                return $name . ' (Roll ' . (int)$this->roll_capacity . 'm)';
            }
            return $name;
        }
        
        if ($this->length_value) {
            $len = (float)$this->length_value == (int)$this->length_value ? (int)$this->length_value : $this->length_value;
            return $name . ' - ' . $len . ' ' . ucfirst($this->length_unit ?? 'meter');
        }

        return $name;
    }
}
