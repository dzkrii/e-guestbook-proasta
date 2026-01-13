<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registration extends Model
{

use HasFactory;
    protected $fillable = [
        'event_id',
        'name',
        'email',
        'whatsapp',
        'qr_code_token',
        'is_attended',
        'attended_at',
        'arrival_order',
        'type',        // Umum / Mahasiswa
        'institution', // Asal Kampus
        'major',       // Jurusan
        'nim',         // NIM
        'semester',    // Semester
    ];

    // Tambahkan ini:
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    protected $casts = [
    'attended_at' => 'datetime',
    'is_attended' => 'boolean',
];

public function studentDetail()
    {
        return $this->hasOne(StudentDetail::class); // Satu pendaftar punya satu detail mahasiswa
    }

}
