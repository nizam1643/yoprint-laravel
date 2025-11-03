<?php

namespace App\Repositories;

use App\Interfaces\UploadRepositoryInterface;
use App\Models\Upload;
use Illuminate\Support\Facades\Auth;

class UploadRepository implements UploadRepositoryInterface
{
    public function findOrCreate(string $checksum, int $size, array $meta): Upload
    {
        return Upload::firstOrCreate(
            ['checksum_sha256' => $checksum, 'size' => $size],
            [
                'user_id' => optional(Auth::user())->id,
                'original_name' => $meta['original_name'],
                'disk' => $meta['disk'],
                'path' => $meta['path'],
                'status' => 'queued',
            ]
        );
    }

    public function latest(int $limit = 50)
    {
        return Upload::orderByDesc('created_at')->limit($limit)->get();
    }
}
