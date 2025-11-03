<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_name',
        'disk',
        'path',
        'size',
        'checksum_sha256',
        'status',
        'row_count',
        'processed_count',
        'upserted_count',
        'error',
        'user_id',
    ];

    protected $casts = [
        'row_count' => 'integer',
        'processed_count' => 'integer',
        'upserted_count' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
