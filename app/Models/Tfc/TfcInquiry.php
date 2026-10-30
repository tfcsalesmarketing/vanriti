<?php

namespace App\Models\Tfc;

use Illuminate\Database\Eloquent\Model;

class TfcInquiry extends Model
{
    protected $table = 'tfc_inquiries';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'type',
        'city',
        'state',
        'status',
        'notes',
        'ip_address',
    ];
}
