<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QRCode extends Model
{
    use HasFactory;

    protected $table = 'qrcodes'; // Explicitly specify the table name

    protected $fillable = [
        'code',
        'is_fraud',
        'scan_count',
        'last_scanned_at',
        'sortie',
        'date_sortie',
    ];

    protected $casts = [
        'last_scanned_at' => 'datetime',
        'date_sortie' => 'datetime',
        'sortie' => 'boolean',
        'is_fraud' => 'boolean',
        'scan_count' => 'integer',
    ];

    protected $attributes = [
        'scan_count' => 0,
        'sortie' => false,
        'is_fraud' => false,
    ];
}
