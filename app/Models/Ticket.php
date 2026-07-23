<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;

    class Ticket extends Model
    {


    protected $fillable = [
        'user_id',
        'subject',
        'ticket_type',
        'notes',
        'status',
        'request_to_cancel',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    }
