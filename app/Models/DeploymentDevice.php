<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str; 
class DeploymentDevice extends Model
{
    protected $table = 'deployment_devices';
    protected $fillable = [
        'client_id', // Foreign key ke User
        'status', // Penghubung ke DeploymentDeviceDetail

        // Tambahan field lain jika ada, misal: 'status', 'location', dll.
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'client_id');
    }
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }
    public function details()
    {
        return $this->hasMany(DeploymentDeviceDetail::class, 'deployment_id', 'id');
    }
}