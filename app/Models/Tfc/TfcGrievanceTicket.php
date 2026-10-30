<?php

namespace App\Models\Tfc;

use Illuminate\Database\Eloquent\Model;

class TfcGrievanceTicket extends Model
{
    protected $table = 'tfc_grievance_tickets';

    protected $fillable = [
        'ticket_number',
        'consumer_name',
        'email',
        'phone',
        'order_number',
        'category',
        'complaint_details',
        'status',
        'resolution_notes',
        'acknowledged_at',
        'resolved_at',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Generate unique ticket number (e.g., TFC-GR-YYYYMMDD-XXXX).
     */
    public static function generateTicketNumber(): string
    {
        $prefix = 'TFC-GR-' . date('Ymd') . '-';
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
        return $prefix . $random;
    }
}
